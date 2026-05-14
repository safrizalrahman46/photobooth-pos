<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Package;
use App\Models\ReferralCode;
use App\Models\ReferralRedemption;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReferralCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_validate_endpoint_returns_discount_preview(): void
    {
        [$branch, $package] = $this->createBranchAndPackage();

        ReferralCode::query()->create([
            'code' => 'PROMO50',
            'source_name' => 'Instagram Campaign',
            'source_type' => 'campaign',
            'discount_type' => ReferralCode::DISCOUNT_FIXED,
            'discount_value' => 50000,
            'min_order_amount' => 100000,
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/referral-codes/validate', [
            'referral_code' => 'promo50',
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'subtotal_amount' => 200000,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.referral_code', 'PROMO50')
            ->assertJsonPath('data.discount_amount', 50000)
            ->assertJsonPath('data.final_amount', 150000);
    }

    public function test_referral_validate_endpoint_rejects_wrong_branch_scope(): void
    {
        [$branch, $package] = $this->createBranchAndPackage();
        $otherBranch = Branch::query()->create([
            'code' => 'BR-REF-02',
            'name' => 'Referral Branch 2',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        ReferralCode::query()->create([
            'code' => 'BRANCHONLY',
            'source_name' => 'Branch Campaign',
            'source_type' => 'campaign',
            'discount_type' => ReferralCode::DISCOUNT_FIXED,
            'discount_value' => 25000,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/referral-codes/validate', [
            'referral_code' => 'BRANCHONLY',
            'branch_id' => $otherBranch->id,
            'package_id' => $package->id,
            'subtotal_amount' => 200000,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'message' => 'Kode referal tidak berlaku untuk cabang ini.',
            ]);
    }

    public function test_percent_referral_discount_is_capped(): void
    {
        [$branch, $package] = $this->createBranchAndPackage();
        $service = app(ReferralService::class);

        ReferralCode::query()->create([
            'code' => 'CAP20',
            'source_name' => 'Partner Cap',
            'source_type' => 'partner',
            'discount_type' => ReferralCode::DISCOUNT_PERCENT,
            'discount_value' => 20,
            'max_discount_amount' => 30000,
            'is_active' => true,
        ]);

        $preview = $service->preview('cap20', $branch->id, $package->id, 250000);

        $this->assertSame(30000.0, $preview['discount_amount']);
        $this->assertSame(220000.0, $preview['final_amount']);
    }

    public function test_apply_to_booking_tracks_redemption_and_enforces_usage_limit(): void
    {
        [$branch, $package] = $this->createBranchAndPackage();
        $booking = $this->createBooking($branch, $package, 'BK-REF-001');
        $secondBooking = $this->createBooking($branch, $package, 'BK-REF-002');
        $actor = User::factory()->create();
        $service = app(ReferralService::class);

        $referralCode = ReferralCode::query()->create([
            'code' => 'LIMIT1',
            'source_name' => 'Staff A',
            'source_type' => 'staff',
            'discount_type' => ReferralCode::DISCOUNT_FIXED,
            'discount_value' => 40000,
            'usage_limit' => 1,
            'is_active' => true,
        ]);

        $redemption = $service->applyToBooking(
            $booking,
            'limit1',
            200000,
            ReferralService::CHANNEL_ADMIN_BOOKING,
            (int) $actor->id,
        );

        $this->assertNotNull($redemption);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'referral_code' => 'LIMIT1',
            'discount_amount' => 40000,
            'referral_discount_amount' => 40000,
            'total_amount' => 160000,
        ]);
        $this->assertDatabaseHas('referral_redemptions', [
            'booking_id' => $booking->id,
            'referral_code' => 'LIMIT1',
            'discount_amount' => 40000,
            'final_amount' => 160000,
            'channel' => ReferralService::CHANNEL_ADMIN_BOOKING,
            'applied_by' => $actor->id,
            'status' => ReferralRedemption::STATUS_APPLIED,
        ]);
        $this->assertSame(1, (int) $referralCode->refresh()->used_count);

        $this->expectException(ValidationException::class);

        $service->applyToBooking(
            $secondBooking,
            'LIMIT1',
            200000,
            ReferralService::CHANNEL_ADMIN_BOOKING,
            (int) $actor->id,
        );
    }

    public function test_void_for_booking_marks_redemption_voided_and_returns_usage_quota(): void
    {
        [$branch, $package] = $this->createBranchAndPackage();
        $booking = $this->createBooking($branch, $package, 'BK-REF-VOID');
        $actor = User::factory()->create();
        $service = app(ReferralService::class);

        $referralCode = ReferralCode::query()->create([
            'code' => 'VOIDME',
            'source_name' => 'Void Campaign',
            'source_type' => 'campaign',
            'discount_type' => ReferralCode::DISCOUNT_FIXED,
            'discount_value' => 25000,
            'usage_limit' => 1,
            'is_active' => true,
        ]);

        $service->applyToBooking($booking, 'VOIDME', 200000, ReferralService::CHANNEL_PUBLIC_WEB);
        $service->voidForBooking($booking->refresh(), 'Booking cancelled.', (int) $actor->id);

        $this->assertSame(0, (int) $referralCode->refresh()->used_count);
        $this->assertDatabaseHas('referral_redemptions', [
            'booking_id' => $booking->id,
            'referral_code' => 'VOIDME',
            'status' => ReferralRedemption::STATUS_VOIDED,
            'voided_reason' => 'Booking cancelled.',
            'applied_by' => $actor->id,
        ]);
    }

    private function createBranchAndPackage(): array
    {
        $branch = Branch::query()->create([
            'code' => 'BR-REF-01',
            'name' => 'Referral Branch',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $package = Package::query()->create([
            'branch_id' => $branch->id,
            'code' => 'PKG-REF-01',
            'name' => 'Referral Package',
            'duration_minutes' => 30,
            'base_price' => 200000,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return [$branch, $package];
    }

    private function createBooking(Branch $branch, Package $package, string $code): Booking
    {
        return Booking::query()->create([
            'booking_code' => $code,
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Referral Customer',
            'customer_phone' => '0812000000',
            'booking_date' => '2026-05-20',
            'start_at' => '2026-05-20 10:00:00',
            'end_at' => '2026-05-20 10:30:00',
            'status' => 'pending',
            'source' => 'web',
            'payment_type' => 'full',
            'addon_total' => 0,
            'subtotal_amount' => 200000,
            'discount_amount' => 0,
            'referral_discount_amount' => 0,
            'total_amount' => 200000,
            'deposit_amount' => 0,
            'paid_amount' => 0,
        ]);
    }
}
