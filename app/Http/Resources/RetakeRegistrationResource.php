<?php
namespace App\Http\Resources;

class RetakeRegistrationResource extends IResource
{
    public function toList(): array
    {
        return to_list($this, [
            'batch_id'  => $this->batch_id,
            'student'   => new StudentResource($this->whenLoaded('student')),
            'term'      => new RetakeTermResource($this->whenLoaded('term')),
            'exam_type' => new ExamTypeResource($this->whenLoaded('examType')),
            'subject'   => $this->subject,
            'lecturer'  => new LecturerResource($this->whenLoaded('lecturer')),

            // Per-batch computed count label — see
            // RetakeRegistration::getStatusNoteAttribute().
            'status_note' => $this->status_note,

            'is_selected'   => (bool) $this->is_selected,
            'registered_at' => $this->registered_at?->format('Y-m-d H:i:s'),

            'payment_status'   => $this->payment_status,
            'payment_batch_id' => $this->payment_batch_id,

            'outcome'             => $this->outcome,
            'telegram_invited_at' => $this->telegram_invited_at?->format('Y-m-d H:i:s'),

            'score'            => $this->whenLoaded('score', fn() => $this->score?->score),
            'score_entered_at' => $this->whenLoaded('score', fn() => $this->score?->entered_at?->format('Y-m-d H:i:s')),

            'previous_registration_id' => $this->previous_registration_id,
            'previous_deletion_log_id' => $this->previous_deletion_log_id,
        ], false);
    }
}
