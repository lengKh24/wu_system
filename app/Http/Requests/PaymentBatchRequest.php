<?php
namespace App\Http\Requests;

class PaymentBatchRequest extends IRequest
{
    protected function formData(): array
    {
        return array_merge(
            check_exist('student_id', 'students'),
            [
                // invoice_path/invoice_type/paid_at are computed server-side
                // from the uploaded file (see PaymentBatchController::store)
                // — SA just picks/pastes an image and writes a remark.
                'invoice_file' => 'nullable|image|max:5120',
                'remark'       => 'nullable|string|max:500',
            ]
        );
    }
}
