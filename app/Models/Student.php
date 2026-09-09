<?php

namespace App\Models;

use App\Helpers\Degree;
use Illuminate\Contracts\Database\Eloquent\Builder;

class Student extends IModel
{
    protected string $keyName = 'payment_as';
    public const NONE         = 'none';
    public const YEARLY       = 'yearly';
    public const SEMESTER     = 'semester';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->fillable = [
            'person_id',
            'guardian_id',
            'batch_id',
            'major_id',
            'group_id',
            'shift_id',
            'campus_id',
            'status_id',
            'code',
            'payment_as',
            'year_level',
            'from_school',
            'admission_date',
            'degree_type',
            'bacc_2_code',
            'intake',
            'scholarship',
            'entrance_exam',
            'exit_exam',
            'remark',
        ];

        $this->searchable = [
            'code',
            ...array_map(fn($p) => "person.{$p}", [
                'first_name',
                'last_name',
                'first_name_kh',
                'last_name_kh',
                'dob',
                'sex',
                'email',
            ]),
            'status.name_kh',
            'status.name_en',
            'batch.name_kh',
            'batch.name_en',
            'major.name_kh',
            'major.name_en',
        ];
    }

    protected function casts(): array
    {
        return ['admission_date' => 'date', 'degree_type' => Degree::class, 'is_restudy' => 'boolean'];
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function guardians()
    {
        return $this->hasMany(Guardian::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function provisionalCertificates()
    {
        return $this->certificates()->provisional();
    }

    public function statusCertificates()
    {
        return $this->certificates()->status();
    }

    public function scopeYearly(Builder $query)
    {
        return $this->getBy($query, self::YEARLY);
    }

    public function scopeSemester(Builder $query)
    {
        return $this->getBy($query, self::SEMESTER);
    }
}
