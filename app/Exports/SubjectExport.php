<?php
namespace App\Exports;

use App\Models\Subject;

/**
 * Same column set as SubjectImport expects — exporting the current list
 * and re-importing it (after edits/additions) round-trips cleanly.
 */
class SubjectExport extends IExport
{
    protected string $model = Subject::class;

    protected array $relationships = ['faculty'];

    protected array $headings = [
        'No', 'Code', 'Name Kh', 'Name En', 'Faculty', 'Level', 'Lecturer Hour', 'Credit', 'Remark',
    ];

    public function __construct(protected array $filters = [])
    {
    }

    public function query()
    {
        $query = Subject::query()->with($this->relationships);

        if ($search = $this->filters['search'] ?? null) {
            $query->search($search);
        }
        if ($facultyId = $this->filters['faculty_id'] ?? null) {
            $query->where('faculty_id', $facultyId);
        }

        return $query;
    }

    public function map(mixed $data): array
    {
        $this->numRow++;

        return [
            $this->numRow,
            $data->code,
            $data->name_kh,
            $data->name_en,
            $data->faculty?->name_en,
            $data->level?->value,
            $data->lecturer_hour,
            $data->credit,
            $data->remark,
        ];
    }
}
