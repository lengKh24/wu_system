<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamTypeRequest;
use App\Http\Resources\ExamTypeResource;
use App\Models\ExamType;
use Illuminate\Http\Request;

class ExamTypeController extends Controller
{
    public function __construct()
    {
        $this->name     = 'Exam Type';
        $this->model    = ExamType::class;
        $this->resource = ExamTypeResource::class;
    }

    public function index(Request $request)
    {
        return $this->list($request);
    }

    public function store(ExamTypeRequest $request)
    {
        return $this->save($request);
    }

    public function show(ExamType $examType)
    {
        return $this->view($examType);
    }

    public function update(ExamTypeRequest $request, ExamType $examType)
    {
        return $this->release($request, $examType);
    }

    public function destroy(ExamType $examType)
    {
        return $this->disable($examType);
    }

    public function restore(ExamType $examType)
    {
        return $this->enable($examType);
    }

    public function force_destroy(ExamType $examType)
    {
        return $this->clear($examType);
    }
}
