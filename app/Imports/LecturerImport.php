<?php
namespace App\Imports;

use App\Models\Lecturer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk lecturer import. Columns match LecturerExport's headings exactly
 * (export the current list, edit/append rows, re-import) — one row per
 * lecturer: Code | Name Kh | Name En | Remark.
 *
 * Code is nullable at the DB level (see the lecturers table migration) but
 * required here — it's what updateOrCreate keys on, so re-importing the
 * same/an edited file updates the existing row instead of duplicating.
 * Without a code there's no safe key to match an existing row against (two
 * blank-code rows would otherwise collide with each other on re-import), so
 * a blank code is a hard skip rather than silently falling back to
 * name-matching.
 */
class LecturerImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public array $created = [];
    public array $skipped = [];

    /**
     * Delimiter and escape-character settings forced explicitly rather than
     * left to auto-detection — see StudentImport's docblock for the full
     * story on why (PhpSpreadsheet's delimiter auto-detection and its
     * default "\" escape character both misparse real-world spreadsheet
     * exports at scale).
     */
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
        $code   = trim((string) ($row['code'] ?? ''));
        $nameKh = trim((string) ($row['name_kh'] ?? ''));
        $nameEn = trim((string) ($row['name_en'] ?? ''));

        if ($code === '' || $nameKh === '' || $nameEn === '') {
            $this->skip($rowNumber, $code, 'Missing required field(s) (code or name Kh/En).');
            return;
        }

        try {
            $lecturer = Lecturer::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name_kh' => $nameKh,
                    'name_en' => $nameEn,
                    'remark'  => trim((string) ($row['remark'] ?? '')) ?: null,
                ]
            );

            $this->created[] = $lecturer->id;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('LecturerImport row failed', ['row' => $rowNumber, 'code' => $code, 'error' => $e->getMessage()]);
            $this->skip($rowNumber, $code, 'Could not save this row — please check for invalid values.');
        }
    }

    protected function skip(int $rowNumber, string $code, string $reason): void
    {
        $this->skipped[] = ['row' => $rowNumber, 'code' => $code, 'reason' => $reason];
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
