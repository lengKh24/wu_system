<?php
namespace App\Http\Resources;

class SubjectResource extends IResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toList(): array
    {
        $level = $this->level;

        return to_list($this, [
            'code'          => $this->code,
            'faculty'       => new FacultyResource($this->whenLoaded('faculty')),
            'level'         => $level?->value ?? $level,
            'level_label'   => [
                'en' => $level?->labelEn(),
                'kh' => $level?->labelKh(),
            ],
            'lecturer_hour' => $this->lecturer_hour,
            'credit'        => $this->credit,
        ]);
    }
}
