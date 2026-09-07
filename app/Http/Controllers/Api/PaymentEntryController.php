<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentEntryRequest;
use App\Http\Resources\PaymentEntryResource;
use App\Models\PaymentEntry;
use Illuminate\Http\Request;

class PaymentEntryController extends Controller
{
    public function __construct()
    {
        $this->name          = 'Payment Entry';
        $this->model         = PaymentEntry::class;
        $this->resource      = PaymentEntryResource::class;
        $this->relationships = ['paymentBatch'];
    }

    public function index(Request $request)
    {
        return $this->list($request, function ($query) use ($request) {
            if ($paymentBatchId = $request->input('payment_batch_id')) {
                $query->where('payment_batch_id', $paymentBatchId);
            }

            return $query;
        });
    }

    /**
     * ACC's independent reconciliation entry — entered_by/entered_at are
     * set server-side. ACC records payment_number/payment_note only; it
     * cannot change payment_status (that stays SA's action, see
     * RetakeRegistrationController::markPaid()).
     */
    public function store(PaymentEntryRequest $request)
    {
        return $this->save($request, [
            'entered_by' => auth()->id(),
            'entered_at' => now(),
        ]);
    }

    public function show(PaymentEntry $paymentEntry)
    {
        return $this->view($paymentEntry);
    }

    public function update(PaymentEntryRequest $request, PaymentEntry $paymentEntry)
    {
        return $this->release($request, $paymentEntry);
    }

    public function destroy(PaymentEntry $paymentEntry)
    {
        return $this->disable($paymentEntry);
    }

    public function restore(PaymentEntry $paymentEntry)
    {
        return $this->enable($paymentEntry);
    }

    public function force_destroy(PaymentEntry $paymentEntry)
    {
        return $this->clear($paymentEntry);
    }
}
