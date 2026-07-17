<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('individual_base_tax', 12, 2)->default(5);
            $table->decimal('individual_rate_amount', 12, 2)->default(1);
            $table->decimal('individual_rate_per', 12, 2)->default(1000);
            $table->decimal('individual_additional_cap', 12, 2)->default(5000);
            $table->decimal('corporation_base_tax', 12, 2)->default(500);
            $table->decimal('corporation_rate_amount', 12, 2)->default(1);
            $table->decimal('corporation_rate_per', 12, 2)->default(5000);
            $table->decimal('corporation_additional_cap', 12, 2)->default(10000);
            $table->decimal('interest_rate_percent', 8, 4)->default(2);
            $table->unsignedTinyInteger('deadline_month')->default(2);
            $table->unsignedTinyInteger('deadline_day')->default(28);
            $table->boolean('interest_counts_from_january')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
