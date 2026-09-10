<?php
namespace App\Exports;

use App\Models\Major;

/**
 * Same column set as MajorImport expects — exporting the current list
 * and re-importing it (after edits/additions) round-trips cleanly.
 */
class MajorExport extends IExport
{
    protected string $model = Major::class;

    protected array $relationships = ['faculty'];

    protected array $headings = [
        'No', 'Name Kh', 'Name En', 'Shortcut', 'Faculty', 'Remark',
    ];

    public function __construct(protected array $filters = [])
    {
    }

    public function query()
    {
        $query = Major::query()->with($this->relationships);

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
            $data->name_kh,
            $data->name_en,
            $data->shortcut,
            $data->faculty?->name_en,
            $data->remark,
        ];
    }
}
