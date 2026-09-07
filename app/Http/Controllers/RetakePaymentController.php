<?php
namespace App\Http\Controllers;

class RetakePaymentController extends Controller
{
    /**
     * SA's own page — confirmed retake registrations only (payment can't
     * happen against a selection that might still change), with the
     * ability to create a payment batch + mark subjects paid, and invite
     * a paid student to the batch's Telegram group.
     */
    public function index()
    {
        return view('retake-exam.payments.index');
    }
}
