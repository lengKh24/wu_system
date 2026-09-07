<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RetakeTermRequest;
use App\Http\Resources\RetakeTermResource;
use App\Models\RetakeTerm;
use Illuminate\Http\Request;

class RetakeTermController extends Controller
{
    public function __construct()
    {
        $this->name          = 'Retake Term';
        $this->model         = RetakeTerm::class;
        $this->resource      = RetakeTermResource::class;
        $this->relationships = ['campus'];
    }

    public function index(Request $request)
    {
        return $this->list($request, fn($query) => $request->boolean('trashed') ? $query->onlyTrashed() : $query);
    }

    public function store(RetakeTermRequest $request)
    {
        return $this->save($request);
    }

    public function show(RetakeTerm $retakeTerm)
    {
        return $this->view($retakeTerm);
    }

    public function update(RetakeTermRequest $request, RetakeTerm $retakeTerm)
    {
        return $this->release($request, $retakeTerm);
    }

    public function destroy(RetakeTerm $retakeTerm)
    {
        return $this->disable($retakeTerm);
    }

    public function restore(RetakeTerm $retakeTerm)
    {
        return $this->enable($retakeTerm);
    }

    public function force_destroy(RetakeTerm $retakeTerm)
    {
        return $this->clear($retakeTerm);
    }
}
