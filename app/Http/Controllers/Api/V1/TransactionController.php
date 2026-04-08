<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $query = Transaction::query()
            ->with(['items', 'payments'])
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
