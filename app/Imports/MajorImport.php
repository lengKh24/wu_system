<?php
namespace App\Imports;

use App\Models\Faculty;
use App\Models\Major;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk major import. Columns match MajorExport's headings exactly
 * (export the current list, edit/append rows, re-import) — one row per
 * major: Name Kh | Name En | Shortcut | Faculty | Remark.
 *
 * Keyed with updateOrCreate on `shortcut` so re-importing the same/an
 * edited file updates the existing row instead of duplicating. Faculty is
 * matched by name (case-insensitive, trimmed, invisible-character-
 * stripped — same as SubjectImport's key()); no match is a hard skip,
 * same as a blank shortcut/name — there's no safe placeholder for a
 * major's faculty.
 */
class MajorImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public array $created = [];
    public array $skipped = [];

    protected array $facultyIndex;

    public function __construct()
    {
        $this->facultyIndex = $this->indexByName(Faculty::query()->get(['id', 'name_en']));
    }

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
        $nameKh      = $this->clean($row['name_kh'] ?? '');
        $nameEn      = $this->clean($row['name_en'] ?? '');
        $shortcut    = $this->clean($row['shortcut'] ?? '');
        $facultyName = $this->clean($row['faculty'] ?? '');

        if ($nameKh === '' || $nameEn === '' || $shortcut === '' || $facultyName === '') {
            $this->skip($rowNumber, $shortcut, 'Missing required field(s) (name Kh/En, shortcut, or faculty).');
            return;
        }

        $facultyId = $this->facultyIndex[$this->key($facultyName)] ?? null;
        if ($facultyId === null) {
            $this->skip($rowNumber, $shortcut, "No faculty matched \"{$facultyName}\".");
            return;
        }

        // withTrashed() — matching only against non-deleted rows would miss
        // a soft-deleted row still holding this shortcut, and updateOrCreate
        // would then try to create a new one and collide with the DB-level
        // unique constraint (which doesn't know about deleted_at). See the
        // "Acc" (Accounting) incident 2026-09-10 this guards against.
        $existing = Major::withTrashed()->where('shortcut', $shortcut)->first();
        if ($existing && $existing->trashed()) {
            $this->skip($rowNumber, $shortcut, 'A soft-deleted major already uses this shortcut. Restore it by hand first if you want it back.');
            return;
        }

        try {
            $major = Major::query()->updateOrCreate(
                ['shortcut' => $shortcut],
                [
                    'name_kh'    => $nameKh,
                    'name_en'    => $nameEn,
                    'faculty_id' => $facultyId,
                    'remark'     => trim((string) ($row['remark'] ?? '')) ?: null,
                ]
            );

            $this->created[] = $major->id;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('MajorImport row failed', ['row' => $rowNumber, 'shortcut' => $shortcut, 'error' => $e->getMessage()]);
            $this->skip($rowNumber, $shortcut, 'Could not save this row — please check for invalid values.');
        }
    }

    protected function skip(int $rowNumber, string $code, string $reason): void
    {
        $this->skipped[] = ['row' => $rowNumber, 'code' => $code, 'reason' => $reason];
    }

    protected function indexByName(iterable $models): array
    {
        $index = [];
        foreach ($models as $model) {
            $index[$this->key((string) $model->name_en)] = $model->id;
        }
        return $index;
    }

    protected function key(string $value): string
    {
        return mb_strtolower($this->clean($value));
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
