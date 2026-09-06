<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class IExport implements FromQuery, WithHeadings, WithMapping, WithEvents, WithCustomCsvSettings
{
    protected string $model;
    protected array $relationships = [];
    protected array $headings      = [];
    protected bool $protectHeader  = false;
    protected int $numRow          = 0;

    public function query()
    {
        return ($this->model)::query()->with($this->relationships);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    abstract public function map(mixed $data): array;

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if ($this->protectHeader) {
                    $this->lockHeaderRow($event->sheet->getDelegate());
                }
                $this->afterSheet($event->sheet->getDelegate());
            },
        ];
    }

    public function getCsvSettings(): array
    {
        return ['output_bom' => 'UTF-8'];
    }

    protected function lockHeaderRow(Worksheet $sheet): void
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow    = $sheet->getHighestRow();

        $sheet->getProtection()->setSheet(true);

        $sheet->getStyle("A2:{$highestColumn}{$highestRow}")
            ->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

        $sheet->getStyle("A1:{$highestColumn}1")
            ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
    }

    /** Optional hook — override only if a specific export needs extra formatting */
    protected function afterSheet(Worksheet $sheet): void
    {}
}
