<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CashierSessionResource;
use App\Models\CashierSession;
use App\Services\CashierSettlementService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CashierSessionController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
        private readonly CashierSettlementService $settlementService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage') || $request->user()?->can('report.view'), 403);

        $perPage = min((int) $request->integer('per_page', 15), 100);
        $query = CashierSession::query()
            ->with(['branch', 'settlement'])
            ->orderByDesc('opened_at');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $sessions = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($sessions, CashierSessionResource::collection($sessions), 'Sesi kasir berhasil dimuat.');
    }

    public function current(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage') || $request->user()?->can('report.view'), 403);

        $session = CashierSession::query()
            ->with(['branch', 'settlement'])
            ->where('user_id', $request->user()->id)
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return $this->responder->success($session ? new CashierSessionResource($session) : null, 'Sesi kasir aktif berhasil dimuat.');
    }

    public function open(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage'), 403);

        $payload = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'opening_cash' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $session = $this->settlementService->openSession(
                (int) $request->user()->id,
                (int) $payload['branch_id'],
                (float) ($payload['opening_cash'] ?? 0),
                $payload['notes'] ?? null,
            );
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Sesi kasir belum dapat dibuka.',
                422,
                $exception->errors(),
            );
        }

        return $this->responder->success(new CashierSessionResource($session->load('branch')), 'Sesi kasir berhasil dibuka.', 201);
    }

    public function close(Request $request, CashierSession $cashierSession): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage'), 403);

        abort_if((int) $cashierSession->user_id !== (int) $request->user()->id && ! $request->user()->can('settings.manage'), 403);

        $payload = $request->validate([
            'closing_cash' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $settlement = $this->settlementService->closeSession(
                $cashierSession,
                (int) $request->user()->id,
                array_key_exists('closing_cash', $payload) ? (float) $payload['closing_cash'] : null,
                $payload['notes'] ?? null,
            );
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Sesi kasir belum dapat ditutup.',
                422,
                $exception->errors(),
            );
        }

        return $this->responder->success([
            'session' => new CashierSessionResource($cashierSession->refresh()->load('branch', 'settlement')),
            'settlement' => $this->settlementService->mapSettlement($settlement),
        ], 'Sesi kasir berhasil ditutup.');
    }

    public function preview(Request $request, CashierSession $cashierSession): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage') || $request->user()?->can('report.view'), 403);
        abort_if((int) $cashierSession->user_id !== (int) $request->user()->id && ! $request->user()->can('report.view'), 403);

        return $this->responder->success($this->settlementService->preview($cashierSession), 'Preview setoran kasir berhasil dimuat.');
    }

    public function storeExpense(Request $request, CashierSession $cashierSession): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage'), 403);
        abort_if((int) $cashierSession->user_id !== (int) $request->user()->id, 403);

        $payload = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'title' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $expense = $this->settlementService->createExpense($cashierSession, $payload, (int) $request->user()->id);
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Pengeluaran cash belum dapat dicatat.',
                422,
                $exception->errors(),
            );
        }

        return $this->responder->success([
            'id' => (int) $expense->id,
            'amount' => (float) $expense->amount,
            'title' => (string) $expense->title,
            'notes' => (string) ($expense->notes ?? ''),
            'occurred_at' => $expense->occurred_at?->toIso8601String(),
        ], 'Pengeluaran cash berhasil dicatat.', 201);
    }
}
