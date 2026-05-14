<?php

namespace App\Http\Resources;

use App\Enums\BookingStatus;
use App\Enums\TransactionStatus;
use App\Models\AddOn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $addOns = $this->mappedAddOns();
        $storedTotalAmount = (float) $this->total_amount;
        $storedPaidAmount = (float) $this->paid_amount;
        $transactionTotalAmount = (float) ($this->transaction?->total_amount ?? 0);
        $transactionPaidAmount = (float) ($this->transaction?->paid_amount ?? 0);
        $effectiveTotalAmount = max($storedTotalAmount, $transactionTotalAmount, 0);
        $paidAmount = max($storedPaidAmount, $transactionPaidAmount, 0);
        $remainingAmount = max($effectiveTotalAmount - $paidAmount, 0);
        $paymentStatus = $this->paymentStatus($effectiveTotalAmount, $paidAmount);
        $statusValue = $this->status?->value ?? (string) $this->status;
        $isClosedStatus = in_array($statusValue, [BookingStatus::Cancelled->value, BookingStatus::Done->value], true);
        $transferProofPath = $this->normalizePublicDiskPath((string) ($this->transfer_proof_path ?? ''));
        $transferProofExists = $transferProofPath !== '' && Storage::disk('public')->exists($transferProofPath);

        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'branch_id' => $this->branch_id,
            'branch_name' => (string) ($this->branch?->name ?? ''),
            'package_id' => $this->package_id,
            'package_name' => (string) ($this->package?->name ?? ''),
            'design_catalog_id' => $this->design_catalog_id,
            'design_name' => (string) ($this->designCatalog?->name ?? ''),
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'booking_date' => $this->booking_date?->toDateString(),
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'status' => $this->status?->value ?? $this->status,
            'source' => $this->source?->value ?? $this->source,
            'payment_type' => $this->payment_type,
            'payment_gateway' => $this->payment_gateway,
            'payment_reference' => $this->payment_reference,
            'payment_url' => $this->payment_url,
            'payment_expires_at' => $this->payment_expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'addons' => $addOns,
            'add_ons' => $addOns,
            'addon_total' => (float) collect($addOns)->sum('line_total'),
            'subtotal_amount' => (float) $this->subtotal_amount,
            'discount_amount' => (float) $this->discount_amount,
            'referral_code_id' => $this->referral_code_id,
            'referral_code' => $this->referral_code,
            'referral_discount_amount' => (float) $this->referral_discount_amount,
            'total_amount' => $effectiveTotalAmount,
            'deposit_amount' => (float) $this->deposit_amount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $paymentStatus,
            'notes' => $this->notes,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'transfer_proof_url' => $transferProofExists
                ? route('api.v1.bookings.transfer-proof', ['booking' => (int) $this->id], false)
                : '',
            'transfer_proof_file_name' => $transferProofExists ? basename($transferProofPath) : '',
            'transfer_proof_uploaded_at' => $this->transfer_proof_uploaded_at?->toIso8601String(),
            'transaction_id' => $this->transaction?->id ? (int) $this->transaction->id : null,
            'can_confirm_booking' => ! $isClosedStatus
                && $this->approved_at === null
                && ($effectiveTotalAmount <= 0 || $paidAmount > 0),
            'can_confirm_payment' => ! $isClosedStatus
                && $this->approved_at === null
                && $effectiveTotalAmount > 0
                && $paidAmount <= 0
                && in_array($paymentStatus, [TransactionStatus::Unpaid->value, TransactionStatus::Partial->value], true),
            'can_decline_booking' => ! $isClosedStatus
                && $this->approved_at === null
                && ! $transferProofExists,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'package' => new PackageResource($this->whenLoaded('package')),
            'design_catalog' => new DesignCatalogResource($this->whenLoaded('designCatalog')),
        ];
    }

    private function mappedAddOns(): array
    {
        if ($this->relationLoaded('addOns')) {
            return collect($this->addOns ?? [])
                ->map(function (AddOn $addOn): array {
                    $qty = max(0, (int) ($addOn->pivot?->qty ?? 0));
                    $unitPrice = (float) ($addOn->pivot?->unit_price ?? $addOn->price ?? 0);
                    $lineTotal = (float) ($addOn->pivot?->line_total ?? ($qty * $unitPrice));

                    return [
                        'add_on_id' => (int) $addOn->id,
                        'label' => (string) $addOn->name,
                        'name' => (string) $addOn->name,
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];
                })
                ->filter(fn (array $item): bool => $item['qty'] > 0)
                ->values()
                ->all();
        }

        return is_array($this->addons ?? null) ? $this->addons : [];
    }

    private function paymentStatus(float $totalAmount, float $paidAmount): string
    {
        if ($paidAmount <= 0) {
            return TransactionStatus::Unpaid->value;
        }

        if ($totalAmount > 0 && $paidAmount < $totalAmount) {
            return TransactionStatus::Partial->value;
        }

        return TransactionStatus::Paid->value;
    }

    private function normalizePublicDiskPath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($normalized, 'public/')) {
            return trim(substr($normalized, 7), '/');
        }

        if (str_starts_with($normalized, 'storage/')) {
            return trim(substr($normalized, 8), '/');
        }

        return $normalized;
    }
}
