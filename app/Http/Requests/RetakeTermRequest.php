<?php
namespace App\Http\Requests;

class RetakeTermRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            check_exist('campus_id', 'campuses'),
            [
                'title'      => 'required|string|max:150',
                'start_date' => 'nullable|date',
                'end_date'   => 'nullable|date|after_or_equal:start_date',
                'is_active'  => 'nullable|boolean',
                'remark'     => 'nullable|string|max:500',
            ]
        );
    }
}
