<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * A retake period shared across 1st Supp / 2nd Supp / Restudy for one
 * cohort (e.g. "B22Y2S2 (Mar-Jul)"). Special sits outside this system
 * entirely (retake_batches.retake_term_id is nullable for it).
 *
 * Not to be confused with the existing Term model — that's a fixed
 * academic-calendar entity (year/semester/code), a different concept.
 */
class RetakeTerm extends IModel
{
    protected $fillable = [
        'campus_id', 'title', 'start_date', 'end_date', 'is_active', 'remark',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function batches()
    {
        return $this->hasMany(RetakeBatch::class);
    }

    public function registrations()
    {
        return $this->hasMany(RetakeRegistration::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
