<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per list-creation event, covering all 4 exam types:
 * source_type = import (1st Supp, from URM's Excel export), carried_forward
 * (2nd Supp / Restudy, generated from the previous batch's leftovers — see
 * RetakeBatch::carryForwardTo()), or manual (Special).
 */
return new class extends Migration
{
    public function up(): void
    {
        make_fields('retake_batches', function (Blueprint $table) {
            $table->foreignId('retake_term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_type_id')->constrained()->cascadeOnDelete();

            $table->string('source_type', 20)->default('manual');
            $table->foreignId('source_batch_id')->nullable()->constrained('retake_batches')->nullOnDelete();
            $table->string('file_name')->nullable();

            $table->string('status', 20)->default('open');

            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('generated_at')->nullable();

            // REG creates one Telegram group per batch and shares its QR
            // with SA, who hands it to students once they've paid.
            $table->string('telegram_group_link')->nullable();
            $table->string('telegram_qr_path')->nullable();
        }, false);
    }

    public function down(): void
    {
        Schema::dropIfExists('retake_batches');
    }
};
