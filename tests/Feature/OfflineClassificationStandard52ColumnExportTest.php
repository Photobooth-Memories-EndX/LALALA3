<?php

namespace Tests\Feature;

use App\Exports\OfflineClassificationExport;
use App\Exports\OfflineCourseExport;
use App\Exports\OfflineParticipantExport;
use App\Exports\OfflineRecordExport;
use App\Models\OfflineTraining;
use App\Models\User;
use App\Services\Record\RecordOfflineQueryService;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class OfflineClassificationStandard52ColumnExportTest extends TestCase
{
    protected ?User $admin;

    public const CANONICAL_52_COLUMNS = [
        'No',
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
        'Total Engagement Time (LH)',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /**
     * Test 1: Verify all three new offline export classes have exactly 52 canonical headings.
     */
    public function test_all_offline_export_classes_have_exact_52_canonical_headings(): void
    {
        $classExport = new OfflineClassificationExport('compliance');
        $this->assertSame(self::CANONICAL_52_COLUMNS, $classExport->headings());
        $this->assertCount(52, $classExport->headings());

        $training = OfflineTraining::whereNull('deleted_at')->first() ?? new OfflineTraining(['course_name' => 'Test Course']);
        $training->id = $training->id ?? 9999;
        $courseExport = new OfflineCourseExport($training);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $courseExport->headings());
        $this->assertCount(52, $courseExport->headings());

        $participant = [
            'raw_identifier' => '12345',
            'employee_id'    => '12345',
            'name'           => 'Test User',
            'user_id'        => 1,
        ];
        $partExport = new OfflineParticipantExport($participant);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $partExport->headings());
        $this->assertCount(52, $partExport->headings());
    }

    /**
     * Test 2: Verify column widths span 52 columns from A to AZ.
     */
    public function test_all_offline_export_classes_have_column_widths_from_a_to_az(): void
    {
        $classes = [
            OfflineClassificationExport::class,
            OfflineCourseExport::class,
            OfflineParticipantExport::class,
        ];

        foreach ($classes as $class) {
            $widths = $class::columnWidths();
            $this->assertCount(52, $widths);
            $this->assertArrayHasKey('A', $widths);
            $this->assertArrayHasKey('Z', $widths);
            $this->assertArrayHasKey('AA', $widths);
            $this->assertArrayHasKey('AZ', $widths);
        }
    }

    /**
     * Test 3: Export endpoint for Basic Common Duties produces valid 52-column XLSX.
     */
    public function test_basic_common_duties_export_generates_valid_52_column_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/classification/basic-common-duties/export/excel');
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('non-kp-owl-basic-common-duties-', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath, ZipArchive::CHECKCONS) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml);
        $this->assertStringContainsString('r="A1"', $sheetXml);
        $this->assertStringContainsString('r="AZ1"', $sheetXml);
        $zip->close();
    }

    /**
     * Test 4: Export endpoint for Compliance produces valid 52-column XLSX.
     */
    public function test_compliance_export_generates_valid_52_column_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/classification/compliance/export/excel');
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('non-kp-owl-compliance-', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath, ZipArchive::CHECKCONS) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml);
        $this->assertStringContainsString('r="A1"', $sheetXml);
        $this->assertStringContainsString('r="AZ1"', $sheetXml);
        $zip->close();
    }

    /**
     * Test 5: Course detail export produces valid 52-column XLSX.
     */
    public function test_course_export_generates_valid_52_column_xlsx(): void
    {
        $training = OfflineTraining::whereNull('deleted_at')->find(3071) ?? OfflineTraining::whereNull('deleted_at')->first();
        if (!$training) {
            $this->markTestSkipped('No offline training in DB.');
        }

        $response = $this->actingAs($this->admin)->get("/manage/record/offline/course/{$training->id}/export/excel?classification=compliance");
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString("non-kp-owl-course-{$training->id}-", $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath, ZipArchive::CHECKCONS) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml);
        $this->assertStringContainsString('r="A1"', $sheetXml);
        $this->assertStringContainsString('r="AZ1"', $sheetXml);
        $zip->close();
    }

    /**
     * Test 6: Participant detail export produces valid 52-column XLSX.
     */
    public function test_participant_export_generates_valid_52_column_xlsx(): void
    {
        $rawId = DB::table('offline_training_users')->whereNotNull('employee_id')->where('employee_id', '!=', '')->value('employee_id') ?? '1158';
        $participant = RecordOfflineQueryService::resolveParticipant((string) $rawId);
        if (!$participant) {
            $this->markTestSkipped('No participant in DB.');
        }

        $response = $this->actingAs($this->admin)->get("/manage/record/offline/participant/{$participant['raw_identifier']}/export/excel?classification=compliance");
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('non-kp-owl-participant-', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath, ZipArchive::CHECKCONS) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml);
        $this->assertStringContainsString('r="A1"', $sheetXml);
        $this->assertStringContainsString('r="AZ1"', $sheetXml);
        $zip->close();
    }
}
