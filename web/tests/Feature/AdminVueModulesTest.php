<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\AddOn;
use App\Models\Booking;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\AdminBookingManagementService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminVueModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_probe_route_blocked_when_vue_driver_enabled(): void
    {
        Route::get('/filament/test-probe', fn () => response('ok'));

        config()->set('admin_ui.driver', 'vue');
        config()->set('admin_ui.block_filament_routes', true);

        $this->get('/filament/test-probe')
            ->assertNotFound();
    }

    public function test_filament_probe_route_accessible_when_filament_driver_enabled(): void
    {
        Route::get('/filament/test-probe', fn () => response('ok'));

        config()->set('admin_ui.driver', 'filament');
        config()->set('admin_ui.block_filament_routes', true);

        $this->get('/filament/test-probe')
            ->assertOk();
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('admin-dashboard-app');
    }

    public function test_branch_module_create_and_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/admin/branches', [
                'code' => 'HQ-01',
                'name' => 'Headquarter',
                'timezone' => 'Asia/Jakarta',
                'phone' => '0812000001',
                'address' => 'Main street',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->getJson('/admin/branches-data')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'code' => 'HQ-01',
                'name' => 'Headquarter',
            ]);
    }

    public function test_add_ons_module_page_and_data_endpoint_load_successfully(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/add-ons')
            ->assertOk()
            ->assertSee('admin-dashboard-app');

        $this->actingAs($user)
            ->getJson('/admin/add-ons-data')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'add_ons',
                ],
            ]);
    }

    public function test_time_slot_rejects_overlap(): void
    {
        $user = User::factory()->create();
        $branch = Branch::query()->create([
            'code' => 'BRANCH-01',
            'name' => 'Branch 1',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $payload = [
            'branch_id' => $branch->id,
            'slot_date' => '2026-04-22',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'capacity' => 2,
            'is_bookable' => true,
        ];

        $this->actingAs($user)
            ->postJson('/admin/time-slots', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->postJson('/admin/time-slots', [
                ...$payload,
                'start_time' => '09:30:00',
                'end_time' => '10:30:00',
            ])
            ->assertStatus(422);
    }

    public function test_booking_availability_respects_parallel_capacity(): void
    {
        $bookingDate = Carbon::now()->addDay()->toDateString();

        $branch = Branch::query()->create([
            'code' => 'BRANCH-SLOT-01',
            'name' => 'Branch Slot',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $package = Package::query()->create([
            'branch_id' => $branch->id,
            'code' => 'PKG-SLOT-01',
            'name' => 'Package Slot',
            'duration_minutes' => 30,
            'base_price' => 100000,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        TimeSlot::query()->create([
            'branch_id' => $branch->id,
            'slot_date' => $bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'capacity' => 2,
            'is_bookable' => true,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-SLOT-001',
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Customer Slot',
            'customer_phone' => '0812000100',
            'booking_date' => $bookingDate,
            'start_at' => $bookingDate.' 10:00:00',
            'end_at' => $bookingDate.' 10:30:00',
            'status' => 'confirmed',
            'source' => 'web',
            'payment_type' => 'full',
            'total_amount' => 100000,
            'paid_amount' => 0,
            'deposit_amount' => 0,
        ]);

        $slots = collect(
            $this->getJson('/booking/availability?branch_id='.$branch->id.'&package_id='.$package->id.'&date='.$bookingDate)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->json('data')
        );

        $slot = $slots->firstWhere('start_time', '10:00:00');

        $this->assertNotNull($slot);
        $this->assertTrue((bool) ($slot['is_available'] ?? false));
        $this->assertSame(1, (int) ($slot['remaining_slots'] ?? -1));
    }

    public function test_booking_service_allows_parallel_bookings_until_slot_capacity(): void
    {
        Queue::fake();

        $bookingDate = Carbon::now()->addDay()->toDateString();

        $branch = Branch::query()->create([
            'code' => 'BRANCH-CAP-01',
            'name' => 'Branch Capacity',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $package = Package::query()->create([
            'branch_id' => $branch->id,
            'code' => 'PKG-CAP-01',
            'name' => 'Package Capacity',
            'duration_minutes' => 30,
            'base_price' => 100000,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        TimeSlot::query()->create([
            'branch_id' => $branch->id,
            'slot_date' => $bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'capacity' => 2,
            'is_bookable' => true,
        ]);

        $service = app(BookingService::class);

        $firstBooking = $service->create([
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Customer One',
            'customer_phone' => '0812000201',
            'booking_date' => $bookingDate,
            'booking_time' => '10:00',
            'payment_type' => 'full',
            'addons' => [],
        ]);

        $secondBooking = $service->create([
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Customer Two',
            'customer_phone' => '0812000202',
            'booking_date' => $bookingDate,
            'booking_time' => '10:00',
            'payment_type' => 'full',
            'addons' => [],
        ]);

        $this->assertNotNull($firstBooking->id);
        $this->assertNotNull($secondBooking->id);
        $this->assertSame(2, Booking::query()->count());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Kapasitas slot sudah penuh.');

        $service->create([
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Customer Three',
            'customer_phone' => '0812000203',
            'booking_date' => $bookingDate,
            'booking_time' => '10:00',
            'payment_type' => 'full',
            'addons' => [],
        ]);
    }

    public function test_booking_availability_marks_past_time_slots_as_unavailable_for_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-23 13:30:00', 'Asia/Jakarta'));

        try {
            $bookingDate = '2026-04-23';

            $branch = Branch::query()->create([
                'code' => 'BRANCH-SLOT-PAST-01',
                'name' => 'Branch Slot Past',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]);

            $package = Package::query()->create([
                'branch_id' => $branch->id,
                'code' => 'PKG-SLOT-PAST-01',
                'name' => 'Package Slot Past',
                'duration_minutes' => 30,
                'base_price' => 100000,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            TimeSlot::query()->create([
                'branch_id' => $branch->id,
                'slot_date' => $bookingDate,
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'capacity' => 1,
                'is_bookable' => true,
            ]);

            TimeSlot::query()->create([
                'branch_id' => $branch->id,
                'slot_date' => $bookingDate,
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'capacity' => 1,
                'is_bookable' => true,
            ]);

            $slots = collect(
                $this->getJson('/booking/availability?branch_id='.$branch->id.'&package_id='.$package->id.'&date='.$bookingDate)
                    ->assertOk()
                    ->assertJsonPath('success', true)
                    ->json('data')
            );

            $pastSlot = $slots->firstWhere('start_time', '10:00:00');
            $futureSlot = $slots->firstWhere('start_time', '14:00:00');

            $this->assertNotNull($pastSlot);
            $this->assertNotNull($futureSlot);
            $this->assertFalse((bool) ($pastSlot['is_available'] ?? true));
            $this->assertSame(0, (int) ($pastSlot['remaining_slots'] ?? -1));
            $this->assertTrue((bool) ($futureSlot['is_available'] ?? false));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_app_setting_rejects_unknown_group(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/admin/app-settings/unknown', [
                'value' => ['enabled' => true],
            ])
            ->assertStatus(422);
    }

    public function test_pending_booking_must_be_verified_before_payment_confirmation(): void
    {
        $user = User::factory()->create();

        $branch = Branch::query()->create([
            'code' => 'BRANCH-PAY-01',
            'name' => 'Branch Payment',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $package = Package::query()->create([
            'branch_id' => $branch->id,
            'code' => 'PKG-PAY-01',
            'name' => 'Package Payment',
            'duration_minutes' => 30,
            'base_price' => 100000,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-PAY-001',
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '0812000002',
            'booking_date' => '2026-04-22',
            'start_at' => '2026-04-22 09:00:00',
            'end_at' => '2026-04-22 09:30:00',
            'status' => 'pending',
            'source' => 'web',
            'payment_type' => 'full',
            'total_amount' => 100000,
            'paid_amount' => 0,
            'deposit_amount' => 0,
        ]);

        $this->actingAs($user)
            ->postJson("/admin/bookings/{$booking->id}/confirm-payment", [
                'method' => 'cash',
                'amount' => 100000,
                'reference_no' => 'MANUAL-001',
                'notes' => 'test',
            ])
            ->assertStatus(422);
    }

    public function test_queue_booking_options_exclude_pending_bookings(): void
    {
        $user = User::factory()->create();

        $branch = Branch::query()->create([
            'code' => 'BRANCH-QUEUE-01',
            'name' => 'Branch Queue',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $package = Package::query()->create([
            'branch_id' => $branch->id,
            'code' => 'PKG-QUEUE-01',
            'name' => 'Package Queue',
            'duration_minutes' => 30,
            'base_price' => 100000,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-PENDING-QUEUE',
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Pending Customer',
            'customer_phone' => '0812000003',
            'booking_date' => '2026-04-22',
            'start_at' => '2026-04-22 10:00:00',
            'end_at' => '2026-04-22 10:30:00',
            'status' => 'pending',
            'source' => 'web',
            'payment_type' => 'full',
            'total_amount' => 100000,
            'paid_amount' => 0,
            'deposit_amount' => 0,
        ]);

        Booking::query()->create([
            'booking_code' => 'BK-CONFIRMED-QUEUE',
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Confirmed Customer',
            'customer_phone' => '0812000004',
            'booking_date' => '2026-04-22',
            'start_at' => '2026-04-22 11:00:00',
            'end_at' => '2026-04-22 11:30:00',
            'status' => 'confirmed',
            'source' => 'web',
            'payment_type' => 'full',
            'total_amount' => 100000,
            'paid_amount' => 0,
            'deposit_amount' => 0,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/admin/queue-data')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data.queue_booking_options');

        $bookingCodes = collect($response)->pluck('booking_code')->all();

        $this->assertContains('BK-CONFIRMED-QUEUE', $bookingCodes);
        $this->assertNotContains('BK-PENDING-QUEUE', $bookingCodes);
    }

    public function test_packages_data_counts_this_month_bookings_until_end_of_month(): void
    {
        Carbon::setTestNow('2026-04-10 10:00:00');

        try {
            $user = User::factory()->create();

            $branch = Branch::query()->create([
                'code' => 'BRANCH-PKG-01',
                'name' => 'Branch Package',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]);

            $activePackage = Package::query()->create([
                'branch_id' => $branch->id,
                'code' => 'PKG-APR-ACTIVE',
                'name' => 'Package Active',
                'duration_minutes' => 30,
                'base_price' => 100000,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $inactivePackage = Package::query()->create([
                'branch_id' => $branch->id,
                'code' => 'PKG-APR-INACTIVE',
                'name' => 'Package Inactive',
                'duration_minutes' => 45,
                'base_price' => 150000,
                'is_active' => false,
                'sort_order' => 1,
            ]);

            Booking::query()->create([
                'booking_code' => 'BK-PKG-A-APR-PAST',
                'branch_id' => $branch->id,
                'package_id' => $activePackage->id,
                'customer_name' => 'Customer A',
                'customer_phone' => '0812000010',
                'booking_date' => '2026-04-05',
                'start_at' => '2026-04-05 10:00:00',
                'end_at' => '2026-04-05 10:30:00',
                'status' => 'confirmed',
                'source' => 'web',
                'payment_type' => 'full',
                'total_amount' => 100000,
                'paid_amount' => 0,
                'deposit_amount' => 0,
            ]);

            Booking::query()->create([
                'booking_code' => 'BK-PKG-A-APR-FUTURE',
                'branch_id' => $branch->id,
                'package_id' => $activePackage->id,
                'customer_name' => 'Customer B',
                'customer_phone' => '0812000011',
                'booking_date' => '2026-04-25',
                'start_at' => '2026-04-25 11:00:00',
                'end_at' => '2026-04-25 11:30:00',
                'status' => 'pending',
                'source' => 'web',
                'payment_type' => 'full',
                'total_amount' => 100000,
                'paid_amount' => 0,
                'deposit_amount' => 0,
            ]);

            Booking::query()->create([
                'booking_code' => 'BK-PKG-A-MAR',
                'branch_id' => $branch->id,
                'package_id' => $activePackage->id,
                'customer_name' => 'Customer C',
                'customer_phone' => '0812000012',
                'booking_date' => '2026-03-28',
                'start_at' => '2026-03-28 09:00:00',
                'end_at' => '2026-03-28 09:30:00',
                'status' => 'done',
                'source' => 'web',
                'payment_type' => 'full',
                'total_amount' => 100000,
                'paid_amount' => 100000,
                'deposit_amount' => 0,
            ]);

            Booking::query()->create([
                'booking_code' => 'BK-PKG-B-APR-FUTURE',
                'branch_id' => $branch->id,
                'package_id' => $inactivePackage->id,
                'customer_name' => 'Customer D',
                'customer_phone' => '0812000013',
                'booking_date' => '2026-04-15',
                'start_at' => '2026-04-15 13:00:00',
                'end_at' => '2026-04-15 13:30:00',
                'status' => 'paid',
                'source' => 'web',
                'payment_type' => 'full',
                'total_amount' => 150000,
                'paid_amount' => 150000,
                'deposit_amount' => 0,
            ]);

            $packages = collect(
                $this->actingAs($user)
                    ->getJson('/admin/packages-data')
                    ->assertOk()
                    ->assertJsonPath('success', true)
                    ->json('data.packages')
            )->keyBy('code');

            $this->assertCount(2, $packages);

            $this->assertSame(3, (int) $packages->sum('this_month_bookings'));
            $this->assertSame(1, (int) $packages->where('is_active', true)->count());

            $this->assertSame(2, (int) ($packages['PKG-APR-ACTIVE']['this_month_bookings'] ?? 0));
            $this->assertSame(3, (int) ($packages['PKG-APR-ACTIVE']['total_bookings'] ?? 0));
            $this->assertSame(1, (int) ($packages['PKG-APR-INACTIVE']['this_month_bookings'] ?? 0));
            $this->assertSame(1, (int) ($packages['PKG-APR-INACTIVE']['total_bookings'] ?? 0));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_verified_booking_deducts_package_and_add_on_inventory_once(): void
    {
        $user = User::factory()->create();

        $branch = Branch::query()->create([
            'code' => 'BRANCH-INV-01',
            'name' => 'Branch Inventory',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $package = Package::query()->create([
            'branch_id' => $branch->id,
            'code' => 'PKG-INV-01',
            'name' => 'Package Inventory',
            'duration_minutes' => 30,
            'base_price' => 100000,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $paper = InventoryItem::query()->create([
            'code' => 'INV-TEST-PAPER',
            'name' => 'Test Paper 4R',
            'unit' => 'lembar',
            'available_stock' => 10,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        $package->inventoryItems()->attach($paper->id, ['qty_per_booking' => 1]);

        $addOn = AddOn::query()->create([
            'package_id' => null,
            'code' => 'AON-INV-PRINT',
            'name' => '+ 1 cetak test',
            'price' => 15000,
            'max_qty' => 10,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $addOn->inventoryItems()->attach($paper->id, ['qty_per_unit' => 1]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-INV-001',
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Inventory Customer',
            'customer_phone' => '0812009999',
            'booking_date' => '2026-04-22',
            'start_at' => '2026-04-22 09:00:00',
            'end_at' => '2026-04-22 09:30:00',
            'status' => 'pending',
            'source' => 'web',
            'payment_type' => 'dp50',
            'total_amount' => 115000,
            'paid_amount' => 57500,
            'deposit_amount' => 57500,
        ]);
        $booking->addOns()->attach($addOn->id, [
            'qty' => 2,
            'unit_price' => 15000,
            'line_total' => 30000,
        ]);

        $service = app(AdminBookingManagementService::class);
        $service->confirm($booking, $user->id, 'Inventory test verification');

        $this->assertSame(7, (int) $paper->refresh()->available_stock);

        $service->confirm($booking->refresh(), $user->id, 'Inventory test verification repeat');

        $this->assertSame(7, (int) $paper->refresh()->available_stock);
    }

    public function test_verified_booking_fails_when_inventory_is_insufficient(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create();

        $branch = Branch::query()->create([
            'code' => 'BRANCH-INV-02',
            'name' => 'Branch Inventory Shortage',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $package = Package::query()->create([
            'branch_id' => $branch->id,
            'code' => 'PKG-INV-02',
            'name' => 'Package Inventory Shortage',
            'duration_minutes' => 30,
            'base_price' => 100000,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $paper = InventoryItem::query()->create([
            'code' => 'INV-TEST-SHORTAGE',
            'name' => 'Shortage Paper 4R',
            'unit' => 'lembar',
            'available_stock' => 0,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        $package->inventoryItems()->attach($paper->id, ['qty_per_booking' => 1]);

        $booking = Booking::query()->create([
            'booking_code' => 'BK-INV-002',
            'branch_id' => $branch->id,
            'package_id' => $package->id,
            'customer_name' => 'Shortage Customer',
            'customer_phone' => '0812009998',
            'booking_date' => '2026-04-22',
            'start_at' => '2026-04-22 10:00:00',
            'end_at' => '2026-04-22 10:30:00',
            'status' => 'pending',
            'source' => 'web',
            'payment_type' => 'dp50',
            'total_amount' => 100000,
            'paid_amount' => 50000,
            'deposit_amount' => 50000,
        ]);

        app(AdminBookingManagementService::class)->confirm($booking, $user->id, 'Inventory shortage test');
    }
}
