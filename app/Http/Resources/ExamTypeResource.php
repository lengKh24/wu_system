<?php
namespace App\Http\Resources;

class ExamTypeResource extends IResource
{
    public function toList(): array
    {
        return to_list($this, [
            'code'            => $this->code,
            'requires_import' => (bool) $this->requires_import,
            'uses_term'       => (bool) $this->uses_term,
        ]);
    }
}
