<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A student re-registering under the same code+major but a different
     * status (e.g. re-enrolling after "Dropout") is a real, allowed case —
     * so uniqueness widens from (code, major_id) to (code, major_id,
     * status_id). Same treatment for bacc_2_code, for consistency.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['code', 'major_id']);
            $table->dropUnique(['bacc_2_code', 'major_id']);

            $table->unique(['code', 'major_id', 'status_id']);
            $table->unique(['bacc_2_code', 'major_id', 'status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['code', 'major_id', 'status_id']);
            $table->dropUnique(['bacc_2_code', 'major_id', 'status_id']);

            $table->unique(['code', 'major_id']);
            $table->unique(['bacc_2_code', 'major_id']);
        });
    }
};
