<?php
namespace App\Models;

class PaymentBatch extends IModel
{
    protected $fillable = [
        'student_id', 'invoice_path', 'invoice_type', 'uploaded_by', 'paid_at', 'remark',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function entries()
    {
        return $this->hasMany(PaymentEntry::class);
    }

    public function registrations()
    {
        return $this->hasMany(RetakeRegistration::class, 'payment_batch_id');
    }
}
