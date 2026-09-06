<?php
namespace App\Http\Resources;

class RetakeExamResource extends IResource
{
    public function toList(): array
    {
        return to_list($this, [
            'no'                => $this->no,
            'full_name'         => $this->full_name,
            'sex'               => $this->sex,
            'student_code'      => $this->student_code,
            'phone_number'      => $this->phone_number,
            'batch'             => $this->batch,
            'subject'           => $this->subject,
            'lecturer_name'     => $this->lecturer_name,
            'major'             => $this->major,
            'shift'             => $this->shift,
            'status'            => $this->status,
            'status_note'       => $this->status_note?->format('Y-m-d H:i:s'),
            'registered_at'     => $this->registered_at?->format('Y-m-d H:i:s'),
            'payment_status'    => $this->payment_status,
            'payment_number'    => $this->payment_number,
            'payment_note'      => $this->payment_note,
            'term'              => $this->term,
            'exam_room'         => $this->exam_room,
            'exam_time'         => $this->exam_time?->format('Y-m-d H:i:s'),
            'exam_seat'         => $this->exam_seat,
            'score'             => $this->score,
            'attendance_status' => $this->attendance_status,
        ], false);
    }
}
