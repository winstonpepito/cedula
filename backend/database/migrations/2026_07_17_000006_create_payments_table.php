<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('method', 40); // paymongo_card | paymongo_gcash | proof_upload | mock
            $table->string('status', 30)->default('pending'); // pending | paid | failed | cancelled
            $table->decimal('amount', 12, 2);
            $table->string('paymongo_checkout_id')->nullable()->index();
            $table->string('paymongo_payment_id')->nullable();
            $table->string('checkout_url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
