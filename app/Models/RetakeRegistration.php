<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class RetakeRegistration extends IModel
{
    public const PAYMENT_UNPAID   = 'unpaid';
    public const PAYMENT_PAID     = 'paid';
    public const PAYMENT_STATUSES = [self::PAYMENT_UNPAID, self::PAYMENT_PAID];

    public const OUTCOME_PENDING = 'pending';
    public const OUTCOME_PASSED  = 'passed';
    public const OUTCOME_FAILED  = 'failed';
    public const OUTCOME_ABSENT  = 'absent';
    public const OUTCOMES        = [self::OUTCOME_PENDING, self::OUTCOME_PASSED, self::OUTCOME_FAILED, self::OUTCOME_ABSENT];

    // Leng's call, 2026-09-08: score >= this passes automatically. REG can
    // still override via setOutcome() for cases this doesn't cover (e.g.
    // a score entered in error, a policy exception) — see
    // RetakeRegistrationController::setScore().
    public const PASSING_SCORE = 50;

    protected $fillable = [
        'batch_id', 'student_id', 'retake_term_id', 'exam_type_id', 'subject_id', 'lecturer_id',
        'previous_registration_id', 'previous_deletion_log_id',
        'is_selected', 'registered_at',
        'payment_status', 'payment_batch_id',
        'outcome', 'telegram_invited_at', 'exam_session_id',
        'remark',
    ];

    // Own columns are almost all FK ids, not worth matching on — search by
    // student code and subject name instead (see Builder::macro('whereLike')).
    protected array $searchable = [
        'student.code', 'subject.name_en', 'subject.name_kh', 'remark',
    ];

    protected $casts = [
        'is_selected'         => 'boolean',
        'registered_at'       => 'datetime',
        'telegram_invited_at' => 'datetime',
    ];

    protected $appends = ['status_note'];

    public function batch()
    {
        return $this->belongsTo(RetakeBatch::class, 'batch_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function term()
    {
        return $this->belongsTo(RetakeTerm::class, 'retake_term_id');
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function previousRegistration()
    {
        return $this->belongsTo(self::class, 'previous_registration_id');
    }

    public function nextRegistration()
    {
        return $this->hasOne(self::class, 'previous_registration_id');
    }

    public function previousDeletionLog()
    {
        return $this->belongsTo(DeletionLog::class, 'previous_deletion_log_id');
    }

    public function paymentBatch()
    {
        return $this->belongsTo(PaymentBatch::class);
    }

    public function score()
    {
        return $this->hasOne(Score::class);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', self::PAYMENT_UNPAID);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    /**
     * How many subjects this student failed within THIS batch (this stage,
     * this exam type) — recomputed fresh every stage, never a lifetime
     * count (decision #7). Preload this in list queries to avoid an N+1;
     * getStatusNoteAttribute() falls back to its own query when it's not
     * preloaded (e.g. when a single model is fetched via show()).
     */
    public function scopeWithSubjectCount(Builder $query): Builder
    {
        return $query->addSelect([
            'subject_count' => self::query()
                ->selectRaw('COUNT(*)')
                ->from('retake_registrations as rr2')
                ->whereColumn('rr2.batch_id', 'retake_registrations.batch_id')
                ->whereColumn('rr2.student_id', 'retake_registrations.student_id'),
        ]);
    }

    /**
     * Khmer routing label. Thresholds are intentional, not a typo:
     * <=3 -> ប្រឡងសង (retake exam), ==5 -> រៀនសង (retake course),
     * anything else (4, 6, 7...) -> ការិយាល័យសិក្សា (registrar office) —
     * a deliberate safety valve: a count outside these two clean buckets
     * usually means a URM export error and needs a human look rather than
     * an automatic label. Confirmed with Leng 2026-09-07.
     */
    public function getStatusNoteAttribute(): string
    {
        $count = $this->attributes['subject_count'] ?? null;

        if ($count === null) {
            $count = self::query()
                ->where('batch_id', $this->batch_id)
                ->where('student_id', $this->student_id)
                ->count();
        }

        $count = (int) $count;

        return match (true) {
            $count <= 3  => 'ប្រឡងសង',
            $count === 5 => 'រៀនសង',
            default      => 'ការិយាល័យសិក្សា',
        };
    }
}
