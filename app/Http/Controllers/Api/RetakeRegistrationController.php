<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RetakeRegistrationRequest;
use App\Http\Resources\RetakeRegistrationResource;
use App\Models\RetakeBatch;
use App\Models\RetakeRegistration;
use Illuminate\Http\Request;

class RetakeRegistrationController extends Controller
{
    public function __construct()
    {
        $this->name          = 'Retake Registration';
        $this->model         = RetakeRegistration::class;
        $this->resource      = RetakeRegistrationResource::class;
        $this->relationships = ['student', 'term', 'examType', 'subject', 'lecturer', 'score', 'paymentBatch'];
    }

    /**
     * REG's main list — every filter is optional so the same endpoint
     * serves the unfiltered Main List and any narrowed view.
     */
    public function index(Request $request)
    {
        return $this->list($request, function ($query) use ($request) {
            $query->withSubjectCount();

            if ($batchId = $request->input('batch_id')) {
                $query->where('batch_id', $batchId);
            }
            if ($examTypeId = $request->input('exam_type_id')) {
                $query->where('exam_type_id', $examTypeId);
            }
            if ($termId = $request->input('retake_term_id')) {
                $query->where('retake_term_id', $termId);
            }
            if ($paymentStatus = $request->input('payment_status')) {
                $query->where('payment_status', $paymentStatus);
            }
            if ($outcome = $request->input('outcome')) {
                $query->where('outcome', $outcome);
            }

            return $query;
        });
    }

    /**
     * Customer Service's read-only view — students who have actually
     * registered (registered_at set). Gated by its own 'retake-cs.view'
     * permission, separate from REG's 'retake-registration.view'.
     */
    public function customerService(Request $request)
    {
        return $this->list($request, fn($query) => $query->withSubjectCount()->whereNotNull('registered_at'));
    }

    /**
     * REG manually adds one registration to an existing batch (e.g. a
     * Special-type row, or a student missed during import).
     */
    public function store(RetakeRegistrationRequest $request)
    {
        $data  = $request->validated();
        $batch = RetakeBatch::findOrFail($data['batch_id']);

        return $this->save($request, [
            'retake_term_id' => $batch->retake_term_id,
            'exam_type_id'   => $batch->exam_type_id,
        ]);
    }

    public function show(RetakeRegistration $retakeRegistration)
    {
        return $this->view($retakeRegistration);
    }

    public function destroy(RetakeRegistration $retakeRegistration)
    {
        return $this->disable($retakeRegistration);
    }

    public function restore(RetakeRegistration $retakeRegistration)
    {
        return $this->enable($retakeRegistration);
    }

    public function force_destroy(RetakeRegistration $retakeRegistration)
    {
        return $this->clear($retakeRegistration);
    }

    /**
     * SA: attaches a payment_batch and marks paid. Only ever touches the
     * one registration passed in — an older stage's row is never mutated
     * (decision #14).
     */
    public function markPaid(Request $request, RetakeRegistration $retakeRegistration)
    {
        $validated = $request->validate([
            'payment_batch_id' => 'required|integer|exists:payment_batches,id',
        ]);

        $retakeRegistration->update([
            'payment_status'   => RetakeRegistration::PAYMENT_PAID,
            'payment_batch_id' => $validated['payment_batch_id'],
        ]);

        return new RetakeRegistrationResource($this->reload($retakeRegistration));
    }

    /**
     * SA: same as markPaid, for the partial-payment case — a student who
     * paid for 2 of their 4 failed subjects against one invoice.
     */
    public function bulkMarkPaid(Request $request)
    {
        $validated = $request->validate([
            'payment_batch_id' => 'required|integer|exists:payment_batches,id',
            'ids'              => 'required|array|min:1',
            'ids.*'            => 'integer|exists:retake_registrations,id',
        ]);

        $count = RetakeRegistration::whereIn('id', $validated['ids'])->update([
            'payment_status'   => RetakeRegistration::PAYMENT_PAID,
            'payment_batch_id' => $validated['payment_batch_id'],
        ]);

        return has_data(null, "{$count} registration(s) marked paid.");
    }

    /**
     * SA: stamps telegram_invited_at once this registration is paid and
     * the student's been handed the batch's Telegram QR.
     */
    public function inviteTelegram(RetakeRegistration $retakeRegistration)
    {
        $retakeRegistration->update(['telegram_invited_at' => now()]);

        return new RetakeRegistrationResource($this->reload($retakeRegistration));
    }

    /**
     * Score: enters (or corrects) this registration's score. One score per
     * registration — updateOrCreate keeps it that way.
     */
    public function setScore(Request $request, RetakeRegistration $retakeRegistration)
    {
        $validated = $request->validate([
            'score'  => 'required|numeric|min:0|max:100',
            'remark' => 'nullable|string|max:500',
        ]);

        $retakeRegistration->score()->updateOrCreate([], [
            'score'      => $validated['score'],
            'remark'     => $validated['remark'] ?? null,
            'entered_by' => auth()->id(),
            'entered_at' => now(),
        ]);

        return new RetakeRegistrationResource($this->reload($retakeRegistration));
    }

    /**
     * REG/Score: marks the outcome once the exam's over — required before
     * RetakeBatch::carryForwardTo() can find failed/absent rows to move on
     * to the next stage.
     */
    public function setOutcome(Request $request, RetakeRegistration $retakeRegistration)
    {
        $validated = $request->validate([
            'outcome' => 'required|in:pending,passed,failed,absent',
        ]);

        $retakeRegistration->update($validated);

        return new RetakeRegistrationResource($this->reload($retakeRegistration));
    }

    /**
     * Manual SA/REG override of a selection after registered_at has
     * already locked it in (decision #15) — self-service can't do this,
     * only staff, since it has payment implications.
     */
    public function updateSelection(Request $request, RetakeRegistration $retakeRegistration)
    {
        $validated = $request->validate([
            'is_selected' => 'required|boolean',
        ]);

        $retakeRegistration->update($validated);

        return new RetakeRegistrationResource($this->reload($retakeRegistration));
    }
}
