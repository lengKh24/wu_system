<?php
namespace App\Http\Requests;

class SubjectRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            DEFAULT_VALIDATE,
            check_exist('faculty_id', 'faculties'),
            check_unique('subjects', 'code', true),
            [
                'level'         => 'required|in:associate,bachelor,master,phd,Doctor/PhD',
                'lecturer_hour' => 'nullable|integer|min:0',
                'credit'        => 'nullable|integer',
            ]
        );
    }
}
