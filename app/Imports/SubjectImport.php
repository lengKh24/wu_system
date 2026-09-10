<?php
namespace App\Imports;

use App\Helpers\Degree;
use App\Models\Faculty;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk subject import. Columns match SubjectExport's headings exactly
 * (export the current list, edit/append rows, re-import) — one row per
 * subject: Code | Name Kh | Name En | Faculty | Level | Lecturer Hour |
 * Credit | Remark.
 *
 * Code is required and globally unique (see SubjectRequest's
 * check_unique('subjects', 'code', true)) — keyed with updateOrCreate so
 * re-importing the same/an edited file updates the existing row instead of
 * erroring on a duplicate. Faculty is matched by name (case-insensitive,
 * trimmed); no match is a hard skip, same as a blank code/name — there's no
 * safe placeholder for a subject's faculty. Level maps onto the same
 * App\Helpers\Degree enum as Student::degree_type (associate/bachelor/
 * master/phd) — an unrecognized value is also a hard skip rather than
 * silently defaulting, since a wrong degree tier is a real data error.
 */
class SubjectImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public array $created = [];
    public array $skipped = [];

    protected array $facultyIndex;

    public function __construct()
    {
        $this->facultyIndex = $this->indexByName(Faculty::query()->get(['id', 'name_en']));
    }

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
        $code        = trim((string) ($row['code'] ?? ''));
        $nameKh      = trim((string) ($row['name_kh'] ?? ''));
        $nameEn      = trim((string) ($row['name_en'] ?? ''));
        $facultyName = trim((string) ($row['faculty'] ?? ''));
        $levelRaw    = trim((string) ($row['level'] ?? ''));

        if ($code === '' || $nameKh === '' || $nameEn === '' || $facultyName === '') {
            $this->skip($rowNumber, $code, 'Missing required field(s) (code, name Kh/En, or faculty).');
            return;
        }

        $facultyId = $this->facultyIndex[$this->key($facultyName)] ?? null;
        if ($facultyId === null) {
            $this->skip($rowNumber, $code, "No faculty matched \"{$facultyName}\".");
            return;
        }

        $level = $levelRaw !== '' ? $this->resolveLevel($levelRaw) : null;
        if ($level === null) {
            $this->skip($rowNumber, $code, "Level must be one of: associate, bachelor, master, phd — got \"{$levelRaw}\".");
            return;
        }

        try {
            $subject = Subject::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name_kh'       => $nameKh,
                    'name_en'       => $nameEn,
                    'faculty_id'    => $facultyId,
                    'level'         => $level->value,
                    'lecturer_hour' => is_numeric($row['lecturer_hour'] ?? null) ? (int) $row['lecturer_hour'] : null,
                    'credit'        => is_numeric($row['credit'] ?? null) ? (int) $row['credit'] : null,
                    'remark'        => trim((string) ($row['remark'] ?? '')) ?: null,
                ]
            );

            $this->created[] = $subject->id;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SubjectImport row failed', ['row' => $rowNumber, 'code' => $code, 'error' => $e->getMessage()]);
            $this->skip($rowNumber, $code, 'Could not save this row — please check for invalid values.');
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

    /**
     * Accepts either the raw enum value ("phd") or common human-readable
     * labels ("PhD", "Doctor/PhD", "Master's degree", ...) so a spreadsheet
     * built from the display labels (e.g. copied from the export/UI) still
     * imports correctly instead of being hard-skipped.
     */
    protected function resolveLevel(string $raw): ?Degree
    {
        $key = $this->key($raw);

        $aliases = [
            'associate'          => Degree::Associate,
            "associate's degree" => Degree::Associate,
            'bachelor'           => Degree::Bachelor,
            "bachelor's degree"  => Degree::Bachelor,
            'master'             => Degree::Master,
            "master's degree"    => Degree::Master,
            'phd'                => Degree::PhD,
            'doctor'             => Degree::PhD,
            'doctor/phd'         => Degree::PhD,
            'doctorate'          => Degree::PhD,
        ];

        return $aliases[$key] ?? Degree::tryFrom($key);
    }

    protected function key(string $value): string
    {
        // Strip zero-width/invisible characters that commonly sneak in via
        // copy-paste from Word/Google Docs (e.g. U+200B) — otherwise they
        // silently break exact-match lookups like the faculty name index.
        $value = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $value);
        return mb_strtolower(trim($value));
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
