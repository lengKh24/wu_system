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
     * SA uploads a payment invoice/proof image — uploaded_by and paid_at
     * are set server-side (this call IS the moment of marking paid, so
     * there's nothing for SA to pick). invoice_path/invoice_type are
     * computed from the stored file, not accepted as raw input — routes
     * through the base save() helper via a plain Model::create() would
     * mass-assign the raw UploadedFile onto a non-existent column, so this
     * bypasses it and builds the array explicitly instead.
     */
    public function store(PaymentBatchRequest $request)
    {
        $data = $request->validated();
        $file = $request->file('invoice_file');
        unset($data['invoice_file']);

        if ($file) {
            $data['invoice_path'] = $file->store('payment-invoices', 'public');
            $data['invoice_type'] = $file->getClientMimeType();
        }

        $data['uploaded_by'] = auth()->id();
        $data['paid_at']     = now();

        $batch = PaymentBatch::create($data);

        return new PaymentBatchResource($this->reload($batch));
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
