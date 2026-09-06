<?php
namespace App\Http\Controllers\Api;

use App\Exports\RetakeExamExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\RetakeExamRequest;
use App\Http\Resources\RetakeExamResource;
use App\Imports\RetakeExamImport;
use App\Models\RetakeExam;
use Illuminate\Http\Request;

class RetakeExamController extends Controller
{
    public function __construct()
    {
        $this->name     = 'Retake Exam';
        $this->model    = RetakeExam::class;
        $this->resource = RetakeExamResource::class;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->list($request, fn($query) => $request->boolean('trashed') ? $query->onlyTrashed() : $query);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RetakeExamRequest $request)
    {
        return $this->save($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(RetakeExam $retakeExam)
    {
        return $this->view($retakeExam);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RetakeExamRequest $request, RetakeExam $retakeExam)
    {
        return $this->release($request, $retakeExam);
    }

    /**
     * Disable the specified resource from storage.
     */
    public function destroy(RetakeExam $retakeExam)
    {
        return $this->disable($retakeExam);
    }

    /**
     * Restore a soft-deleted of the resource.
     */
    public function restore(RetakeExam $retakeExam)
    {
        return $this->enable($retakeExam);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function force_destroy(RetakeExam $retakeExam)
    {
        return $this->clear($retakeExam);
    }

    /**
     * Export retake exams to an Excel/CSV file.
     */
    public function exportExcel()
    {
        return $this->export(new RetakeExamExport, 'retake_exams');
    }

    /**
     * Import retake exams from an uploaded Excel/CSV file.
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        return handle(fn() => $this->import(new RetakeExamImport, $request->file('file')));
    }
}
