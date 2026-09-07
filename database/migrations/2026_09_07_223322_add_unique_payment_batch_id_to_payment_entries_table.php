<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A payment_batch is already the invoice-level split (SA can create more
 * than one batch for the same student+exam when subjects are paid in
 * separate transactions — confirmed with Leng, 2026-09-07). Each invoice
 * only ever needs ONE reconciliation record from Accounting, so this is
 * enforced at the DB level, not just in the app (see
 * PaymentEntryController::store()'s updateOrCreate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_entries', function (Blueprint $table) {
            $table->unique('payment_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_entries', function (Blueprint $table) {
            $table->dropUnique(['payment_batch_id']);
        });
    }
};
