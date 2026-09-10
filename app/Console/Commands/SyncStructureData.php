<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Major;
use App\Models\Nationality;
use App\Models\Shift;
use Illuminate\Console\Command;

/**
 * Applies hand-edits to database/data/*.json (faculties, majors, shifts,
 * groups, statuses, batches, nationalities) onto the real database —
 * without the duplication StructureSeeder would cause on a re-run (it does
 * an unconditional create() every time, see its own docblock) and without
 * ever truncating/re-seeding (which would cascade-delete dependent rows —
 * Major/Subject/Student all hang off these tables via FK).
 *
 * Matches each JSON row to an existing DB row by a stable key (shortcut,
 * or "code" for nationalities), updates name_kh/name_en if changed,
 * creates the row if the key doesn't exist yet. Never deletes anything —
 * removing a row from the JSON file does NOT remove it from the database;
 * do that by hand if that's really what you want.
 *
 * Strips zero-width/invisible characters (U+200B, U+200C, U+200D, U+FEFF)
 * from name_kh/name_en before comparing/saving — these sneak in silently
 * via copy-paste from Word/Google Docs and otherwise cause exact-match
 * lookups elsewhere (imports, dropdowns) to mysteriously fail. See the
 * 2026-09-10 faculties.json incident this command was built to replace.
 *
 * subjects.json is deliberately NOT included — Subject stopped matching
 * this file's shape (major_id/year_level/semester) once the Subject module
 * rework moved it to faculty_id/level/lecturer_hour (2026-09-09). Use the
 * Subject page's own Import feature for subject data instead.
 *
 * statuses.json is ALSO deliberately excluded (found 2026-09-10, while
 * testing this command): its "shortcut" values (active/deferred/...) are a
 * different concept than the DB's actual `statuses.shortcut` column, which
 * is a module-category tag ("student"/"Unpaid"/"Other"), not a per-status
 * unique key — every existing status row shares "student". Matching on it
 * would either collide or (as it did on first run) mass-create 11 pure
 * duplicate rows. Needs a real design decision (probably matching on
 * name_en instead) before this file can be synced safely; not attempted
 * here.
 */
class SyncStructureData extends Command
{
    protected $signature = 'data:sync {file? : One of faculties, majors, shifts, groups, batches, nationalities — omit to sync all}';

    protected $description = 'Sync a hand-edited database/data/*.json file onto the database (update-or-create by shortcut/code, never deletes)';

    /**
     * file => [Model class, unique key field].
     */
    protected const MODULES = [
        'faculties'     => [Faculty::class, 'shortcut'],
        'majors'        => [Major::class, 'shortcut'],
        'shifts'        => [Shift::class, 'shortcut'],
        'groups'        => [Group::class, 'shortcut'],
        'batches'       => [Batch::class, 'shortcut'],
        'nationalities' => [Nationality::class, 'code'],
    ];

    public function handle(): int
    {
        $file = $this->argument('file');

        if ($file !== null && ! array_key_exists($file, self::MODULES)) {
            $this->error("Unknown file \"{$file}\". Expected one of: " . implode(', ', array_keys(self::MODULES)));
            return self::FAILURE;
        }

        $targets = $file !== null ? [$file] : array_keys(self::MODULES);

        foreach ($targets as $name) {
            $this->syncFile($name);
        }

        return self::SUCCESS;
    }

    protected function syncFile(string $name): void
    {
        [$modelClass, $key] = self::MODULES[$name];

        $path = database_path("data/{$name}.json");
        if (! is_file($path)) {
            $this->warn("Skipping {$name} — database/data/{$name}.json not found.");
            return;
        }

        $rows = json_decode(file_get_contents($path), true) ?? [];
        $this->line("<fg=cyan>== {$name} ==</>");

        foreach ($rows as $row) {
            foreach (['name_en', 'name_kh'] as $field) {
                if (isset($row[$field])) {
                    $row[$field] = $this->stripInvisible($row[$field]);
                }
            }

            if (! isset($row[$key])) {
                $this->warn("  Skipped a row with no \"{$key}\" — cannot match or create safely.");
                continue;
            }

            // withTrashed() — matching only against non-deleted rows would
            // miss a soft-deleted row still holding this key, and then
            // create() would collide with it on the DB-level unique
            // constraint (which doesn't know about deleted_at). See the
            // shifts.json "ES" incident (2026-09-10) this guards against.
            $existing = $modelClass::withTrashed()->where($key, $row[$key])->first();

            // Fallback: match by name_en when the key itself doesn't hit —
            // covers legacy rows created before the key column was
            // populated (e.g. a pre-existing "Weekend" shift with a NULL
            // shortcut). Without this, such a row would silently get a
            // duplicate created next to it instead of being backfilled.
            if (! $existing && isset($row['name_en'])) {
                $existing = $modelClass::withTrashed()->where('name_en', $row['name_en'])->first();
            }

            if (! $existing) {
                $modelClass::create($row);
                $this->info("  Created [{$row[$key]}]");
                continue;
            }

            if ($existing->trashed()) {
                $this->warn("  Skipped [{$row[$key]}] — a soft-deleted row already holds this {$key}. Restore it by hand first if you want it back.");
                continue;
            }

            $changed = collect($row)->some(fn ($value, $field) => $existing->{$field} !== $value);

            if ($changed) {
                $existing->update($row);
                $this->info("  Updated [{$row[$key]}]");
            } else {
                $this->line("  Unchanged [{$row[$key]}]");
            }
        }
    }

    protected function stripInvisible(string $value): string
    {
        return preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $value);
    }
}
