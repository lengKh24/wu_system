<?php
namespace App\Models;

class PaymentEntry extends IModel
{
    protected $fillable = [
        'payment_batch_id', 'payment_number', 'payment_note', 'entered_by', 'entered_at', 'remark',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
    ];

    public function paymentBatch()
    {
        return $this->belongsTo(PaymentBatch::class);
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
