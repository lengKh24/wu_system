<?php
namespace App\Http\Requests;

class PaymentEntryRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            check_exist('payment_batch_id', 'payment_batches'),
            [
                'payment_number' => 'nullable|string|max:100',
                'payment_note'   => 'nullable|string|max:1000',
                'remark'         => 'nullable|string|max:500',
            ]
        );
    }
}
