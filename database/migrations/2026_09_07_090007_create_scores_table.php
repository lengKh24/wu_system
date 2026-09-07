<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One score per registration, entered by Score (a REG sub-function).
 */
return new class extends Migration
{
    public function up(): void
    {
        make_fields('scores', function (Blueprint $table) {
            $table->foreignId('retake_registration_id')->unique()->constrained('retake_registrations')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('entered_at')->nullable();
        }, false);
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
