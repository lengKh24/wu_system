<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leng's call, 2026-09-09: subjects don't need year_level/semester (dropped
 * outright — table had zero rows), belong to a Faculty directly instead of
 * a Major (skips the Major->Faculty hop), and need two new fields: level
 * (degree tier — same App\Helpers\Degree enum as Student::degree_type, see
 * that column's own migration for the matching default) and lecturer_hour
 * (total teaching hours for the whole subject, not per week).
 */
return new class extends Migration
{
    /**
     * Every step below is guarded with a hasColumn() check rather than run
     * unconditionally. MySQL's DDL isn't transactional, so if this
     * migration ever fails partway through (e.g. the dropForeign() call),
     * the statements before the failure stay applied even though Laravel
     * never marks the migration as run — a plain retry would then blow up
     * on columns/keys that are already gone. Guarding each step lets a
     * retry safely resume from wherever the previous attempt actually
     * stopped, in either direction.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'year_level')) {
                $table->dropColumn('year_level');
            }
            if (Schema::hasColumn('subjects', 'semester')) {
                $table->dropColumn('semester');
            }

            // dropForeign() before dropColumn() — MySQL refuses to drop a
            // column a foreign key still depends on (see the retake
            // registrations subject_id migration's note from this same
            // session for the full story on why this order matters).
            if (Schema::hasColumn('subjects', 'major_id')) {
                $table->dropForeign(['major_id']);
                $table->dropColumn('major_id');
            }

            if (! Schema::hasColumn('subjects', 'faculty_id')) {
                $table->foreignId('faculty_id')->after('id')->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('subjects', 'level')) {
                $table->string('level', 20)->default('associate')->after('code');
            }
            if (! Schema::hasColumn('subjects', 'lecturer_hour')) {
                $table->unsignedInteger('lecturer_hour')->nullable()->after('level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'level')) {
                $table->dropColumn('level');
            }
            if (Schema::hasColumn('subjects', 'lecturer_hour')) {
                $table->dropColumn('lecturer_hour');
            }

            if (Schema::hasColumn('subjects', 'faculty_id')) {
                $table->dropForeign(['faculty_id']);
                $table->dropColumn('faculty_id');
            }

            if (! Schema::hasColumn('subjects', 'major_id')) {
                $table->foreignId('major_id')->after('id')->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('subjects', 'year_level')) {
                $table->unsignedTinyInteger('year_level')->default(1);
            }
            if (! Schema::hasColumn('subjects', 'semester')) {
                $table->unsignedTinyInteger('semester')->default(1);
            }
        });
    }
};
