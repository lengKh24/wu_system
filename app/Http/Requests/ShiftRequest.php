<?php
namespace App\Http\Requests;

class ShiftRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            DEFAULT_VALIDATE,
            check_unique('shifts', 'shortcut', true)
        );
    }
}
