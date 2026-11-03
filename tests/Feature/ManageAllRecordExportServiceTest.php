<?php

namespace Tests\Feature;

use App\Exports\AllRecordExport;
use App\Exports\Manage\ClassificationAllRecordExport;
use App\Services\Record\ManageAllRecordExportService;
use Tests\TestCase;

class ManageAllRecordExportServiceTest extends TestCase
{
    public const EXPECTED_52_COLUMNS = [
        'NO',
        'Source',
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
    public function service_headings_strictly_match_all_record_52_columns_specification()
    {
        $headings = ManageAllRecordExportService::headings();
        $this->assertCount(52, $headings);
        $this->assertSame(self::EXPECTED_52_COLUMNS, $headings);
        $this->assertEquals('NO', $headings[0]);
        $this->assertEquals('Source', $headings[1]);
        $this->assertEquals('Employee ID', $headings[2]);
        $this->assertEquals('Course Title', $headings[9]);
        $this->assertEquals('Sub Course Title', $headings[10]);
        $this->assertEquals('Total Engagement Time', $headings[51]);
    }

    /** @test */
    public function all_record_export_headings_match_service_headings()
    {
        $allHeadings = AllRecordExport::headings();
        $this->assertCount(52, $allHeadings);
        $this->assertSame(self::EXPECTED_52_COLUMNS, $allHeadings);

        $classHeadings = (new ClassificationAllRecordExport('leadership'))->headings();
        $this->assertCount(52, $classHeadings);
        $this->assertSame(self::EXPECTED_52_COLUMNS, $classHeadings);
    }

    /** @test */
    public function column_widths_cover_exactly_a_to_az()
    {
        $widths = ManageAllRecordExportService::columnWidths();
        $this->assertCount(52, $widths);
        $this->assertArrayHasKey('A', $widths);
        $this->assertArrayHasKey('B', $widths);
        $this->assertArrayHasKey('C', $widths);
        $this->assertArrayHasKey('AZ', $widths);
    }

    /** @test */
    public function sub_course_title_logic_and_offline_record_mapping()
    {
        $service = app(ManageAllRecordExportService::class);

        $offlineRecord = (object) [
            'source' => 'offline',
            'employee_id' => '009876',
            'employee_name' => 'Budi Santoso',
            'grade' => 'G4',
            'job_position' => 'Plant Supervisor',
            'team' => 'Blast Furnace Team',
            'department' => 'Ironmaking Department',
            'company' => 'PT Krakatau Posco',
            'learning_title' => 'Advanced Safety & Metallurgy',
            'sub_course_name' => 'Furnace Heat Management Module 1',
            'type' => 'In-House',
            'level' => 'Advanced',
            'batch' => 'Batch 3',
            'classification' => 'Safety & Health',
            'sub_classification' => 'Metallurgy',
            'delivery_method' => 'Offline',
            'sub_delivery_method' => 'Classroom',
            'provider' => 'External',
            'sub_provider' => 'Safety Institute',
            'provider_name' => 'Krakatau Training Center',
            'venue' => 'Room 301',
            'instructor' => 'Ir. Hartono, M.T.',
            'duration_seconds' => 14400,
            'learning_hours' => 4.0,
            'progress' => 100,
            'status' => 'Finished',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-02',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'record_month' => 'February',
            'requirement' => 'Mandatory',
            'attendance' => 'Yes',
            'attendance_reason' => '-',
            'bond_status' => 'Yes',
            'bond_duration' => 12,
            'bond_end_date' => '2027-02-02',
            'bond_amount_idr' => 15000000,
            'l1_program' => 88.5,
            'assessment_score' => 92.0,
            'l2_verbal' => 'Passed',
            'l3_numerical' => 90.0,
            'l3_verbal' => 'Passed',
            'certificate' => 'PT Krakatau Posco',
            'pic' => 'Admin Team',
        ];

        $mapped = $service->mapRow($offlineRecord, 1);
        $values = array_values(array_slice($mapped, 0, 52));

        $this->assertCount(52, $values);
        $this->assertEquals(1, $mapped['no']);
        $this->assertEquals('Non KP OWL', $mapped['source']);
        $this->assertEquals('009876', $mapped['employee_id']);
        $this->assertEquals('Advanced Safety & Metallurgy', $mapped['course_title']);
        $this->assertEquals('Furnace Heat Management Module 1', $mapped['sub_course_title']); // Standard catalog sub-course title
        $this->assertEquals('Non KP-OWL', $mapped['category']);
        $this->assertEquals(12, $mapped['bond_duration']);
        $this->assertEquals(15000000, $mapped['bond_amount']);
        $this->assertEquals(92.0, $mapped['score_l2']);
        $this->assertEquals('Passed', $mapped['l2_status']);
    }

    /** @test */
    public function online_record_sub_course_title_strictly_dash()
    {
        $service = app(ManageAllRecordExportService::class);

        $onlineRecord = (object) [
            'source' => 'online',
            'source_type' => 'content',
            'employee_id' => '001122',
            'employee_name' => 'Siti Rahma',
            'grade' => 'G2',
            'job_position' => 'Junior Analyst',
            'team' => 'Finance Team',
            'department' => 'Finance',
            'company' => 'PT Krakatau Posco',
            'learning_title' => 'Financial Reporting Standards',
            'sub_course_name' => 'Irrelevant Sub Course',
            'type' => 'Video',
            'learning_hours' => 2.5,
            'progress' => 100,
            'status' => 'Finished',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-02',
            'start_time' => '09:00:00',
            'end_time' => '11:30:00',
        ];

        $mapped = $service->mapRow($onlineRecord, 2);
        $this->assertEquals('KP OWL', $mapped['source']);
        $this->assertEquals('001122', $mapped['employee_id']);
        $this->assertEquals('Financial Reporting Standards', $mapped['course_title']);
        $this->assertEquals('-', $mapped['sub_course_title']); // strictly '-' for online
        $this->assertEquals('All Content', $mapped['category']);
    }

    /** @test */
    public function spreadsheet_exports_with_52_columns_and_proper_styling()
    {
        $service = app(ManageAllRecordExportService::class);
        $spreadsheet = $service->exportSpreadsheet(['search' => 'NONEXISTENT_FILTER_STRING_12345']);
        $sheet = $spreadsheet->getActiveSheet();

        // Header check
        $this->assertEquals('NO', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Source', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Employee ID', $sheet->getCell('C1')->getValue());
        $this->assertEquals('Course Title', $sheet->getCell('J1')->getValue());
        $this->assertEquals('Sub Course Title', $sheet->getCell('K1')->getValue());
        $this->assertEquals('Total Engagement Time', $sheet->getCell('AZ1')->getValue());

        // Width check
        $this->assertEquals(6, $sheet->getColumnDimension('A')->getWidth());
        $this->assertEquals(14, $sheet->getColumnDimension('B')->getWidth());
        $this->assertEquals(16, $sheet->getColumnDimension('C')->getWidth());
        $this->assertEquals(25, $sheet->getColumnDimension('AZ')->getWidth());
    }
}
