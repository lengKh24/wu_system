<?php
namespace App\Models;

use App\Helpers\Degree;

class Subject extends IModel
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // DEFAULT_FIELD_AND_CODE may be defined as a constant array elsewhere.
        $base = [];
        if (defined('DEFAULT_FIELD_AND_CODE') && is_array(DEFAULT_FIELD_AND_CODE)) {
            $base = DEFAULT_FIELD_AND_CODE;
        }

        $this->fillable   = array_merge($base, ['faculty_id', 'level', 'lecturer_hour', 'credit']);
        $this->searchable = array_merge($this->fillable, [
            'faculty.name', 'faculty.name_kh', 'faculty.name_en', 'faculty.shortcut',
        ]);
    }

    protected function casts(): array
    {
        return ['level' => Degree::class];
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}
