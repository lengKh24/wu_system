<?php
namespace App\Exports;

use App\Models\RetakeRegistration;

/**
 * REG's "prepare schedule" export — a plain filtered download, not a real
 * scheduling feature (that's still an open TODO — retake_registrations.
 * exam_session_id exists as a stub column but is never set/read anywhere).
 * Takes the same filters as RetakeRegistrationController::index() so it
 * always matches whatever REG is currently looking at on the Main List.
 */
class RetakeRegistrationExport extends IExport
{
    protected string $model = RetakeRegistration::class;

    protected array $relationships = ['student.person', 'term', 'examType', 'lecturer', 'score'];

    protected array $headings = [
        'No', 'Student Code', 'Full Name', 'Term', 'Exam Type', 'Subject', 'Lecturer',
        'Registered At', 'Payment Status', 'Outcome', 'Score', 'Remark',
    ];

    public function __construct(protected array $filters = [])
    {
    }

    public function query()
    {
        $query = RetakeRegistration::query()->with($this->relationships);

        if ($batchId = $this->filters['batch_id'] ?? null) {
            $query->where('batch_id', $batchId);
        }
        if ($examTypeId = $this->filters['exam_type_id'] ?? null) {
            $query->where('exam_type_id', $examTypeId);
        }
        if ($termId = $this->filters['retake_term_id'] ?? null) {
            $query->where('retake_term_id', $termId);
        }
        if ($paymentStatus = $this->filters['payment_status'] ?? null) {
            $query->where('payment_status', $paymentStatus);
        }
        if ($outcome = $this->filters['outcome'] ?? null) {
            $query->where('outcome', $outcome);
        }

        return $query;
    }

    public function map(mixed $data): array
    {
        $this->numRow++;

        $person = $data->student?->person;

        return [
            $this->numRow,
            $data->student?->code,
            trim(($person?->first_name ?? '') . ' ' . ($person?->last_name ?? '')),
            $data->term?->title,
            $data->examType?->name_en,
            $data->subject,
            $data->lecturer?->name_en,
            $data->registered_at?->format('Y-m-d H:i:s'),
            $data->payment_status,
            $data->outcome,
            $data->score?->score,
            $data->remark,
        ];
    }
}
