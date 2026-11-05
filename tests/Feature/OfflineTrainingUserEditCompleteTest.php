<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineTrainingUserEditCompleteTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /**
     * Test target record 515 edit page loads successfully and displays the 3 main sections matching Create page.
     */
    public function test_target_record_515_edit_page_loads_with_all_sections(): void
    {
        $record = OfflineTrainingUser::find(515);
        if (!$record) {
            $this->markTestSkipped('Record 515 does not exist in database.');
        }

        $response = $this->actingAs($this->admin)
            ->get('/manage/record/offline/515/edit');

        $response->assertStatus(200);

        // Assert Title & Header info
        $response->assertSee('Edit Offline Training User');
        $response->assertSee('INDRO ARIO PRAKOSO');
        $response->assertSee('101203');

        // Assert the 3 main sections matching Create page structure
        $response->assertSee('Training Information');
        $response->assertSee('Participant Information');
        $response->assertSee('Learning Results');

        // Assert all subsections
        $response->assertSee('General Information');
        $response->assertSee('Classification &amp; KPI', false);
        $response->assertSee('Delivery &amp; Provider', false);
        $response->assertSee('Schedule &amp; Duration Program Evaluation', false);
        $response->assertSee('Learning Progress &amp; Assessment', false);
        $response->assertSee('Certificate Details &amp; Validity', false);
        $response->assertSee('Attendance');
        $response->assertSee('Obligation (Service Bond)');
        $response->assertSee('Kirkpatrick Evaluation (L2 - L3)');

        // Assert System Information & Audit Metadata is completely REMOVED from UI
        $response->assertDontSee('System Information');
        $response->assertDontSee('Audit Metadata');
        $response->assertDontSee('created_at');
        $response->assertDontSee('updated_at');
        $response->assertDontSee('deleted_at');

        // Assert populated values for record 515
        $response->assertSee('value="101203"', false);
        $response->assertSee('value="INDRO ARIO PRAKOSO"', false);
        $response->assertSee('value="62"', false); // User account ID
        $response->assertSee('Blast Furnace and Sinter Technology Team');
        $response->assertSee('Ironmaking Department');
        $response->assertSee('PT Krakatau Posco');
        $response->assertSee('value="Innovation"', false);
        $response->assertSee('General Skill');
        $response->assertSee('value="3.0000"', false);
        $response->assertSee('value="2026-08-10"', false);
        $response->assertSee('value="08:00"', false);
        $response->assertSee('value="17:00"', false);
        $response->assertSee('value="100"', false); // progress
        $response->assertSee('value="116"', false); // assessment_score
        $response->assertSee('value="Yes"', false); // certificate
        $response->assertSee('Present in all sessions');
        $response->assertSee('value="4.5"', false); // l1_program
        $response->assertSee('value="116"', false); // l2_numerical
        $response->assertSee('value="90"', false); // l3_numerical
        $response->assertSee('value="Naffis"', false); // pic
    }

    /**
     * Test updating record persists all offline_training_users columns.
     */
    public function test_update_persists_all_fields_to_offline_training_user(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Test Audit Training Course',
            'sub_course_name'     => 'Module Alpha',
            'type'                => 'In-House',
            'category'            => 'Technical',
            'level'               => 'Basic',
            'batch'               => 'Batch 1',
            'main_classification' => 'Technical',
            'sub_classification'  => 'Mechanical',
            'start_date'          => '2026-09-01',
            'finish_date'         => '2026-09-02',
            'start_time'          => '08:00:00',
            'end_time'            => '16:00:00',
            'learning_hours'      => 8.0,
        ]);

        $user = OfflineTrainingUser::create([
            'offline_training_id'      => $training->id,
            'employee_id'              => 'TESTAUDIT999',
            'name'                     => 'TEST PARTICIPANT',
            'grade'                    => '3A',
            'job_position'             => 'Technician',
            'team'                     => 'Alpha Team',
            'department'               => 'Maintenance',
            'company'                  => 'PT Krakatau Posco',
            'main_classification'      => 'Technical',
            'sub_classification'       => 'Mechanical',
            'learning_hours'           => 8.0,
            'start_date'               => '2026-09-01',
            'end_date'                 => '2026-09-02',
            'start_time'               => '08:00:00',
            'end_time'                 => '16:00:00',
            'progress'                 => 50.0,
            'assessment_score'         => 80.0,
            'certificate'              => 'No',
            'attendance'               => true,
            'attendance_reason'        => 'Present',
            'bond_status'              => 'No',
            'l1_program'               => '4.0',
            'l2_numerical'             => 80.0,
            'l2_verbal'                => 'Good',
            'l3_numerical'             => 75.0,
            'l3_verbal'                => 'Applied',
            'pic'                      => 'PIC Test',
        ]);

        try {
            $updatePayload = [
                'course_name'               => 'Test Audit Training Course Updated',
                'employee_id'               => 'TESTAUDIT999',
                'name'                      => 'TEST PARTICIPANT RENAMED',
                'grade'                     => '4B',
                'job_position'              => 'Lead Technician',
                'team'                      => 'Beta Team',
                'department'                => 'Engineering',
                'company'                   => 'PT Krakatau Posco',
                'main_classification'       => 'Innovation',
                'sub_classification'        => 'Digital Transformation',
                'learning_hours'            => '12.5000',
                'start_date'                => '2026-10-10',
                'end_date'                  => '2026-10-12',
                'start_time'                => '09:00',
                'end_time'                  => '17:00',
                'progress'                  => '100.00',
                'assessment_score'          => '116.50', // Testing score > 100!
                'certificate'               => 'Yes',
                'certificate_issued_date'   => '2026-10-12',
                'certificate_expired_date'  => '2028-10-12',
                'attendance'                => '1',
                'attendance_reason'         => 'Full attendance across all 3 days',
                'bond_status'               => 'Yes',
                'bond_duration'             => '24',
                'bond_end_date'             => '2028-10-12',
                'bond_amount_idr'           => '15000000',
                'l1_program'                => '4.85 Excellent',
                'l2_numerical'              => '116.5000',
                'l2_verbal'                 => 'Excellent',
                'l3_numerical'              => '95.0000',
                'l3_verbal'                 => 'Fully Implemented',
                'pic'                       => 'Senior Trainer',
            ];

            $response = $this->actingAs($this->admin)
                ->putJson(route('manage.record.offline.update', $user->id), $updatePayload);

            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            // Refresh user and verify all fields persisted to DB
            $user->refresh();

            $this->assertEquals('TEST PARTICIPANT RENAMED', $user->name);
            $this->assertEquals('4B', $user->grade);
            $this->assertEquals('Lead Technician', $user->job_position);
            $this->assertEquals('Beta Team', $user->team);
            $this->assertEquals('Engineering', $user->department);
            $this->assertEquals('Innovation', $user->getRawOriginal('main_classification'));
            $this->assertEquals('Digital Transformation', $user->getRawOriginal('sub_classification'));
            $this->assertEquals('12.5000', $user->learning_hours);
            $this->assertEquals('2026-10-10', $user->start_date->format('Y-m-d'));
            $this->assertEquals('2026-10-12', $user->end_date->format('Y-m-d'));
            $this->assertEquals('09:00:00', $user->start_time);
            $this->assertEquals('17:00:00', $user->end_time);
            $this->assertEquals('100.0000', $user->progress);
            $this->assertEquals('116.50', $user->assessment_score);
            $this->assertEquals('Yes', $user->certificate);
            $this->assertEquals('2026-10-12', $user->certificate_issued_date->format('Y-m-d'));
            $this->assertEquals('2028-10-12', $user->certificate_expired_date->format('Y-m-d'));
            $this->assertTrue((bool) $user->attendance);
            $this->assertEquals('Full attendance across all 3 days', $user->attendance_reason);
            $this->assertEquals('Yes', $user->bond_status);
            $this->assertEquals(24, $user->bond_duration);
            $this->assertEquals('2028-10-12', $user->bond_end_date->format('Y-m-d'));
            $this->assertEquals('15000000.00', $user->bond_amount_idr);
            $this->assertEquals('4.85 Excellent', $user->l1_program);
            $this->assertEquals('116.5000', $user->l2_numerical);
            $this->assertEquals('Excellent', $user->l2_verbal);
            $this->assertEquals('95.0000', $user->l3_numerical);
            $this->assertEquals('Fully Implemented', $user->l3_verbal);
            $this->assertEquals('Senior Trainer', $user->pic);

        } finally {
            $user->delete();
            $training->delete();
        }
    }

    /**
     * Test zero values (0, 0.00, false) and nullable fields are handled properly without data corruption.
     */
    public function test_zero_values_and_nullables_handled_correctly(): void
    {
        $training = OfflineTraining::create([
            'course_name' => 'Zero Values Test Course',
        ]);

        $user = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'employee_id'         => 'ZEROTEST001',
            'name'                => 'ZERO TEST PARTICIPANT',
            'progress'            => 100.0,
            'assessment_score'    => 85.0,
            'attendance'          => true,
            'bond_status'         => 'Yes',
            'bond_duration'       => 12,
            'bond_amount_idr'     => 5000000,
        ]);

        try {
            $response = $this->actingAs($this->admin)
                ->putJson(route('manage.record.offline.update', $user->id), [
                    'course_name'      => 'Zero Values Test Course',
                    'employee_id'      => 'ZEROTEST001',
                    'name'             => 'ZERO TEST PARTICIPANT',
                    'progress'         => 0, // Zero progress
                    'assessment_score' => 0, // Zero score
                    'attendance'       => 0, // Absent
                    'attendance_reason'=> 'Sick leave with medical certificate',
                    'bond_status'      => 'No',
                    'grade'            => null,
                    'job_position'     => null,
                ]);

            $response->assertStatus(200);
            $user->refresh();

            $this->assertEquals('0.0000', $user->progress);
            $this->assertEquals('0.00', $user->assessment_score);
            $this->assertFalse((bool) $user->attendance);
            $this->assertEquals('Sick leave with medical certificate', $user->attendance_reason);
            $this->assertEquals('No', $user->bond_status);
            $this->assertNull($user->bond_duration);
            $this->assertNull($user->grade);
            $this->assertNull($user->job_position);
        } finally {
            $user->delete();
            $training->delete();
        }
    }

    /**
     * Test validation fails when end_date is earlier than start_date.
     */
    public function test_validation_rejects_invalid_date_order(): void
    {
        $training = OfflineTraining::create([
            'course_name' => 'Validation Test Course',
            'start_date'  => '2026-09-01',
            'finish_date' => '2026-09-02',
        ]);

        $user = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'employee_id'         => 'VALTEST001',
            'name'                => 'VAL PARTICIPANT',
        ]);

        try {
            $response = $this->actingAs($this->admin)
                ->putJson(route('manage.record.offline.update', $user->id), [
                    'course_name' => 'Validation Test Course',
                    'employee_id' => 'VALTEST001',
                    'name'        => 'VAL PARTICIPANT',
                    'start_date'  => '2026-09-10',
                    'end_date'    => '2026-09-05', // Earlier than start_date!
                ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['finish_date']);
        } finally {
            $user->delete();
            $training->delete();
        }
    }

    /**
     * Test system metadata fields (id, created_at, updated_at, deleted_at) cannot be overridden by user input.
     */
    public function test_system_metadata_cannot_be_overridden(): void
    {
        $training = OfflineTraining::create([
            'course_name' => 'Security Audit Training',
        ]);

        $originalCreatedAt = '2026-01-01 10:00:00';
        $user = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'employee_id'         => 'SECTEST001',
            'name'                => 'SEC PARTICIPANT',
            'created_at'          => $originalCreatedAt,
        ]);

        $originalId = $user->id;

        try {
            $response = $this->actingAs($this->admin)
                ->putJson(route('manage.record.offline.update', $user->id), [
                    'course_name' => 'Security Audit Training',
                    'employee_id' => 'SECTEST001',
                    'name'        => 'SEC PARTICIPANT UPDATED',
                    'id'          => 99999,
                    'created_at'  => '2020-01-01 00:00:00',
                    'deleted_at'  => '2026-11-01 00:00:00',
                ]);

            $response->assertStatus(200);
            $user->refresh();

            $this->assertEquals($originalId, $user->id);
            $this->assertNull($user->deleted_at);
            $this->assertEquals('SEC PARTICIPANT UPDATED', $user->name);
        } finally {
            $user->delete();
            $training->delete();
        }
    }

    /**
     * Test target record 588 edit page loads and all dropdowns have clear (X) icon disabled
     * while retaining dropdown arrow / chevron functionality.
     */
    public function test_target_record_588_edit_page_loads_with_dropdowns_without_clear_x_icon(): void
    {
        $record = OfflineTrainingUser::find(588);
        if (!$record) {
            $this->markTestSkipped('Record 588 does not exist in database.');
        }

        $response = $this->actingAs($this->admin)
            ->get('/manage/record/offline/588/edit');

        $response->assertStatus(200);

        // Assert record 588 participant and training data
        $response->assertSee('RUSJAYA');
        $response->assertSee('110727');
        $response->assertSee('QSS Improvement Leader');
        $response->assertSee('Personalized Skill Development');
        $response->assertSee('Innovation');
        $response->assertSee('QSS');

        // Assert that all dropdown fields have data-allow-clear="false"
        $dropdownFields = [
            'sub_course_name',
            'type',
            'category',
            'level',
            'main_classification',
            'sub_classification',
            'delivery_method',
            'sub_delivery_method',
            'provider',
            'sub_provider',
            'provider_name',
            'venue',
            'instructor',
            'l2_verbal',
            'l3_verbal',
            'pic',
        ];

        foreach ($dropdownFields as $field) {
            $response->assertSee('name="' . $field . '"', false);
        }

        // Assert that none of the dropdowns have allowClear set to true
        $response->assertDontSee('data-allow-clear="true"');

        // Assert that CSS specifically hides .select2-selection__clear while ensuring .select2-selection__arrow remains visible
        $response->assertSee('.select2-selection__clear', false);
        $response->assertSee('display: none !important', false);
        $response->assertSee('.select2-selection__arrow', false);

        // Assert Select2 script initialization strictly disables allowClear
        $response->assertSee('allowClear: false', false);
    }
}
