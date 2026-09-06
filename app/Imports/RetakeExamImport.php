<?php
namespace App\Imports;

use App\Models\RetakeExam;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class RetakeExamImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        return new RetakeExam([
            'no'                => $row['no'] ?? null,
            'full_name'         => $row['full_name'] ?? null,
            'sex'               => $row['sex'] ?? null,
            'student_code'      => $row['student_code'] ?? null,
            'phone_number'      => $row['phone_number'] ?? null,
            'batch'             => $row['batch'] ?? null,
            'subject'           => $row['subject'] ?? null,
            'lecturer_name'     => $row['lecturer_name'] ?? null,
            'major'             => $row['major'] ?? null,
            'shift'             => $row['shift'] ?? null,
            'status'            => $row['status'] ?? false,
            'status_note'       => $row['status_note'] ?? null,
            'registered_at'     => $row['registered_at'] ?? null,
            'payment_status'    => $row['payment_status'] ?? null,
            'payment_number'    => $row['payment_number'] ?? null,
            'payment_note'      => $row['payment_note'] ?? null,
            'term'              => $row['term'] ?? null,
            'exam_room'         => $row['exam_room'] ?? null,
            'exam_time'         => $row['exam_time'] ?? null,
            'exam_seat'         => $row['exam_seat'] ?? null,
            'score'             => $row['score'] ?? null,
            'attendance_status' => $row['attendance_status'] ?? null,
            'remark'            => $row['remark'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name'     => 'required|string|max:150',
            'sex'           => 'required|in:female,male,other',
            'student_code'  => 'required|string|max:50',
            'batch'         => 'required|string|max:100',
            'subject'       => 'required|string|max:150',
            'lecturer_name' => 'required|string|max:150',
            'major'         => 'required|string|max:150',
            'shift'         => 'required|string|max:50',
        ];
    }
}
