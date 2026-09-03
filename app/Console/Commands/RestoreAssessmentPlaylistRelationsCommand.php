<?php

namespace App\Console\Commands;

use App\Models\Assesment;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Console\Command;

class RestoreAssessmentPlaylistRelationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assessment:restore-playlist-relations {--dry-run : Perform validation and audit simulation (read-only)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dry-run validation and audit for restoring historical assessment playlist relations';

    /**
     * Explicit verified mapping for the 17 historical target items.
     */
    public const TARGET_MAPPING = [
        // Playlist 1: PTKP Health E-Learning 2026 (trainingplan_id: 32)
        [
            'no' => 1,
            'playlist_name' => 'PTKP Health E-Learning 2026',
            'trainingplan_id' => 32,
            'content_id' => 280,
            'content_title' => 'First Aid Training: CPR dan Penggunaan AED (Part 2)',
            'assessment_id' => 326,
            'assessment_title' => '[Health E-Learning 2026] CPR and AED Post-test',
        ],
        [
            'no' => 2,
            'playlist_name' => 'PTKP Health E-Learning 2026',
            'trainingplan_id' => 32,
            'content_id' => 282,
            'content_title' => 'Penanggulangan TBC di Tempat Kerja (Part 2)',
            'assessment_id' => 325,
            'assessment_title' => '[Health E-Learning 2026] TBC Post-test',
        ],
        [
            'no' => 3,
            'playlist_name' => 'PTKP Health E-Learning 2026',
            'trainingplan_id' => 32,
            'content_id' => 284,
            'content_title' => 'Penanggulangan HIV/AIDS di Tempat Kerja (Part 2)',
            'assessment_id' => 324,
            'assessment_title' => '[Health E-Learning 2026] HIV/AIDS Post-test',
        ],

        // Playlist 2: Safety Mandatory E-Learning 2025 [Series #2] (trainingplan_id: 25)
        [
            'no' => 4,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 155,
            'content_title' => '[Video] Isolation Locking System (ILS) Operation & Non-Operation',
            'assessment_id' => 260,
            'assessment_title' => '[Safety series 2] Isolation Locking System',
        ],
        [
            'no' => 5,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 157,
            'content_title' => '[Video] Working At Height (Bekerja Di Ketinggian)',
            'assessment_id' => 262,
            'assessment_title' => '[Safety series 2] Working At Height',
        ],
        [
            'no' => 6,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 158,
            'content_title' => '[Video] Electrical Safety',
            'assessment_id' => 263,
            'assessment_title' => '[Safety series 2] Electrical Safety',
        ],
        [
            'no' => 7,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 159,
            'content_title' => '[Video] Lifting & Rigging',
            'assessment_id' => 264,
            'assessment_title' => '[Safety series 2] Lifting & Rigging',
        ],
        [
            'no' => 8,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 160,
            'content_title' => '[Video] Natural Disaster & Emergency Responses',
            'assessment_id' => 265,
            'assessment_title' => '[Safety series 2] Natural Disaster & Emergency Responses',
        ],
        [
            'no' => 9,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 161,
            'content_title' => '[Video] Industrial Hygiene',
            'assessment_id' => 266,
            'assessment_title' => '[Safety series 2] Industrial Hygiene',
        ],

        // Playlist 3: Safety Mandatory E-Learning 2025 [Series #1] (trainingplan_id: 18)
        [
            'no' => 10,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 123,
            'content_title' => '3 Major Accident',
            'assessment_id' => 177,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 1 : 3 Major Accident',
        ],
        [
            'no' => 11,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 124,
            'content_title' => '10 Safety Iron Rules & 7 Global Safety Model',
            'assessment_id' => 178,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 2 : 10 SIR and 7 Global Safety Model',
        ],
        [
            'no' => 12,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 125,
            'content_title' => 'Hazard Identification',
            'assessment_id' => 179,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 3 : Hazard Identification',
        ],
        [
            'no' => 13,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 126,
            'content_title' => 'Hierarchy of Hazard Control',
            'assessment_id' => 180,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 4 : Hierarchy of Hazard Control',
        ],
        [
            'no' => 14,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 127,
            'content_title' => 'Tool Box Meeting',
            'assessment_id' => 181,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 5 : Tool Box Meeting',
        ],
        [
            'no' => 15,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 128,
            'content_title' => 'Personal Protective Equipment',
            'assessment_id' => 182,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 6 : Personal Protective Equipment',
        ],
        [
            'no' => 16,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 129,
            'content_title' => 'Good Driver',
            'assessment_id' => 183,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 7 : Good Driver',
        ],
        [
            'no' => 17,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 130,
            'content_title' => 'Fire Prevention and Protection System',
            'assessment_id' => 184,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 8 : Fire Prevention and Protection System',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('ASSESSMENT PLAYLIST RELATION RECOVERY — DRY RUN');
        $this->newLine();

        $stats = [
            'total'             => count(self::TARGET_MAPPING),
            'assessment_found'  => 0,
            'content_found'     => 0,
            'playlist_found'    => 0,
            'pivot_found'       => 0,
            'ready_for_update'  => 0,
            'already_correct'   => 0,
            'not_found'         => 0,
            'mismatch'          => 0,
            'conflict'          => 0,
            'database_modified' => 0,
        ];

        foreach (self::TARGET_MAPPING as $item) {
            $no       = $item['no'];
            $tpId     = $item['trainingplan_id'];
            $cId      = $item['content_id'];
            $aId      = $item['assessment_id'];

            // Query models (Pure Read-Only SELECT)
            $assessment = Assesment::find($aId);
            $content    = Content::find($cId);
            $tp         = TrainingPlan::find($tpId);
            $tpd        = TrainingPlanDetail::where('trainingplan_id', $tpId)
                ->where('content_id', $cId)
                ->first();

            $assessmentExists = $assessment !== null;
            $contentExists    = $content !== null;
            $playlistExists   = $tp !== null;
            $pivotExists      = $tpd !== null;

            if ($assessmentExists) {
                $stats['assessment_found']++;
            }
            if ($contentExists) {
                $stats['content_found']++;
            }
            if ($playlistExists) {
                $stats['playlist_found']++;
            }
            if ($pivotExists) {
                $stats['pivot_found']++;
            }

            $currentContentFor = $assessment ? ($assessment->content_for ?? 'NULL') : 'NOT FOUND';
            $currentTpId       = $assessment ? ($assessment->trainingplan_id ?? 'NULL') : 'NOT FOUND';

            $this->line('--------------------------------------------------');
            $this->line("#{$no}");
            $this->line("Assessment ID : {$aId}");
            $this->line("Assessment   : " . ($assessment ? $assessment->title : 'NOT FOUND'));
            $this->line("Content ID   : {$cId}");
            $this->line("Content      : " . ($content ? $content->title : $item['content_title']));
            $this->newLine();
            $this->line('Current:');
            $this->line("content_for     = {$currentContentFor}");
            $this->line("trainingplan_id = {$currentTpId}");
            $this->newLine();
            $this->line('Target:');
            $this->line('content_for     = playlist');
            $this->line("trainingplan_id = {$tpId}");
            $this->newLine();
            $this->line('TrainingPlanDetail:');
            $this->line($pivotExists ? 'FOUND' : 'NOT FOUND');
            $this->newLine();

            // Result determination
            $result = '';
            if (!$assessmentExists || !$contentExists || !$playlistExists) {
                $stats['not_found']++;
                $result = 'CONFLICT / SKIPPED: Required record (Assessment, Content, or Playlist) not found';
            } elseif ((int) $assessment->content_id !== (int) $cId) {
                $stats['mismatch']++;
                $result = "CONFLICT / SKIPPED: Content mismatch (Assessment content_id: {$assessment->content_id} != {$cId})";
            } elseif (!$pivotExists) {
                $stats['mismatch']++;
                $result = 'CONFLICT / SKIPPED: TrainingPlanDetail not found in target playlist';
            } elseif (!is_null($assessment->trainingplan_id) && (int) $assessment->trainingplan_id !== (int) $tpId) {
                $stats['conflict']++;
                $result = "CONFLICT / SKIPPED: Assessment already assigned to different Playlist ID ({$assessment->trainingplan_id})";
            } elseif ((int) $assessment->trainingplan_id === (int) $tpId && $assessment->content_for === 'playlist') {
                $stats['already_correct']++;
                $result = 'ALREADY UP TO DATE';
            } else {
                $stats['ready_for_update']++;
                $result = 'READY FOR UPDATE';
            }

            $this->line('Result:');
            $this->line($result);
            $this->line('--------------------------------------------------');
            $this->newLine();
        }

        $this->line('==================================================');
        $this->line('SUMMARY');
        $this->line('==================================================');
        $this->line("Total Target       : {$stats['total']}");
        $this->line("Assessment Found   : {$stats['assessment_found']}");
        $this->line("Content Found      : {$stats['content_found']}");
        $this->line("Playlist Found     : {$stats['playlist_found']}");
        $this->line("Pivot Found        : {$stats['pivot_found']}");
        $this->newLine();
        $this->line("Ready for Update   : {$stats['ready_for_update']}");
        $this->line("Already Correct    : {$stats['already_correct']}");
        $this->line("Not Found          : {$stats['not_found']}");
        $this->line("Mismatch           : {$stats['mismatch']}");
        $this->line("Conflict           : {$stats['conflict']}");
        $this->newLine();
        $this->line("Database Modified  : {$stats['database_modified']}");
        $this->line('==================================================');

        return self::SUCCESS;
    }
}
