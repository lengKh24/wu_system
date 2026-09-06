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
        make_fields('retake_exams', function (Blueprint $table) {
            $table->string('no')->nullable();
            $table->string('full_name');
            $table->string('sex');
            $table->string('student_code');
            $table->string('phone_number')->nullable();
            $table->string('batch');
            $table->string('subject');
            $table->string('lecturer_name');
            $table->string('major');
            $table->string('shift');
            $table->boolean('status')->default(false);
            $table->dateTime('status_note')->nullable();
            $table->dateTime('registered_at')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_number')->nullable();
            $table->string('payment_note')->nullable();
            $table->string('term')->nullable();
            $table->string('exam_room')->nullable();
            $table->dateTime('exam_time')->nullable();
            $table->unsignedTinyInteger('exam_seat')->default(30);
            $table->decimal('score')->default(0);
            $table->string('attendance_status')->nullable();
        }, false);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retake_exams');
    }
};
