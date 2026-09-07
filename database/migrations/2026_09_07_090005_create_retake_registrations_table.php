<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core table — one row per student+subject per stage. Every stage is a
 * fully independent row (nothing on an older row is ever mutated by a
 * later stage — see RetakeBatch::carryForwardTo()), so payment history
 * survives intact even though a student pays up to 3 separate times.
 *
 * status_note ("ប្រឡងសង" / "រៀនសង" / "ការិយាល័យសិក្សា") is intentionally
 * NOT a column here — it's a per-batch computed count, so storing it would
 * go stale the moment a sibling row in the same batch is added or removed.
 * See RetakeRegistration::getStatusNoteAttribute().
 */
return new class extends Migration
{
    public function up(): void
    {
        make_fields('retake_registrations', function (Blueprint $table) {
            $table->foreignId('batch_id')->constrained('retake_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('retake_term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->nullable()->constrained()->nullOnDelete();

            // Carry-forward lineage: exactly one of these is set (or both
            // null for a fresh 1st Supp / Special row) — see decision #12.
            $table->foreignId('previous_registration_id')->nullable()->constrained('retake_registrations')->nullOnDelete();
            $table->foreignId('previous_deletion_log_id')->nullable()->constrained('deletion_log')->nullOnDelete();

            // Student picks which of their failed subjects to register for
            // this stage; registered_at freezes alongside is_selected once
            // they confirm on the public review screen (decision #15).
            $table->boolean('is_selected')->default(true);
            $table->dateTime('registered_at')->nullable();

            // Tracked per subject/row, not per student/term (decision #1) —
            // NOT NULL, default 'unpaid' so no query has to check both
            // '= unpaid' and 'IS NULL' (decision #2).
            $table->string('payment_status', 20)->default('unpaid');
            $table->foreignId('payment_batch_id')->nullable()->constrained('payment_batches')->nullOnDelete();

            $table->string('outcome', 20)->default('pending');

            // Stamped by SA once this registration is paid and the student
            // has been given the Telegram group's QR code.
            $table->dateTime('telegram_invited_at')->nullable();

            // Phase 2 (exam scheduling) — no exam_sessions table yet, so no
            // FK constraint until that table exists.
            $table->foreignId('exam_session_id')->nullable();

            $table->unique(['batch_id', 'student_id', 'subject_id'], 'uq_reg_batch_student_subject');
            $table->index(['payment_status', 'registered_at']);
            $table->index(['student_id', 'subject_id']);
        }, false);
    }

    public function down(): void
    {
        Schema::dropIfExists('retake_registrations');
    }
};
