<?php

namespace Tests\Feature;

use App\Exports\Manage\ClassificationOnlineRecordExport;
use App\Services\Record\ManageOnlineRecordExportService;
use Tests\TestCase;

class ManageOnlineRecordExportServiceTest extends TestCase
{
    public const EXPECTED_51_COLUMNS = [
        'NO',
        'Employee ID',
        'Employee Name',
        'Grade',
        'Job Position',
        'Team',
        'Department',
        'Company',
        'Course Title',
        'Sub Course Title',
        'Type',
        'Category',
        'Level',
        'Batch',
        'Main Classification',
        'Sub Classification',
        'Delivery Method',
        'Sub Delivery Method',
        'Provider',
        'Sub Provider',
        'Provider Name',
        'Venue',
        'Instructor',
        'Course Duration',
        'Total Recognized Duration',
        'Learning Hours',
        '% Progress',
        'Progress Status',
        'Start Date',
        'End Date',
        'Start Time',
        'End Time',
        'Record Month',
        'Requirement',
        'Attendance',
        'Attendance Reason',
        'Bond Status',
        'Bond Duration',
        'Bond End Date',
        'Bond Amount',
        'Score (L1) - Course',
        'Score (L2)',
        'L2 Status',
        'Score (L3)',
        'L3 Status',
        'Certificate Availability',
        'Certificate Institution',
        'Certificate Issued Date',
        'Certificate Expired Date',
        'Person in Charge (PIC)',
        'Total Engagement Time',
    ];

    /** @test */
    public function service_headings_strictly_match_kp_owl_51_columns_specification()
    {
        $headings = ManageOnlineRecordExportService::headings();
        $this->assertCount(51, $headings);
        $this->assertSame(self::EXPECTED_51_COLUMNS, $headings);
        $this->assertNotContains('Source', $headings);
        $this->assertEquals('Employee ID', $headings[1]);
        $this->assertEquals('Sub Course Title', $headings[9]);
        $this->assertEquals('Total Engagement Time', $headings[50]);
    }

    /** @test */
    public function classification_online_record_export_headers_match_service_headings()
    {
        $classHeaders = ClassificationOnlineRecordExport::headers();
        $this->assertCount(51, $classHeaders);
        $this->assertSame(self::EXPECTED_51_COLUMNS, $classHeaders);
    }

    /** @test */
    public function column_widths_cover_exactly_a_to_ay()
    {
        $widths = ManageOnlineRecordExportService::columnWidths();
        $this->assertCount(51, $widths);
        $this->assertArrayHasKey('A', $widths);
        $this->assertArrayHasKey('B', $widths);
        $this->assertArrayHasKey('AY', $widths);
        $this->assertArrayNotHasKey('AZ', $widths);
    }

    /** @test */
    public function sub_course_title_is_strictly_dash_and_parent_playlist_is_resolved()
    {
        $dummyRecord = (object) [
            'employee_id' => '001234',
            'user_name' => 'John Doe',
            'grade' => 'G3',
            'job_title' => 'Software Engineer',
            'team' => 'DevOps',
            'department' => 'IT',
            'company' => 'PT Krakatau Posco',
            'content_id' => 999999,
            'trainingplan_id' => 101,
            'playlist_title' => 'Leadership Masterclass',
            'title' => 'Test Course Under Playlist',
            'course_title' => 'Test Course Under Playlist',
            'category' => 'Content Playlist',
            'type' => 'Online',
            'level' => 'Basic',
            'batch' => 'Batch 1',
            'main_classification' => 'Technical',
            'sub_classification' => 'Backend',
            'delivery_method' => 'E-Learning',
            'sub_delivery_method' => 'Self-Paced',
            'provider' => 'Internal',
            'sub_provider' => '-',
            'provider_name' => 'Internal KP',
            'venue' => 'LMS',
            'instructor' => 'Trainer A',
            'duration' => 7200,
            'learning_hours' => 2.0,
            'progress' => 100,
            'progress_status' => 'Finished',
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-15',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'record_month' => 'January',
            'requirement' => 'Mandatory',
            'attendance' => 'Present',
            'attendance_reason' => '-',
            'bond_status' => 'No',
            'bond_duration' => 0,
            'bond_end_date' => null,
            'bond_amount' => 0,
            'score_l1' => 85.5,
            'score_l2' => 90.0,
            'l2_status' => 'Passed',
            'score_l3' => 88.0,
            'l3_status' => 'Passed',
            'certificate_availability' => 'Available',
            'certificate_institution' => 'HRDC',
            'certificate_issued_date' => '2026-01-16',
            'certificate_expired_date' => null,
            'pic' => 'PIC Team',
            'total_engagement_time' => 7200,
        ];

        $service = app(ManageOnlineRecordExportService::class);
        $mapped = $service->mapRow($dummyRecord, 1);
        $values = array_values(array_slice($mapped, 0, 51));
        $this->assertCount(51, $values);
        $this->assertEquals(1, $mapped['no']);
        $this->assertEquals('001234', $mapped['employee_id']);
        $this->assertEquals('Test Course Under Playlist', $mapped['course_title']);
        $this->assertEquals('-', $mapped['sub_course_title']); // Sub Course Title strictly '-'
        $this->assertEquals('Content Playlist', $mapped['category']);
        $this->assertEquals('Leadership Masterclass', $mapped['playlist']);
    }

    /** @test */
    public function pure_playlist_and_standalone_content_mappings()
    {
        $service = app(ManageOnlineRecordExportService::class);

        // Pure playlist
        $playlistRecord = (object) [
            'id_emp' => '10001',
            'user_name' => 'Jane Doe',
            'trainingplan_id' => 50,
            'content_id' => null,
            'source_type' => 'playlist',
            'playlist_title' => 'Complete Leadership Track',
            'learning_hours' => 10.5,
            'progress' => 50,
        ];
        $mappedPl = $service->mapRow($playlistRecord, 1);
        $this->assertEquals('Playlist', $mappedPl['category']);
        $this->assertEquals('Complete Leadership Track', $mappedPl['course_title']);
        $this->assertEquals('-', $mappedPl['sub_course_title']);
        $this->assertEquals('-', $mappedPl['playlist']);

        // Standalone content
        $contentRecord = (object) [
            'id_emp' => '10002',
            'user_name' => 'Bob Smith',
            'trainingplan_id' => null,
            'content_id' => 77,
            'source_type' => 'content',
            'content_title' => 'Cybersecurity Basics',
            'learning_hours' => 1.5,
            'progress' => 100,
            'finish' => '1',
        ];
        $mappedCt = $service->mapRow($contentRecord, 2);
        $this->assertEquals('All Content', $mappedCt['category']);
        $this->assertEquals('Cybersecurity Basics', $mappedCt['course_title']);
        $this->assertEquals('-', $mappedCt['sub_course_title']);
        $this->assertEquals('-', $mappedCt['playlist']);
    }

    /** @test */
    public function spreadsheet_formats_columns_explicitly()
    {
        $service = app(ManageOnlineRecordExportService::class);
        $spreadsheet = $service->exportSpreadsheet(['search' => 'NONEXISTENT_QUERY_FOR_TEST_ONLY_XYZ']);
        $sheet = $spreadsheet->getActiveSheet();

        // Header check
        $this->assertEquals('NO', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Employee ID', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Total Engagement Time', $sheet->getCell('AY1')->getValue());

        // Width check
        $this->assertEquals(6, $sheet->getColumnDimension('A')->getWidth());
        $this->assertEquals(16, $sheet->getColumnDimension('B')->getWidth());
        $this->assertEquals(25, $sheet->getColumnDimension('AY')->getWidth());
    }
}
