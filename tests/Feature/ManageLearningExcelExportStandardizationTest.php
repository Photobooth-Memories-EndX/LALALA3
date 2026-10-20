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
     * TEST 1: Classification Export has standard headings and preserves employee data.
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

        // 1. Verify exact 17 column headings on row 1
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
     * TEST 2: User Profile Export has identical 17 standardized headings and filters to single user.
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

        // If there are data rows, verify employee name matches target user
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 2) {
            $exportedName = $sheet->getCell('C2')->getValue();
            if ($exportedName !== '-' && $targetUser->name) {
                $this->assertEquals($targetUser->name, $exportedName);
            }
        }
    }

    /**
     * TEST 3: Playlist Detail Export exports records for that playlist with Category = Playlist: <Name>.
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
            // Check row 2 Category (column H)
            $category = $sheet->getCell('H2')->getValue();
            $this->assertStringStartsWith('Playlist: ', $category);
            $this->assertEquals('Playlist: ' . $playlist->title, $category);

            // Course name (column F) must NOT be renamed to playlist title if content title differs
            $course = $sheet->getCell('F2')->getValue();
            $this->assertNotEmpty($course);
        }
    }

    /**
     * TEST 4: Content Detail Export exports standalone and playlist rows with proper Category.
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
            $course = $sheet->getCell('F2')->getValue();
            $this->assertEquals($content->title, $course);

            $category = $sheet->getCell('H2')->getValue();
            $this->assertTrue(
                $category === 'Content' || str_starts_with($category, 'Playlist: '),
                "Category must be Content or start with 'Playlist: ', got: {$category}"
            );
        }
    }

    /**
     * TEST 5: Category rule - Standalone = 'Content', Playlist = 'Playlist: <Title>'.
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
        $this->assertEquals('012345', $mappedStandalone[1], 'NIP leading zero must be preserved');
        $this->assertEquals('John Doe', $mappedStandalone[2]);
        $this->assertEquals('IT', $mappedStandalone[3]);
        $this->assertEquals('Dev', $mappedStandalone[4]);
        $this->assertEquals('Standalone Course', $mappedStandalone[5]);
        $this->assertEquals('Document', $mappedStandalone[6]);
        $this->assertEquals('Content', $mappedStandalone[7], 'Standalone Category must be Content');
        $this->assertEquals('Present', $mappedStandalone[16], '100% completed must derive Present attendance');

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
        $this->assertEquals('Playlist Course', $mappedPlaylist[5]);
        $this->assertEquals('Video', $mappedPlaylist[6]);
        $this->assertEquals('Playlist: Introduction to ISO 14001:2015', $mappedPlaylist[7], 'Playlist item Category must be Playlist: <Title>');
        $this->assertEquals('Not Completed', $mappedPlaylist[16], '< 100% progress must derive Not Completed attendance');

        // 3. Fallback when playlist title is empty
        $playlistFallbackRow = (object) [
            'source_type' => 'playlist',
            'trainingplan_id' => 99,
            'playlist_title' => '',
        ];
        $mappedFallback = LearningExportService::mapRow($playlistFallbackRow, 3);
        $this->assertEquals('Playlist', $mappedFallback[7]);
    }

    /**
     * TEST 6: Assessment Score resolution rules (Actual score, Not Taken, Not Applicable).
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

        // User B has not taken the assessment

        // 2. Content WITHOUT assessment
        $contentNoAss = Content::create([
            'title' => 'Content Without Exam',
            'type' => 'Document',
            'classification_id' => $classId,
            'duration' => '00:10:00',
            'active' => 'Active',
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
            (object) [
                'user_id' => $userA->id,
                'content_id' => $contentNoAss->id,
                'source_type' => 'content',
                'trainingplan_id' => null,
                'progress' => 100,
                'finish' => 1,
            ],
        ];

        $context = LearningExportService::buildBatchContext($rows);

        $mappedUserA = LearningExportService::mapRow($rows[0], 1, $context);
        $mappedUserB = LearningExportService::mapRow($rows[1], 2, $context);
        $mappedNoAss = LearningExportService::mapRow($rows[2], 3, $context);

        // User A took exam and got 95
        $this->assertEquals('95', $mappedUserA[14], 'Completed assessment must show actual score');

        // User B has not taken exam
        $this->assertEquals('Not Taken', $mappedUserB[14], 'Uncompleted assessment must show Not Taken');

        // Content has no exam
        $this->assertEquals('Not Applicable', $mappedNoAss[14], 'Content without exam must show Not Applicable');
    }

    /**
     * TEST 7: Certificate Eligibility rules (Available vs Not Available).
     */
    public function test_certificate_eligibility_rules(): void
    {
        $users = User::take(2)->get();
        $user = $users[0];
        $userIncomplete = $users[1];

        $classId = Classification::first()?->id ?? 1;

        // 1. Content with setting, completed 100%, no assessment -> Available
        $contentNoExam = Content::create([
            'title' => 'Cert Content No Exam',
            'type' => 'Document',
            'classification_id' => $classId,
            'duration' => '00:10:00',
            'active' => 'Active',
        ]);
        $settingNoExam = CertificateSetting::create([
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
        $this->assertEquals('Available', $mappedCompleted[15], '100% progress without assessment must be Available');

        // 2. Content with setting, but incomplete (50% progress) -> Not Available
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
        $this->assertEquals('Not Available', $mappedIncomplete[15], 'Incomplete progress must be Not Available');

        // 3. Content without certificate setting -> Not Available
        $contentNoSetting = Content::create([
            'title' => 'Content Without Setting',
            'type' => 'Document',
            'classification_id' => $classId,
            'duration' => '00:10:00',
            'active' => 'Active',
        ]);
        $rowNoSetting = (object) [
            'user_id' => $user->id,
            'content_id' => $contentNoSetting->id,
            'source_type' => 'content',
            'trainingplan_id' => null,
            'progress' => 100,
            'finish' => 1,
        ];

        $contextNoSetting = LearningExportService::buildBatchContext([$rowNoSetting]);
        $mappedNoSetting = LearningExportService::mapRow($rowNoSetting, 3, $contextNoSetting);
        $this->assertEquals('Not Available', $mappedNoSetting[15], 'Content without setting must be Not Available');
    }

    /**
     * TEST 8: Attendance derivation rule - Present for 100% / finish 1, Not Completed otherwise.
     */
    public function test_derived_attendance_present_vs_not_completed(): void
    {
        // Finished via progress 100%
        $row100 = (object) [
            'progress' => 100,
            'finish' => 0,
        ];
        $mapped100 = LearningExportService::mapRow($row100, 1);
        $this->assertEquals('Present', $mapped100[16]);

        // Finished via finish 1
        $rowFinish = (object) [
            'progress' => 90,
            'finish' => 1,
        ];
        $mappedFinish = LearningExportService::mapRow($rowFinish, 2);
        $this->assertEquals('Present', $mappedFinish[16]);

        // Ongoing (progress 45%, finish 0)
        $rowOngoing = (object) [
            'progress' => 45,
            'finish' => 0,
        ];
        $mappedOngoing = LearningExportService::mapRow($rowOngoing, 3);
        $this->assertEquals('Not Completed', $mappedOngoing[16]);

        // Not started (progress 0%, finish 0)
        $rowNotStarted = (object) [
            'progress' => 0,
            'finish' => 0,
        ];
        $mappedNotStarted = LearningExportService::mapRow($rowNotStarted, 4);
        $this->assertEquals('Not Completed', $mappedNotStarted[16]);

        // Offline record preserves database attendance
        $rowOffline = (object) [
            'source' => 'offline',
            'attendance' => 'Absent',
        ];
        $mappedOffline = LearningExportService::mapRow($rowOffline, 5);
        $this->assertEquals('Absent', $mappedOffline[16]);
    }
}
