<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Same change as retake_registrations (see the matching migration for that
 * table) — deletion_log mirrors retake_registrations' columns since it logs
 * a snapshot of a registration before it's hard-deleted (see
 * DeletionLog::logAndPurge()), so it needs the same subject_id -> subject
 * text swap to stay consistent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deletion_log', function (Blueprint $table) {
            // dropForeign() MUST run before dropIndex() — see the matching
            // note in 2026_09_08_220000_..._on_retake_registrations.php.
            $table->dropForeign(['subject_id']);
            $table->dropIndex(['student_id', 'subject_id']);
            $table->dropColumn('subject_id');

            $table->string('subject')->after('student_id');
        });

        Schema::table('deletion_log', function (Blueprint $table) {
            $table->index(['student_id', 'subject']);
        });
    }

    public function down(): void
    {
        Schema::table('deletion_log', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'subject']);
            $table->dropColumn('subject');

            $table->foreignId('subject_id')->after('student_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('deletion_log', function (Blueprint $table) {
            $table->index(['student_id', 'subject_id']);
        });
    }
};
