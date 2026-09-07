<?php
namespace App\Http\Controllers;

class RetakeCustomerServiceController extends Controller
{
    /**
     * Customer Service's own page — fully read-only, confirmed
     * registrations only. No batch, payment, score, outcome, or delete
     * access at all; just enough to answer "did I register?" questions.
     */
    public function index()
    {
        return view('retake-exam.customer-service.index');
    }
}
