<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The flat, single-table `retake_exams` (2026_09_05_083357) is superseded
 * by the normalized schema created just above: retake_terms, exam_types,
 * retake_batches, payment_batches, deletion_log, retake_registrations,
 * payment_entries, scores. It mixed all 4 exam types with no lineage and
 * no real FKs, and couldn't express per-subject payment or carry-forward.
 * Confirmed with Leng to replace it outright rather than migrate data —
 * see the Retake Exam module design doc in the WU System project.
 *
 * down() recreates it exactly as it was, for reversibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('retake_exams');
    }

    public function down(): void
    {
        make_fields('retake_exams', function (Blueprint $table) {
            $table->string('no')->nullable();
            $table->string('full_name');
            $table->string('sex');
            $table->string('student_code');
            $table->string('phone_number')->nullable();
            $table->string('batch');
            $table->string('subject');
            $table->string('lecturer_name');
            $table->string('major');
            $table->string('shift');
            $table->boolean('status')->default(false);
            $table->dateTime('status_note')->nullable();
            $table->dateTime('registered_at')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_number')->nullable();
            $table->string('payment_note')->nullable();
            $table->string('term')->nullable();
            $table->string('exam_room')->nullable();
            $table->dateTime('exam_time')->nullable();
            $table->unsignedTinyInteger('exam_seat')->default(30);
            $table->decimal('score')->default(0);
            $table->string('attendance_status')->nullable();
        }, false);
    }
};
