<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_contents', function (Blueprint $table) {
            $table->id();
            $table->string('headline')->default('Apply for your Community Tax Certificate online');
            $table->text('intro_text')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_position', 20)->default('after'); // before | after
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_contents');
    }
};
