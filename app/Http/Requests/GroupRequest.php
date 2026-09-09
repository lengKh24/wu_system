<?php
namespace App\Http\Requests;

class GroupRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            DEFAULT_VALIDATE,
            check_unique('groups', 'shortcut', true)
        );
    }
}
