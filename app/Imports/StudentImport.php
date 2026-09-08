<?php
namespace App\Imports;

use App\Helpers\Degree;
use App\Models\Batch;
use App\Models\Group;
use App\Models\Major;
use App\Models\Nationality;
use App\Models\Person;
use App\Models\Shift;
use App\Models\Status;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk student enrollment import. Columns match StudentExport's headings
 * exactly (export the current list, edit/append rows, re-import) — one
 * row per student:
 *   Code | First Name | Last Name | First Name Kh | Last Name Kh | Sex |
 *   Dob | Nationality | Email | Phone | Batch | Major | Group | Shift |
 *   Status | Year Level | Payment As | Admission Date | From School |
 *   Degree Type | Intake | Scholarship | Bacc 2 Code
 *
 * Addresses and guardians are deliberately out of scope here — too much
 * structure for a flat spreadsheet row; staff add those afterward via the
 * normal edit form, same as decision made for the retake-exam importer
 * (RetakeRegistrationImport) not covering every field either.
 *
 * A row is skipped (not fatal to the rest of the file) when: the code is
 * blank or already in use, any of the required identity fields
 * (first/last name EN+KH, sex) are blank, or Nationality/Batch/Major/
 * Group/Shift/Status doesn't match an existing record by name. These are
 * all NOT NULL foreign keys on students/people — there's no safe
 * placeholder to substitute the way the retake-exam importer's local-only
 * bypass does, since this creates real, permanent enrollment records.
 */
class StudentImport implements ToCollection, WithHeadingRow
{
    public array $created = [];
    public array $skipped = [];

    protected array $nationalityIndex;
    protected array $batchIndex;
    protected array $majorIndex;
    protected array $groupIndex;
    protected array $shiftIndex;
    protected array $statusIndex;

    public function __construct()
    {
        $this->nationalityIndex = $this->indexByName(Nationality::query()->get(['id', 'name_en']));
        $this->batchIndex       = $this->indexByName(Batch::query()->get(['id', 'name_en']));
        $this->majorIndex       = $this->indexByName(Major::query()->get(['id', 'name_en']));
        $this->groupIndex       = $this->indexByName(Group::query()->get(['id', 'name_en']));
        $this->shiftIndex       = $this->indexByName(Shift::query()->get(['id', 'name_en']));
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

        if (Student::withTrashed()->where('code', $code)->exists()) {
            $this->skip($rowNumber, $code, 'A student with this code already exists.');
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

        $degreeType = Degree::tryFrom($this->key((string) ($row['degree_type'] ?? '')))?->value ?? Degree::Associate->value;

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
            'status_id'       => $statusId,
            'year_level'      => (int) ($row['year_level'] ?? 1) ?: 1,
            'payment_as'      => trim((string) ($row['payment_as'] ?? '')) ?: Student::NONE,
            'admission_date'  => $this->parseDate($row['admission_date'] ?? null),
            'from_school'     => trim((string) ($row['from_school'] ?? '')) ?: null,
            'degree_type'     => $degreeType,
            'intake'          => trim((string) ($row['intake'] ?? '')) ?: 'primary',
            'scholarship'     => trim((string) ($row['scholarship'] ?? '')) ?: 'none',
            'bacc_2_code'     => trim((string) ($row['bacc_2_code'] ?? '')) ?: null,
        ]);

        $this->created[] = $student->id;
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
            'created_count' => count($this->created),
            'created_ids'   => $this->created,
            'skipped'       => $this->skipped,
        ];
    }
}
