<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\TransactionIndexRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionReadService;
use App\Services\TransactionService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionReadService $transactionReadService,
        private readonly TransactionService $transactionService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(TransactionIndexRequest $request): JsonResponse
    {
<<<<<<< HEAD
        abort_unless($request->user()?->can('transaction.view'), 403);

        $perPage = min((int) $request->integer('per_page', 15), 100);
=======
        $payload = $request->validated();
        $perPage = (int) ($payload['per_page'] ?? 15);
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93

        $transactions = $this->transactionReadService->paginate($payload, $perPage);

        return $this->responder->paginated($transactions, TransactionResource::collection($transactions), 'Daftar transaksi berhasil dimuat.');
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage'), 403);

        $transaction = $this->transactionService->create(
            $request->validated(),
            (int) $request->user()->id
        );

        return $this->responder->success(
            new TransactionResource($transaction->load('items', 'payments')),
            'Transaksi berhasil dibuat.',
            201
        );
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.view'), 403);

        return $this->responder->success(new TransactionResource($transaction->load('items', 'payments')));
    }
}
