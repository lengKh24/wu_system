<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class RetakeExam extends IModel
{
    protected $searchable = [
        'full_name',
        'student_code',
        'phone_number',
        'subject',
        'lecturer_name',
        'major',
    ];

    protected $fillable = [
        'no',
        'full_name',
        'sex',
        'student_code',
        'phone_number',
        'batch',
        'subject',
        'lecturer_name',
        'major',
        'shift',
        'status',
        'status_note',
        'registered_at',
        'payment_status',
        'payment_number',
        'payment_note',
        'term',
        'exam_room',
        'exam_time',
        'exam_seat',
        'score',
        'attendance_status',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'status'        => 'boolean',
            'status_note'   => 'datetime',
            'registered_at' => 'datetime',
            'exam_time'     => 'datetime',
            'exam_seat'     => 'integer',
            'score'         => 'decimal:2',
        ];
    }

    public function scopeWithSubjectCount(Builder $query)
    {
        return $query->addSelect([
            'subject_count' => static::query()
                ->selectRaw('COUNT(*)')
                ->from('retake_exams as re2')
                ->whereColumn('re2.student_code', 'retake_exams.student_code'),
            // ->whereColumn('re2.term', 'retake_exams.term') // only if scoped per-term/round
        ]);
    }

    public function getRetakeNoteAttribute(): ?string
    {
        $count = (int) ($this->attributes['subject_count'] ?? $this->computeSubjectCountFallback());

        return match (true) {
            $count <= 3 => 'ប្រឡងសង',
            $count === 4 => 'ការិយាល័យ',
            $count >= 5 => 'រៀនសង', // confirm — your notes say វេនសង here
            default => null,
        };
    }

    protected function computeSubjectCountFallback(): int
    {
        return static::where('student_code', $this->student_code)->count();
    }
}
