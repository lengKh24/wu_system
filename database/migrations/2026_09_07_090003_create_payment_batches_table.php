<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SA's upload — one invoice/proof-of-payment can cover several
 * registrations for the same student (see payment_entries for ACC's
 * independent reconciliation of it).
 */
return new class extends Migration
{
    public function up(): void
    {
        make_fields('payment_batches', function (Blueprint $table) {
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_path')->nullable();
            $table->string('invoice_type', 30)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at')->nullable();
        }, false);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_batches');
    }
};
