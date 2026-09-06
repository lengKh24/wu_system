<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

abstract class IModel extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected array $searchable = [];
    protected string $keyName   = 'shortcut';
    protected string $operator  = '=';

    /**
     * Every module logs create/update/delete automatically — who did it,
     * when, and which fields changed. Only fillable attributes are
     * recorded (skips relations/timestamps), only when something actually
     * changed, so an untouched "update" doesn't spam the log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(class_basename($this));
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (empty($this->searchable) && ! empty($this->fillable)) {
            $this->searchable = $this->fillable;
        }
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword) || empty($this->searchable)) {
            return $query;
        }

        return $query->whereLike($this->searchable, $keyword);
    }

    public function getNameAttribute()
    {
        return to_name($this);
    }

    protected function getBy(Builder $query, mixed $value = null)
    {
        return $query->where($this->keyName, $this->operator, $value);
    }
}
