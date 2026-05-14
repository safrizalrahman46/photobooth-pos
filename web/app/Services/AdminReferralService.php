<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Package;
use App\Models\ReferralCode;
use App\Models\ReferralRedemption;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AdminReferralService
{
    public function __construct(
        private readonly ReferralService $referralService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function payload(array $filters = []): array
    {
        return [
            'summary' => $this->summary($filters),
            'codes' => $this->codeRows(),
            'redemptions' => $this->redemptionRows($filters),
            'breakdowns' => $this->breakdowns($filters),
            'options' => $this->options(),
        ];
    }

    public function create(array $payload, ?int $actorId = null): ReferralCode
    {
        $payload = $this->normalizePayload($payload);
        $this->assertPackageBranchCompatibility($payload);

        $referralCode = ReferralCode::query()->create($payload + [
            'created_by' => $actorId,
            'used_count' => 0,
        ]);

        $this->activityLogger->log(
            'referrals',
            'created',
            $actorId,
            ReferralCode::class,
            (int) $referralCode->id,
            [
                'message' => sprintf('Kode referal %s dibuat.', (string) $referralCode->code),
                'label' => (string) $referralCode->code,
                'source_name' => (string) $referralCode->source_name,
                'discount_type' => (string) $referralCode->discount_type,
                'discount_value' => (float) $referralCode->discount_value,
            ],
        );

        return $referralCode->refresh();
    }

    public function update(ReferralCode $referralCode, array $payload, ?int $actorId = null): ReferralCode
    {
        $payload = $this->normalizePayload($payload);
        $this->assertPackageBranchCompatibility($payload);

        $referralCode->update($payload);

        $this->activityLogger->log(
            'referrals',
            'updated',
            $actorId,
            ReferralCode::class,
            (int) $referralCode->id,
            [
                'message' => sprintf('Kode referal %s diperbarui.', (string) $referralCode->code),
                'label' => (string) $referralCode->code,
                'updated_fields' => array_keys($payload),
            ],
        );

        return $referralCode->refresh();
    }

    public function delete(ReferralCode $referralCode, ?int $actorId = null): void
    {
        $this->activityLogger->log(
            'referrals',
            'deleted',
            $actorId,
            ReferralCode::class,
            (int) $referralCode->id,
            [
                'message' => sprintf('Kode referal %s dihapus.', (string) $referralCode->code),
                'label' => (string) $referralCode->code,
            ],
        );

        $referralCode->delete();
    }

    private function normalizePayload(array $payload): array
    {
        $code = $this->referralService->normalizeCode((string) ($payload['code'] ?? ''));
        $discountType = (string) ($payload['discount_type'] ?? ReferralCode::DISCOUNT_FIXED);

        return [
            'code' => $code,
            'source_name' => trim((string) ($payload['source_name'] ?? '')),
            'source_type' => (string) ($payload['source_type'] ?? 'other'),
            'description' => filled($payload['description'] ?? null) ? (string) $payload['description'] : null,
            'discount_type' => $discountType,
            'discount_value' => max(round((float) ($payload['discount_value'] ?? 0), 2), 0),
            'max_discount_amount' => $discountType === ReferralCode::DISCOUNT_PERCENT && (float) ($payload['max_discount_amount'] ?? 0) > 0
                ? max(round((float) $payload['max_discount_amount'], 2), 0)
                : null,
            'min_order_amount' => max(round((float) ($payload['min_order_amount'] ?? 0), 2), 0),
            'branch_id' => ! empty($payload['branch_id']) ? (int) $payload['branch_id'] : null,
            'package_id' => ! empty($payload['package_id']) ? (int) $payload['package_id'] : null,
            'usage_limit' => ! empty($payload['usage_limit']) ? max((int) $payload['usage_limit'], 1) : null,
            'valid_from' => ! empty($payload['valid_from']) ? $payload['valid_from'] : null,
            'valid_until' => ! empty($payload['valid_until']) ? $payload['valid_until'] : null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ];
    }

    private function assertPackageBranchCompatibility(array $payload): void
    {
        $branchId = $payload['branch_id'] ?? null;
        $packageId = $payload['package_id'] ?? null;

        if (! $branchId || ! $packageId) {
            return;
        }

        $packageBranchId = Package::query()
            ->whereKey($packageId)
            ->value('branch_id');

        if ($packageBranchId !== null && (int) $packageBranchId !== (int) $branchId) {
            throw ValidationException::withMessages([
                'package_id' => 'Paket tidak tersedia untuk cabang referral yang dipilih.',
            ]);
        }
    }

    private function summary(array $filters): array
    {
        $base = $this->filteredRedemptions($filters);
        $active = (clone $base)->where('status', '!=', ReferralRedemption::STATUS_VOIDED);
        $voided = (clone $base)->where('status', ReferralRedemption::STATUS_VOIDED);

        $totals = (clone $active)
            ->selectRaw('COUNT(*) as total_redemptions')
            ->selectRaw('COUNT(DISTINCT customer_phone) as unique_customers')
            ->selectRaw('COALESCE(SUM(subtotal_amount), 0) as subtotal_amount')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount_amount')
            ->selectRaw('COALESCE(SUM(final_amount), 0) as final_amount')
            ->first();

        $paidCount = (clone $active)
            ->whereIn('status', [ReferralRedemption::STATUS_PAID, ReferralRedemption::STATUS_DONE])
            ->count();

        return [
            'total_redemptions' => (int) ($totals?->total_redemptions ?? 0),
            'unique_customers' => (int) ($totals?->unique_customers ?? 0),
            'subtotal_amount' => (float) ($totals?->subtotal_amount ?? 0),
            'subtotal_text' => $this->formatRupiah((float) ($totals?->subtotal_amount ?? 0)),
            'discount_amount' => (float) ($totals?->discount_amount ?? 0),
            'discount_text' => $this->formatRupiah((float) ($totals?->discount_amount ?? 0)),
            'final_amount' => (float) ($totals?->final_amount ?? 0),
            'final_text' => $this->formatRupiah((float) ($totals?->final_amount ?? 0)),
            'paid_redemptions' => $paidCount,
            'voided_redemptions' => (int) $voided->count(),
        ];
    }

    private function codeRows(): array
    {
        $aggregates = ReferralRedemption::query()
            ->where('status', '!=', ReferralRedemption::STATUS_VOIDED)
            ->selectRaw('referral_code_id, COUNT(*) as total_redemptions, COALESCE(SUM(discount_amount), 0) as total_discount, COALESCE(SUM(final_amount), 0) as net_sales')
            ->groupBy('referral_code_id')
            ->get()
            ->keyBy('referral_code_id');

        return ReferralCode::query()
            ->with(['branch:id,name', 'package:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ReferralCode $code) use ($aggregates): array {
                $aggregate = $aggregates->get($code->id);
                $totalDiscount = (float) ($aggregate?->total_discount ?? 0);
                $netSales = (float) ($aggregate?->net_sales ?? 0);

                return [
                    'id' => (int) $code->id,
                    'code' => (string) $code->code,
                    'source_name' => (string) $code->source_name,
                    'source_type' => (string) $code->source_type,
                    'description' => (string) ($code->description ?? ''),
                    'discount_type' => (string) $code->discount_type,
                    'discount_value' => (float) $code->discount_value,
                    'max_discount_amount' => $code->max_discount_amount !== null ? (float) $code->max_discount_amount : null,
                    'min_order_amount' => (float) $code->min_order_amount,
                    'branch_id' => $code->branch_id ? (int) $code->branch_id : null,
                    'branch_name' => (string) ($code->branch?->name ?? 'Semua cabang'),
                    'package_id' => $code->package_id ? (int) $code->package_id : null,
                    'package_name' => (string) ($code->package?->name ?? 'Semua paket'),
                    'usage_limit' => $code->usage_limit ? (int) $code->usage_limit : null,
                    'used_count' => (int) $code->used_count,
                    'valid_from' => $code->valid_from?->toDateTimeString(),
                    'valid_until' => $code->valid_until?->toDateTimeString(),
                    'is_active' => (bool) $code->is_active,
                    'total_redemptions' => (int) ($aggregate?->total_redemptions ?? 0),
                    'total_discount' => $totalDiscount,
                    'total_discount_text' => $this->formatRupiah($totalDiscount),
                    'net_sales' => $netSales,
                    'net_sales_text' => $this->formatRupiah($netSales),
                    'created_at' => $code->created_at?->toIso8601String(),
                    'updated_at' => $code->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    private function redemptionRows(array $filters): array
    {
        return $this->filteredRedemptions($filters)
            ->with(['branch:id,name', 'package:id,name', 'booking:id,booking_code', 'transaction:id,transaction_code'])
            ->latest('redeemed_at')
            ->limit(250)
            ->get()
            ->map(fn (ReferralRedemption $redemption): array => $this->mapRedemption($redemption))
            ->values()
            ->all();
    }

    private function breakdowns(array $filters): array
    {
        return [
            'by_code' => $this->aggregateBreakdown($filters, 'referral_code', 'referral_code'),
            'by_source_type' => $this->aggregateBreakdown($filters, 'source_type', 'source_type'),
            'by_channel' => $this->aggregateBreakdown($filters, 'channel', 'channel'),
            'by_branch' => $this->aggregateBreakdown($filters, 'branch_id', 'branch_id'),
            'by_package' => $this->aggregateBreakdown($filters, 'package_id', 'package_id'),
        ];
    }

    private function aggregateBreakdown(array $filters, string $column, string $keyName): array
    {
        $rows = $this->filteredRedemptions($filters)
            ->where('status', '!=', ReferralRedemption::STATUS_VOIDED)
            ->select($column)
            ->selectRaw('COUNT(*) as total_redemptions')
            ->selectRaw('COALESCE(SUM(subtotal_amount), 0) as subtotal_amount')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount_amount')
            ->selectRaw('COALESCE(SUM(final_amount), 0) as final_amount')
            ->groupBy($column)
            ->orderByDesc('total_redemptions')
            ->limit(20)
            ->get();

        $branchNames = $column === 'branch_id'
            ? Branch::query()->whereIn('id', $rows->pluck($column)->filter()->all())->pluck('name', 'id')
            : collect();
        $packageNames = $column === 'package_id'
            ? Package::query()->whereIn('id', $rows->pluck($column)->filter()->all())->pluck('name', 'id')
            : collect();

        return $rows
            ->map(function ($row) use ($column, $keyName, $branchNames, $packageNames): array {
                $value = $row->{$column};
                $label = (string) ($value ?? '-');

                if ($column === 'branch_id') {
                    $label = (string) ($branchNames[$value] ?? 'Semua cabang');
                } elseif ($column === 'package_id') {
                    $label = (string) ($packageNames[$value] ?? 'Semua paket');
                }

                return [
                    $keyName => $value,
                    'label' => $label,
                    'total_redemptions' => (int) $row->total_redemptions,
                    'subtotal_amount' => (float) $row->subtotal_amount,
                    'discount_amount' => (float) $row->discount_amount,
                    'discount_text' => $this->formatRupiah((float) $row->discount_amount),
                    'final_amount' => (float) $row->final_amount,
                    'final_text' => $this->formatRupiah((float) $row->final_amount),
                ];
            })
            ->values()
            ->all();
    }

    private function filteredRedemptions(array $filters): Builder
    {
        $query = ReferralRedemption::query();

        if (! empty($filters['from'])) {
            $query->where('redeemed_at', '>=', Carbon::parse((string) $filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('redeemed_at', '<=', Carbon::parse((string) $filters['to'])->endOfDay());
        }

        if (! empty($filters['code'])) {
            $query->where('referral_code', $this->referralService->normalizeCode((string) $filters['code']));
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', (string) $filters['channel']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $query;
    }

    private function mapRedemption(ReferralRedemption $redemption): array
    {
        return [
            'id' => (int) $redemption->id,
            'referral_code_id' => $redemption->referral_code_id ? (int) $redemption->referral_code_id : null,
            'referral_code' => (string) $redemption->referral_code,
            'source_name' => (string) ($redemption->source_name ?? ''),
            'source_type' => (string) ($redemption->source_type ?? ''),
            'booking_id' => $redemption->booking_id ? (int) $redemption->booking_id : null,
            'booking_code' => (string) ($redemption->booking?->booking_code ?? ''),
            'transaction_id' => $redemption->transaction_id ? (int) $redemption->transaction_id : null,
            'transaction_code' => (string) ($redemption->transaction?->transaction_code ?? ''),
            'branch_name' => (string) ($redemption->branch?->name ?? '-'),
            'package_name' => (string) ($redemption->package?->name ?? '-'),
            'customer_name' => (string) ($redemption->customer_name ?? ''),
            'customer_phone' => (string) ($redemption->customer_phone ?? ''),
            'channel' => (string) $redemption->channel,
            'subtotal_amount' => (float) $redemption->subtotal_amount,
            'subtotal_text' => $this->formatRupiah((float) $redemption->subtotal_amount),
            'discount_amount' => (float) $redemption->discount_amount,
            'discount_text' => $this->formatRupiah((float) $redemption->discount_amount),
            'final_amount' => (float) $redemption->final_amount,
            'final_text' => $this->formatRupiah((float) $redemption->final_amount),
            'status' => (string) $redemption->status,
            'redeemed_at' => $redemption->redeemed_at?->toIso8601String(),
            'redeemed_at_text' => $redemption->redeemed_at?->translatedFormat('d M Y, H:i') ?? '-',
            'voided_at' => $redemption->voided_at?->toIso8601String(),
            'voided_reason' => (string) ($redemption->voided_reason ?? ''),
        ];
    }

    private function options(): array
    {
        return [
            'source_types' => collect(ReferralCode::SOURCE_TYPES)
                ->map(fn (string $type): array => ['value' => $type, 'label' => ucfirst($type)])
                ->values()
                ->all(),
            'discount_types' => [
                ['value' => ReferralCode::DISCOUNT_FIXED, 'label' => 'Fixed'],
                ['value' => ReferralCode::DISCOUNT_PERCENT, 'label' => 'Percent'],
            ],
            'channels' => [
                ['value' => ReferralService::CHANNEL_PUBLIC_WEB, 'label' => 'Public Web'],
                ['value' => ReferralService::CHANNEL_DESKTOP_POS, 'label' => 'Desktop POS'],
                ['value' => ReferralService::CHANNEL_ADMIN_BOOKING, 'label' => 'Admin Booking'],
                ['value' => ReferralService::CHANNEL_API, 'label' => 'API'],
            ],
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Branch $branch): array => ['id' => (int) $branch->id, 'name' => (string) $branch->name])
                ->values()
                ->all(),
            'packages' => Package::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'branch_id', 'name'])
                ->map(fn (Package $package): array => [
                    'id' => (int) $package->id,
                    'branch_id' => $package->branch_id ? (int) $package->branch_id : null,
                    'name' => (string) $package->name,
                ])
                ->values()
                ->all(),
        ];
    }

    private function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
