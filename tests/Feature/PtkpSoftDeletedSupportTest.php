<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PtkpSoftDeletedSupportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $resignedUser;
    protected User $activeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_SUPER_ADMIN]);

        // Resigned user with deleted_at IS NOT NULL
        $this->resignedUser = $this->createTestUser([
            'id_emp'       => 'RESIGNED_999',
            'name'         => 'Resigned Employee Name',
            'jobgrade'     => 'Senior Staff',
            'jobposition'  => 'Mechanical Engineer',
            'team'         => 'Blast Furnace Team',
            'department'   => 'Ironmaking Department',
            'company_name' => 'PT Krakatau Posco',
        ]);
        $this->resignedUser->delete(); // Soft delete

        // Active user with deleted_at IS NULL
        $this->activeUser = $this->createTestUser([
            'id_emp'       => 'ACTIVE_999',
            'name'         => 'Active Employee Name',
            'jobgrade'     => 'Lead Engineer',
            'jobposition'  => 'Process Automation',
            'team'         => 'Digital Transformation',
            'department'   => 'IT Department',
            'company_name' => 'PT Krakatau Posco',
        ]);
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 8800;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Test User ' . $seq,
            'email'        => "testuser_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'EMP_' . $seq,
            'id_user'      => 'UID_' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'Team Sample',
            'department'   => 'Dept Sample',
            'division'     => 'Division Sample',
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
                    $sheet->setCellValueExplicit("{$letter}{$rowIdx}", (string) ($data['employee_id'] ?? ''), DataType::TYPE_STRING);
                } elseif (array_key_exists($key, $data)) {
                    $sheet->setCellValue("{$letter}{$rowIdx}", $data[$key]);
                } else {
                    $val = $col['example'] ?? '';
                    if ($key === 'bond_amount' && ($val === '' || $val === null)) {
                        $val = 0;
                    }
                    $sheet->setCellValue("{$letter}{$rowIdx}", $val);
                }
            }
            $rowIdx++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_soft_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet, $writer);

        return new UploadedFile(
            $tempPath,
            'ptkp_soft_delete_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    /**
     * Test Case A: Resigned Employee (deleted_at IS NOT NULL) is FOUND and not marked INVALID ID.
     */
    public function test_case_a_resigned_employee_is_found_and_not_marked_invalid(): void
    {
        $this->assertTrue($this->resignedUser->trashed(), 'Prerequisite failed: user must be soft deleted');

        $file = $this->createPtkpExcel([
            [
                'row_no'       => 1,
                'employee_id'  => $this->resignedUser->id_emp,
                'name'         => 'Wrong Name In Excel',
                'grade'        => 'Wrong Grade In Excel',
                'job_position' => 'Wrong Position In Excel',
                'team'         => 'Wrong Team In Excel',
                'department'   => 'Wrong Dept In Excel',
                'company'      => 'PT Krakatau Posco',
                'course_name'  => 'Safety Awareness Course 2026',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertOk();
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals(1, $json['importable_count']);

        $row = $json['rows'][0];
        $this->assertEquals('VALID', $row['status']);
        $this->assertEquals($this->resignedUser->id, $row['data']['user_id']);
        $this->assertEquals($this->resignedUser->id_emp, $row['data']['employee_id']);

        // Verify 5 identity attributes are retrieved from database
        $this->assertEquals($this->resignedUser->name, $row['name']);
        $this->assertEquals($this->resignedUser->jobgrade, $row['data']['grade']);
        $this->assertEquals($this->resignedUser->jobposition, $row['data']['job_position']);
        $this->assertEquals($this->resignedUser->team, $row['data']['team']);
        $this->assertEquals($this->resignedUser->department, $row['data']['department']);

        // Confirm import and verify user remains soft-deleted in database
        $confirmResponse = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.confirm'), ['token' => $json['token']]);

        $confirmResponse->assertOk();
        $this->assertEquals(1, $confirmResponse->json('result.imported'));

        // Verify participant record was created and linked to the user
        $participant = OfflineTrainingUser::where('employee_id', $this->resignedUser->id_emp)->first();
        $this->assertNotNull($participant);
        $this->assertEquals($this->resignedUser->id, $participant->user_id);
        $this->assertEquals($this->resignedUser->name, $participant->name);

        // Verify user remains soft deleted (NOT restored)
        $this->resignedUser->refresh();
        $this->assertTrue($this->resignedUser->trashed());
        $this->assertNotNull($this->resignedUser->deleted_at);
    }

    /**
     * Test Case B: Active Employee is found and all info populated correctly.
     */
    public function test_case_b_active_employee_is_found_and_all_info_populated(): void
    {
        $this->assertFalse($this->activeUser->trashed());

        $file = $this->createPtkpExcel([
            [
                'row_no'       => 1,
                'employee_id'  => $this->activeUser->id_emp,
                'name'         => 'Some Other Name',
                'company'      => 'PT Krakatau Posco',
                'course_name'  => 'Digital Transformation Workshop',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertOk();
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['invalid']);

        $row = $json['rows'][0];
        $this->assertEquals('VALID', $row['status']);
        $this->assertEquals($this->activeUser->name, $row['name']);
        $this->assertEquals($this->activeUser->jobgrade, $row['data']['grade']);
        $this->assertEquals($this->activeUser->jobposition, $row['data']['job_position']);
        $this->assertEquals($this->activeUser->team, $row['data']['team']);
        $this->assertEquals($this->activeUser->department, $row['data']['department']);
    }

    /**
     * Test Case C: Nonexistent Employee is marked INVALID ID.
     */
    public function test_case_c_nonexistent_employee_is_marked_invalid_id(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'       => 1,
                'employee_id'  => 'TOTALLY_NONEXISTENT_999999',
                'company'      => 'PT Krakatau Posco',
                'course_name'  => 'Some Training Event',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertOk();
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
        $this->assertStringContainsString('not found', implode(' ', $json['rows'][0]['messages']));
    }

    /**
     * Test Case D: Soft Deleted Course is found, not marked INVALID, and not restored.
     */
    public function test_case_d_soft_deleted_course_is_found_and_not_marked_invalid_and_not_restored(): void
    {
        $uniqueCourseName = 'Historical Special Course ' . uniqid();

        // Create a training and soft delete it
        $training = OfflineTraining::create([
            'course_name'     => $uniqueCourseName,
            'title'           => $uniqueCourseName,
            'type'            => 'Public',
            'category'        => 'Technical',
            'level'           => 'Intermediate',
            'batch'           => 'BATCH-HIST-01',
            'start_date'      => '2026-03-01',
            'finish_date'     => '2026-03-02',
            'delivery_method' => 'Offline Classroom',
            'provider'        => 'Internal',
            'venue'           => 'Room 101',
            'learning_hours'  => 8.0,
            'created_by'      => $this->adminUser->id,
        ]);
        $training->delete(); // Soft delete

        $this->assertTrue($training->trashed(), 'Training must be soft deleted');

        $file = $this->createPtkpExcel([
            [
                'row_no'       => 1,
                'employee_id'  => $this->activeUser->id_emp,
                'company'      => 'PT Krakatau Posco',
                'course_name'  => $uniqueCourseName,
                'batch'        => 'BATCH-HIST-01',
                'start_date'   => '01/03/2026',
                'end_date'     => '02/03/2026',
                'type'         => 'Public',
                'venue'        => 'Room 101',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertOk();
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals('VALID', $json['rows'][0]['status']);

        // Matched training ID should be the soft deleted training ID
        $this->assertEquals($training->id, $json['rows'][0]['matched_training_id']);

        // Confirm import
        $confirmResponse = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.confirm'), ['token' => $json['token']]);

        $confirmResponse->assertOk();
        $this->assertEquals(1, $confirmResponse->json('result.imported'));
        $this->assertEquals(1, $confirmResponse->json('result.trainings_reused'));
        $this->assertEquals(0, $confirmResponse->json('result.trainings_created'));

        // Training must NOT be restored
        $training->refresh();
        $this->assertTrue($training->trashed(), 'Soft deleted training must remain soft deleted');
        $this->assertNotNull($training->deleted_at);
    }

    /**
     * Test Case E: Nonexistent / Empty Course Title is marked INVALID COURSE TITLE.
     */
    public function test_case_e_nonexistent_course_title_is_marked_invalid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'       => 1,
                'employee_id'  => $this->activeUser->id_emp,
                'company'      => 'PT Krakatau Posco',
                'course_name'  => '', // Empty / nonexistent course title
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertOk();
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
        $this->assertStringContainsString('Course Title is required', implode(' ', $json['rows'][0]['messages']));
    }

    /**
     * Test Case F: Duplicate Checking remains intact for soft-deleted user records.
     */
    public function test_case_f_duplicate_checking_remains_active_with_soft_deleted_users(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'       => 1,
                'employee_id'  => $this->resignedUser->id_emp,
                'company'      => 'PT Krakatau Posco',
                'course_name'  => 'Duplicate Safety Protocol 2026',
                'batch'        => 'BATCH-DUP-01',
                'start_date'   => '2026-04-10',
                'finish_date'  => '2026-04-10',
            ],
            [
                'row_no'       => 2,
                'employee_id'  => $this->resignedUser->id_emp,
                'company'      => 'PT Krakatau Posco',
                'course_name'  => 'Duplicate Safety Protocol 2026',
                'batch'        => 'BATCH-DUP-01',
                'start_date'   => '2026-04-10',
                'finish_date'  => '2026-04-10',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertOk();
        $json = $response->json();

        // Row 1 is VALID, Row 2 is DUPLICATE
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(1, $json['stats']['duplicate']);
        $this->assertEquals(0, $json['stats']['invalid']);

        $this->assertEquals('VALID', $json['rows'][0]['status']);
        $this->assertEquals('DUPLICATE', $json['rows'][1]['status']);
        $this->assertEquals('file', $json['rows'][1]['duplicate_source']);
    }

    /**
     * Test Case G: Real Database Employee 620742 (MUHAMAD REZA RACHMAT, User ID 3055).
     */
    public function test_case_g_real_database_resigned_employee_620742(): void
    {
        $realUser = User::withTrashed()->where('id_emp', '620742')->first();
        if (!$realUser) {
            $this->markTestSkipped('Employee 620742 not present in database.');
        }

        $this->assertTrue($realUser->trashed(), 'Employee 620742 must be soft deleted (resigned)');
        $this->assertEquals('620742', $realUser->id_emp);

        $file = $this->createPtkpExcel([
            [
                'row_no'       => 1,
                'employee_id'  => '620742',
                'name'         => 'Placeholder Excel Name',
                'grade'        => 'Placeholder Grade',
                'job_position' => 'Placeholder Position',
                'team'         => 'Placeholder Team',
                'department'   => 'Placeholder Dept',
                'company'      => 'PT Krakatau Posco',
                'course_name'  => 'Real World Employee Verification 2026',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertOk();
        $json = $response->json();

        $this->assertEquals(0, $json['stats']['invalid'], 'Employee 620742 must not be marked INVALID ID');
        $this->assertEquals(1, $json['importable_count']);

        $row = $json['rows'][0];
        $this->assertNotEquals('INVALID', $row['status']);
        $this->assertEquals($realUser->id, $row['data']['user_id']);

        // Check 5 canonical identity fields retrieved from User 3055
        $this->assertEquals($realUser->name, $row['name']);
        $this->assertEquals($realUser->jobgrade, $row['data']['grade']);
        $this->assertEquals($realUser->jobposition, $row['data']['job_position']);
        $this->assertEquals($realUser->team, $row['data']['team']);
        $this->assertEquals($realUser->department, $row['data']['department']);

        // Confirm import
        $confirmResponse = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.confirm'), ['token' => $json['token']]);

        $confirmResponse->assertOk();
        $this->assertEquals(1, $confirmResponse->json('result.imported'));

        // Real user must remain soft deleted
        $realUser->refresh();
        $this->assertTrue($realUser->trashed());
        $this->assertNotNull($realUser->deleted_at);
    }
}
