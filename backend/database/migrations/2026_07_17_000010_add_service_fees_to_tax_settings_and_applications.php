<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->decimal('convenience_fee', 12, 2)->default(0)->after('interest_counts_from_january');
            $table->decimal('server_fee', 12, 2)->default(0)->after('convenience_fee');
            $table->decimal('payment_processor_fee', 12, 2)->default(0)->after('server_fee');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->decimal('convenience_fee', 12, 2)->default(0)->after('delivery_fee');
            $table->decimal('server_fee', 12, 2)->default(0)->after('convenience_fee');
            $table->decimal('payment_processor_fee', 12, 2)->default(0)->after('server_fee');
        });
    }

    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->dropColumn(['convenience_fee', 'server_fee', 'payment_processor_fee']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['convenience_fee', 'server_fee', 'payment_processor_fee']);
        });
    }
};
