<?php
namespace App\Imports;

use App\Helpers\Degree;
use App\Models\Batch;
use App\Models\Campus;
use App\Models\Group;
use App\Models\Major;
use App\Models\Nationality;
use App\Models\Person;
use App\Models\Shift;
use App\Models\Status;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk student enrollment import. Columns match StudentExport's headings
 * exactly (export the current list, edit/append rows, re-import) — one
 * row per student:
 *   Code | First Name | Last Name | First Name Kh | Last Name Kh | Sex |
 *   Dob | Nationality | Email | Phone | Batch | Major | Group | Shift |
 *   Campus | Status | Year Level | Payment As | Admission Date |
 *   From School | Degree Type | Intake | Scholarship | Bacc 2 Code | Remark
 *
 * Addresses and guardians are deliberately out of scope here — too much
 * structure for a flat spreadsheet row; staff add those afterward via the
 * normal edit form, same as decision made for the retake-exam importer
 * (RetakeRegistrationImport) not covering every field either.
 *
 * A row is skipped (not fatal to the rest of the file) when: the code is
 * blank, any of the required identity fields (first/last name EN+KH, sex)
 * are blank, or Nationality/Batch/Major/Group/Shift/Status doesn't match an
 * existing record. These are all NOT NULL foreign keys on students/people —
 * there's no safe placeholder to substitute the way the retake-exam
 * importer's local-only bypass does, since this creates real, permanent
 * enrollment records. Campus is the one optional lookup (campus_id is
 * nullable) — blank is fine, but a non-blank value that doesn't match is
 * still a skip rather than silently dropped.
 *
 * Code and Bacc 2 Code are only required to be unique *within the same
 * (major, status)* combination (matches the composite unique index added in
 * scope_students_code_uniqueness_to_major_and_status) — the same
 * code/bacc_2_code reused under a different major, OR the same major with a
 * different status (e.g. re-enrolling after "Dropout"), is allowed. That
 * check happens after Major and Status are resolved, not before.
 *
 * Batch is matched by its shortcut (e.g. "B21"); every other lookup
 * (Nationality/Major/Group/Shift/Campus/Status) is matched by name_en.
 */
class StudentImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public array $created = [];
    public array $skipped = [];

    /**
     * Two independent CSV-parsing footguns, both only visible at real-file
     * scale (never reproduced from small pasted/retyped samples):
     *
     * 1. Remark fields routinely contain patterns like "Date:29-Apr-26(Time:2:13PM)"
     *    — a stable number of colons per row next to a wildly varying number
     *    of commas (remark length differs row to row). That skews
     *    PhpSpreadsheet's delimiter auto-detection (it picks whichever
     *    candidate has the most *consistent* count per line) into wrongly
     *    picking ":" over ",", which silently glues the entire header into
     *    one field and corrupts every row identically. Force the delimiter
     *    instead of relying on auto-detection.
     *
     * 2. PhpSpreadsheet defaults to "\" as the CSV escape character. Excel
     *    (and every standard CSV writer) never uses backslash-escaping —
     *    only doubled quotes ("") represent a literal quote inside a quoted
     *    field. So a remark that happens to contain a backslash immediately
     *    before a straight quote (e.g. someone's own typed punctuation) gets
     *    misread as "the following quote is escaped, not a field
     *    terminator" — the field then stays open across newlines, silently
     *    swallowing every subsequent row as literal text until an unrelated
     *    quote several rows later accidentally closes it. Disabling the
     *    escape character (empty string) makes a bare quote always end the
     *    field, matching how Excel actually writes CSVs.
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter'        => ',',
            'escape_character' => '',
        ];
    }

    protected array $nationalityIndex;
    protected array $batchIndex;
    protected array $majorIndex;
    protected array $groupIndex;
    protected array $shiftIndex;
    protected array $campusIndex;
    protected array $statusIndex;

    public function __construct()
    {
        $this->nationalityIndex = $this->indexByName(Nationality::query()->get(['id', 'name_en']));
        $this->batchIndex       = $this->indexByColumn(Batch::query()->get(['id', 'shortcut']), 'shortcut');
        $this->majorIndex       = $this->indexByName(Major::query()->get(['id', 'name_en']));
        $this->groupIndex       = $this->indexByName(Group::query()->get(['id', 'name_en']));
        $this->shiftIndex       = $this->indexByName(Shift::query()->get(['id', 'name_en']));
        $this->campusIndex      = $this->indexByName(Campus::query()->get(['id', 'name_en']));
        $this->statusIndex      = $this->indexByName(Status::query()->get(['id', 'name_en']));
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $this->processRow($i + 2, $row);
        }
    }

    protected function processRow(int $rowNumber, Collection $row): void
    {
        $code         = trim((string) ($row['code'] ?? ''));
        $firstName    = trim((string) ($row['first_name'] ?? ''));
        $lastName     = trim((string) ($row['last_name'] ?? ''));
        $firstNameKh  = trim((string) ($row['first_name_kh'] ?? ''));
        $lastNameKh   = trim((string) ($row['last_name_kh'] ?? ''));
        $sex          = $this->key((string) ($row['sex'] ?? ''));

        if ($code === '' || $firstName === '' || $lastName === '' || $firstNameKh === '' || $lastNameKh === '') {
            $this->skip($rowNumber, $code, 'Missing required name field(s) (code, first/last name EN or KH).');
            return;
        }

        if (! in_array($sex, ['male', 'female', 'other'], true)) {
            $this->skip($rowNumber, $code, "Sex must be male, female, or other — got \"{$row['sex']}\".");
            return;
        }

        $nationalityId = $this->nationalityIndex[$this->key((string) ($row['nationality'] ?? ''))] ?? null;
        $batchId       = $this->batchIndex[$this->key((string) ($row['batch'] ?? ''))] ?? null;
        $majorId       = $this->majorIndex[$this->key((string) ($row['major'] ?? ''))] ?? null;
        $groupId       = $this->groupIndex[$this->key((string) ($row['group'] ?? ''))] ?? null;
        $shiftId       = $this->shiftIndex[$this->key((string) ($row['shift'] ?? ''))] ?? null;
        $statusId      = $this->statusIndex[$this->key((string) ($row['status'] ?? ''))] ?? null;

        $missing = array_filter([
            'Nationality' => $nationalityId === null,
            'Batch'       => $batchId === null,
            'Major'       => $majorId === null,
            'Group'       => $groupId === null,
            'Shift'       => $shiftId === null,
            'Status'      => $statusId === null,
        ]);

        if ($missing) {
            $this->skip($rowNumber, $code, 'No match for: ' . implode(', ', array_keys($missing)) . '.');
            return;
        }

        // Campus is optional (campus_id is nullable) — blank is fine, but a
        // typo'd non-blank value is still surfaced rather than dropped.
        $campusRaw = trim((string) ($row['campus'] ?? ''));
        $campusId  = null;
        if ($campusRaw !== '') {
            $campusId = $this->campusIndex[$this->key($campusRaw)] ?? null;
            if ($campusId === null) {
                $this->skip($rowNumber, $code, "No match for: Campus (\"{$campusRaw}\").");
                return;
            }
        }

        // Code/Bacc 2 Code only need to be unique within this major — same
        // value reused under a different major OR status (e.g. re-enrolling
        // after "Dropout") is allowed (see docblock).
        if (Student::withTrashed()->where('code', $code)->where('major_id', $majorId)->where('status_id', $statusId)->exists()) {
            $this->skip($rowNumber, $code, 'A student with this code, major, and status already exists.');
            return;
        }

        // bacc_2_code is nullable+unique(within major+status) — spreadsheets
        // routinely fill blanks with junk placeholders ("N/A", "Not
        // Found", ...) which would otherwise collide against each other.
        // Treat those as blank, and proactively check the real ones so a
        // genuine duplicate is a clean skip instead of a thrown DB error.
        $bacc2Code = $this->normalizePlaceholder((string) ($row['bacc_2_code'] ?? ''));
        if ($bacc2Code !== null && Student::withTrashed()->where('bacc_2_code', $bacc2Code)->where('major_id', $majorId)->where('status_id', $statusId)->exists()) {
            $this->skip($rowNumber, $code, "Bacc 2 Code \"{$bacc2Code}\" already exists for this major and status.");
            return;
        }

        $degreeType = Degree::tryFrom($this->key((string) ($row['degree_type'] ?? '')))?->value ?? Degree::Associate->value;

        // Defense in depth: even after the checks above, a genuine DB-level
        // constraint failure (e.g. a race with another row/request) should
        // skip this one row, not throw and abort every row after it.
        $person = null;
        try {
            $person = Person::create([
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'first_name_kh'  => $firstNameKh,
                'last_name_kh'   => $lastNameKh,
                'nationality_id' => $nationalityId,
                'dob'            => $this->parseDate($row['dob'] ?? null),
                'sex'            => $sex,
                'email'          => trim((string) ($row['email'] ?? '')) ?: null,
                'phones'         => $this->parsePhones($row['phone'] ?? null),
            ]);

            $student = $person->student()->create([
                'code'            => $code,
                'batch_id'        => $batchId,
                'major_id'        => $majorId,
                'group_id'        => $groupId,
                'shift_id'        => $shiftId,
                'campus_id'       => $campusId,
                'status_id'       => $statusId,
                'year_level'      => (int) ($row['year_level'] ?? 1) ?: 1,
                'payment_as'      => trim((string) ($row['payment_as'] ?? '')) ?: Student::NONE,
                'admission_date'  => $this->parseDate($row['admission_date'] ?? null),
                'from_school'     => trim((string) ($row['from_school'] ?? '')) ?: null,
                'degree_type'     => $degreeType,
                'intake'          => trim((string) ($row['intake'] ?? '')) ?: 'primary',
                'scholarship'     => trim((string) ($row['scholarship'] ?? '')) ?: 'none',
                'bacc_2_code'     => $bacc2Code,
                'remark'          => trim((string) ($row['remark'] ?? '')) ?: null,
            ]);

            $this->created[] = $student->id;
        } catch (\Throwable $e) {
            $person?->forceDelete();
            \Illuminate\Support\Facades\Log::warning('StudentImport row failed', ['row' => $rowNumber, 'code' => $code, 'error' => $e->getMessage()]);
            $this->skip($rowNumber, $code, 'Could not save this row — please check for duplicate or invalid values.');
        }
    }

    /**
     * Blanks out common spreadsheet junk ("N/A", "Not Found", "-", ...) so it
     * doesn't get saved as a literal duplicate string against a unique column.
     */
    protected function normalizePlaceholder(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $placeholders = ['n/a', 'na', 'not found', 'none', 'null', '-', '--', 'n\a'];
        return in_array($this->key($value), $placeholders, true) ? null : $value;
    }

    protected function skip(int $rowNumber, string $code, string $reason): void
    {
        $this->skipped[] = ['row' => $rowNumber, 'code' => $code, 'reason' => $reason];
    }

    protected function parseDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parsePhones(mixed $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    protected function indexByName(iterable $models): array
    {
        return $this->indexByColumn($models, 'name_en');
    }

    protected function indexByColumn(iterable $models, string $column): array
    {
        $index = [];
        foreach ($models as $model) {
            $value = (string) $model->{$column};
            if ($value === '') {
                continue;
            }
            $index[$this->key($value)] = $model->id;
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
            'created_count' => count($this->created),
            'created_ids'   => $this->created,
            'skipped'       => $this->skipped,
        ];
    }
}
