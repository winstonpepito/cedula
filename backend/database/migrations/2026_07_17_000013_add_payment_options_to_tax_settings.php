<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->boolean('manual_payment_only')->default(false)->after('default_province');
            $table->string('gcash_number')->nullable()->after('manual_payment_only');
        });
    }

    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->dropColumn(['manual_payment_only', 'gcash_number']);
        });
    }
};
