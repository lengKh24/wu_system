<?php
namespace App\Http\Controllers;

class RetakeScoreController extends Controller
{
    /**
     * Score's own page — a trimmed view of the retake registrations list
     * with only the ability to enter/edit a score. No batch lifecycle,
     * outcome, selection, or delete access (that's REG's separate page,
     * app/Http/Controllers/RetakeExamController).
     */
    public function index()
    {
        return view('retake-exam.scores.index');
    }
}
