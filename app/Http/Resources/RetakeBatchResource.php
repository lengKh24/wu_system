<?php
namespace App\Http\Resources;

class RetakeBatchResource extends IResource
{
    public function toList(): array
    {
        return to_list($this, [
            'term'                => new RetakeTermResource($this->whenLoaded('term')),
            'exam_type'           => new ExamTypeResource($this->whenLoaded('examType')),
            'source_type'         => $this->source_type,
            'source_batch_id'     => $this->source_batch_id,
            'file_name'           => $this->file_name,
            'status'              => $this->status,
            'generated_by'        => $this->generated_by,
            'generated_at'        => $this->generated_at?->format('Y-m-d H:i:s'),
            'telegram_group_link' => $this->telegram_group_link,
            'telegram_qr_path'    => $this->telegram_qr_path,
            'registrations_count' => $this->whenCounted('registrations'),
        ], false);
    }
}
