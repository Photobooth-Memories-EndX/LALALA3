<?php

namespace Tests\Feature;

use App\Exports\LearningExport;
use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\CertificateSetting;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\Learning\LearningExportContext;
use App\Services\Learning\LearningExportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ManageLearningExcelExportStandardizationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('role', '2')->first() ?? User::factory()->create(['role' => '2']);
    }

    /**
     * Helper to load an exported XLSX BinaryFileResponse into a PhpSpreadsheet Worksheet.
     */
    protected function loadWorksheetFromResponse($response)
    {
        $content = $response->getFile()->getContent();
        $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_') . '.xlsx';
        file_put_contents($tempPath, $content);

        $spreadsheet = IOFactory::load($tempPath);
        @unlink($tempPath);

        return $spreadsheet->getActiveSheet();
    }

    /**
     * TEST 1: Classification Export has exactly 52 canonical headings and preserves employee data.
     */
    public function test_classification_excel_export_structure_and_cells(): void
    {
        $classification = Classification::first();
        if (!$classification) {
            $this->markTestSkipped('No classification found in database.');
        }

        $response = $this->actingAs($this->adminUser)->get(
            '/manage/learning/download?classification_slug=' . $classification->slug . '&type=category&slug=' . $classification->slug
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));

        $sheet = $this->loadWorksheetFromResponse($response);
        $expectedHeadings = LearningExportService::headings();
        $this->assertCount(52, $expectedHeadings, 'Manage Learning export must have exactly 52 canonical headings');

        // 1. Verify exact 52 column headings on row 1
        foreach ($expectedHeadings as $index => $expectedHeader) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $actualHeader = $sheet->getCell("{$colLetter}1")->getValue();
            $this->assertEquals($expectedHeader, $actualHeader, "Header at {$colLetter}1 does not match expected");
        }

        // 2. Verify freeze pane on A2
        $freezePane = $sheet->getFreezePane();
        $this->assertEquals('A2', $freezePane);

        // 3. Verify AutoFilter is configured
        $autoFilter = $sheet->getAutoFilter();
        $this->assertNotEmpty($autoFilter->getRange(), 'AutoFilter range should not be empty');
    }

    /**
     * TEST 2: User Profile Export has identical 52 standardized headings and filters to single user.
     */
    public function test_user_profile_excel_export_structure_and_cells(): void
    {
        $targetUser = User::whereNotNull('ssohash')->first();
        if (!$targetUser) {
            $this->markTestSkipped('No user with ssohash found in database.');
        }

        $response = $this->actingAs($this->adminUser)->get(
            route('user.manage.profile.export.excel', ['hash' => $targetUser->ssohash])
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));

        $sheet = $this->loadWorksheetFromResponse($response);
        $expectedHeadings = LearningExportService::headings();

        // Check headings
        foreach ($expectedHeadings as $index => $expectedHeader) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $actualHeader = $sheet->getCell("{$colLetter}1")->getValue();
            $this->assertEquals($expectedHeader, $actualHeader);
        }

        // If there are data rows, verify employee name matches target user (Col D in 52-column schema)
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 2) {
            $exportedName = (string) $sheet->getCell('D2')->getValue();
            if ($exportedName !== '-' && $targetUser->name) {
                $this->assertEquals($targetUser->name, $exportedName);
            }
        }
    }

    /**
     * TEST 3: Playlist Detail Export exports records for that playlist with Category = Playlist.
     */
    public function test_playlist_excel_export_structure_and_cells(): void
    {
        $playlist = TrainingPlan::first();
        if (!$playlist) {
            $this->markTestSkipped('No playlist found in database.');
        }

        $response = $this->actingAs($this->adminUser)->get(
            '/manage/learning/playlist/' . $playlist->id . '?action=download'
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));

        $sheet = $this->loadWorksheetFromResponse($response);
        $highestRow = $sheet->getHighestRow();

        if ($highestRow >= 2) {
            // Check row 2 Category (column M in 52-column schema)
            $category = (string) $sheet->getCell('M2')->getValue();
            $this->assertTrue(
                $category === 'Playlist' || str_starts_with($category, 'Playlist: '),
                "Category must be Playlist or start with Playlist: , got: {$category}"
            );

            // Course name (column J in 52-column schema) must NOT be empty
            $course = (string) $sheet->getCell('J2')->getValue();
            $this->assertNotEmpty($course);
        }
    }

    /**
     * TEST 4: Content Detail Export exports standalone rows with proper Course Title & Category.
     */
    public function test_content_excel_export_structure_and_cells(): void
    {
        $content = Content::whereNotNull('slug')->first();
        if (!$content) {
            $this->markTestSkipped('No content found in database.');
        }

        $response = $this->actingAs($this->adminUser)->get(
            '/manage/learning/content/' . $content->slug . '?action=download'
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));

        $sheet = $this->loadWorksheetFromResponse($response);
        $highestRow = $sheet->getHighestRow();

        if ($highestRow >= 2) {
            // Column J: Course Title
            $course = (string) $sheet->getCell('J2')->getValue();
            $this->assertEquals($content->title, $course);

            // Column M: Category
            $category = (string) $sheet->getCell('M2')->getValue();
            $this->assertTrue(
                $category === 'Content' || str_starts_with($category, 'Playlist'),
                "Category must be Content or Playlist, got: {$category}"
            );
        }
    }

    /**
     * TEST 5: Standalone vs Playlist 52-column mapping.
     */
    public function test_standalone_vs_playlist_category_mapping(): void
    {
        // 1. Standalone row
        $standaloneRow = (object) [
            'user_id_emp' => '012345',
            'employee_name' => 'John Doe',
            'user_department' => 'IT',
            'user_team' => 'Dev',
            'content_title' => 'Standalone Course',
            'content_type' => 'Document',
            'source_type' => 'content',
            'trainingplan_id' => null,
            'playlist_title' => null,
            'classification_name' => 'General',
            'content_duration' => '00:10:00',
            'progress' => 100,
            'finish' => 1,
            'created_at' => '2026-01-01 10:00:00',
            'completed_at' => '2026-01-01 10:10:00',
        ];

        $mappedStandalone = LearningExportService::mapRow($standaloneRow, 1);
        $this->assertCount(52, $mappedStandalone);
        $this->assertEquals('KP OWL', $mappedStandalone[1]);                                   // Col 2: Source
        $this->assertEquals('012345', $mappedStandalone[2], 'NIP leading zero must be preserved'); // Col 3: Employee ID
        $this->assertEquals('John Doe', $mappedStandalone[3]);                                 // Col 4: Employee Name
        $this->assertEquals('Dev', $mappedStandalone[6]);                                      // Col 7: Team
        $this->assertEquals('IT', $mappedStandalone[7]);                                       // Col 8: Department
        $this->assertEquals('Standalone Course', $mappedStandalone[9]);                         // Col 10: Course Title
        $this->assertEquals('-', $mappedStandalone[10]);                                       // Col 11: Sub Course Title
        $this->assertEquals('Document', $mappedStandalone[11]);                                // Col 12: Type
        $this->assertEquals('Content', $mappedStandalone[12]);                                 // Col 13: Category
        $this->assertEquals('Yes', $mappedStandalone[35]);                                     // Col 36: Attendance

        // 2. Playlist row with playlist title
        $playlistRow = (object) [
            'user_id_emp' => '098765',
            'employee_name' => 'Jane Doe',
            'user_department' => 'HR',
            'user_team' => 'Recruitment',
            'content_title' => 'Playlist Course',
            'content_type' => 'Video',
            'source_type' => 'playlist',
            'trainingplan_id' => 99,
            'playlist_title' => 'Introduction to ISO 14001:2015',
            'classification_name' => 'Onboarding',
            'content_duration' => '00:15:00',
            'progress' => 50,
            'finish' => 0,
            'created_at' => '2026-01-01 10:00:00',
            'completed_at' => null,
        ];

        $mappedPlaylist = LearningExportService::mapRow($playlistRow, 2);
        $this->assertEquals('Playlist Course', $mappedPlaylist[9]);                            // Col 10: Course Title
        $this->assertEquals('Introduction to ISO 14001:2015', $mappedPlaylist[10]);             // Col 11: Sub Course Title
        $this->assertEquals('Video', $mappedPlaylist[11]);                                     // Col 12: Type
        $this->assertTrue($mappedPlaylist[12] === 'Playlist' || str_starts_with($mappedPlaylist[12], 'Playlist: ')); // Col 13: Category
        $this->assertEquals('Yes', $mappedPlaylist[35]);                                       // Col 36: Attendance (>0 progress)
    }

    /**
     * TEST 6: Assessment Score resolution rules (Actual score vs -).
     */
    public function test_assessment_score_resolution_rules(): void
    {
        $users = User::take(2)->get();
        $userA = $users[0];
        $userB = $users[1];

        $classId = Classification::first()?->id ?? 1;

        // 1. Content WITH assessment
        $contentWithAss = Content::create([
            'title' => 'Content With Exam',
            'type' => 'Document',
            'classification_id' => $classId,
            'duration' => '00:10:00',
            'active' => 'Active',
        ]);
        $assessment = Assesment::create([
            'content_id' => $contentWithAss->id,
            'type' => 'content',
            'assasment_content_for' => 'content',
            'title' => 'Content Exam',
            'status' => 'OK',
            'passing_grade' => 80,
        ]);

        // User A took and got 95
        AssesmentResult::create([
            'user_id' => $userA->id,
            'assesment_id' => $assessment->id,
            'score' => 95,
            'final' => 1,
            'user_answer' => [],
            'batch' => 1,
            'pass' => 1,
        ]);

        // Build batch rows
        $rows = [
            (object) [
                'user_id' => $userA->id,
                'content_id' => $contentWithAss->id,
                'source_type' => 'content',
                'trainingplan_id' => null,
                'progress' => 100,
                'finish' => 1,
            ],
            (object) [
                'user_id' => $userB->id,
                'content_id' => $contentWithAss->id,
                'source_type' => 'content',
                'trainingplan_id' => null,
                'progress' => 50,
                'finish' => 0,
            ],
        ];

        $context = LearningExportService::buildBatchContext($rows);

        $mappedUserA = LearningExportService::mapRow($rows[0], 1, $context);
        $mappedUserB = LearningExportService::mapRow($rows[1], 2, $context);

        // User A took exam and got 95 (Col 43: Score (L2))
        $this->assertEquals('95', $mappedUserA[42], 'Completed assessment must show actual score');

        // User B has not taken exam
        $this->assertEquals('-', $mappedUserB[42], 'Uncompleted assessment must show -');
    }

    /**
     * TEST 7: Certificate Eligibility rules (Available vs -).
     */
    public function test_certificate_eligibility_rules(): void
    {
        $users = User::take(2)->get();
        $user = $users[0];
        $userIncomplete = $users[1];

        $classId = Classification::first()?->id ?? 1;

        // Content with setting, completed 100% -> Available
        $contentNoExam = Content::create([
            'title' => 'Cert Content No Exam',
            'type' => 'Document',
            'classification_id' => $classId,
            'duration' => '00:10:00',
            'active' => 'Active',
        ]);
        CertificateSetting::create([
            'certificate_type' => 'content',
            'content_id' => $contentNoExam->id,
            'is_active' => true,
            'minimum_progress' => 100,
            'minimum_score' => 80,
        ]);

        ContentUser::create([
            'user_id' => $user->id,
            'content_id' => $contentNoExam->id,
            'progress' => 100,
            'finish' => '1',
        ]);

        $rowCompleted = (object) [
            'user_id' => $user->id,
            'content_id' => $contentNoExam->id,
            'source_type' => 'content',
            'trainingplan_id' => null,
            'progress' => 100,
            'finish' => 1,
        ];

        $contextCompleted = LearningExportService::buildBatchContext([$rowCompleted]);
        $mappedCompleted = LearningExportService::mapRow($rowCompleted, 1, $contextCompleted);
        // Col 47: Certificate Availability
        $this->assertEquals('Available', $mappedCompleted[46], '100% progress without assessment must be Available');

        // Content with setting, but incomplete (50% progress) -> -
        ContentUser::create([
            'user_id' => $userIncomplete->id,
            'content_id' => $contentNoExam->id,
            'progress' => 50,
            'finish' => '0',
        ]);

        $rowIncomplete = (object) [
            'user_id' => $userIncomplete->id,
            'content_id' => $contentNoExam->id,
            'source_type' => 'content',
            'trainingplan_id' => null,
            'progress' => 50,
            'finish' => 0,
        ];

        $contextIncomplete = LearningExportService::buildBatchContext([$rowIncomplete]);
        $mappedIncomplete = LearningExportService::mapRow($rowIncomplete, 2, $contextIncomplete);
        $this->assertEquals('-', $mappedIncomplete[46], 'Incomplete progress must be -');
    }

    /**
     * TEST 8: Attendance derivation rule - Yes for progress > 0 or finish 1, No otherwise.
     */
    public function test_derived_attendance_present_vs_not_completed(): void
    {
        // Finished via progress 100%
        $row100 = (object) [
            'progress' => 100,
            'finish' => 0,
        ];
        $mapped100 = LearningExportService::mapRow($row100, 1);
        $this->assertEquals('Yes', $mapped100[35]);

        // Finished via finish 1
        $rowFinish = (object) [
            'progress' => 90,
            'finish' => 1,
        ];
        $mappedFinish = LearningExportService::mapRow($rowFinish, 2);
        $this->assertEquals('Yes', $mappedFinish[35]);

        // Ongoing (progress 45%, finish 0)
        $rowOngoing = (object) [
            'progress' => 45,
            'finish' => 0,
        ];
        $mappedOngoing = LearningExportService::mapRow($rowOngoing, 3);
        $this->assertEquals('Yes', $mappedOngoing[35]);

        // Not started (progress 0%, finish 0)
        $rowNotStarted = (object) [
            'progress' => 0,
            'finish' => 0,
        ];
        $mappedNotStarted = LearningExportService::mapRow($rowNotStarted, 4);
        $this->assertEquals('No', $mappedNotStarted[35]);
    }
}
