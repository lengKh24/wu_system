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
            // Set true by RetakeBatch::carryForwardTo() whenever a student
            // is carried into a Restudy-type batch — a persistent flag
            // rather than something derived per-view, since it's meant to
            // be visible/usable outside the retake exam module too.
            $table->boolean('is_restudy')->default(false)->after('payment_as');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('is_restudy');
        });
    }
};
