<?php

namespace App\Console\Commands;

use App\Models\AssesmentResult;
use App\Services\AssessmentAccessService;
use Illuminate\Console\Command;

class FinalizeExpiredAssessmentDraftsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assessment:finalize-expired-drafts {--assessment= : Specific assessment ID to finalize} {--user= : Specific user ID to finalize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finalize active draft assessment attempts whose duration or access period has expired';

    /**
     * Execute the console command.
     */
    public function handle(AssessmentAccessService $assessmentAccess): int
    {
        $assessmentId = $this->option('assessment');
        $userId = $this->option('user');

        $query = AssesmentResult::with('assesment')
            ->where('final', 0);

        if ($assessmentId) {
            $query->where('assesment_id', $assessmentId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $drafts = $query->get();
        $this->info("Found {$drafts->count()} active draft(s) to inspect.");

        $finalized = 0;
        foreach ($drafts as $draft) {
            $assessment = $draft->assesment;
            if ($assessment && $assessmentAccess->isDraftExpired($assessment, $draft)) {
                $assessmentAccess->finalizeExpiredDraft($draft, $assessment);
                $finalized++;
            }
        }

        $this->info("Successfully finalized {$finalized} expired draft assessment attempt(s).");

        return 0;
    }
}

