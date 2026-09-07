<?php
namespace App\Http\Resources;

class PaymentBatchResource extends IResource
{
    public function toList(): array
    {
        return to_list($this, [
            'student'      => new StudentResource($this->whenLoaded('student')),
            'invoice_path' => $this->invoice_path,
            'invoice_type' => $this->invoice_type,
            'invoice_url'  => $this->invoice_path ? asset('storage/' . $this->invoice_path) : null,
            'uploaded_by'  => $this->uploaded_by,
            'paid_at'      => $this->paid_at?->format('Y-m-d H:i:s'),
            'entries'      => PaymentEntryResource::collection($this->whenLoaded('entries')),
        ], false);
    }
}
