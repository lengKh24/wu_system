<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Campus;
use App\Models\Major;
use App\Models\Student;

class DashboardController extends Controller
{
    /**
     * Registrar overview — one JSON payload for the whole /dashboard page:
     * overall headline counts plus a breakdown of every currently-enrolled
     * student (soft-deleted ones excluded automatically, same as every
     * other list in the app) by status, batch, major, campus, sex, and
     * degree type. No date-range filter (Leng's request, 2026-09-09) — this
     * is a live snapshot of "who's enrolled right now", not a historical
     * report.
     */
    public function report()
    {
        $totalStudents = Student::count();

        // statuses.shortcut is NOT used here — it's badly corrupted in the
        // live data (almost every row literally holds the string "student"
        // instead of a real shortcut like "active"/"dropout"; confirmed via
        // a raw DB::table query, so it's a genuine data issue, not an
        // artifact of this query). name_en is the only reliable label.
        $byStatus = Student::query()
            ->join('statuses', 'statuses.id', '=', 'students.status_id')
            ->selectRaw('statuses.id, statuses.name_en, statuses.name_kh, count(*) as total')
            ->groupBy('statuses.id', 'statuses.name_en', 'statuses.name_kh')
            ->orderByDesc('total')
            ->get();

        // Ordered by academic_year, not by count — a batch breakdown reads
        // as an enrollment-over-time trend (oldest cohort to newest), which
        // only makes sense in chronological order.
        $byBatch = Student::query()
            ->join('batches', 'batches.id', '=', 'students.batch_id')
            ->selectRaw('batches.id, batches.name_en, batches.name_kh, batches.shortcut, batches.academic_year, count(*) as total')
            ->groupBy('batches.id', 'batches.name_en', 'batches.name_kh', 'batches.shortcut', 'batches.academic_year')
            ->orderBy('batches.academic_year')
            ->get();

        $byMajor = Student::query()
            ->join('majors', 'majors.id', '=', 'students.major_id')
            ->selectRaw('majors.id, majors.name_en, majors.name_kh, majors.shortcut, count(*) as total')
            ->groupBy('majors.id', 'majors.name_en', 'majors.name_kh', 'majors.shortcut')
            ->orderByDesc('total')
            ->get();

        // leftJoin, not join — campus_id is nullable (see its migration),
        // so a student never assigned one still needs to show up as its
        // own "no campus" row instead of silently vanishing from the total.
        $byCampus = Student::query()
            ->leftJoin('campuses', 'campuses.id', '=', 'students.campus_id')
            ->selectRaw('campuses.id, campuses.name_en, campuses.name_kh, count(*) as total')
            ->groupBy('campuses.id', 'campuses.name_en', 'campuses.name_kh')
            ->orderByDesc('total')
            ->get();

        $bySex = Student::query()
            ->join('people', 'people.id', '=', 'students.person_id')
            ->selectRaw('people.sex, count(*) as total')
            ->groupBy('people.sex')
            ->orderByDesc('total')
            ->get();

        $byDegree = Student::query()
            ->selectRaw('degree_type, count(*) as total')
            ->groupBy('degree_type')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                // $row is still a Student instance (Student::query()), so
                // degree_type already went through the model's own cast to
                // the Degree enum — no need to re-parse it from a string.
                $degree = $row->degree_type;
                return [
                    'degree_type' => $degree?->value,
                    'name_en'     => $degree?->labelEn() ?? (string) $degree,
                    'name_kh'     => $degree?->labelKh() ?? (string) $degree,
                    'total'       => $row->total,
                ];
            });

        // Cross-tab: every status broken down per batch (Leng's request,
        // 2026-09-09), rendered client-side as a batch x status matrix. One
        // flat row per (batch, status) combo that actually has students —
        // empty combos are just absent, the frontend fills those as 0.
        $byBatchStatus = Student::query()
            ->join('batches', 'batches.id', '=', 'students.batch_id')
            ->join('statuses', 'statuses.id', '=', 'students.status_id')
            ->selectRaw('batches.id as batch_id, batches.shortcut as batch_shortcut, batches.academic_year, statuses.id as status_id, statuses.name_en as status_name, count(*) as total')
            ->groupBy('batches.id', 'batches.shortcut', 'batches.academic_year', 'statuses.id', 'statuses.name_en')
            ->orderBy('batches.academic_year')
            ->get();

        // Matched on name_en, not statuses.shortcut — see the note above.
        $activeCount = Student::query()
            ->join('statuses', 'statuses.id', '=', 'students.status_id')
            ->where('statuses.name_en', 'Active')
            ->count();

        return has_data([
            'overall' => [
                'total_students'  => $totalStudents,
                'total_batches'   => Batch::count(),
                'total_majors'    => Major::count(),
                'total_campuses'  => Campus::count(),
                'active_students' => $activeCount,
            ],
            'by_status'       => $byStatus,
            'by_batch'        => $byBatch,
            'by_major'        => $byMajor,
            'by_campus'       => $byCampus,
            'by_sex'          => $bySex,
            'by_degree'       => $byDegree,
            'by_batch_status' => $byBatchStatus,
        ]);
    }
}
