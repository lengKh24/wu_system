<?php
namespace App\Http\Requests;

class RetakeExamRequest extends IRequest
{
    protected function formData(): array
    {
        return [
            'no'                => 'nullable|string|max:50',
            'full_name'         => 'required|string|max:150',
            'sex'               => 'required|in:female,male,other',
            'student_code'      => 'required|string|max:50',
            'phone_number'      => 'nullable|string|max:20',
            'batch'             => 'required|string|max:100',
            'subject'           => 'required|string|max:150',
            'lecturer_name'     => 'required|string|max:150',
            'major'             => 'required|string|max:150',
            'shift'             => 'required|string|max:50',
            'status'            => 'nullable|boolean',
            'status_note'       => 'nullable|date',
            'registered_at'     => 'nullable|date',
            'payment_status'    => 'nullable|string|max:50',
            'payment_number'    => 'nullable|string|max:100',
            'payment_note'      => 'nullable|string|max:255',
            'term'              => 'nullable|string|max:50',
            'exam_room'         => 'nullable|string|max:50',
            'exam_time'         => 'nullable|date',
            'exam_seat'         => 'nullable|integer|min:0|max:255',
            'score'             => 'nullable|numeric|min:0',
            'attendance_status' => 'nullable|string|max:50',
            'remark'            => 'nullable|string|max:500',
        ];
    }
}
