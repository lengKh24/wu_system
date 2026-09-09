<?php
namespace App\Http\Requests;

/**
 * Covers REG manually adding one registration to an existing batch (e.g.
 * a Special-type row, or a student missed during import). retake_term_id
 * and exam_type_id are NOT accepted here — the controller derives both
 * from the batch, so a registration can never disagree with its own batch.
 */
class RetakeRegistrationRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            check_exist('batch_id', 'retake_batches'),
            check_exist('student_id', 'students'),
            check_exist('lecturer_id', 'lecturers', required: false),
            [
                'subject'     => 'required|string|max:191',
                'is_selected' => 'nullable|boolean',
                'remark'      => 'nullable|string|max:500',
            ]
        );
    }
}
