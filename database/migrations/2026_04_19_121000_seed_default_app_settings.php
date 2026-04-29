<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            'general' => [
                'brand_name' => 'Ready To Pict',
                'short_name' => 'Studio',
                'tagline' => 'Photo booth cepat, estetik, dan anti ribet.',
                'support_email' => 'hello@example.com',
                'support_phone' => '',
                'address' => '',
                'logo_url' => '/favicon.ico',
            ],
            'booking' => [
                'hold_minutes' => 15,
                'arrival_notice_minutes' => 10,
                'queue_board_enabled' => true,
            ],
            'payment' => [
                'manual_payment_enabled' => true,
                'onsite_enabled' => false,
                'midtrans_enabled' => false,
                'currency' => 'IDR',
                'qr_label' => 'QR Pembayaran',
                'qr_image_url' => '',
                'transfer_instructions' => 'Scan QR sesuai nominal pembayaran lalu unggah bukti pembayaran untuk verifikasi admin.',
            ],
        ];

        foreach ($defaults as $key => $value) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($value, JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('app_settings')->whereIn('key', ['general', 'booking', 'payment'])->delete();
    }
};
