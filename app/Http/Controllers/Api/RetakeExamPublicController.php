<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RetakeRegistrationResource;
use App\Models\RetakeRegistration;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Public self-service retake-exam registration. No auth — mirrors the
 * exam-states precedent (see routes/api.php) of a fully public JSON group.
 *
 * There is no login/session here, so every action re-verifies ownership
 * with the student code alone (Leng's call, 2026-09-08 — previously code +
 * date of birth) rather than trusting a stored token — a student can look
 * up, adjust selections, and confirm across multiple visits without ever
 * authenticating. Single-factor by design now: anyone who knows or guesses
 * a student code can view/change that student's registration, since codes
 * are not random. Rate-limiting/logging on these endpoints is still an
 * open TODO from the original design notes.
 */
class RetakeExamPublicController extends Controller
{
    /**
     * Step 1: find the student and list every pending registration
     * (registered_at still null) across whatever batch(es) they're in,
     * split into will-register / will-not-register per is_selected
     * (decision #15).
     */
    public function lookup(Request $request)
    {
        $validated = $this->validateIdentity($request);
        $student   = $this->resolveStudent($validated);

        if (! $student) {
            return no_data('No matching student found. Please check the student code.', 404);
        }

        return has_data($this->buildPayload($student));
    }

    /**
     * Step 2 (optional, repeatable): the student ticks/unticks which
     * subjects they actually want to register for. Only rows still open
     * (registered_at null) and belonging to this student can be touched —
     * once confirm() locks a row in, this silently ignores it rather than
     * erroring, since the front end only ever renders open rows anyway.
     */
    public function select(Request $request)
    {
        $validated = $this->validateIdentity($request, [
            'selections'              => 'required|array|min:1',
            'selections.*.id'         => 'required|integer',
            'selections.*.is_selected' => 'required|boolean',
        ]);
        $student = $this->resolveStudent($validated);

        if (! $student) {
            return no_data('No matching student found. Please check the student code.', 404);
        }

        foreach ($validated['selections'] as $selection) {
            RetakeRegistration::query()
                ->where('id', $selection['id'])
                ->where('student_id', $student->id)
                ->whereNull('registered_at')
                ->update(['is_selected' => $selection['is_selected']]);
        }

        return has_data($this->buildPayload($student), 'Selections saved.');
    }

    /**
     * Step 3: locks in whatever is_selected currently is for every still-
     * open row belonging to this student, across every batch they're part
     * of at once — stamping registered_at finalizes the selection and is
     * what RetakeRegistrationController/PermissionSeeder-gated staff act on
     * next (payment, scoring, etc).
     */
    public function confirm(Request $request)
    {
        $validated = $this->validateIdentity($request);
        $student   = $this->resolveStudent($validated);

        if (! $student) {
            return no_data('No matching student found. Please check the student code.', 404);
        }

        $pending = RetakeRegistration::query()
            ->where('student_id', $student->id)
            ->whereNull('registered_at')
            ->count();

        if ($pending === 0) {
            return no_data('Nothing left to confirm — there is no pending registration for this student.', 404);
        }

        RetakeRegistration::query()
            ->where('student_id', $student->id)
            ->whereNull('registered_at')
            ->update(['registered_at' => now()]);

        return has_data($this->buildPayload($student), 'Registration confirmed.');
    }

    protected function validateIdentity(Request $request, array $extra = []): array
    {
        return Validator::make($request->all(), array_merge([
            'code' => 'required|string|max:50',
        ], $extra))->validate();
    }

    protected function resolveStudent(array $validated): ?Student
    {
        return Student::query()
            ->with('person')
            ->where('code', $validated['code'])
            ->first();
    }

    protected function buildPayload(Student $student): array
    {
        $registrations = RetakeRegistration::query()
            ->with(['term', 'examType', 'lecturer', 'batch'])
            ->where('student_id', $student->id)
            ->whereNull('registered_at')
            ->get()
            ->groupBy('batch_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'batch_id'          => $first->batch_id,
                    // RetakeTerm has no name_kh/name_en (see RetakeTermResource,
                    // built with is_common=false) — it uses 'title' instead.
                    'term'              => $first->term?->title,
                    'exam_type'         => $first->examType?->name,
                    'will_register'     => RetakeRegistrationResource::collection($rows->where('is_selected', true)->values()),
                    'will_not_register' => RetakeRegistrationResource::collection($rows->where('is_selected', false)->values()),
                ];
            })
            ->values();

        $confirmed = RetakeRegistration::query()
            ->with(['term', 'examType', 'lecturer'])
            ->where('student_id', $student->id)
            ->whereNotNull('registered_at')
            ->latest('registered_at')
            ->get();

        return [
            // Raw name parts, not a single concatenated string — this app
            // never composes one server-side (see PersonResource), so the
            // page joins first/last (and the Khmer pair) itself.
            'student' => [
                'id'            => $student->id,
                'code'          => $student->code,
                'first_name'    => $student->person?->first_name,
                'last_name'     => $student->person?->last_name,
                'first_name_kh' => $student->person?->first_name_kh,
                'last_name_kh'  => $student->person?->last_name_kh,
            ],
            'pending_batches'         => $registrations,
            'confirmed_registrations' => RetakeRegistrationResource::collection($confirmed),
        ];
    }
}
