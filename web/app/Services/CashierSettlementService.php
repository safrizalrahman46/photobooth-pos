<?php

namespace App\Services;

use App\Models\CashExpense;
use App\Models\CashierSession;
use App\Models\CashierSettlement;
use App\Models\CashierSettlementCorrection;
use App\Models\Payment;
use App\Support\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashierSettlementService
{
    public const STAGE_FULL = 'full';
    public const STAGE_DP = 'dp';
    public const STAGE_PELUNASAN = 'pelunasan';
    public const STAGE_EXTRA_PRINT = 'extra_print';
    public const STAGE_CORRECTION = 'correction';

    public function __construct(
        private readonly CodeGenerator $codeGenerator,
    ) {}

    public function activeSession(int $cashierId, ?int $branchId = null): ?CashierSession
    {
        return CashierSession::query()
            ->with(['branch', 'settlement'])
            ->where('user_id', $cashierId)
            ->when($branchId !== null, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function requireActiveSession(int $cashierId, int $branchId, bool $lock = false): CashierSession
    {
        $query = CashierSession::query()
            ->with(['branch', 'settlement'])
            ->where('user_id', $cashierId)
            ->where('branch_id', $branchId)
            ->where('status', 'open')
            ->latest('opened_at');

        if ($lock) {
            $query->lockForUpdate();
        }

        $session = $query->first();

        if (! $session) {
            throw ValidationException::withMessages([
                'cashier_session' => 'Buka sesi kasir terlebih dahulu sebelum menerima pembayaran.',
            ]);
        }

        $this->ensureSessionBusinessDate($session);

        if (! $this->isTodayBusinessSession($session)) {
            throw ValidationException::withMessages([
                'cashier_session' => sprintf(
                    'Sesi kasir tanggal %s belum ditutup. Tutup sesi lama terlebih dahulu sebelum transaksi baru.',
                    $session->business_date?->format('d M Y') ?? '-'
                ),
            ]);
        }

        return $session->refresh();
    }

    public function openSession(int $cashierId, int $branchId, float $openingCash = 0, ?string $notes = null): CashierSession
    {
        return DB::transaction(function () use ($cashierId, $branchId, $openingCash, $notes): CashierSession {
            $current = CashierSession::query()
                ->where('user_id', $cashierId)
                ->where('branch_id', $branchId)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($current) {
                $this->ensureSessionBusinessDate($current);

                if (! $this->isTodayBusinessSession($current)) {
                    throw ValidationException::withMessages([
                        'cashier_session' => sprintf(
                            'Sesi kasir tanggal %s belum ditutup. Tutup sesi lama terlebih dahulu sebelum membuka sesi baru.',
                            $current->business_date?->format('d M Y') ?? '-'
                        ),
                    ]);
                }

                return $current->refresh()->load('branch');
            }

            $now = now();
            $businessDate = $this->businessDateForBranch($branchId, $now);

            return CashierSession::query()->create([
                'user_id' => $cashierId,
                'branch_id' => $branchId,
                'business_date' => $businessDate,
                'opened_at' => $now,
                'opening_cash' => max($openingCash, 0),
                'status' => 'open',
                'notes' => $notes,
            ])->load('branch');
        });
    }

    public function createExpense(CashierSession $session, array $payload, int $cashierId): CashExpense
    {
        return DB::transaction(function () use ($session, $payload, $cashierId): CashExpense {
            /** @var CashierSession $lockedSession */
            $lockedSession = CashierSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ((string) $lockedSession->status !== 'open') {
                throw ValidationException::withMessages([
                    'cashier_session' => 'Pengeluaran tidak dapat ditambahkan karena sesi kasir sudah ditutup.',
                ]);
            }

            if ((int) $lockedSession->user_id !== $cashierId) {
                throw ValidationException::withMessages([
                    'cashier_session' => 'Pengeluaran hanya bisa dicatat oleh kasir sesi aktif.',
                ]);
            }

            $this->ensureSessionBusinessDate($lockedSession);

            if (! $this->isTodayBusinessSession($lockedSession)) {
                throw ValidationException::withMessages([
                    'cashier_session' => 'Sesi lama harus ditutup sebelum menambah pengeluaran baru.',
                ]);
            }

            return CashExpense::query()->create([
                'cashier_session_id' => (int) $lockedSession->id,
                'branch_id' => (int) $lockedSession->branch_id,
                'cashier_id' => $cashierId,
                'amount' => max((float) $payload['amount'], 0),
                'title' => trim((string) $payload['title']),
                'notes' => $payload['notes'] ?? null,
                'occurred_at' => now(),
            ])->load(['cashierSession', 'branch', 'cashier']);
        });
    }

    public function preview(CashierSession $session): array
    {
        $session->loadMissing(['branch', 'user', 'cashExpenses']);

        return $this->buildSnapshot($session, now(), null);
    }

    public function closeSession(CashierSession $session, int $actorId, ?float $closingCash = null, ?string $notes = null): CashierSettlement
    {
        return DB::transaction(function () use ($session, $actorId, $closingCash, $notes): CashierSettlement {
            /** @var CashierSession $lockedSession */
            $lockedSession = CashierSession::query()
                ->with(['branch', 'user'])
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureSessionBusinessDate($lockedSession);

            if ((string) $lockedSession->status !== 'open') {
                $settlement = $lockedSession->settlement()->first();

                if ($settlement) {
                    return $settlement->refresh()->load(['branch', 'cashier', 'cashierSession', 'corrections.creator']);
                }

                throw ValidationException::withMessages([
                    'cashier_session' => 'Sesi kasir sudah ditutup.',
                ]);
            }

            $closedAt = now();
            $isLateClose = ! $this->isTodayBusinessSession($lockedSession, $closedAt);
            $snapshot = $this->buildSnapshot($lockedSession, $closedAt, $closingCash);
            $summary = $snapshot['summary'];

            $lockedSession->fill([
                'closed_at' => $closedAt,
                'closed_by' => $actorId,
                'closing_cash' => $closingCash,
                'is_late_close' => $isLateClose,
                'status' => 'closed',
                'notes' => $notes ?? $lockedSession->notes,
            ])->save();

            CashExpense::query()
                ->where('cashier_session_id', (int) $lockedSession->id)
                ->whereNull('locked_at')
                ->update(['locked_at' => $closedAt]);

            $settlement = CashierSettlement::query()->create([
                'settlement_code' => $this->codeGenerator->generateSettlementCode($lockedSession->business_date?->copy() ?? $closedAt->copy()),
                'cashier_session_id' => (int) $lockedSession->id,
                'branch_id' => (int) $lockedSession->branch_id,
                'cashier_id' => (int) $lockedSession->user_id,
                'business_date' => $lockedSession->business_date?->toDateString() ?? $closedAt->toDateString(),
                'opened_at' => $lockedSession->opened_at,
                'closed_at' => $closedAt,
                'opening_cash' => (float) $lockedSession->opening_cash,
                'total_sales' => (float) $summary['total_sales'],
                'cash_received' => (float) $summary['cash_received'],
                'non_cash_received' => (float) $summary['non_cash_received'],
                'qris_received' => (float) $summary['qris_received'],
                'transfer_received' => (float) $summary['transfer_received'],
                'card_received' => (float) $summary['card_received'],
                'cash_expenses_total' => (float) $summary['cash_expenses_total'],
                'cash_to_deposit' => (float) $summary['cash_to_deposit'],
                'is_late_close' => $isLateClose,
                'snapshot' => $snapshot,
                'created_by' => $actorId,
                'notes' => $notes,
            ]);

            $snapshot['settlement_code'] = (string) $settlement->settlement_code;
            $settlement->forceFill(['snapshot' => $snapshot])->save();

            return $settlement->refresh()->load(['branch', 'cashier', 'cashierSession', 'corrections.creator']);
        });
    }

    public function markPrinted(CashierSettlement $settlement): CashierSettlement
    {
        $now = now();
        $settlement->forceFill([
            'print_count' => (int) $settlement->print_count + 1,
            'first_printed_at' => $settlement->first_printed_at ?? $now,
            'last_printed_at' => $now,
        ])->save();

        return $settlement->refresh()->load(['branch', 'cashier', 'cashierSession', 'corrections.creator']);
    }

    public function verifyCash(CashierSettlement $settlement, float $ownerReceivedCash, int $actorId, ?string $notes = null): CashierSettlement
    {
        $expected = (float) $settlement->cash_to_deposit + (float) $settlement->corrections_total;

        $settlement->forceFill([
            'owner_received_cash' => max($ownerReceivedCash, 0),
            'discrepancy_amount' => max($ownerReceivedCash, 0) - $expected,
            'verified_by' => $actorId,
            'verified_at' => now(),
            'notes' => $notes ?? $settlement->notes,
        ])->save();

        return $settlement->refresh()->load(['branch', 'cashier', 'cashierSession', 'corrections.creator']);
    }

    public function addCorrection(CashierSettlement $settlement, array $payload, int $actorId): CashierSettlementCorrection
    {
        return DB::transaction(function () use ($settlement, $payload, $actorId): CashierSettlementCorrection {
            /** @var CashierSettlement $lockedSettlement */
            $lockedSettlement = CashierSettlement::query()->whereKey($settlement->id)->lockForUpdate()->firstOrFail();
            $before = $lockedSettlement->snapshot;
            $amount = (float) $payload['amount'];
            $affectsCash = array_key_exists('affects_cash', $payload) ? (bool) $payload['affects_cash'] : true;

            $correctionsTotal = (float) $lockedSettlement->corrections_total;

            if ($affectsCash) {
                $correctionsTotal += $amount;
            }

            $after = $before;
            $after['corrections'] = [
                'total' => $correctionsTotal,
                'total_text' => $this->formatRupiah($correctionsTotal),
                'final_cash_to_deposit' => (float) $lockedSettlement->cash_to_deposit + $correctionsTotal,
                'final_cash_to_deposit_text' => $this->formatRupiah((float) $lockedSettlement->cash_to_deposit + $correctionsTotal),
            ];

            $lockedSettlement->forceFill([
                'corrections_total' => $correctionsTotal,
                'snapshot' => $after,
            ])->save();

            return CashierSettlementCorrection::query()->create([
                'cashier_settlement_id' => (int) $lockedSettlement->id,
                'created_by' => $actorId,
                'amount' => $amount,
                'affects_cash' => $affectsCash,
                'reason' => trim((string) $payload['reason']),
                'snapshot_before' => $before,
                'snapshot_after' => $after,
            ])->load('creator');
        });
    }

    public function settlementRows(array $filters = []): array
    {
        $query = CashierSettlement::query()
            ->with(['branch:id,name', 'cashier:id,name', 'cashierSession:id,status,is_late_close', 'corrections.creator:id,name'])
            ->orderByDesc('business_date')
            ->orderByDesc('closed_at');

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['cashier_id'])) {
            $query->where('cashier_id', (int) $filters['cashier_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('business_date', (string) $filters['date']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('settlement_code', 'like', "%{$search}%")
                    ->orWhereHas('cashier', fn (Builder $cashierQuery) => $cashierQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $limit = max(1, min((int) ($filters['limit'] ?? 150), 500));

        return $query->limit($limit)->get()->map(fn (CashierSettlement $settlement): array => $this->mapSettlement($settlement))->values()->all();
    }

    public function openSessionRows(array $filters = []): array
    {
        $query = CashierSession::query()
            ->with(['branch:id,name', 'user:id,name'])
            ->where('status', 'open')
            ->orderByDesc('opened_at');

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        return $query->limit(100)->get()->map(function (CashierSession $session): array {
            $this->ensureSessionBusinessDate($session);

            return [
                'id' => (int) $session->id,
                'cashier_id' => (int) $session->user_id,
                'cashier_name' => (string) ($session->user?->name ?? '-'),
                'branch_id' => (int) $session->branch_id,
                'branch_name' => (string) ($session->branch?->name ?? '-'),
                'business_date' => $session->business_date?->toDateString(),
                'business_date_text' => $session->business_date?->format('d M Y') ?? '-',
                'opened_at' => $session->opened_at?->toIso8601String(),
                'opened_at_text' => $session->opened_at?->format('d M Y H:i') ?? '-',
                'opening_cash' => (float) $session->opening_cash,
                'opening_cash_text' => $this->formatRupiah((float) $session->opening_cash),
                'is_stale' => ! $this->isTodayBusinessSession($session),
            ];
        })->values()->all();
    }

    public function mapSettlement(CashierSettlement $settlement): array
    {
        $snapshot = is_array($settlement->snapshot) ? $settlement->snapshot : [];
        $expected = (float) $settlement->cash_to_deposit + (float) $settlement->corrections_total;

        return [
            'id' => (int) $settlement->id,
            'settlement_code' => (string) $settlement->settlement_code,
            'cashier_session_id' => (int) $settlement->cashier_session_id,
            'branch_id' => (int) $settlement->branch_id,
            'branch_name' => (string) ($settlement->branch?->name ?? ($snapshot['branch']['name'] ?? '-')),
            'cashier_id' => (int) $settlement->cashier_id,
            'cashier_name' => (string) ($settlement->cashier?->name ?? ($snapshot['cashier']['name'] ?? '-')),
            'business_date' => $settlement->business_date?->toDateString(),
            'business_date_text' => $settlement->business_date?->format('d M Y') ?? '-',
            'period_label' => (string) ($snapshot['period']['label'] ?? '-'),
            'total_sales' => (float) $settlement->total_sales,
            'total_sales_text' => $this->formatRupiah((float) $settlement->total_sales),
            'cash_received' => (float) $settlement->cash_received,
            'cash_received_text' => $this->formatRupiah((float) $settlement->cash_received),
            'non_cash_received' => (float) $settlement->non_cash_received,
            'non_cash_received_text' => $this->formatRupiah((float) $settlement->non_cash_received),
            'cash_expenses_total' => (float) $settlement->cash_expenses_total,
            'cash_expenses_total_text' => $this->formatRupiah((float) $settlement->cash_expenses_total),
            'cash_to_deposit' => (float) $settlement->cash_to_deposit,
            'cash_to_deposit_text' => $this->formatRupiah((float) $settlement->cash_to_deposit),
            'corrections_total' => (float) $settlement->corrections_total,
            'corrections_total_text' => $this->formatRupiah((float) $settlement->corrections_total),
            'final_cash_to_deposit' => $expected,
            'final_cash_to_deposit_text' => $this->formatRupiah($expected),
            'opening_cash' => (float) $settlement->opening_cash,
            'opening_cash_text' => $this->formatRupiah((float) $settlement->opening_cash),
            'owner_received_cash' => $settlement->owner_received_cash !== null ? (float) $settlement->owner_received_cash : null,
            'owner_received_cash_text' => $settlement->owner_received_cash !== null ? $this->formatRupiah((float) $settlement->owner_received_cash) : '-',
            'discrepancy_amount' => (float) $settlement->discrepancy_amount,
            'discrepancy_amount_text' => $this->formatRupiah((float) $settlement->discrepancy_amount),
            'print_count' => (int) $settlement->print_count,
            'is_reprint' => (int) $settlement->print_count > 1,
            'is_late_close' => (bool) $settlement->is_late_close,
            'closed_at' => $settlement->closed_at?->toIso8601String(),
            'closed_at_text' => $settlement->closed_at?->format('d M Y H:i') ?? '-',
            'verified_at' => $settlement->verified_at?->toIso8601String(),
            'snapshot' => $snapshot,
            'corrections' => $settlement->corrections->map(fn (CashierSettlementCorrection $correction): array => [
                'id' => (int) $correction->id,
                'amount' => (float) $correction->amount,
                'amount_text' => $this->formatRupiah((float) $correction->amount),
                'affects_cash' => (bool) $correction->affects_cash,
                'reason' => (string) $correction->reason,
                'created_by_name' => (string) ($correction->creator?->name ?? '-'),
                'created_at_text' => $correction->created_at?->format('d M Y H:i') ?? '-',
            ])->values()->all(),
        ];
    }

    private function buildSnapshot(CashierSession $session, Carbon $closedAt, ?float $closingCash): array
    {
        $this->ensureSessionBusinessDate($session);
        $session->loadMissing(['branch', 'user']);

        $paymentTotals = $this->paymentMethodTotals((int) $session->id);
        $cashReceived = (float) ($paymentTotals['cash'] ?? 0);
        $qrisReceived = (float) ($paymentTotals['qris'] ?? 0);
        $transferReceived = (float) ($paymentTotals['transfer'] ?? 0);
        $cardReceived = (float) ($paymentTotals['card'] ?? 0);
        $nonCash = $qrisReceived + $transferReceived + $cardReceived;
        $totalSales = $cashReceived + $nonCash;
        $expenses = $this->expenseRows((int) $session->id);
        $expenseTotal = array_reduce($expenses, fn (float $carry, array $row): float => $carry + (float) $row['amount'], 0.0);
        $cashToDeposit = $cashReceived - $expenseTotal;
        $openedAt = $session->opened_at ?? $session->created_at ?? now();

        return [
            'settlement_code' => null,
            'cashier_session_id' => (int) $session->id,
            'branch' => [
                'id' => (int) $session->branch_id,
                'name' => (string) ($session->branch?->name ?? '-'),
            ],
            'cashier' => [
                'id' => (int) $session->user_id,
                'name' => (string) ($session->user?->name ?? '-'),
            ],
            'period' => [
                'business_date' => $session->business_date?->toDateString(),
                'business_date_text' => $session->business_date?->format('d M Y') ?? '-',
                'opened_at' => $openedAt->toIso8601String(),
                'closed_at' => $closedAt->toIso8601String(),
                'label' => $openedAt->format('d M Y H:i') . ' - ' . $closedAt->format('d M Y H:i'),
            ],
            'summary' => [
                'total_sales' => $totalSales,
                'total_sales_text' => $this->formatRupiah($totalSales),
                'cash_received' => $cashReceived,
                'cash_received_text' => $this->formatRupiah($cashReceived),
                'non_cash_received' => $nonCash,
                'non_cash_received_text' => $this->formatRupiah($nonCash),
                'qris_received' => $qrisReceived,
                'qris_received_text' => $this->formatRupiah($qrisReceived),
                'transfer_received' => $transferReceived,
                'transfer_received_text' => $this->formatRupiah($transferReceived),
                'card_received' => $cardReceived,
                'card_received_text' => $this->formatRupiah($cardReceived),
                'cash_expenses_total' => $expenseTotal,
                'cash_expenses_total_text' => $this->formatRupiah($expenseTotal),
                'cash_to_deposit' => $cashToDeposit,
                'cash_to_deposit_text' => $this->formatRupiah($cashToDeposit),
                'opening_cash' => (float) $session->opening_cash,
                'opening_cash_text' => $this->formatRupiah((float) $session->opening_cash),
                'closing_cash' => $closingCash,
                'closing_cash_text' => $closingCash !== null ? $this->formatRupiah($closingCash) : '-',
            ],
            'non_cash' => [
                ['method' => 'QRIS Manual', 'amount' => $qrisReceived, 'amount_text' => $this->formatRupiah($qrisReceived)],
                ['method' => 'Transfer', 'amount' => $transferReceived, 'amount_text' => $this->formatRupiah($transferReceived)],
                ['method' => 'Card', 'amount' => $cardReceived, 'amount_text' => $this->formatRupiah($cardReceived)],
            ],
            'package_sales' => $this->packageSalesRows((int) $session->id),
            'dp_info' => $this->stageRows((int) $session->id),
            'expenses' => $expenses,
            'corrections' => [
                'total' => 0,
                'total_text' => $this->formatRupiah(0),
                'final_cash_to_deposit' => $cashToDeposit,
                'final_cash_to_deposit_text' => $this->formatRupiah($cashToDeposit),
            ],
            'notes' => [
                'DP dan pelunasan sudah termasuk di Total Penjualan.',
                'Uang laci disisakan untuk modal kas berikutnya dan tidak ikut JML. DISETOR CASH.',
            ],
        ];
    }

    private function paymentMethodTotals(int $sessionId): array
    {
        return Payment::query()
            ->where('cashier_session_id', $sessionId)
            ->selectRaw('method, SUM(COALESCE(net_amount, amount)) as total')
            ->groupBy('method')
            ->pluck('total', 'method')
            ->map(fn ($value): float => (float) $value)
            ->all();
    }

    private function packageSalesRows(int $sessionId): array
    {
        return Payment::query()
            ->join('transactions', 'transactions.id', '=', 'payments.transaction_id')
            ->leftJoin('transaction_items', function ($join): void {
                $join->on('transaction_items.transaction_id', '=', 'transactions.id')
                    ->whereIn('transaction_items.item_type', ['package', 'booking']);
            })
            ->where('payments.cashier_session_id', $sessionId)
            ->selectRaw("COALESCE(transaction_items.item_name, 'Tanpa Paket') as package_name, SUM(COALESCE(payments.net_amount, payments.amount)) as total")
            ->groupBy('package_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'package_name' => (string) $row->package_name,
                'amount' => (float) $row->total,
                'amount_text' => $this->formatRupiah((float) $row->total),
            ])
            ->values()
            ->all();
    }

    private function stageRows(int $sessionId): array
    {
        $labels = [
            self::STAGE_DP => 'DP Masuk',
            self::STAGE_PELUNASAN => 'Pelunasan',
            self::STAGE_FULL => 'Full Payment',
            self::STAGE_EXTRA_PRINT => 'Extra Print',
        ];

        $totals = Payment::query()
            ->where('cashier_session_id', $sessionId)
            ->selectRaw('payment_stage, SUM(COALESCE(net_amount, amount)) as total')
            ->groupBy('payment_stage')
            ->pluck('total', 'payment_stage')
            ->map(fn ($value): float => (float) $value)
            ->all();

        return collect($labels)->map(fn (string $label, string $stage): array => [
            'stage' => $stage,
            'label' => $label,
            'amount' => (float) ($totals[$stage] ?? 0),
            'amount_text' => $this->formatRupiah((float) ($totals[$stage] ?? 0)),
        ])->values()->all();
    }

    private function expenseRows(int $sessionId): array
    {
        return CashExpense::query()
            ->where('cashier_session_id', $sessionId)
            ->orderBy('occurred_at')
            ->get(['id', 'amount', 'title', 'notes', 'occurred_at'])
            ->map(fn (CashExpense $expense): array => [
                'id' => (int) $expense->id,
                'title' => (string) $expense->title,
                'notes' => (string) ($expense->notes ?? ''),
                'amount' => (float) $expense->amount,
                'amount_text' => $this->formatRupiah((float) $expense->amount),
                'occurred_at' => $expense->occurred_at?->toIso8601String(),
                'occurred_at_text' => $expense->occurred_at?->format('d M Y H:i') ?? '-',
            ])
            ->values()
            ->all();
    }

    private function ensureSessionBusinessDate(CashierSession $session): void
    {
        if ($session->business_date !== null) {
            return;
        }

        $openedAt = $session->opened_at ?? $session->created_at ?? now();
        $session->forceFill([
            'business_date' => $this->businessDateForBranch((int) $session->branch_id, $openedAt),
        ])->save();
    }

    private function businessDateForBranch(int $branchId, Carbon $time): string
    {
        $timezone = DB::table('branches')->where('id', $branchId)->value('timezone') ?: config('app.queue_timezone', 'Asia/Jakarta');

        return $time->copy()->timezone((string) $timezone)->toDateString();
    }

    private function isTodayBusinessSession(CashierSession $session, ?Carbon $time = null): bool
    {
        $today = $this->businessDateForBranch((int) $session->branch_id, $time ?? now());

        return $session->business_date?->toDateString() === $today;
    }

    private function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
