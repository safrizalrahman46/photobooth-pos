<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CashierSessionResource;
use App\Models\CashierSession;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierSessionController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage') || $request->user()?->can('report.view'), 403);

        $perPage = min((int) $request->integer('per_page', 15), 100);
        $query = CashierSession::query()
            ->with('branch')
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
            ->with('branch')
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

        $session = DB::transaction(function () use ($payload, $request): CashierSession {
            $current = CashierSession::query()
                ->where('user_id', $request->user()->id)
                ->where('branch_id', $payload['branch_id'])
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($current) {
                return $current;
            }

            return CashierSession::query()->create([
                'user_id' => $request->user()->id,
                'branch_id' => $payload['branch_id'],
                'opened_at' => now(),
                'opening_cash' => $payload['opening_cash'] ?? 0,
                'status' => 'open',
                'notes' => $payload['notes'] ?? null,
            ]);
        });

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

        if ($cashierSession->status !== 'open') {
            return $this->responder->error('Sesi kasir sudah ditutup.', 422);
        }

        $cashierSession->fill([
            'closed_at' => now(),
            'closing_cash' => $payload['closing_cash'] ?? null,
            'status' => 'closed',
            'notes' => $payload['notes'] ?? $cashierSession->notes,
        ]);
        $cashierSession->save();

        return $this->responder->success(new CashierSessionResource($cashierSession->load('branch')), 'Sesi kasir berhasil ditutup.');
    }
}
