<?php
namespace App\Exports;

use App\Models\Group;

/**
 * Same column set as GroupImport expects — exporting the current list
 * and re-importing it (after edits/additions) round-trips cleanly.
 */
class GroupExport extends IExport
{
    protected string $model = Group::class;

    protected array $headings = [
        'No', 'Name Kh', 'Name En', 'Shortcut', 'Remark',
    ];

    public function __construct(protected array $filters = [])
    {
    }

    public function query()
    {
        $query = Group::query();

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
            $data->name_kh,
            $data->name_en,
            $data->shortcut,
            $data->remark,
        ];
    }
}
