<?php
namespace App\Http\Requests;

class CampusRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            DEFAULT_VALIDATE,
            check_unique('campuses', 'shortcut', true)
        );
    }
}
