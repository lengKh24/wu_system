<?php
namespace App\Http\Requests;

class PaymentBatchRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            check_exist('student_id', 'students'),
            [
                'invoice_path' => 'nullable|string|max:255',
                'invoice_type' => 'nullable|string|max:30',
                'paid_at'      => 'nullable|date',
                'remark'       => 'nullable|string|max:500',
            ]
        );
    }
}
