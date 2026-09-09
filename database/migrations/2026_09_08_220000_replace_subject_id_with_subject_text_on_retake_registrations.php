<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retake subjects come from a messy import file with typos/inconsistent
 * casing and names that often don't exist in the Subject master table yet
 * (see RetakeRegistrationImport's old Major+Subject matching logic). Leng's
 * call, 2026-09-08: stop requiring a match at all — store whatever subject
 * name the file/staff enters as plain text. Table had zero rows at the time
 * of this change, so no backfill is needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retake_registrations', function (Blueprint $table) {
            $table->dropUnique('uq_reg_batch_student_subject');
            $table->dropForeign(['subject_id']);
            $table->dropIndex(['student_id', 'subject_id']);
            $table->dropColumn('subject_id');

            $table->string('subject')->after('exam_type_id');
        });

        Schema::table('retake_registrations', function (Blueprint $table) {
            $table->unique(['batch_id', 'student_id', 'subject'], 'uq_reg_batch_student_subject');
            $table->index(['student_id', 'subject']);
        });
    }

    public function down(): void
    {
        Schema::table('retake_registrations', function (Blueprint $table) {
            $table->dropUnique('uq_reg_batch_student_subject');
            $table->dropIndex(['student_id', 'subject']);
            $table->dropColumn('subject');

            $table->foreignId('subject_id')->after('exam_type_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('retake_registrations', function (Blueprint $table) {
            $table->unique(['batch_id', 'student_id', 'subject_id'], 'uq_reg_batch_student_subject');
            $table->index(['student_id', 'subject_id']);
        });
    }
};
