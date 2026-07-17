<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->string('public_token', 64)->unique();
            $table->string('applicant_type', 20); // individual | corporation
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('corporation_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('tin')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('citizenship')->nullable()->default('Filipino');
            $table->string('occupation')->nullable();
            $table->text('address_line');
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->foreignId('barangay_id')->constrained();
            $table->string('delivery_mode', 20); // soft_copy | pickup | delivery
            $table->decimal('monthly_salary', 14, 2)->nullable();
            $table->decimal('thirteenth_month', 14, 2)->nullable();
            $table->decimal('other_bonuses', 14, 2)->nullable();
            $table->decimal('annual_income', 14, 2)->nullable();
            $table->decimal('property_value', 14, 2)->nullable();
            $table->decimal('gross_receipts', 14, 2)->nullable();
            $table->json('tax_snapshot');
            $table->json('breakdown');
            $table->decimal('base_tax', 12, 2)->default(0);
            $table->decimal('additional_tax', 12, 2)->default(0);
            $table->decimal('interest_amount', 12, 2)->default(0);
            $table->decimal('community_tax_total', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total_due', 12, 2)->default(0);
            $table->unsignedTinyInteger('interest_months')->default(0);
            $table->string('status', 40)->default('awaiting_payment');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
