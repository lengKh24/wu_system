<?php
namespace App\Http\Resources;

class RetakeTermResource extends IResource
{
    public function toList(): array
    {
        return to_list($this, [
            'campus'     => new CampusResource($this->whenLoaded('campus')),
            'title'      => $this->title,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date'   => $this->end_date?->format('Y-m-d'),
            'is_active'  => (bool) $this->is_active,
        ], false);
    }
}
