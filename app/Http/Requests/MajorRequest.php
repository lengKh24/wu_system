<?php
namespace App\Http\Requests;

class MajorRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            DEFAULT_VALIDATE,
            check_exist('faculty_id', 'faculties'),
            check_unique('majors', 'shortcut', true)
        );
    }
}
