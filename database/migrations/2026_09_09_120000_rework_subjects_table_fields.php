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
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['year_level', 'semester']);

            // dropForeign() before dropColumn() — MySQL refuses to drop a
            // column a foreign key still depends on (see the retake
            // registrations subject_id migration's note from this same
            // session for the full story on why this order matters).
            $table->dropForeign(['major_id']);
            $table->dropColumn('major_id');

            $table->foreignId('faculty_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('level', 20)->default('associate')->after('code');
            $table->unsignedInteger('lecturer_hour')->nullable()->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['level', 'lecturer_hour']);

            $table->dropForeign(['faculty_id']);
            $table->dropColumn('faculty_id');

            $table->foreignId('major_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('year_level')->default(1);
            $table->unsignedTinyInteger('semester')->default(1);
        });
    }
};
