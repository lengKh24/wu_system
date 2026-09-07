<?php
namespace App\Http\Requests;

class ExamTypeRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            DEFAULT_VALIDATE,
            check_unique('exam_types', 'code', true),
            [
                'requires_import' => 'nullable|boolean',
                'uses_term'       => 'nullable|boolean',
            ]
        );
    }
}
