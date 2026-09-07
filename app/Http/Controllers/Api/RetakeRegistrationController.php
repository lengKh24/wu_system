<?php
namespace App\Http\Controllers\Api;

use App\Exports\RetakeRegistrationExport;
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
            // SA only ever acts on confirmed registrations (payment can't
            // happen against a selection that might still change) — this
            // lets their page ask for just those instead of the full list.
            if ($request->boolean('confirmed_only')) {
                $query->whereNotNull('registered_at');
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
     * REG's "prepare schedule" export — an Excel download of whatever the
     * Main List is currently filtered to (same filter params as index()).
     * Named exportList, not export, since export(object, string) is a
     * reserved method name on the base Controller (see importFile() on
     * RetakeBatchController for the same reasoning — reusing a base method
     * name with an incompatible signature is a fatal error, not a warning).
     */
    public function exportList(Request $request)
    {
        return $this->export(
            new RetakeRegistrationExport($request->only([
                'batch_id', 'exam_type_id', 'retake_term_id', 'payment_status', 'outcome',
            ])),
            'retake-registrations'
        );
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
     * (decision #14). Requires the student to have already confirmed this
     * registration themselves (registered_at set) — payment can't happen
     * against a selection that might still change on the public page.
     */
    public function markPaid(Request $request, RetakeRegistration $retakeRegistration)
    {
        $validated = $request->validate([
            'payment_batch_id' => 'required|integer|exists:payment_batches,id',
        ]);

        if (! $retakeRegistration->registered_at) {
            return no_data('This student has not confirmed their registration yet — cannot mark it paid.', 422);
        }

        $retakeRegistration->update([
            'payment_status'   => RetakeRegistration::PAYMENT_PAID,
            'payment_batch_id' => $validated['payment_batch_id'],
        ]);

        return new RetakeRegistrationResource($this->reload($retakeRegistration));
    }

    /**
     * SA: same as markPaid, for the partial-payment case — a student who
     * paid for 2 of their 4 failed subjects against one invoice. Same
     * registered_at requirement as markPaid — unconfirmed ids are skipped
     * rather than failing the whole batch, and reported back so SA can see
     * which ones still need the student to confirm first.
     */
    public function bulkMarkPaid(Request $request)
    {
        $validated = $request->validate([
            'payment_batch_id' => 'required|integer|exists:payment_batches,id',
            'ids'              => 'required|array|min:1',
            'ids.*'            => 'integer|exists:retake_registrations,id',
        ]);

        $unconfirmed = RetakeRegistration::whereIn('id', $validated['ids'])
            ->whereNull('registered_at')
            ->pluck('id');

        $count = RetakeRegistration::whereIn('id', $validated['ids'])
            ->whereNotNull('registered_at')
            ->update([
                'payment_status'   => RetakeRegistration::PAYMENT_PAID,
                'payment_batch_id' => $validated['payment_batch_id'],
            ]);

        $message = "{$count} registration(s) marked paid.";
        if ($unconfirmed->isNotEmpty()) {
            $message .= " {$unconfirmed->count()} skipped — not yet confirmed by the student.";
        }

        return has_data(['skipped_ids' => $unconfirmed->values()], $message);
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
     *
     * Also auto-sets outcome (Leng's call, 2026-09-08): score >=
     * PASSING_SCORE -> passed, otherwise failed. Skipped if the outcome is
     * already 'absent' — a score showing up for a student flagged absent
     * is itself the edge case, not something to silently overwrite; REG
     * resolves that by hand via setOutcome() below, same as any other
     * case this rule doesn't cover.
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

        if ($retakeRegistration->outcome !== RetakeRegistration::OUTCOME_ABSENT) {
            $retakeRegistration->update([
                'outcome' => $validated['score'] >= RetakeRegistration::PASSING_SCORE
                    ? RetakeRegistration::OUTCOME_PASSED
                    : RetakeRegistration::OUTCOME_FAILED,
            ]);
        }

        return new RetakeRegistrationResource($this->reload($retakeRegistration));
    }

    /**
     * REG: manual override of outcome — required before
     * RetakeBatch::carryForwardTo() can find failed/absent rows to move on
     * to the next stage. Normally outcome is set automatically by
     * setScore() above; this exists for whatever that rule doesn't cover
     * (absent students, corrections, policy exceptions).
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
