<?php
namespace App\Imports;

use App\Models\Faculty;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk faculty import. Columns match FacultyExport's headings exactly
 * (export the current list, edit/append rows, re-import) — one row per
 * faculty: Name Kh | Name En | Shortcut | Remark.
 *
 * Keyed with updateOrCreate on `shortcut` (case-insensitive, trimmed, with
 * invisible/zero-width characters stripped — see SubjectImport's key()
 * docblock for why) so re-importing the same/an edited file updates the
 * existing row instead of duplicating. A blank shortcut is a hard skip —
 * it's the only stable identity a re-import can match on.
 */
class FacultyImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
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
        $nameKh   = $this->clean($row['name_kh'] ?? '');
        $nameEn   = $this->clean($row['name_en'] ?? '');
        $shortcut = $this->clean($row['shortcut'] ?? '');

        if ($nameKh === '' || $nameEn === '' || $shortcut === '') {
            $this->skip($rowNumber, $shortcut, 'Missing required field(s) (name Kh/En, or shortcut).');
            return;
        }

        try {
            $faculty = Faculty::query()->updateOrCreate(
                ['shortcut' => $shortcut],
                [
                    'name_kh' => $nameKh,
                    'name_en' => $nameEn,
                    'remark'  => trim((string) ($row['remark'] ?? '')) ?: null,
                ]
            );

            $this->created[] = $faculty->id;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FacultyImport row failed', ['row' => $rowNumber, 'shortcut' => $shortcut, 'error' => $e->getMessage()]);
            $this->skip($rowNumber, $shortcut, 'Could not save this row — please check for invalid values.');
        }
    }

    protected function skip(int $rowNumber, string $code, string $reason): void
    {
        $this->skipped[] = ['row' => $rowNumber, 'code' => $code, 'reason' => $reason];
    }

    /**
     * Trims and strips zero-width/invisible characters (U+200B, U+200C,
     * U+200D, U+FEFF) that sneak in silently via copy-paste from Word/
     * Google Docs — otherwise they break exact-match lookups elsewhere
     * (re-import matching, dropdowns). See the 2026-09-10 faculties.json
     * incident (a hand-edited name silently carried these).
     */
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
