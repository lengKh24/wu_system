<?php
namespace App\Exports;

use App\Models\Lecturer;

/**
 * Same column set as LecturerImport expects — exporting the current list
 * and re-importing it (after edits/additions) round-trips cleanly.
 */
class LecturerExport extends IExport
{
    protected string $model = Lecturer::class;

    protected array $headings = ['No', 'Code', 'Name Kh', 'Name En', 'Remark'];

    public function __construct(protected array $filters = [])
    {
    }

    public function query()
    {
        $query = Lecturer::query();

        if ($search = $this->filters['search'] ?? null) {
            $query->search($search);
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
            $data->remark,
        ];
    }
}
