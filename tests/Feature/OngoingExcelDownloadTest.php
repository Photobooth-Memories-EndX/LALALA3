<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class OngoingExcelDownloadTest extends TestCase
{
    use DatabaseTransactions;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->learner = User::factory()->create([
            'team' => 'QA', 'department' => 'Technology', 'division' => 'LMS',
            'ssohash' => 'ongoing-export-' . uniqid(), 'sap_code' => uniqid('OE'),
            'role' => 1, 'active' => 1,
        ]);
        $this->actingAs($this->learner);
    }

    private function content(string $title): Content
    {
        return Content::create([
            'title' => $title, 'type' => 'Video', 'duration' => '01:00:00',
            'active' => 'Active', 'playlistonly' => 'N',
        ]);
    }

    private function download(array $filters = [])
    {
        $response = $this->get(route('user.learning.ongoing.export', $filters));
        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            // The Excel exporter installs a static binder. Read as a fresh client would.
            \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
            return IOFactory::load($path)->getActiveSheet();
        } finally {
            @unlink($path);
        }
    }

    private function assertHeaders($sheet): void
    {
        $this->assertSame('M', $sheet->getHighestColumn());
        $this->assertSame([
            'No', 'Course', 'Type', 'Category', 'Classification', 'Duration',
            'Learning Hour', 'Learning Progress', 'Start Date', 'Finish Date',
            'Attendance', 'Assessment Score', 'Certificate',
        ], $sheet->rangeToArray('A1:M1')[0]);
        $this->assertSame('A2', $sheet->getFreezePane());
    }

    public function test_content_download_uses_ongoing_scope_filters_and_all_pages(): void
    {
        for ($i = 0; $i < 11; $i++) {
            ContentUser::create([
                'user_id' => $this->learner->id, 'content_id' => $this->content('Match ' . $i)->id,
                'progress' => 25, 'finish' => '0', 'actual_watch_seconds' => 900,
                'duration_seconds' => 1800,
            ]);
        }
        ContentUser::create([
            'user_id' => $this->learner->id, 'content_id' => $this->content('Excluded')->id,
            'progress' => 50, 'finish' => '0',
        ]);
        ContentUser::create([
            'user_id' => $this->learner->id, 'content_id' => $this->content('Match Completed')->id,
            'progress' => 100, 'finish' => '1',
        ]);
        $other = User::factory()->create(['team' => 'QA', 'department' => 'QA', 'division' => 'QA', 'ssohash' => uniqid(), 'sap_code' => uniqid()]);
        ContentUser::create([
            'user_id' => $other->id, 'content_id' => $this->content('Match Other User')->id,
            'progress' => 25, 'finish' => '0',
        ]);
        $before = ContentUser::where('user_id', $this->learner->id)->get()->toArray();
        $sheet = $this->download(['ongoing_search' => 'Match', 'ongoing_page' => 2, 'record_search' => 'Excluded', 'source' => 'offline']);
        $this->assertHeaders($sheet);
        for ($row = 2; $row <= 12; $row++) {
            $this->assertSame('Content', $sheet->getCell('D' . $row)->getValue());
            $this->assertSame('01:00:00', $sheet->getCell('F' . $row)->getFormattedValue());
            $this->assertEquals(0.25, $sheet->getCell('G' . $row)->getValue());
            $this->assertSame('n', $sheet->getCell('G' . $row)->getDataType());
            $this->assertEquals(0.25, $sheet->getCell('H' . $row)->getValue());
            $this->assertEmpty($sheet->getCell('J' . $row)->getValue());
            $this->assertSame('Absent', $sheet->getCell('K' . $row)->getValue());
        }
        $total = $sheet->getHighestRow();
        $this->assertSame('TOTAL', $sheet->getCell('A' . $total)->getValue());
        $this->assertEquals(2.75, $sheet->getCell('G' . $total)->getValue());
        $this->assertSame($before, ContentUser::where('user_id', $this->learner->id)->get()->toArray());
    }

    public function test_playlist_download_preserves_children_and_counts_only_parents(): void
    {
        $plan = TrainingPlan::create(['title' => 'Export Playlist Eligible', 'active' => 'Active', 'total_duration_seconds' => 10800]);
        foreach ([100, 50] as $i => $progress) {
            $content = $this->content('Module ' . $i);
            TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => $i]);
            PlaylistContentUser::create([
                'user_id' => $this->learner->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id,
                'progress' => $progress, 'finish' => $progress === 100 ? '1' : '0',
                'duration_seconds' => 3600, 'actual_watch_seconds' => $progress * 36,
            ]);
            // Standalone progress must never replace playlist-specific progress.
            ContentUser::create(['user_id' => $this->learner->id, 'content_id' => $content->id, 'progress' => 10, 'finish' => '0']);
        }
        $unstartedChild = $this->content('Unstarted playlist detail');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $unstartedChild->id, 'content_order' => 2]);

        $withoutCompletedChild = TrainingPlan::create(['title' => 'No child completed', 'active' => 'Active', 'total_duration_seconds' => 3600]);
        $uncompletedContent = $this->content('Only in-progress child');
        TrainingPlanDetail::create(['trainingplan_id' => $withoutCompletedChild->id, 'content_id' => $uncompletedContent->id, 'content_order' => 0]);
        PlaylistContentUser::create(['user_id' => $this->learner->id, 'trainingplan_id' => $withoutCompletedChild->id,
            'content_id' => $uncompletedContent->id, 'progress' => 60, 'finish' => '0']);

        $completedPlan = TrainingPlan::create(['title' => 'Already completed playlist', 'active' => 'Active', 'total_duration_seconds' => 3600]);
        $completedContent = $this->content('Completed playlist child');
        TrainingPlanDetail::create(['trainingplan_id' => $completedPlan->id, 'content_id' => $completedContent->id, 'content_order' => 0]);
        PlaylistContentUser::create(['user_id' => $this->learner->id, 'trainingplan_id' => $completedPlan->id,
            'content_id' => $completedContent->id, 'progress' => 100, 'finish' => '1']);

        $sheet = $this->download(['ongoing_mode' => 'playlist']);
        $this->assertHeaders($sheet);
        $this->assertSame('Export Playlist Eligible', $sheet->getCell('B2')->getValue());
        $this->assertSame('Playlist', $sheet->getCell('C2')->getValue());
        $this->assertEquals(1.5, $sheet->getCell('G2')->getValue());
        $this->assertEquals(0.33, $sheet->getCell('H2')->getValue());
        $this->assertEmpty($sheet->getCell('J2')->getValue());
        $this->assertSame('Absent', $sheet->getCell('K2')->getValue());
        $this->assertEmpty($sheet->getCell('A3')->getValue());
        $this->assertEmpty($sheet->getCell('A4')->getValue());
        $this->assertEquals(1, $sheet->getCell('H3')->getValue());
        $this->assertEquals(0.5, $sheet->getCell('H4')->getValue());
        $this->assertEquals(0.5, $sheet->getCell('G4')->getValue());
        $this->assertSame('Absent', $sheet->getCell('K4')->getValue());
        $this->assertEquals(1.5, $sheet->getCell('G' . $sheet->getHighestRow())->getValue());
        $this->assertNull($sheet->getCell('K3')->getValue());
        $this->assertSame('Unstarted playlist detail', $sheet->getCell('B5')->getValue());
        $this->assertSame('Absent', $sheet->getCell('K5')->getValue());
        $courseNames = array_column($sheet->rangeToArray('B1:B' . $sheet->getHighestRow(), null, true, false), 0);
        $this->assertNotContains('No child completed', $courseNames);
        $this->assertNotContains('Already completed playlist', $courseNames);
    }

    public function test_empty_downloads_are_valid_workbooks(): void
    {
        foreach (['content', 'playlist'] as $mode) {
            $sheet = $this->download(['ongoing_mode' => $mode]);
            $this->assertHeaders($sheet);
            $this->assertEquals(0, $sheet->getCell('G' . $sheet->getHighestRow())->getValue());
        }
    }

    public function test_score_is_exported_without_certificate_and_unfinished_date_is_blank(): void
    {
        $content = $this->content('=1+1');
        ContentUser::create([
            'user_id' => $this->learner->id, 'content_id' => $content->id,
            'progress' => 50, 'finish' => '0', 'completed_at' => now()->subDay(),
        ]);
        $assessment = \App\Models\Assesment::create([
            'title' => 'Export Assessment', 'type' => 'content', 'assasment_content_for' => 'content',
            'content_id' => $content->id, 'status' => 'OK',
        ]);
        foreach ([[72, 1], [99, 0]] as [$score, $final]) {
            \App\Models\AssesmentResult::create([
                'user_id' => $this->learner->id, 'assesment_id' => $assessment->id,
                'score' => $score, 'final' => $final, 'batch' => 1, 'user_answer' => [], 'pass' => 1,
            ]);
        }
        $sheet = $this->download();
        $this->assertSame('=1+1', $sheet->getCell('B2')->getValue());
        $this->assertSame('s', $sheet->getCell('B2')->getDataType());
        $this->assertSame('Absent', $sheet->getCell('K2')->getValue());
        $this->assertEquals(72, $sheet->getCell('L2')->getValue());
        $this->assertSame('Not Available', $sheet->getCell('M2')->getValue());
        $this->assertEmpty($sheet->getCell('J2')->getValue());
    }

    public function test_content_export_excludes_low_progress_and_canonically_completed_records(): void
    {
        foreach ([0, 4, 5, 30, 99, 100] as $progress) {
            ContentUser::create([
                'user_id' => $this->learner->id, 'content_id' => $this->content('Progress ' . $progress)->id,
                'progress' => $progress, 'finish' => '0',
            ]);
        }
        ContentUser::create([
            'user_id' => $this->learner->id, 'content_id' => $this->content('Finish flag only')->id,
            'progress' => 60, 'finish' => '1',
        ]);

        $sheet = $this->download();
        $titles = $sheet->rangeToArray('B2:B' . ($sheet->getHighestRow() - 2), null, true, false);
        $titles = array_column($titles, 0);
        $this->assertContains('Progress 5', $titles);
        $this->assertContains('Progress 30', $titles);
        $this->assertContains('Progress 99', $titles);
        $this->assertNotContains('Progress 0', $titles);
        $this->assertNotContains('Progress 4', $titles);
        $this->assertNotContains('Progress 100', $titles);
        $this->assertNotContains('Finish flag only', $titles);
    }

    public function test_excel_column_order_places_attendance_before_score_and_certificate(): void
    {
        $sheet = $this->download();
        $this->assertSame('Attendance', $sheet->getCell('K1')->getValue());
        $this->assertSame('Assessment Score', $sheet->getCell('L1')->getValue());
        $this->assertSame('Certificate', $sheet->getCell('M1')->getValue());
        $this->assertSame('0.00', $sheet->getStyle('G2')->getNumberFormat()->getFormatCode());
        $this->assertSame('0%', $sheet->getStyle('H2')->getNumberFormat()->getFormatCode());
    }
}
