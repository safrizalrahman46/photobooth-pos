<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('walk_in_requests', function (Blueprint $table): void {
            $table->string('customer_email', 255)->nullable()->after('customer_phone');
            $table->boolean('social_media_consent')->default(false)->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('walk_in_requests', function (Blueprint $table): void {
            $table->dropColumn(['social_media_consent', 'customer_email']);
        });
    }
};
