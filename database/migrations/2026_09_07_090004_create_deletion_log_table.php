<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Created before retake_registrations on purpose: retake_registrations
 * needs a real FK to this table (previous_deletion_log_id), but this table
 * must NOT have a real FK back to retake_registrations — that's the whole
 * point of it. original_registration_id / previous_registration_id are
 * historical pointers to rows that get hard-deleted (see
 * DeletionLog::logAndPurge()); a real FK would either block the delete or
 * null itself out, defeating the "survive the purge" purpose. They're kept
 * as plain unsignedBigInteger columns instead.
 *
 * This table is required carry-forward input, not just an audit nicety —
 * RetakeBatch::carryForwardTo() reads it to find students who were purged
 * before the next stage was generated.
 */
return new class extends Migration
{
    public function up(): void
    {
        make_fields('deletion_log', function (Blueprint $table) {
            $table->unsignedBigInteger('original_registration_id');
            $table->unsignedBigInteger('previous_registration_id')->nullable();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('retake_term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_type_id')->constrained()->cascadeOnDelete();

            $table->string('reason', 30)->default('unpaid_expired');
            $table->dateTime('deleted_at_source');

            $table->index(['student_id', 'subject_id']);
            $table->index(['exam_type_id', 'retake_term_id']);
        }, false);
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_log');
    }
};
