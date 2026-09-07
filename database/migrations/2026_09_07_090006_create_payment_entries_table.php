<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ACC's independent reconciliation of a payment_batch against their own
 * system — payment_number/payment_note only; ACC cannot change
 * payment_status directly (that stays SA's action).
 */
return new class extends Migration
{
    public function up(): void
    {
        make_fields('payment_entries', function (Blueprint $table) {
            $table->foreignId('payment_batch_id')->constrained()->cascadeOnDelete();
            $table->string('payment_number', 100)->nullable();
            $table->text('payment_note')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('entered_at')->nullable();
        }, false);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_entries');
    }
};
