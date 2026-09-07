<?php
namespace App\Models;

class Score extends IModel
{
    protected $fillable = [
        'retake_registration_id', 'score', 'entered_by', 'entered_at', 'remark',
    ];

    protected $casts = [
        'score'      => 'decimal:2',
        'entered_at' => 'datetime',
    ];

    public function registration()
    {
        return $this->belongsTo(RetakeRegistration::class, 'retake_registration_id');
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
