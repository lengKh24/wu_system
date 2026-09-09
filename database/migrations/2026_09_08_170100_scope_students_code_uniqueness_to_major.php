<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A student's code/bacc_2_code only needs to be unique within a major —
     * the same student re-enrolling under a different major (or two
     * different students sharing a legacy code across majors) is a real,
     * allowed case. Was a plain unique() on each column; now scoped to
     * (column, major_id).
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropUnique(['bacc_2_code']);

            $table->unique(['code', 'major_id']);
            $table->unique(['bacc_2_code', 'major_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['code', 'major_id']);
            $table->dropUnique(['bacc_2_code', 'major_id']);

            $table->unique('code');
            $table->unique('bacc_2_code');
        });
    }
};
