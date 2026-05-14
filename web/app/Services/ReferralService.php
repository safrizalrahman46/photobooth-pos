<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\QueueTicket;
use App\Models\ReferralCode;
use App\Models\ReferralRedemption;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReferralService
{
    public const CHANNEL_PUBLIC_WEB = 'public_web';

    public const CHANNEL_DESKTOP_POS = 'desktop_pos';

    public const CHANNEL_ADMIN_BOOKING = 'admin_booking';

    public const CHANNEL_API = 'api';

    public function normalizeCode(?string $code): string
    {
        return mb_strtoupper(trim((string) $code));
    }

    public function preview(?string $rawCode, int $branchId, int $packageId, float $subtotal): ?array
    {
        $code = $this->normalizeCode($rawCode);

        if ($code === '') {
            return null;
        }

        $referralCode = $this->resolveValidReferralCode($code, $branchId, $packageId, $subtotal, false);

        return $this->buildPreview($referralCode, $subtotal);
    }

    public function applyToBooking(
        Booking $booking,
        ?string $rawCode,
        float $subtotal,
        string $channel,
        ?int $appliedBy = null,
    ): ?ReferralRedemption {
        return DB::transaction(function () use ($booking, $rawCode, $subtotal, $channel, $appliedBy): ?ReferralRedemption {
            $code = $this->normalizeCode($rawCode);
            $booking = $booking->refresh();
            $existing = $this->activeBookingRedemption($booking);

            if ($code === '') {
                if ($existing) {
                    $this->voidRedemption($existing, 'Referral code removed from booking.', $appliedBy);
                }

                $this->updateBookingReferralSnapshot($booking, null, $subtotal, 0);

                return null;
            }

            if ($existing && $existing->referral_code === $code) {
                $referralCode = ReferralCode::query()->whereKey($existing->referral_code_id)->first();

                if (! $referralCode) {
                    throw ValidationException::withMessages([
                        'referral_code' => 'Kode referal tidak valid.',
                    ]);
                }

                $this->assertReferralCodeStillApplicable($referralCode, (int) $booking->branch_id, (int) $booking->package_id, $subtotal);
                $discount = $this->calculateDiscount($referralCode, $subtotal);
                $this->updateBookingReferralSnapshot($booking, $referralCode, $subtotal, $discount);

                $existing->update($this->redemptionAmounts($subtotal, $discount) + [
                    'branch_id' => (int) $booking->branch_id,
                    'package_id' => (int) $booking->package_id,
                    'customer_name' => (string) $booking->customer_name,
                    'customer_phone' => (string) $booking->customer_phone,
                    'channel' => $channel,
                    'applied_by' => $appliedBy,
                ]);

                return $existing->refresh();
            }

            if ($existing) {
                $this->voidRedemption($existing, 'Referral code replaced on booking.', $appliedBy);
            }

            $referralCode = $this->resolveValidReferralCode($code, (int) $booking->branch_id, (int) $booking->package_id, $subtotal, true);
            $discount = $this->calculateDiscount($referralCode, $subtotal);
            $this->incrementUsage($referralCode);
            $this->updateBookingReferralSnapshot($booking, $referralCode, $subtotal, $discount);

            return ReferralRedemption::query()->create($this->baseRedemptionPayload($referralCode, $subtotal, $discount, $channel, $appliedBy) + [
                'booking_id' => (int) $booking->id,
                'branch_id' => (int) $booking->branch_id,
                'package_id' => (int) $booking->package_id,
                'customer_name' => (string) $booking->customer_name,
                'customer_phone' => (string) $booking->customer_phone,
            ]);
        });
    }

    public function applyToTransaction(
        Transaction $transaction,
        ?string $rawCode,
        float $subtotal,
        string $channel,
        ?int $appliedBy = null,
        ?QueueTicket $queueTicket = null,
        ?int $packageId = null,
    ): ?ReferralRedemption {
        return DB::transaction(function () use ($transaction, $rawCode, $subtotal, $channel, $appliedBy, $queueTicket, $packageId): ?ReferralRedemption {
            $code = $this->normalizeCode($rawCode);
            $transaction = $transaction->refresh()->loadMissing(['booking', 'queueTicket']);
            $existing = $this->activeTransactionRedemption($transaction);
            $resolvedPackageId = $packageId ?: $this->packageIdFromTransaction($transaction);

            if ($code === '') {
                if ($existing) {
                    $this->voidRedemption($existing, 'Referral code removed from transaction.', $appliedBy);
                }

                $this->updateTransactionReferralSnapshot($transaction, null, $subtotal, 0);

                return null;
            }

            if ($existing && $existing->referral_code === $code) {
                $referralCode = ReferralCode::query()->whereKey($existing->referral_code_id)->first();

                if (! $referralCode) {
                    throw ValidationException::withMessages([
                        'referral_code' => 'Kode referal tidak valid.',
                    ]);
                }

                $this->assertReferralCodeStillApplicable($referralCode, (int) $transaction->branch_id, $resolvedPackageId, $subtotal);
                $discount = $this->calculateDiscount($referralCode, $subtotal);
                $this->updateTransactionReferralSnapshot($transaction, $referralCode, $subtotal, $discount);

                $existing->update($this->redemptionAmounts($subtotal, $discount) + [
                    'branch_id' => (int) $transaction->branch_id,
                    'package_id' => $resolvedPackageId > 0 ? $resolvedPackageId : null,
                    'customer_name' => $this->transactionCustomerName($transaction, $queueTicket),
                    'customer_phone' => $this->transactionCustomerPhone($transaction, $queueTicket),
                    'channel' => $channel,
                    'applied_by' => $appliedBy,
                ]);

                return $existing->refresh();
            }

            if ($existing) {
                $this->voidRedemption($existing, 'Referral code replaced on transaction.', $appliedBy);
            }

            $referralCode = $this->resolveValidReferralCode($code, (int) $transaction->branch_id, $resolvedPackageId, $subtotal, true);
            $discount = $this->calculateDiscount($referralCode, $subtotal);
            $this->incrementUsage($referralCode);
            $this->updateTransactionReferralSnapshot($transaction, $referralCode, $subtotal, $discount);

            return ReferralRedemption::query()->create($this->baseRedemptionPayload($referralCode, $subtotal, $discount, $channel, $appliedBy) + [
                'booking_id' => $transaction->booking_id ? (int) $transaction->booking_id : null,
                'transaction_id' => (int) $transaction->id,
                'queue_ticket_id' => $transaction->queue_ticket_id ? (int) $transaction->queue_ticket_id : ($queueTicket?->id ? (int) $queueTicket->id : null),
                'branch_id' => (int) $transaction->branch_id,
                'package_id' => $resolvedPackageId > 0 ? $resolvedPackageId : null,
                'customer_name' => $this->transactionCustomerName($transaction, $queueTicket),
                'customer_phone' => $this->transactionCustomerPhone($transaction, $queueTicket),
            ]);
        });
    }

    public function linkBookingRedemptionToTransaction(Booking $booking, Transaction $transaction): void
    {
        ReferralRedemption::query()
            ->where('booking_id', $booking->id)
            ->whereNull('transaction_id')
            ->where('status', '!=', ReferralRedemption::STATUS_VOIDED)
            ->update(['transaction_id' => (int) $transaction->id]);
    }

    public function markBookingStatus(Booking $booking, string $status): void
    {
        if (! in_array($status, [ReferralRedemption::STATUS_APPLIED, ReferralRedemption::STATUS_PAID, ReferralRedemption::STATUS_DONE], true)) {
            return;
        }

        ReferralRedemption::query()
            ->where('booking_id', $booking->id)
            ->where('status', '!=', ReferralRedemption::STATUS_VOIDED)
            ->update(['status' => $status]);
    }

    public function markTransactionStatus(Transaction $transaction, string $status): void
    {
        if (! in_array($status, [ReferralRedemption::STATUS_APPLIED, ReferralRedemption::STATUS_PAID, ReferralRedemption::STATUS_DONE], true)) {
            return;
        }

        ReferralRedemption::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', '!=', ReferralRedemption::STATUS_VOIDED)
            ->update(['status' => $status]);
    }

    public function voidForBooking(Booking $booking, string $reason, ?int $actorId = null): void
    {
        DB::transaction(function () use ($booking, $reason, $actorId): void {
            $redemptions = ReferralRedemption::query()
                ->where('booking_id', $booking->id)
                ->where('status', '!=', ReferralRedemption::STATUS_VOIDED)
                ->lockForUpdate()
                ->get();

            foreach ($redemptions as $redemption) {
                $this->voidRedemption($redemption, $reason, $actorId);
            }
        });
    }

    public function voidForTransaction(Transaction $transaction, string $reason, ?int $actorId = null): void
    {
        DB::transaction(function () use ($transaction, $reason, $actorId): void {
            $redemptions = ReferralRedemption::query()
                ->where('transaction_id', $transaction->id)
                ->where('status', '!=', ReferralRedemption::STATUS_VOIDED)
                ->lockForUpdate()
                ->get();

            foreach ($redemptions as $redemption) {
                $this->voidRedemption($redemption, $reason, $actorId);
            }
        });
    }

    public function calculateDiscount(ReferralCode $referralCode, float $subtotal): float
    {
        $subtotal = max(round($subtotal, 2), 0);
        $discountValue = max((float) $referralCode->discount_value, 0);

        if ($subtotal <= 0 || $discountValue <= 0) {
            return 0.0;
        }

        if ($referralCode->discount_type === ReferralCode::DISCOUNT_PERCENT) {
            $discount = round($subtotal * min($discountValue, 100) / 100, 2);
            $maxDiscount = (float) ($referralCode->max_discount_amount ?? 0);

            if ($maxDiscount > 0) {
                $discount = min($discount, $maxDiscount);
            }
        } else {
            $discount = $discountValue;
        }

        return min(max(round($discount, 2), 0), $subtotal);
    }

    private function resolveValidReferralCode(string $code, int $branchId, int $packageId, float $subtotal, bool $lock): ReferralCode
    {
        $query = ReferralCode::query()->where('code', $code);

        if ($lock) {
            $query->lockForUpdate();
        }

        /** @var ReferralCode|null $referralCode */
        $referralCode = $query->first();

        if (! $referralCode) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode referal tidak valid.',
            ]);
        }

        $this->assertReferralCodeStillApplicable($referralCode, $branchId, $packageId, $subtotal);

        if ($referralCode->usage_limit !== null && (int) $referralCode->used_count >= (int) $referralCode->usage_limit) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode referal sudah mencapai batas pemakaian.',
            ]);
        }

        return $referralCode;
    }

    private function assertReferralCodeStillApplicable(ReferralCode $referralCode, int $branchId, int $packageId, float $subtotal): void
    {
        if (! $referralCode->is_active) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode referal sedang nonaktif.',
            ]);
        }

        $now = now();

        if ($referralCode->valid_from && $referralCode->valid_from->gt($now)) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode referal belum berlaku.',
            ]);
        }

        if ($referralCode->valid_until && $referralCode->valid_until->lt($now)) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode referal sudah kedaluwarsa.',
            ]);
        }

        if ($referralCode->branch_id !== null && (int) $referralCode->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode referal tidak berlaku untuk cabang ini.',
            ]);
        }

        if ($referralCode->package_id !== null && (int) $referralCode->package_id !== $packageId) {
            throw ValidationException::withMessages([
                'referral_code' => 'Kode referal tidak berlaku untuk paket ini.',
            ]);
        }

        if (max((float) $referralCode->min_order_amount, 0) > max($subtotal, 0)) {
            throw ValidationException::withMessages([
                'referral_code' => 'Subtotal belum memenuhi minimum transaksi kode referal.',
            ]);
        }
    }

    private function buildPreview(ReferralCode $referralCode, float $subtotal): array
    {
        $discount = $this->calculateDiscount($referralCode, $subtotal);

        return [
            'referral_code_id' => (int) $referralCode->id,
            'referral_code' => (string) $referralCode->code,
            'source_name' => (string) $referralCode->source_name,
            'source_type' => (string) $referralCode->source_type,
            'discount_type' => (string) $referralCode->discount_type,
            'discount_value' => (float) $referralCode->discount_value,
            'discount_amount' => $discount,
            'subtotal_amount' => max(round($subtotal, 2), 0),
            'final_amount' => max(round($subtotal - $discount, 2), 0),
        ];
    }

    private function baseRedemptionPayload(ReferralCode $referralCode, float $subtotal, float $discount, string $channel, ?int $appliedBy): array
    {
        return [
            'referral_code_id' => (int) $referralCode->id,
            'referral_code' => (string) $referralCode->code,
            'source_name' => (string) $referralCode->source_name,
            'source_type' => (string) $referralCode->source_type,
            'channel' => $channel,
            'status' => ReferralRedemption::STATUS_APPLIED,
            'applied_by' => $appliedBy,
            'redeemed_at' => now(),
        ] + $this->redemptionAmounts($subtotal, $discount);
    }

    private function redemptionAmounts(float $subtotal, float $discount): array
    {
        $subtotal = max(round($subtotal, 2), 0);
        $discount = min(max(round($discount, 2), 0), $subtotal);

        return [
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'final_amount' => max(round($subtotal - $discount, 2), 0),
        ];
    }

    private function updateBookingReferralSnapshot(Booking $booking, ?ReferralCode $referralCode, float $subtotal, float $discount): void
    {
        $subtotal = max(round($subtotal, 2), 0);
        $discount = min(max(round($discount, 2), 0), $subtotal);

        $booking->update([
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'referral_code_id' => $referralCode?->id,
            'referral_code' => $referralCode?->code,
            'referral_discount_amount' => $discount,
            'total_amount' => max(round($subtotal - $discount, 2), 0),
        ]);
    }

    private function updateTransactionReferralSnapshot(Transaction $transaction, ?ReferralCode $referralCode, float $subtotal, float $discount): void
    {
        $subtotal = max(round($subtotal, 2), 0);
        $discount = min(max(round($discount, 2), 0), $subtotal);
        $tax = max((float) $transaction->tax_amount, 0);
        $total = max(round($subtotal - $discount + $tax, 2), 0);
        $paid = max((float) $transaction->paid_amount, 0);

        $transaction->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'referral_code_id' => $referralCode?->id,
            'referral_code' => $referralCode?->code,
            'referral_discount_amount' => $discount,
            'total_amount' => $total,
            'change_amount' => max(round($paid - $total, 2), 0),
        ]);
    }

    private function activeBookingRedemption(Booking $booking): ?ReferralRedemption
    {
        return ReferralRedemption::query()
            ->where('booking_id', $booking->id)
            ->where('status', '!=', ReferralRedemption::STATUS_VOIDED)
            ->lockForUpdate()
            ->first();
    }

    private function activeTransactionRedemption(Transaction $transaction): ?ReferralRedemption
    {
        return ReferralRedemption::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', '!=', ReferralRedemption::STATUS_VOIDED)
            ->lockForUpdate()
            ->first();
    }

    private function incrementUsage(ReferralCode $referralCode): void
    {
        $referralCode->used_count = max((int) $referralCode->used_count, 0) + 1;
        $referralCode->save();
    }

    private function decrementUsage(?int $referralCodeId): void
    {
        if (! $referralCodeId) {
            return;
        }

        ReferralCode::query()
            ->whereKey($referralCodeId)
            ->where('used_count', '>', 0)
            ->decrement('used_count');
    }

    private function voidRedemption(ReferralRedemption $redemption, string $reason, ?int $actorId = null): void
    {
        if ($redemption->status === ReferralRedemption::STATUS_VOIDED) {
            return;
        }

        $redemption->update([
            'status' => ReferralRedemption::STATUS_VOIDED,
            'voided_at' => now(),
            'voided_reason' => $reason,
            'applied_by' => $actorId ?: $redemption->applied_by,
        ]);

        $this->decrementUsage($redemption->referral_code_id ? (int) $redemption->referral_code_id : null);
    }

    private function packageIdFromTransaction(Transaction $transaction): int
    {
        if ($transaction->booking?->package_id) {
            return (int) $transaction->booking->package_id;
        }

        $packageItem = $transaction->items()
            ->whereIn('item_type', ['package', 'booking'])
            ->first();

        return $packageItem?->item_ref_id ? (int) $packageItem->item_ref_id : 0;
    }

    private function transactionCustomerName(Transaction $transaction, ?QueueTicket $queueTicket): string
    {
        return (string) ($transaction->booking?->customer_name
            ?? $transaction->queueTicket?->customer_name
            ?? $queueTicket?->customer_name
            ?? '');
    }

    private function transactionCustomerPhone(Transaction $transaction, ?QueueTicket $queueTicket): string
    {
        return (string) ($transaction->booking?->customer_phone
            ?? $transaction->queueTicket?->customer_phone
            ?? $queueTicket?->customer_phone
            ?? '');
    }
}
