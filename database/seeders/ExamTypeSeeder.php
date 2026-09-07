<?php
namespace Database\Seeders;

use App\Models\ExamType;
use Illuminate\Database\Seeder;

class ExamTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        set_records('exam-types', fn($data) => ExamType::create($data));
    }
}
