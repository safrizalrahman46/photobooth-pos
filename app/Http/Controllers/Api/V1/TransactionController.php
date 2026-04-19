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
        $payload = $request->validated();
        $perPage = (int) ($payload['per_page'] ?? 15);

        $transactions = $this->transactionReadService->paginate($payload, $perPage);

        return $this->responder->paginated($transactions, TransactionResource::collection($transactions), 'Daftar transaksi berhasil dimuat.');
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
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

    public function show(Transaction $transaction): JsonResponse
    {
        return $this->responder->success(new TransactionResource($transaction->load('items', 'payments')));
    }
}
