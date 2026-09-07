<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deliberately named `retake_terms`, not `terms` — the existing `terms`
 * table (see create_terms_table.php) is a fixed academic-calendar entity
 * (year/semester/code). A retake term is a different concept: one term
 * spans a whole cohort's retake period across 1st Supp -> 2nd Supp ->
 * Restudy (e.g. "B22Y2S2 (Mar-Jul)"); which stage is currently live is
 * tracked on retake_batches.status, not here.
 */
return new class extends Migration
{
    public function up(): void
    {
        make_fields('retake_terms', function (Blueprint $table) {
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(false);
        }, false);
    }

    public function down(): void
    {
        Schema::dropIfExists('retake_terms');
    }
};
