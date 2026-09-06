<?php
namespace App\Exports;

use App\Models\RetakeExam;

class RetakeExamExport extends IExport
{
    protected string $model = RetakeExam::class;

    protected array $headings = [
        'No', 'Full Name', 'Sex', 'Student Code', 'Phone Number', 'Batch',
        'Subject', 'Lecturer Name', 'Major', 'Shift', 'Status', 'Status Note',
        'Registered At', 'Payment Status', 'Payment Number', 'Payment Note',
        'Term', 'Exam Room', 'Exam Time', 'Exam Seat', 'Score', 'Attendance Status', 'Remark',
    ];

    public function map(mixed $data): array
    {
        return [
            $data->no,
            $data->full_name,
            $data->sex,
            $data->student_code,
            $data->phone_number,
            $data->batch,
            $data->subject,
            $data->lecturer_name,
            $data->major,
            $data->shift,
            $data->status ? 'Yes' : 'No',
            $data->status_note?->format('Y-m-d H:i:s'),
            $data->registered_at?->format('Y-m-d H:i:s'),
            $data->payment_status,
            $data->payment_number,
            $data->payment_note,
            $data->term,
            $data->exam_room,
            $data->exam_time?->format('Y-m-d H:i:s'),
            $data->exam_seat,
            $data->score,
            $data->attendance_status,
            $data->remark,
        ];
    }
}
