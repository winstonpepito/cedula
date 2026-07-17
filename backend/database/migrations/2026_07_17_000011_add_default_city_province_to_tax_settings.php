<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->string('default_city')->default('Cebu City')->after('payment_processor_fee');
            $table->string('default_province')->default('Cebu')->after('default_city');
        });
    }

    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->dropColumn(['default_city', 'default_province']);
        });
    }
};
