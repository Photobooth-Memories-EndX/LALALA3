<?php

namespace Tests\Feature;

use App\Models\OfflineTrainingUser;
use App\Services\OfflineTraining\PtkpClassificationBackfillService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PtkpBackfillClassificationTest extends TestCase
{
    use DatabaseTransactions;

    protected PtkpClassificationBackfillService $backfillService;
    protected static ?array $cachedExcelData = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backfillService = app(PtkpClassificationBackfillService::class);
    }

    protected function getSharedExcelData(): array
    {
        if (self::$cachedExcelData === null) {
            $sourcePath = base_path('docs/PTKP_OWL_Import_Preview_5000 3.xlsx');
            self::$cachedExcelData = $this->backfillService->loadAndIndexExcelSource($sourcePath);
        }
        return self::$cachedExcelData;
    }

    public function test_dry_run_command_executes_cleanly_and_writes_zero_db_updates(): void
    {
        $preUpdatedCount = OfflineTrainingUser::whereNotNull('main_classification')->count();

        $this->artisan('ptkp:backfill-classification', ['--dry-run' => true, '--limit' => 50])
            ->expectsOutputToContain('OWL LMS — PTKP LEGACY CLASSIFICATION RECOVERY & INTEGRITY AUDIT (PHASE 5)')
            ->expectsOutputToContain('DRY-RUN ONLY')
            ->expectsOutputToContain('AUDIT METRICS & SUMMARY:')
            ->expectsOutputToContain('Full participant-level audit report exported successfully:')
            ->assertExitCode(0);

        $postUpdatedCount = OfflineTrainingUser::whereNotNull('main_classification')->count();

        // Strictly verify that zero database records were modified in dry-run mode
        $this->assertSame($preUpdatedCount, $postUpdatedCount, 'Dry-run must not modify any database records.');
    }

    public function test_execute_flag_is_strictly_locked_in_phase_5_and_reverts_to_dry_run(): void
    {
        $preUpdatedCount = OfflineTrainingUser::whereNotNull('main_classification')->count();

        $this->artisan('ptkp:backfill-classification', ['--execute' => true, '--limit' => 30])
            ->expectsOutputToContain('[SAFETY NOTICE] Execution mode is currently LOCKED in Phase 5.')
            ->expectsOutputToContain('Proceeding in SAFE DRY-RUN (READ-ONLY) mode.')
            ->assertExitCode(0);

        $postUpdatedCount = OfflineTrainingUser::whereNotNull('main_classification')->count();
        $this->assertSame($preUpdatedCount, $postUpdatedCount, 'Execution flag must be locked in dry-run mode.');
    }

    public function test_case_a_rijal_setiawan_belt_analysis_detected_as_needs_backfill(): void
    {
        // Participant ID 309 is RIJAL SETIAWAN in Belt Analysis (Batch 1)
        $participant = OfflineTrainingUser::with('training')->find(309);
        $this->assertNotNull($participant);
        $this->assertSame('117834', $participant->employee_id);
        $this->assertSame('Belt Analysis', $participant->training->course_name);

        $excelData = $this->getSharedExcelData();
        $audit = $this->backfillService->evaluateParticipant($participant, $excelData['index']);

        $this->assertSame(PtkpClassificationBackfillService::STATUS_NEEDS_BACKFILL, $audit['recovery_status']);
        $this->assertSame('Compliance', $audit['existing_db_main']);
        $this->assertSame('Job Functional', $audit['proposed_main']);
        $this->assertSame('Technology-Mechanical', $audit['proposed_sub']);
        $this->assertContains(289, $audit['excel_source_rows']);
    }

    public function test_case_b_corporate_security_management_detected_as_matched(): void
    {
        // Participant ID 1968 is RIJAL SETIAWAN in Corporate Security Management Awareness (Online)
        $participant = OfflineTrainingUser::with('training')->find(1968);
        $this->assertNotNull($participant);
        $this->assertSame('117834', $participant->employee_id);

        $excelData = $this->getSharedExcelData();
        $audit = $this->backfillService->evaluateParticipant($participant, $excelData['index']);

        $this->assertSame(PtkpClassificationBackfillService::STATUS_MATCHED, $audit['recovery_status']);
        $this->assertSame('Basic & Common Duties', $audit['existing_db_main']);
        $this->assertSame('Basic & Common Duties', $audit['proposed_main']);
        $this->assertContains(2136, $audit['excel_source_rows']);
    }

    public function test_case_c_ifrs_18_detected_as_needs_backfill_for_pradipta_cahya_utama(): void
    {
        // Participant ID 3662 is PRADIPTA CAHYA UTAMA (126618) in IFRS 18
        $participant = OfflineTrainingUser::with('training')->find(3662);
        $this->assertNotNull($participant);
        $this->assertSame('126618', $participant->employee_id);
        $this->assertSame('International Financial Reporting Standards (IFRS 18)', $participant->training->course_name);

        $excelData = $this->getSharedExcelData();
        $audit = $this->backfillService->evaluateParticipant($participant, $excelData['index']);

        $this->assertSame(PtkpClassificationBackfillService::STATUS_NEEDS_BACKFILL, $audit['recovery_status']);
        $this->assertSame('Basic & Common Duties', $audit['existing_db_main']);
        $this->assertSame('Compliance', $audit['proposed_main']);
        $this->assertSame('Non-Technology', $audit['proposed_sub']);
        $this->assertContains(3863, $audit['excel_source_rows']);
    }

    public function test_conflicting_participant_in_excel_is_flagged_and_excluded_from_backfill(): void
    {
        // Participant ID 13 is FERDIAN CAHYO DWIPUTRO in Personalized Skill Development
        $participant = OfflineTrainingUser::with('training')->find(13);
        $this->assertNotNull($participant);
        $this->assertSame('131478', $participant->employee_id);

        $excelData = $this->getSharedExcelData();
        $audit = $this->backfillService->evaluateParticipant($participant, $excelData['index']);

        $this->assertSame(PtkpClassificationBackfillService::STATUS_CONFLICT, $audit['recovery_status']);
        $this->assertSame('Job Functional', $audit['existing_db_main']);
        $this->assertSame('Job Functional', $audit['proposed_main'], 'Conflicting record must retain existing database value without auto-overwrite.');
        $this->assertStringContainsString('Conflicting classifications in Excel', $audit['reason']);
        $this->assertCount(2, $audit['excel_source_rows']);
        $this->assertContains(18, $audit['excel_source_rows']);
        $this->assertContains(350, $audit['excel_source_rows']);
    }

    public function test_csv_export_generation_and_headers(): void
    {
        $tempCsv = tempnam(sys_get_temp_dir(), 'ptkp_audit_test_') . '.csv';

        try {
            $sampleRecords = [
                [
                    'recovery_status'          => PtkpClassificationBackfillService::STATUS_NEEDS_BACKFILL,
                    'employee_id'              => '117834',
                    'employee_name'            => 'RIJAL SETIAWAN',
                    'offline_training_id'      => 21,
                    'offline_training_user_id' => 309,
                    'course_title'             => 'Belt Analysis',
                    'batch'                    => '1',
                    'excel_source_main'        => 'Job Functional',
                    'excel_source_sub'         => 'Technology-Mechanical',
                    'existing_db_main'         => 'Compliance',
                    'existing_db_sub'          => 'Technology-Mechanical',
                    'proposed_main'            => 'Job Functional',
                    'proposed_sub'             => 'Technology-Mechanical',
                    'excel_source_rows'        => [289],
                    'reason'                   => 'Test reason',
                ],
            ];

            $this->backfillService->exportAuditToCsv($sampleRecords, $tempCsv);

            $this->assertFileExists($tempCsv);
            $content = file_get_contents($tempCsv);
            $lines = explode("\n", trim($content));
            $this->assertGreaterThanOrEqual(2, count($lines));

            $header = str_getcsv($lines[0]);
            $this->assertStringContainsString('Recovery Status', $header[0]);
            $this->assertSame('Employee ID', $header[1]);
            $this->assertSame('Audit Reason', $header[14]);

            $row1 = str_getcsv($lines[1]);
            $this->assertSame('NEEDS_BACKFILL', $row1[0]);
            $this->assertSame('117834', $row1[1]);
            $this->assertSame('Job Functional', $row1[11]);
        } finally {
            if (file_exists($tempCsv)) {
                @unlink($tempCsv);
            }
        }
    }
}
