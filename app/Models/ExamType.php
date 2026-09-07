<?php
namespace App\Models;

class ExamType extends IModel
{
    public const FIRST_SUPPLEMENTARY  = '1st_supplementary';
    public const SECOND_SUPPLEMENTARY = '2nd_supplementary';
    public const RESTUDY              = 'restudy';
    public const SPECIAL              = 'special';

    public const CODES = [
        self::FIRST_SUPPLEMENTARY,
        self::SECOND_SUPPLEMENTARY,
        self::RESTUDY,
        self::SPECIAL,
    ];

    protected $fillable = [
        'name_en', 'name_kh', 'code', 'requires_import', 'uses_term', 'remark',
    ];

    protected $casts = [
        'requires_import' => 'boolean',
        'uses_term'       => 'boolean',
    ];

    public function batches()
    {
        return $this->hasMany(RetakeBatch::class);
    }

    public function registrations()
    {
        return $this->hasMany(RetakeRegistration::class);
    }
}
