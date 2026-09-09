<?php
namespace App\Exports;

use App\Models\Student;

/**
 * Same column set as StudentImport expects — exporting the current list
 * and re-importing it (after edits/additions) round-trips cleanly, since
 * the headings here are exactly what the importer's WithHeadingRow keys
 * resolve to (see that class's docblock).
 */
class StudentExport extends IExport
{
    protected string $model = Student::class;

    protected array $relationships = ['person.nationality', 'batch', 'major', 'group', 'shift', 'campus', 'status'];

    protected array $headings = [
        'No', 'Code', 'First Name', 'Last Name', 'First Name Kh', 'Last Name Kh',
        'Sex', 'Dob', 'Nationality', 'Email', 'Phone',
        'Batch', 'Major', 'Group', 'Shift', 'Campus', 'Status',
        'Year Level', 'Payment As', 'Admission Date', 'From School',
        'Degree Type', 'Intake', 'Scholarship', 'Bacc 2 Code', 'Remark',
    ];

    public function __construct(protected array $filters = [])
    {
    }

    public function query()
    {
        $query = Student::query()->with($this->relationships);

        if ($search = $this->filters['search'] ?? null) {
            $query->search($search);
        }
        if ($payment = $this->filters['payment'] ?? null) {
            if ($payment === Student::YEARLY) {
                $query->yearly();
            } elseif ($payment === Student::SEMESTER) {
                $query->semester();
            }
        }

        return $query;
    }

    public function map(mixed $data): array
    {
        $this->numRow++;

        $person = $data->person;
        $phones = is_array($person?->phones) ? implode(', ', $person->phones) : null;
        $degreeType = $data->degree_type;

        return [
            $this->numRow,
            $data->code,
            $person?->first_name,
            $person?->last_name,
            $person?->first_name_kh,
            $person?->last_name_kh,
            $person?->sex,
            $person?->dob?->format('Y-m-d'),
            $person?->nationality?->name_en,
            $person?->email,
            $phones,
            $data->batch?->shortcut,
            $data->major?->name_en,
            $data->group?->name_en,
            $data->shift?->name_en,
            $data->campus?->name_en,
            $data->status?->name_en,
            $data->year_level,
            $data->payment_as,
            $data->admission_date?->format('Y-m-d'),
            $data->from_school,
            $degreeType?->value ?? $degreeType,
            $data->intake,
            $data->scholarship,
            $data->bacc_2_code,
            $data->remark,
        ];
    }
}
