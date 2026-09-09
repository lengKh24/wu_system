<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Nullable + nullOnDelete (unlike batch/major/group/shift/status,
            // which are required identity fields) since campus is optional
            // metadata — removing a campus shouldn't wipe out its students.
            $table->foreignId('campus_id')->nullable()->after('shift_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campus_id');
        });
    }
};
