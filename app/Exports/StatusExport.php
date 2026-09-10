<?php
namespace App\Exports;

use App\Models\Status;

/**
 * Same column set as StatusImport expects — exporting the current list
 * and re-importing it (after edits/additions) round-trips cleanly.
 *
 * "Group" here is Status::shortcut — NOT a per-row unique key like on
 * Faculty/Major/Shift/Group. It's a shared module-category tag (e.g.
 * "student", "Unpaid") that several status rows deliberately have in
 * common — see StatusImport's docblock for why matching never uses it.
 */
class StatusExport extends IExport
{
    protected string $model = Status::class;

    protected array $headings = [
        'No', 'Name Kh', 'Name En', 'Group', 'Remark',
    ];

    public function __construct(protected array $filters = [])
    {
    }

    public function query()
    {
        $query = Status::query();

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
