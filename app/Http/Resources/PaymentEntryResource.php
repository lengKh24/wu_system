<?php
namespace App\Http\Resources;

class PaymentEntryResource extends IResource
{
    public function toList(): array
    {
        return to_list($this, [
            'payment_batch_id' => $this->payment_batch_id,
            'payment_number'   => $this->payment_number,
            'payment_note'     => $this->payment_note,
            'entered_by'       => $this->entered_by,
            'entered_at'       => $this->entered_at?->format('Y-m-d H:i:s'),
        ], false);
    }
}
