<?php
namespace App\Imports;

use App\Models\Status;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk status import. Columns match StatusExport's headings exactly
 * (export the current list, edit/append rows, re-import) — one row per
 * status: Name Kh | Name En | Group | Remark.
 *
 * Keyed with updateOrCreate on `name_en` — NOT `shortcut`, unlike every
 * other reference-data import (Faculty/Major/Shift/Group). Status's
 * `shortcut` column is a shared module-category tag (e.g. "student",
 * "Unpaid"), not a per-row unique value — most existing rows share
 * "student". Matching on it would either collide with an existing row or
 * (as verified 2026-09-10 while building the generic data:sync command)
 * mass-create duplicate rows the moment two statuses share a group. `name_en`
 * is the only field that's actually unique per status.
 */
class StatusImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public array $created = [];
    public array $skipped = [];

    public function getCsvSettings(): array
    {
        return [
            'delimiter'        => ',',
            'escape_character' => '',
        ];
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $this->processRow($i + 2, $row);
        }
    }

    protected function processRow(int $rowNumber, Collection $row): void
    {
        $nameKh = $this->clean($row['name_kh'] ?? '');
        $nameEn = $this->clean($row['name_en'] ?? '');
        $group  = $this->clean($row['group'] ?? '');

        if ($nameKh === '' || $nameEn === '') {
            $this->skip($rowNumber, $nameEn, 'Missing required field(s) (name Kh/En).');
            return;
        }

        // Group is only written when the cell is non-blank — a blank Group
        // cell leaves the existing value alone rather than nulling out a
        // real classification (unlike remark, which a blank cell does
        // clear — losing a note is low-stakes, losing which module a
        // status belongs to is not).
        $attributes = [
            'name_kh' => $nameKh,
            'remark'  => trim((string) ($row['remark'] ?? '')) ?: null,
        ];
        if ($group !== '') {
            $attributes['shortcut'] = $group;
        }

        try {
            $status = Status::query()->updateOrCreate(['name_en' => $nameEn], $attributes);

            $this->created[] = $status->id;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('StatusImport row failed', ['row' => $rowNumber, 'name_en' => $nameEn, 'error' => $e->getMessage()]);
            $this->skip($rowNumber, $nameEn, 'Could not save this row — please check for invalid values.');
        }
    }

    protected function skip(int $rowNumber, string $code, string $reason): void
    {
        $this->skipped[] = ['row' => $rowNumber, 'code' => $code, 'reason' => $reason];
    }

    protected function clean(mixed $value): string
    {
        $value = trim((string) $value);
        return preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $value);
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
