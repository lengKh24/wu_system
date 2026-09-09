<?php

namespace App\Http\Resources;

class StudentResource extends IResource
{
    public function toList(): array
    {
        $degree = $this->degree_type;
        return to_list($this, [
            'code'           => $this->code,
            'bacc_2_code'    => $this->bacc_2_code,
            'entrance_exam'  => $this->entrance_exam,
            'exit_exam'      => $this->exit_exam,
            'degree_type'    => $degree?->value ?? $degree,
            'degree_label' => [
                'en' => $degree?->labelEn(),
                'kh' => $degree?->labelKh(),
            ],
            'payment_as'     => $this->payment_as,
            'is_restudy'     => (bool) $this->is_restudy,
            'year_level'     => $this->year_level,
            'from_school'    => $this->from_school,
            'intake'         => $this->intake,
            'scholarship'    => $this->scholarship,
            'admission_date' => $this->admission_date?->format('Y-m-d'),
            'status'         => new StatusResource($this->whenLoaded('status')),
            'batch'          => new BatchResource($this->whenLoaded('batch')),
            'group'          => new GroupResource($this->whenLoaded('group')),
            'shift'          => new ShiftResource($this->whenLoaded('shift')),
            'campus'         => new CampusResource($this->whenLoaded('campus')),
            'major'          => new MajorResource($this->whenLoaded('major')),
            'person'         => new PersonResource($this->whenLoaded('person')),
            'guardians'      => GuardianResource::collection($this->whenLoaded('guardians')),
        ], false);
    }
}
