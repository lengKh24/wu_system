<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RetakeBatchRequest;
use App\Http\Resources\RetakeBatchResource;
use App\Imports\RetakeRegistrationImport;
use App\Models\ExamType;
use App\Models\RetakeBatch;
use App\Models\RetakeTerm;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RetakeBatchController extends Controller
{
    public function __construct()
    {
        $this->name          = 'Retake Batch';
        $this->model         = RetakeBatch::class;
        $this->resource      = RetakeBatchResource::class;
        $this->relationships = ['term', 'examType'];
    }

    public function index(Request $request)
    {
        return $this->list($request, function ($query) use ($request) {
            if ($examTypeId = $request->input('exam_type_id')) {
                $query->where('exam_type_id', $examTypeId);
            }
            if ($termId = $request->input('retake_term_id')) {
                $query->where('retake_term_id', $termId);
            }
            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            return $query->withCount('registrations');
        });
    }

    public function store(RetakeBatchRequest $request)
    {
        return $this->save($request);
    }

    public function show(RetakeBatch $retakeBatch)
    {
        return new RetakeBatchResource($this->reload($retakeBatch)->loadCount('registrations'));
    }

    public function update(RetakeBatchRequest $request, RetakeBatch $retakeBatch)
    {
        return $this->release($request, $retakeBatch);
    }

    public function destroy(RetakeBatch $retakeBatch)
    {
        return $this->disable($retakeBatch);
    }

    public function restore(RetakeBatch $retakeBatch)
    {
        return $this->enable($retakeBatch);
    }

    public function force_destroy(RetakeBatch $retakeBatch)
    {
        return $this->clear($retakeBatch);
    }

    /**
     * REG closes this batch once scores/outcomes are final — required
     * before the next stage can be generated (see carryForward() below).
     */
    public function close(RetakeBatch $retakeBatch)
    {
        $retakeBatch->close();

        return new RetakeBatchResource($this->reload($retakeBatch));
    }

    /**
     * Generates the next stage (2nd Supp from 1st Supp, or Restudy from
     * 2nd Supp) from this batch's failed/absent/purged students. See
     * RetakeBatch::carryForwardTo() — throws if the batch isn't closed yet.
     */
    public function carryForward(Request $request, RetakeBatch $retakeBatch)
    {
        $validated = $request->validate([
            'exam_type_id'   => 'required|integer|exists:exam_types,id',
            'retake_term_id' => 'nullable|integer|exists:retake_terms,id',
        ]);

        $nextType = ExamType::findOrFail($validated['exam_type_id']);
        $term     = isset($validated['retake_term_id']) ? RetakeTerm::find($validated['retake_term_id']) : null;

        $next = $retakeBatch->carryForwardTo($nextType, $term);

        return new RetakeBatchResource($this->reload($next));
    }

    /**
     * REG records the Telegram group link/QR for this batch, so SA can
     * hand it to students once they've paid.
     */
    public function telegram(Request $request, RetakeBatch $retakeBatch)
    {
        $validated = $request->validate([
            'telegram_group_link' => 'nullable|string|max:255',
            'telegram_qr_path'    => 'nullable|string|max:255',
        ]);

        $retakeBatch->update($validated);

        return new RetakeBatchResource($this->reload($retakeBatch));
    }

    /**
     * REG imports URM's 1st Supplementary export. This is the only exam
     * type ever sourced from a file (decision #6) — 2nd Supp/Restudy are
     * always generated via carryForwardTo() instead. Creates a new
     * import-sourced batch, then runs RetakeRegistrationImport against it;
     * unmatched students/subjects are skipped (not fatal to the rest of
     * the file) and unmatched lecturers are flagged but still create the
     * registration — see that class's docblock for the full reasoning.
     */
    public function importFile(Request $request)
    {
        $validated = $request->validate([
            'file'           => 'required|file|mimes:xlsx,xls,csv',
            'retake_term_id' => 'required|integer|exists:retake_terms,id',
        ]);

        $examType = ExamType::where('code', ExamType::FIRST_SUPPLEMENTARY)->firstOrFail();

        $batch = RetakeBatch::create([
            'retake_term_id' => $validated['retake_term_id'],
            'exam_type_id'   => $examType->id,
            'source_type'    => RetakeBatch::SOURCE_IMPORT,
            'file_name'      => $validated['file']->getClientOriginalName(),
            'status'         => RetakeBatch::STATUS_OPEN,
            'generated_by'   => auth()->id(),
            'generated_at'   => now(),
        ]);

        $import = new RetakeRegistrationImport($batch);
        Excel::import($import, $validated['file']);

        return has_data([
            'batch'  => new RetakeBatchResource($this->reload($batch)->loadCount('registrations')),
            'report' => $import->report(),
        ], 'Import complete.');
    }
}
