<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lookup table, 4 seed rows: 1st Supplementary, 2nd Supplementary, Restudy,
 * Special. is_common=true so make_fields() gives us name_kh/name_en for
 * free — that's exactly the label_km/label_en pair from the design doc.
 */
return new class extends Migration
{
    public function up(): void
    {
        make_fields('exam_types', function (Blueprint $table) {
            $table->string('code', 30)->unique();
            $table->boolean('requires_import')->default(false);
            $table->boolean('uses_term')->default(true);
        }, true);
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_types');
    }
};
