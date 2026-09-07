<?php
namespace App\Http\Controllers;

class RetakeExamController extends Controller
{
    /**
     * REG's Main List — every retake registration across every batch, with
     * client-side filters for term/exam type/batch/payment/outcome. Batch
     * lifecycle actions (import, close, carry-forward, Telegram) live on
     * this same page since REG owns both.
     */
    public function index()
    {
        return view('retake-exam.registrations.index');
    }
}
