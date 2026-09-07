<?php
namespace App\Console\Commands;

use App\Models\DeletionLog;
use App\Models\RetakeRegistration;
use Illuminate\Console\Command;

class PurgeUnpaidRetakeRegistrations extends Command
{
    protected $signature = 'retake:purge-unpaid';

    protected $description = "Hard-delete retake registrations left unpaid for more than 60 days, logging each to deletion_log first so the next stage's carry-forward can still find them.";

    /**
     * 60 days, per Leng's confirmed decision (2026-09-07) — kept as a class
     * constant, visible right next to the job that enforces it, rather
     * than a config value, so bump this one line if the policy changes.
     */
    protected const UNPAID_DAYS = 60;

    public function handle(): int
    {
        $cutoff = now()->subDays(self::UNPAID_DAYS);

        // Only rows the student actually confirmed (registered_at set) —
        // an unconfirmed row hasn't started its clock yet, and a
        // deselected row (is_selected = false) was never really "in" this
        // stage to begin with.
        $expired = RetakeRegistration::query()
            ->where('is_selected', true)
            ->where('payment_status', RetakeRegistration::PAYMENT_UNPAID)
            ->whereNotNull('registered_at')
            ->where('registered_at', '<=', $cutoff)
            ->get();

        $purged = 0;

        foreach ($expired as $registration) {
            DeletionLog::logAndPurge($registration, DeletionLog::REASON_UNPAID_EXPIRED);
            $purged++;
        }

        $this->info("Purged {$purged} unpaid registration(s) older than " . self::UNPAID_DAYS . ' days.');

        return self::SUCCESS;
    }
}
