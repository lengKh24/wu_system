<?php
namespace App\Imports;

use App\Models\Batch;
use App\Models\Group;
use App\Models\Lecturer;
use App\Models\Major;
use App\Models\Nationality;
use App\Models\Person;
use App\Models\RetakeBatch;
use App\Models\RetakeRegistration;
use App\Models\Shift;
use App\Models\Status;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * 1st Supplementary import — the only exam type ever sourced from an
 * external file (decision #6). Real URM export columns (confirmed
 * 2026-09-07 against an unedited export): Student Code | Student Name |
 * contact | lecturer | Shift | Major | Subject — one row per student per
 * failed subject.
 *
 * Per decision #9, the sheet's own Student Name/contact are display-only
 * for whoever prepared the file; the student master (looked up by
 * Student Code) is the only source of truth for identity. "Shift" is a
 * messy multi-value class-code list, not a real shift, and is dropped
 * entirely (decision #10) — never read here. "Major" is likewise dropped
 * outside local dev (see createPlaceholderStudent()) — it was only ever
 * needed to scope Subject matching, and Subject is no longer matched
 * against anything (Leng's call, 2026-09-08): the sheet's Subject text is
 * stored as-is, trimmed, on subject (a plain string column, not a FK) —
 * the real export's casing is inconsistent even for the same subject
 * across rows (e.g. "Digital drawing media" vs "Digital Drawing Media"),
 * and forcing a match caused otherwise-good rows to be skipped just
 * because the subjects table hadn't been kept in perfect sync. Lecturer is
 * still matched by name, and is allowed to miss — a typo'd or unrecognized
 * lecturer name never blocks the student's registration; the row is still
 * created with lecturer_id null and surfaced in the report for REG to fix
 * by hand.
 */
class RetakeRegistrationImport implements ToCollection, WithHeadingRow
{
    public array $created = [];
    public array $skippedStudent = [];
    public array $flaggedLecturer = [];

    protected array $majorIndex;
    protected array $lecturerIndex;

    public function __construct(protected RetakeBatch $batch)
    {
        $this->majorIndex    = $this->indexByName(Major::query()->get(['id', 'name_en']));
        $this->lecturerIndex = $this->indexByName(Lecturer::query()->get(['id', 'name_en']));
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            // +1 because $i is 0-based, +1 again for the heading row itself.
            $this->processRow($i + 2, $row);
        }
    }

    protected function processRow(int $rowNumber, Collection $row): void
    {
        $code         = trim((string) ($row['student_code'] ?? ''));
        $majorName    = trim((string) ($row['major'] ?? ''));
        $subjectName  = trim((string) ($row['subject'] ?? ''));
        $lecturerName = trim((string) ($row['lecturer'] ?? ''));

        if ($code === '' || $subjectName === '') {
            $this->skippedStudent[] = [
                'row' => $rowNumber, 'student_code' => $code,
                'reason' => 'Missing student code or subject — row left blank.',
            ];
            return;
        }

        $student = Student::query()->where('code', $code)->first();
        $majorId = $this->majorIndex[$this->key($majorName)] ?? null;

        // --- Original strict match, commented out for local testing (2026-09-07) ---
        // No real students exist yet in dev, so every row was being skipped.
        // Restore this block (and delete the placeholder block below) before
        // importing against production, where a real match is required.
        // if (! $student) {
        //     $this->skippedStudent[] = [
        //         'row' => $rowNumber, 'student_code' => $code,
        //         'reason' => 'No student found with this code.',
        //     ];
        //     return;
        // }

        if (! $student) {
            if (! app()->environment('local')) {
                $this->skippedStudent[] = [
                    'row' => $rowNumber, 'student_code' => $code,
                    'reason' => 'No student found with this code.',
                ];
                return;
            }

            // Local-only: fabricate a minimal student so the import flow can
            // be exercised end-to-end without real student data loaded yet.
            $student = $this->createPlaceholderStudent($code, $majorId);
        }

        $lecturerId = $this->lecturerIndex[$this->key($lecturerName)] ?? null;

        // Keyed on (batch, student, subject) — matches the
        // uq_reg_batch_student_subject unique constraint, so re-importing
        // the same or an overlapping file updates the lecturer link
        // instead of throwing a duplicate-row error.
        $registration = RetakeRegistration::query()->updateOrCreate(
            [
                'batch_id'   => $this->batch->id,
                'student_id' => $student->id,
                'subject'    => $subjectName,
            ],
            [
                'retake_term_id' => $this->batch->retake_term_id,
                'exam_type_id'   => $this->batch->exam_type_id,
                'lecturer_id'    => $lecturerId,
                'is_selected'    => true,
            ]
        );

        $this->created[] = $registration->id;

        if (! $lecturerId && $lecturerName !== '') {
            $this->flaggedLecturer[] = [
                'row' => $rowNumber, 'student_code' => $code,
                'lecturer' => $lecturerName, 'registration_id' => $registration->id,
                'reason' => 'No lecturer matched this name — registration created without one.',
            ];
        }
    }

    /**
     * Local-testing-only helper — see the "local-only" branch in
     * processRow(). Fabricates a bare-minimum Person + Student so a
     * registration can be created for a code that isn't in the (currently
     * empty/sparse) local database. Picks whatever batch/group/shift/status/
     * nationality already exists locally; never runs outside `local` env.
     */
    protected function createPlaceholderStudent(string $code, ?int $majorId): Student
    {
        $person = Person::create([
            'first_name'     => 'Test',
            'last_name'      => $code,
            'first_name_kh'  => 'តេស្ត',
            'last_name_kh'   => $code,
            'nationality_id' => Nationality::query()->value('id'),
            'sex'            => 'other',
        ]);

        return Student::create([
            'person_id' => $person->id,
            'batch_id'  => Batch::query()->value('id'),
            'major_id'  => $majorId ?? Major::query()->value('id'),
            'group_id'  => Group::query()->value('id'),
            'shift_id'  => Shift::query()->value('id'),
            'status_id' => Status::query()->value('id'),
            'code'      => $code,
        ]);
    }

    protected function indexByName(iterable $models): array
    {
        $index = [];
        foreach ($models as $model) {
            $index[$this->key((string) $model->name_en)] = $model->id;
        }
        return $index;
    }

    protected function key(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public function report(): array
    {
        return [
            'created_count'    => count($this->created),
            'created_ids'      => $this->created,
            'skipped_student'  => $this->skippedStudent,
            'flagged_lecturer' => $this->flaggedLecturer,
        ];
    }
}
