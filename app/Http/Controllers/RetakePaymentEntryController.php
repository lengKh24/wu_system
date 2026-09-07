<?php
namespace App\Http\Controllers;

class RetakePaymentEntryController extends Controller
{
    /**
     * ACC's own page — lists SA's payment batches (read-only) and lets ACC
     * record their own reconciliation entry against one. ACC never changes
     * payment_status itself (see payment_entries migration docblock).
     */
    public function index()
    {
        return view('retake-exam.payment-entries.index');
    }
}
