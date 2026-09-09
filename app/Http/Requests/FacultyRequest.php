<?php
namespace App\Http\Requests;

class FacultyRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            DEFAULT_VALIDATE,
            check_unique('faculties', 'shortcut', true)
        );
    }
}
