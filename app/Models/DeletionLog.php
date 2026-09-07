<?php
namespace App\Models;

class DeletionLog extends IModel
{
    // Table is 'deletion_log' (singular, see its migration) — Eloquent's
    // default convention would otherwise guess 'deletion_logs' and every
    // query here would 404 against a table that doesn't exist.
    protected $table = 'deletion_log';

    public const REASON_UNPAID_EXPIRED = 'unpaid_expired';
    public const REASON_MANUAL         = 'manual';
    public const REASONS               = [self::REASON_UNPAID_EXPIRED, self::REASON_MANUAL];

    protected $fillable = [
        'original_registration_id', 'previous_registration_id',
        'student_id', 'subject_id', 'retake_term_id', 'exam_type_id',
        'reason', 'deleted_at_source', 'remark',
    ];

    protected $casts = [
        'deleted_at_source' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function term()
    {
        return $this->belongsTo(RetakeTerm::class, 'retake_term_id');
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    /**
     * Logs a registration before hard-deleting it, so the next stage's
     * carry-forward query (RetakeBatch::carryForwardTo()) can still find it
     * after the row itself is gone. This table is required carry-forward
     * input, not just an audit nicety — see decision #3/#12 in the design
     * doc. forceDelete(), not delete(): decision #3 is an explicit hard
     * delete, and IModel's SoftDeletes would otherwise just set deleted_at.
     */
    public static function logAndPurge(RetakeRegistration $registration, string $reason = self::REASON_UNPAID_EXPIRED): self
    {
        return execute(function () use ($registration, $reason) {
            $log = self::create([
                'original_registration_id' => $registration->id,
                'previous_registration_id' => $registration->previous_registration_id,
                'student_id'               => $registration->student_id,
                'subject_id'               => $registration->subject_id,
                'retake_term_id'           => $registration->retake_term_id,
                'exam_type_id'             => $registration->exam_type_id,
                'reason'                   => $reason,
                'deleted_at_source'        => now(),
            ]);

            $registration->forceDelete();

            return $log;
        });
    }
}
