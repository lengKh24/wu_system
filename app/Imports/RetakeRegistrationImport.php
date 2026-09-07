<?php
namespace App\Imports;

use App\Models\Lecturer;
use App\Models\Major;
use App\Models\RetakeBatch;
use App\Models\RetakeRegistration;
use App\Models\Student;
use App\Models\Subject;
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
 * entirely (decision #10) — never read here.
 *
 * Subject is matched by Major + Subject name together, not name alone —
 * the real export has the same subject name (e.g. "Macroeconomics")
 * appearing under more than one major. Matching is case-insensitive and
 * trimmed since the sheet's casing is inconsistent even for the same
 * subject across rows (e.g. "Digital drawing media" vs "Digital Drawing
 * Media"). Lecturer is matched by name alone and is allowed to miss — a
 * typo'd or unrecognized lecturer name never blocks the student's
 * registration; the row is still created with lecturer_id null and
 * surfaced in the report for REG to fix by hand.
 */
class RetakeRegistrationImport implements ToCollection, WithHeadingRow
{
    public array $created = [];
    public array $skippedStudent = [];
    public array $skippedSubject = [];
    public array $flaggedLecturer = [];

    protected array $majorIndex;
    protected array $lecturerIndex;
    protected array $subjectCache = [];

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

        if (! $student) {
            $this->skippedStudent[] = [
                'row' => $rowNumber, 'student_code' => $code,
                'reason' => 'No student found with this code.',
            ];
            return;
        }

        $majorId   = $this->majorIndex[$this->key($majorName)] ?? null;
        $subjectId = $majorId ? $this->resolveSubjectId($majorId, $subjectName) : null;

        if (! $subjectId) {
            $this->skippedSubject[] = [
                'row' => $rowNumber, 'student_code' => $code,
                'major' => $majorName, 'subject' => $subjectName,
                'reason' => $majorId ? 'No subject matched that name under that major.' : 'Major not found.',
            ];
            return;
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
                'subject_id' => $subjectId,
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

    protected function resolveSubjectId(int $majorId, string $subjectName): ?int
    {
        $cacheKey = $majorId . '|' . $this->key($subjectName);

        if (array_key_exists($cacheKey, $this->subjectCache)) {
            return $this->subjectCache[$cacheKey];
        }

        $subject = Subject::query()
            ->where('major_id', $majorId)
            ->whereRaw('LOWER(TRIM(name_en)) = ?', [mb_strtolower($subjectName)])
            ->first();

        return $this->subjectCache[$cacheKey] = $subject?->id;
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
            'skipped_subject'  => $this->skippedSubject,
            'flagged_lecturer' => $this->flaggedLecturer,
        ];
    }
}
