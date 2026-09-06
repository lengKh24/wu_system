<?php

namespace App\Http\Controllers;

use App\Helpers\Generic;
use App\Models\ExamState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

abstract class Controller
{
    use Generic;

    protected ?string $name = null;
    protected string $model;
    protected string $resource;
    protected array|string $relationships = [];
    protected array $rounds                 = ['ម៉ោងទី១', 'ម៉ោងទី២', 'ម៉ោងទី៣'];

    protected function not_found(string $id = '', string $message = 'not found'): JsonResponse
    {
        return no_data("{$this->name} {$message} with ID: {$id}", 404);
    }

    protected function list(Request $request,  ?callable $fn = null)
    {
        $limit    = $request->integer('per_page', 10);
        $response = $this->model::query();
        if ($fn) {
            $response = $fn($response);
        }

        if ($sort = $request->input('sort')) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $column    = ltrim($sort, '-');
            $response  = $response->orderBy($column, $direction);
        }

        $response = $response->search($request->search)->with($this->relationships);

        return $this->resource::collection($response->latest()->paginate($limit)->onEachSide(1));
    }

    protected function save(FormRequest $request, ?array $columns = [])
    {
        $response = ($this->model)::create(array_merge($request->validated(), $columns));
        return new $this->resource($this->reload($response));
    }

    protected function view(Model $response)
    {
        return new $this->resource($this->reload($response));
    }

    protected function release(FormRequest $request, Model $response, ?array $columns = [])
    {
        $response->update(array_merge($request->validated(), $columns));
        return new $this->resource($this->reload($response));
    }

    protected function disable(Model $response)
    {
        $response->delete();
        return has_data(null, 'Moved to trash.');
    }

    protected function enable(Model $response)
    {
        $response->restore();
        return new $this->resource($this->reload($response));
    }

    protected function clear(Model $response)
    {
        $response->forceDelete();
        return has_data(null, 'Permanently deleted.');
    }

    protected function reload(object $data)
    {
        return $data->fresh()->load($this->relationships ?? []);
    }

    protected function export(object $data, string $file_name = 'student')
    {
        $time = now()->format('Y_m_d_His');
        return Excel::download($data, "{$file_name}_{$time}.xlsx");
    }

    protected function import(object $data, UploadedFile $file): array
    {
        Excel::import($data, $file);
        return [
            'imported' => true,
            'failures' => $data->failures(), // rows that failed validation
        ];
    }

    protected function withStudent(string $key = 'student')
    {
        return array_map(
            fn($s) => "{$key}.{$s}",
            array_merge([
                'person',
                'guardians',
                'batch',
                'group',
                'shift',
                'major',
                'status',
            ], $this->withPerson())
        );
    }

    protected function withPerson(string $key = 'person', string $otherKey = 'addresses')
    {
        return array_map(
            fn($p) => "{$key}.{$p}",
            array_merge(
                ['nationality', 'addresses'],
                array_map(fn($a) => "{$otherKey}.$a", ['province', 'district', 'commune', 'village'])
            )
        );
    }

    public function summarize(?string $from = null, ?string $to = null): array
    {
        $rooms = ExamState::query()
            ->when($from, fn($q) => $q->whereDate('exam_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('exam_date', '<=', $to))
            ->get(['student_total', 'absences']);

        $summary = array_fill(0, count($this->rounds), ['total' => 0, 'absent' => 0]);

        // absences is one entry per session/round, positionally: absences[0] = round 1, etc.
        foreach ($rooms as $room) {
            foreach ($this->rounds as $round => $label) {
                $summary[$round]['total']  += $room->student_total ?? 0;
                $summary[$round]['absent'] += $room->absences[$round]['total'] ?? 0;
            }
        }

        return collect($summary)->map(function ($data, $i) {
            $present = $data['total'] - $data['absent'];
            return [
                'label'   => $this->rounds[$i],
                'total'   => $data['total'],
                'present' => $present,
                'absent'  => $data['absent'],
                'percent' => $data['total'] > 0 ? round($present / $data['total'] * 100) : 0,
            ];
        })->all();
    }
}
