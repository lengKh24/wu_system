<?php
namespace App\Http\Requests;

class StatusRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            DEFAULT_VALIDATE,
            check_unique('statuses', 'shortcut', true)
        );
    }
}
