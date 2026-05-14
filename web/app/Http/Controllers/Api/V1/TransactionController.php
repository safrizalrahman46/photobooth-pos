<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\ReferralService;
use App\Services\TransactionService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly ReferralService $referralService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.view'), 403);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $query = Transaction::query()
            ->with(['branch', 'booking', 'queueTicket', 'items', 'payments'])
            ->orderByDesc('created_at');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $transactions = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($transactions, TransactionResource::collection($transactions), 'Daftar transaksi berhasil dimuat.');
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage'), 403);

        $payload = $request->validated();
        $subtotal = (float) collect($payload['items'] ?? [])->sum(fn (array $item): float => (float) $item['qty'] * (float) $item['unit_price']);
        $packageId = $this->packageIdFromPayloadItems($payload['items'] ?? []);

        if (! empty($payload['referral_code'])) {
            try {
                $preview = $this->referralService->preview(
                    (string) $payload['referral_code'],
                    (int) $payload['branch_id'],
                    $packageId,
                    $subtotal,
                );
            } catch (ValidationException $exception) {
                return $this->responder->error(
                    $exception->validator->errors()->first() ?: 'Kode referal tidak valid.',
                    422,
                    $exception->errors(),
                );
            }

            $payload['discount_amount'] = (float) ($preview['discount_amount'] ?? 0);
            $payload['referral_code_id'] = $preview['referral_code_id'] ?? null;
            $payload['referral_code'] = $preview['referral_code'] ?? null;
            $payload['referral_discount_amount'] = (float) ($preview['discount_amount'] ?? 0);
        }

        $transaction = $this->transactionService->create($payload, (int) $request->user()->id);

        if (! empty($payload['referral_code'])) {
            $this->referralService->applyToTransaction(
                $transaction,
                (string) $payload['referral_code'],
                $subtotal,
                ReferralService::CHANNEL_API,
                (int) $request->user()->id,
                null,
                $packageId,
            );
            $transaction = $transaction->refresh();
        }

        return $this->responder->success(
            new TransactionResource($transaction->load('branch', 'booking', 'queueTicket', 'items', 'payments')),
            'Transaksi berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.view'), 403);

        return $this->responder->success(new TransactionResource($transaction->load('branch', 'booking', 'queueTicket', 'items', 'payments')));
    }

    private function packageIdFromPayloadItems(array $items): int
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (in_array((string) ($item['item_type'] ?? ''), ['package', 'booking'], true) && ! empty($item['item_ref_id'])) {
                return (int) $item['item_ref_id'];
            }
        }

        return 0;
    }
}
