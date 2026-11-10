<?php

namespace Tests\Feature;

use App\Exports\OfflineRecordExport;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\OfflineTraining\PtkpImportService;
use App\Services\Record\PtkpExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PtkpClassificationDataIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->getOrCreateTestUser('ADM_INT_01', 'PTKP Test Admin', ['role' => User::ROLE_ADMIN]);
    }

    protected function getOrCreateTestUser(string $empId, string $name, array $attrs = []): User
    {
        $existing = User::where('id_emp', $empId)->first();
        if ($existing) {
            return $existing;
        }

        static $seq = 8100;
        $seq++;

        return User::create(array_merge([
            'name'         => $name,
            'email'        => "emp_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => $empId, // varchar(12)
            'id_user'      => 'U' . substr(str_pad((string)$seq, 8, '0', STR_PAD_LEFT), -8), // varchar(10)
            'id_org_unit'  => 'OU_01',
            'team'         => 'Test Team',
            'department'   => 'Test Dept',
            'division'     => 'Test Div',
            'sap_code'     => 'SAP_' . $seq,
            'ssohash'      => 'HASH_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    protected function createPtkpExcel(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $columns = PtkpExcelSchema::columns();
        foreach ($columns as $col) {
            $letter = $col['col_letter'];
            $sheet->setCellValue("{$letter}1", $col['header']);
        }

        $rowIdx = 2;
        foreach ($rows as $data) {
            foreach ($columns as $col) {
                $letter = $col['col_letter'];
                $key = $col['key'];

                if ($key === 'employee_id') {
                    $sheet->setCellValueExplicit("{$letter}{$rowIdx}", (string) ($data['employee_id'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } elseif (array_key_exists($key, $data)) {
                    $sheet->setCellValue("{$letter}{$rowIdx}", $data[$key]);
                } else {
                    $sheet->setCellValue("{$letter}{$rowIdx}", $col['example'] ?? '');
                }
            }
            $rowIdx++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_integrity_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, 'ptkp_integrity_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * Test A — Different classification in same training session (Case RIJAL SETIAWAN):
     * Verify participant 1 (Job Functional) and participant 2 (Compliance) retain their distinct classifications
     * even when grouped under the same physical training session.
     */
    public function test_case_a_different_classification_in_same_training_session_preserves_both(): void
    {
        $this->getOrCreateTestUser('117834', 'RIJAL SETIAWAN');
        $this->getOrCreateTestUser('128125', 'PESERTA LAIN');

        $courseName = 'Belt Analysis Session Test A';
        $batch = 'Batch A';
        $testDate = '2026-12-10';

        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => '128125',
                'name'                => 'PESERTA LAIN',
                'course_name'         => $courseName,
                'sub_course_name'     => 'Personalized Skill Development',
                'batch'               => $batch,
                'start_date'          => $testDate,
                'end_date'            => $testDate,
                'venue'               => 'HRDC Room A',
                'main_classification' => 'Compliance',
                'sub_classification'  => 'Technology-Mechanical',
            ],
            [
                'row_no'              => 2,
                'employee_id'         => '117834',
                'name'                => 'RIJAL SETIAWAN',
                'course_name'         => $courseName,
                'sub_course_name'     => 'Personalized Skill Development',
                'batch'               => $batch,
                'start_date'          => $testDate,
                'end_date'            => $testDate,
                'venue'               => 'HRDC Room A',
                'main_classification' => 'Job Functional',
                'sub_classification'  => 'Technology-Mechanical',
            ],
        ]);

        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $previewResp->assertStatus(200);
        $previewJson = $previewResp->json();
        $this->assertTrue($previewJson['can_confirm']);
        $this->assertSame(2, $previewJson['importable_count']);
        $token = $previewJson['token'];

        $confirmResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);
        $confirmResp->assertStatus(200);
        $this->assertSame(2, $confirmResp->json('result.imported'));

        // Verify training grouping: Exactly 1 offline training session created
        $trainings = OfflineTraining::where('course_name', $courseName)
            ->where('batch', $batch)
            ->get();
        $this->assertCount(1, $trainings);
        $trainingId = $trainings->first()->id;

        // Verify participant-level classification persistence in database
        $rijalParticipant = OfflineTrainingUser::where('offline_training_id', $trainingId)
            ->where('employee_id', '117834')
            ->first();
        $this->assertNotNull($rijalParticipant);
        $this->assertSame('Job Functional', $rijalParticipant->main_classification);
        $this->assertSame('Technology-Mechanical', $rijalParticipant->sub_classification);

        $otherParticipant = OfflineTrainingUser::where('offline_training_id', $trainingId)
            ->where('employee_id', '128125')
            ->first();
        $this->assertNotNull($otherParticipant);
        $this->assertSame('Compliance', $otherParticipant->main_classification);
        $this->assertSame('Technology-Mechanical', $otherParticipant->sub_classification);

        // Verify query service dual fallback returns participant-level values
        $queryService = app(RecordOfflineQueryService::class);
        $results = $queryService->buildQuery(['course_name' => $courseName])->get();

        $rijalRow = $results->firstWhere('employee_id', '117834');
        $this->assertNotNull($rijalRow);
        $this->assertSame('Job Functional', $rijalRow->main_classification);

        $otherRow = $results->firstWhere('employee_id', '128125');
        $this->assertNotNull($otherRow);
        $this->assertSame('Compliance', $otherRow->main_classification);

        // Verify export mapping uses participant-level classification
        $export = new OfflineRecordExport();
        $rijalMapped = $export->map($rijalRow);
        $this->assertSame('Job Functional', $rijalMapped[14]); // Index 14: Main Classification

        $otherMapped = $export->map($otherRow);
        $this->assertSame('Compliance', $otherMapped[14]);
    }

    /**
     * Test B — Belt Analysis Batch 3:
     * Participants in rows 3496-3502 (Job Functional) and rows 3503-3506 (Basic & Common Duties)
     * must both retain their expected classifications.
     */
    public function test_case_b_belt_analysis_batch_3_preserves_job_functional_and_basic_common_duties(): void
    {
        $this->getOrCreateTestUser('EMP_B_3496', 'Employee 3496');
        $this->getOrCreateTestUser('EMP_B_3503', 'Employee 3503');

        $courseName = 'Belt Analysis Batch 3 Test B';
        $testDate = '2026-12-15';

        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_B_3496',
                'course_name'         => $courseName,
                'batch'               => 'Batch 3',
                'start_date'          => $testDate,
                'end_date'            => $testDate,
                'venue'               => 'HRDC Room 1',
                'main_classification' => 'Job Functional',
                'sub_classification'  => 'Technology-Mechanical',
            ],
            [
                'row_no'              => 2,
                'employee_id'         => 'EMP_B_3503',
                'course_name'         => $courseName,
                'batch'               => 'Batch 3',
                'start_date'          => $testDate,
                'end_date'            => $testDate,
                'venue'               => 'HRDC Room 1',
                'main_classification' => 'Basic & Common Duties',
                'sub_classification'  => 'Technology-Mechanical',
            ],
        ]);

        $previewResp = $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $token = $previewResp->json('token');
        $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);

        $queryService = app(RecordOfflineQueryService::class);
        $results = $queryService->buildQuery(['course_name' => $courseName])->get();

        $row1 = $results->firstWhere('employee_id', 'EMP_B_3496');
        $row2 = $results->firstWhere('employee_id', 'EMP_B_3503');

        $this->assertNotNull($row1);
        $this->assertNotNull($row2);
        $this->assertSame('Job Functional', $row1->main_classification);
        $this->assertSame('Basic & Common Duties', $row2->main_classification);
    }

    /**
     * Test C — Personalized Skill Development:
     * One training group with 5 different classifications:
     * Job Functional, Leadership, Innovation, Compliance, Basic & Common Duties.
     */
    public function test_case_c_personalized_skill_development_preserves_five_different_classifications(): void
    {
        $classifications = [
            'Job Functional'        => 'EMP_C_JF',
            'Leadership'            => 'EMP_C_LD',
            'Innovation'            => 'EMP_C_IN',
            'Compliance'            => 'EMP_C_CP',
            'Basic & Common Duties' => 'EMP_C_BC',
        ];

        $courseName = 'Personalized Skill Development Multiclass Test C';
        $testDate = '2026-12-20';

        $excelRows = [];
        $rowIdx = 1;
        foreach ($classifications as $classification => $empId) {
            $this->getOrCreateTestUser($empId, "User {$empId}");
            $excelRows[] = [
                'row_no'              => $rowIdx++,
                'employee_id'         => $empId,
                'course_name'         => $courseName,
                'batch'               => 'Batch 2026-A',
                'start_date'          => $testDate,
                'end_date'            => $testDate,
                'venue'               => 'Room Multiclass',
                'main_classification' => $classification,
                'sub_classification'  => 'Sub ' . $classification,
            ];
        }

        $file = $this->createPtkpExcel($excelRows);
        $previewResp = $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $token = $previewResp->json('token');
        $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);

        // Verify all 5 were assigned to a single physical training session
        $trainings = OfflineTraining::where('course_name', $courseName)->get();
        $this->assertCount(1, $trainings);

        $queryService = app(RecordOfflineQueryService::class);
        $results = $queryService->buildQuery(['course_name' => $courseName])->get();

        foreach ($classifications as $expectedClassification => $empId) {
            $row = $results->firstWhere('employee_id', $empId);
            $this->assertNotNull($row, "Participant {$empId} must exist");
            $this->assertSame($expectedClassification, $row->main_classification, "Participant {$empId} must retain its own classification");
            $this->assertSame('Sub ' . $expectedClassification, $row->sub_classification);
        }
    }

    /**
     * Test D — IFRS 18:
     * Participants with Basic & Common Duties, Job Functional, and Compliance (Employee 126618).
     * Verify Employee 126618 retains Compliance.
     */
    public function test_case_d_ifrs_18_preserves_compliance_classification(): void
    {
        $this->getOrCreateTestUser('EMP_D_01', 'Participant Alpha');
        $this->getOrCreateTestUser('126618', 'PRADIPTA CAHYA UTAMA');

        $courseName = 'IFRS 18 Standards Integrity Test D';
        $testDate = '2026-12-25';

        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_D_01',
                'course_name'         => $courseName,
                'batch'               => 'Batch 1',
                'start_date'          => $testDate,
                'end_date'            => $testDate,
                'venue'               => 'Finance Room',
                'main_classification' => 'Basic & Common Duties',
                'sub_classification'  => 'Finance Standard',
            ],
            [
                'row_no'              => 2,
                'employee_id'         => '126618',
                'name'                => 'PRADIPTA CAHYA UTAMA',
                'course_name'         => $courseName,
                'batch'               => 'Batch 1',
                'start_date'          => $testDate,
                'end_date'            => $testDate,
                'venue'               => 'Finance Room',
                'main_classification' => 'Compliance',
                'sub_classification'  => 'Finance Compliance',
            ],
        ]);

        $previewResp = $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $token = $previewResp->json('token');
        $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);

        $queryService = app(RecordOfflineQueryService::class);
        $results = $queryService->buildQuery(['course_name' => $courseName])->get();

        $targetRow = $results->firstWhere('employee_id', '126618');
        $this->assertNotNull($targetRow);
        $this->assertSame('Compliance', $targetRow->main_classification);
        $this->assertSame('Finance Compliance', $targetRow->sub_classification);
    }

    /**
     * Test E — Existing database training reuse:
     * When training already exists in DB with Compliance, importing a new participant
     * with Job Functional reuses the training and preserves Job Functional on the participant.
     */
    public function test_case_e_existing_db_training_reuse_preserves_participant_classification(): void
    {
        $existingTraining = OfflineTraining::create([
            'course_name'         => 'Existing Safety Certification E',
            'sub_course_name'     => 'General Safety',
            'batch'               => 'Batch 1',
            'start_date'          => '2026-10-01',
            'finish_date'         => '2026-10-01',
            'end_date'            => '2026-10-01',
            'venue'               => 'Safety Lab',
            'type'                => 'In-House',
            'main_classification' => 'Compliance',
            'sub_classification'  => 'EHS',
            'created_by'          => $this->adminUser->id,
        ]);

        $this->getOrCreateTestUser('EMP_E_NEW', 'New Reuse Participant');

        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_E_NEW',
                'course_name'         => 'Existing Safety Certification E',
                'sub_course_name'     => 'General Safety',
                'batch'               => 'Batch 1',
                'start_date'          => '2026-10-01',
                'end_date'            => '2026-10-01',
                'venue'               => 'Safety Lab',
                'type'                => 'In-House',
                'main_classification' => 'Job Functional',
                'sub_classification'  => 'Plant Safety',
            ],
        ]);

        $previewResp = $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $token = $previewResp->json('token');
        $confirmResp = $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);
        $confirmResp->assertStatus(200);

        // Verify training was reused and not duplicated
        $trainings = OfflineTraining::where('course_name', 'Existing Safety Certification E')->get();
        $this->assertCount(1, $trainings);
        $this->assertSame($existingTraining->id, $trainings->first()->id);
        $this->assertSame('Compliance', $trainings->first()->main_classification);

        // Verify participant stored with Job Functional
        $participant = OfflineTrainingUser::where('offline_training_id', $existingTraining->id)
            ->where('employee_id', 'EMP_E_NEW')
            ->first();
        $this->assertNotNull($participant);
        $this->assertSame('Job Functional', $participant->main_classification);
        $this->assertSame('Plant Safety', $participant->sub_classification);

        // Verify query service yields Job Functional
        $queryService = app(RecordOfflineQueryService::class);
        $row = $queryService->buildQuery(['course_name' => 'Existing Safety Certification E'])->get()->firstWhere('employee_id', 'EMP_E_NEW');
        $this->assertNotNull($row);
        $this->assertSame('Job Functional', $row->main_classification);
    }

    /**
     * Test F — Legacy record compatibility (Dual fallback):
     * When offline_training_users.main_classification is NULL,
     * the system falls back to offline_trainings.main_classification.
     */
    public function test_case_f_legacy_records_fallback_to_training_classification(): void
    {
        $legacyTraining = OfflineTraining::create([
            'course_name'         => 'Legacy Pre-Migration Course F',
            'batch'               => 'Batch 2025-Legacy',
            'start_date'          => '2025-05-01',
            'finish_date'         => '2025-05-01',
            'end_date'            => '2025-05-01',
            'venue'               => 'Room Legacy',
            'type'                => 'In-House',
            'main_classification' => 'Compliance',
            'sub_classification'  => 'Regulatory Standard',
            'created_by'          => $this->adminUser->id,
        ]);

        $legacyUser = $this->getOrCreateTestUser('EMP_LEG_01', 'Legacy User');

        // Create participant with explicit NULL classifications
        $participant = OfflineTrainingUser::create([
            'offline_training_id' => $legacyTraining->id,
            'user_id'             => $legacyUser->id,
            'employee_id'         => 'EMP_LEG_01',
            'name'                => 'Legacy User',
            'main_classification' => null,
            'sub_classification'  => null,
        ]);

        // 1. Verify Model Accessor Fallback
        $this->assertSame('Compliance', $participant->main_classification);
        $this->assertSame('Regulatory Standard', $participant->sub_classification);

        // 2. Verify Query Service COALESCE
        $queryService = app(RecordOfflineQueryService::class);
        $row = $queryService->buildQuery(['course_name' => 'Legacy Pre-Migration Course F'])->get()->firstWhere('employee_id', 'EMP_LEG_01');
        $this->assertNotNull($row);
        $this->assertSame('Compliance', $row->main_classification);
        $this->assertSame('Regulatory Standard', $row->sub_classification);

        // 3. Verify Filter Matching on Legacy Record
        $complianceMatches = $queryService->buildQuery(['course_name' => 'Legacy Pre-Migration Course F', 'main_classification' => 'Compliance'])->get();
        $this->assertTrue($complianceMatches->contains('employee_id', 'EMP_LEG_01'));

        $jobFunctionalMatches = $queryService->buildQuery(['course_name' => 'Legacy Pre-Migration Course F', 'main_classification' => 'Job Functional'])->get();
        $this->assertFalse($jobFunctionalMatches->contains('employee_id', 'EMP_LEG_01'));
    }

    /**
     * Test G — Round-trip integration test:
     * Excel upload -> Preview -> Confirm Import -> Query -> Export row mapping.
     */
    public function test_case_g_round_trip_excel_preview_persist_query_export_integrity(): void
    {
        $this->getOrCreateTestUser('EMP_RT_01', 'RoundTrip User 1');
        $this->getOrCreateTestUser('EMP_RT_02', 'RoundTrip User 2');

        $courseName = 'Round Trip Full Verification Course G';
        $testDate = '2026-11-01';

        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_RT_01',
                'course_name'         => $courseName,
                'batch'               => 'Batch RT',
                'start_date'          => $testDate,
                'end_date'            => $testDate,
                'venue'               => 'Auditorium',
                'main_classification' => 'Job Functional',
                'sub_classification'  => 'Mechanical Engineering',
            ],
            [
                'row_no'              => 2,
                'employee_id'         => 'EMP_RT_02',
                'course_name'         => $courseName,
                'batch'               => 'Batch RT',
                'start_date'          => $testDate,
                'end_date'            => $testDate,
                'venue'               => 'Auditorium',
                'main_classification' => 'Compliance',
                'sub_classification'  => 'Environmental Standard',
            ],
        ]);

        // Step 1: Preview verification
        $previewResp = $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $previewResp->assertStatus(200);
        $previewJson = $previewResp->json();
        $this->assertSame('Job Functional', $previewJson['rows'][0]['data']['main_classification']);
        $this->assertSame('Mechanical Engineering', $previewJson['rows'][0]['data']['sub_classification']);
        $this->assertSame('Compliance', $previewJson['rows'][1]['data']['main_classification']);
        $this->assertSame('Environmental Standard', $previewJson['rows'][1]['data']['sub_classification']);

        // Step 2: Confirm import
        $token = $previewJson['token'];
        $confirmResp = $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);
        $confirmResp->assertStatus(200);

        // Step 3: Query via RecordOfflineQueryService
        $queryService = app(RecordOfflineQueryService::class);
        $rows = $queryService->buildQuery(['course_name' => $courseName])->get();

        $rt1 = $rows->firstWhere('employee_id', 'EMP_RT_01');
        $rt2 = $rows->firstWhere('employee_id', 'EMP_RT_02');

        $this->assertNotNull($rt1);
        $this->assertNotNull($rt2);
        $this->assertSame('Job Functional', $rt1->main_classification);
        $this->assertSame('Mechanical Engineering', $rt1->sub_classification);
        $this->assertSame('Compliance', $rt2->main_classification);
        $this->assertSame('Environmental Standard', $rt2->sub_classification);

        // Step 4: Map through OfflineRecordExport
        $export = new OfflineRecordExport();
        $exportRow1 = $export->map($rt1);
        $exportRow2 = $export->map($rt2);

        $this->assertSame('Job Functional', $exportRow1[14]);
        $this->assertSame('Mechanical Engineering', $exportRow1[15]);

        $this->assertSame('Compliance', $exportRow2[14]);
        $this->assertSame('Environmental Standard', $exportRow2[15]);
    }
}
