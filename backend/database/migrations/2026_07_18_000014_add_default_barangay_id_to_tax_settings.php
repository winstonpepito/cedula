<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->foreignId('default_barangay_id')
                ->nullable()
                ->after('default_province')
                ->constrained('barangays')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_barangay_id');
        });
    }
};
