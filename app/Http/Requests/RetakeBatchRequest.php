<?php
namespace App\Http\Requests;

class RetakeBatchRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            check_exist('exam_type_id', 'exam_types'),
            check_exist('retake_term_id', 'retake_terms', required: false),
            [
                'source_type'         => 'nullable|in:import,carried_forward,manual',
                'file_name'           => 'nullable|string|max:255',
                'status'              => 'nullable|in:open,closed',
                'telegram_group_link' => 'nullable|string|max:255',
                'telegram_qr_path'    => 'nullable|string|max:255',
                'remark'              => 'nullable|string|max:500',
            ]
        );
    }
}
