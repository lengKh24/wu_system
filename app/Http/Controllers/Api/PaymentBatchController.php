<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentBatchRequest;
use App\Http\Resources\PaymentBatchResource;
use App\Models\PaymentBatch;
use Illuminate\Http\Request;

class PaymentBatchController extends Controller
{
    public function __construct()
    {
        $this->name          = 'Payment Batch';
        $this->model         = PaymentBatch::class;
        $this->resource      = PaymentBatchResource::class;
        $this->relationships = ['student', 'entries'];
    }

    public function index(Request $request)
    {
        return $this->list($request, function ($query) use ($request) {
            if ($studentId = $request->input('student_id')) {
                $query->where('student_id', $studentId);
            }

            return $query;
        });
    }

    /**
     * SA uploads a payment invoice/proof — uploaded_by is set server-side,
     * never trusted from the request.
     */
    public function store(PaymentBatchRequest $request)
    {
        return $this->save($request, ['uploaded_by' => auth()->id()]);
    }

    public function show(PaymentBatch $paymentBatch)
    {
        return $this->view($paymentBatch);
    }

    public function update(PaymentBatchRequest $request, PaymentBatch $paymentBatch)
    {
        return $this->release($request, $paymentBatch);
    }

    public function destroy(PaymentBatch $paymentBatch)
    {
        return $this->disable($paymentBatch);
    }

    public function restore(PaymentBatch $paymentBatch)
    {
        return $this->enable($paymentBatch);
    }

    public function force_destroy(PaymentBatch $paymentBatch)
    {
        return $this->clear($paymentBatch);
    }
}
