<?php
namespace App\Http\Requests;

class LecturerRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            DEFAULT_VALIDATE,
            check_unique('lecturers', 'shortcut', true)
        );
    }
}
