<?php
namespace App\Http\Controllers;

class RetakeExamPublicController extends Controller
{
    /**
     * Public self-service page — student looks up their pending retake
     * registrations (student code + date of birth), picks which subjects
     * to register for, and confirms. No auth — mirrors the state-exam
     * attendance page's precedent (routes/web.php).
     *
     * Deliberately minimal markup: functional first, restyle later once
     * the flow itself is confirmed to work end to end.
     */
    public function index()
    {
        return view('retake-exam.public');
    }
}
