<?php
namespace App\Models;

class RetakeBatch extends IModel
{
    public const SOURCE_IMPORT          = 'import';
    public const SOURCE_CARRIED_FORWARD = 'carried_forward';
    public const SOURCE_MANUAL          = 'manual';
    public const SOURCE_TYPES           = [self::SOURCE_IMPORT, self::SOURCE_CARRIED_FORWARD, self::SOURCE_MANUAL];

    public const STATUS_OPEN   = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUSES      = [self::STATUS_OPEN, self::STATUS_CLOSED];

    protected $fillable = [
        'retake_term_id', 'exam_type_id', 'source_type', 'source_batch_id',
        'file_name', 'status', 'generated_by', 'generated_at',
        'telegram_group_link', 'telegram_qr_path', 'remark',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function term()
    {
        return $this->belongsTo(RetakeTerm::class, 'retake_term_id');
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function sourceBatch()
    {
        return $this->belongsTo(self::class, 'source_batch_id');
    }

    public function generatedBatches()
    {
        return $this->hasMany(self::class, 'source_batch_id');
    }

    public function registrations()
    {
        return $this->hasMany(RetakeRegistration::class, 'batch_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function close(): void
    {
        $this->update(['status' => self::STATUS_CLOSED]);
    }

    /**
     * Generates the next stage's batch + registrations from this batch's
     * leftovers: students who failed or never sat the exam (outcome), plus
     * anyone whose row was already hard-deleted by the unpaid cleanup job
     * (looked up in deletion_log by student+subject+exam_type+term, since
     * that table intentionally has no batch_id — see its migration).
     *
     * Manual trigger by design (open question in the design doc, leaning
     * this way): nothing moves to the next stage until REG calls this
     * explicitly, after confirming this batch's scores/outcomes are final.
     * Requires the batch to be closed first.
     */
    public function carryForwardTo(ExamType $nextType, ?RetakeTerm $term = null): self
    {
        if ($this->status !== self::STATUS_CLOSED) {
            throw new \RuntimeException('Close this batch before generating the next stage.');
        }

        return execute(function () use ($nextType, $term) {
            $next = self::create([
                'retake_term_id'  => $term?->id ?? $this->retake_term_id,
                'exam_type_id'    => $nextType->id,
                'source_type'     => self::SOURCE_CARRIED_FORWARD,
                'source_batch_id' => $this->id,
                'status'          => self::STATUS_OPEN,
                'generated_by'    => auth()->id(),
                'generated_at'    => now(),
            ]);

            // Still-open rows from this batch that failed or never sat the exam.
            $stillOpen = $this->registrations()
                ->whereIn('outcome', [RetakeRegistration::OUTCOME_FAILED, RetakeRegistration::OUTCOME_ABSENT])
                ->get();

            foreach ($stillOpen as $registration) {
                $next->registrations()->create([
                    'student_id'               => $registration->student_id,
                    'retake_term_id'           => $next->retake_term_id,
                    'exam_type_id'             => $nextType->id,
                    'subject'                  => $registration->subject,
                    'lecturer_id'              => $registration->lecturer_id,
                    'previous_registration_id' => $registration->id,
                    // Opt-in, not opt-out (Leng's call, 2026-09-07): a
                    // failed/absent student is only *eligible* for the next
                    // stage here — they still have to actively pick this
                    // subject again via the public self-service page.
                    'is_selected'              => false,
                ]);
            }

            // Rows purged by the 60-day unpaid cleanup — same treatment as
            // failed/absent, since an unpaid student never sat the exam
            // either.
            $deletedRows = DeletionLog::query()
                ->where('exam_type_id', $this->exam_type_id)
                ->where('retake_term_id', $this->retake_term_id)
                ->get();

            foreach ($deletedRows as $log) {
                $next->registrations()->create([
                    'student_id'               => $log->student_id,
                    'retake_term_id'           => $next->retake_term_id,
                    'exam_type_id'             => $nextType->id,
                    'subject'                  => $log->subject,
                    'previous_deletion_log_id' => $log->id,
                    // Opt-in — see the matching note in the loop above.
                    'is_selected'              => false,
                ]);
            }

            // Persistent flag, not derived per-view — Leng's call, 2026-09-07:
            // wanted a single field to check "is this student in Restudy"
            // outside this module too, not just an inference from having a
            // restudy-typed registration. Set here, once, the moment a
            // student is actually carried into Restudy — a mass query-builder
            // update, deliberately bypassing $fillable (this is never meant
            // to be settable through a normal student create/update request).
            if ($nextType->code === ExamType::RESTUDY) {
                $studentIds = $stillOpen->pluck('student_id')
                    ->merge($deletedRows->pluck('student_id'))
                    ->unique();

                Student::whereIn('id', $studentIds)->update(['is_restudy' => true]);
            }

            return $next;
        });
    }
}
