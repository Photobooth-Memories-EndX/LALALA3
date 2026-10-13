<?php

namespace Tests\Feature;

use App\Exports\ExportMyLearningOngoing;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\OngoingContentService;
use App\Services\Learning\OngoingPlaylistService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MyLearningOngoingExportTest extends TestCase
{
    use DatabaseTransactions;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->learner = User::factory()->create([
            'team' => 'QA', 'department' => 'Technology', 'division' => 'Engineering',
            'ssohash' => 'ongoing-export-' . uniqid(), 'sap_code' => 'OE-' . uniqid(), 'role' => 1, 'active' => 1,
        ]);
        $this->actingAs($this->learner);
    }

    private function content(string $title): Content
    {
        return Content::create(['title' => $title, 'type' => 'Video', 'duration' => '01:00:00',
            'active' => 'Active', 'playlistonly' => 'N']);
    }

    private function progress(Content $content, ?User $user = null): ContentUser
    {
        return ContentUser::create(['user_id' => ($user ?? $this->learner)->id, 'content_id' => $content->id,
            'progress' => 25, 'finish' => '0', 'duration_seconds' => 3600, 'actual_watch_seconds' => 720,
            'learning_status' => 'in_progress']);
    }

    public function test_content_export_round_trips_numeric_cells_dates_styles_and_filters_without_writes(): void
    {
        $content = $this->content('Export selected video');
        $record = $this->progress($content);
        $this->progress($this->content('Excluded search result'));
        $before = $record->fresh()->getAttributes();
        $cards = app(OngoingContentService::class)->cards($this->learner->id, ['search' => 'Export selected']);
        $export = new ExportMyLearningOngoing($cards, $this->learner);
        $data = $export->array();
        $this->assertSame(['No', 'Course', 'Type', 'Category', 'Classification', 'Duration', 'Learning Hour',
            'Learning Progress', 'Start Date', 'Finish Date', 'Attendance', 'Assessment Score', 'Certificate'], $data[0]);
        $this->assertCount(4, $data);
        $this->assertSame('Content', $data[1][3]);
        $this->assertSame(0.2, $data[1][6]);
        $this->assertSame(0.25, $data[1][7]);
        $this->assertNull($data[1][9]);
        $this->assertSame('Absent', $data[1][10]);
        $this->assertSame($before, $record->fresh()->getAttributes());

        $path = tempnam(sys_get_temp_dir(), 'ongoing-xlsx-');
        try {
            file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame('M', $sheet->getHighestColumn());
            $this->assertSame('01:00:00', $sheet->getCell('F2')->getFormattedValue());
            $this->assertSame('n', $sheet->getCell('G2')->getDataType());
            $this->assertSame('25%', $sheet->getCell('H2')->getFormattedValue());
            $this->assertSame('n', $sheet->getCell('I2')->getDataType());
            $this->assertSame('A2', $sheet->getFreezePane());
            $this->assertSame('A1:M2', $sheet->getAutoFilter()->getRange());
            $this->assertTrue($sheet->getStyle('M1')->getFont()->getBold());
        } finally {
            unlink($path);
        }
    }

    public function test_playlist_children_use_own_progress_and_parent_only_totals(): void
    {
        $plan = TrainingPlan::create(['title' => 'Export playlist', 'active' => 'Active', 'total_duration_seconds' => 7200]);
        foreach ([100, 50] as $index => $progress) {
            $content = $this->content('Module ' . $index);
            TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => $index]);
            PlaylistContentUser::create(['user_id' => $this->learner->id, 'trainingplan_id' => $plan->id,
                'content_id' => $content->id, 'progress' => $progress, 'finish' => $progress === 100 ? '1' : '0',
                'duration_seconds' => 3600, 'actual_watch_seconds' => $progress === 100 ? 3600 : 900]);
            // Conflicting direct history must never replace playlist-specific progress.
            $this->progress($content);
        }
        $service = app(OngoingPlaylistService::class);
        $cards = $service->cards($service->userPlaylists($this->learner->id), OngoingContentService::DEFAULT_FILTERS);
        $export = new ExportMyLearningOngoing($cards, $this->learner);
        $data = $export->array();
        $this->assertCount(6, $data);
        foreach ($data as $row) {
            $this->assertCount(13, $row);
        }
        $this->assertSame('Playlist', $data[1][2]);
        $this->assertSame('Playlist', $data[1][3]);
        $this->assertSame(1.25, $data[1][6]);
        $this->assertSame(0.5, $data[1][7]);
        $this->assertSame('Absent', $data[1][10]);
        $this->assertNull($data[1][9]);
        $this->assertNull($data[2][0]);
        $this->assertEquals(1, $data[2][7]);
        $this->assertSame(0.25, $data[3][6]);
        $this->assertSame('Absent', $data[3][10]);
        $this->assertNull($data[3][9]);
        $this->assertEquals($data[1][5], $data[5][5]);
        $this->assertSame(1.25, $data[5][6]);

        $writer = Excel::getFacadeRoot();
        Excel::fake();
        Excel::matchByRegex();
        $this->get(route('user.learning.ongoing.export', ['ongoing_mode' => 'playlist', 'ongoing_search' => 'Export playlist']))->assertOk();
        Excel::assertDownloaded('/^MyLearningOngoing_Playlist_\d{8}_\d{6}\.xlsx$/', function ($download) use ($data) {
            $this->assertEquals($data, $download->array());
            return true;
        });
        // Restore the real writer for the XLSX round-trip below.
        Excel::swap($writer);

        $path = tempnam(sys_get_temp_dir(), 'ongoing-playlist-');
        try {
            file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame(1, $sheet->getStyle('B3')->getAlignment()->getIndent());
            $this->assertSame('', $sheet->getAutoFilter()->getRange());
        } finally {
            unlink($path);
        }
    }

    public function test_endpoint_uses_all_filtered_rows_and_ignores_record_filters_and_other_users(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $this->progress($this->content('Matching course ' . $i));
        }
        $this->progress($this->content('Different course'));
        $other = User::factory()->create(['team' => 'QA', 'department' => 'IT', 'division' => 'IT',
            'ssohash' => uniqid(), 'sap_code' => uniqid()]);
        $this->progress($this->content('Matching private course'), $other);
        Excel::fake();
        Excel::matchByRegex();
        $this->get(route('user.learning.ongoing.export', ['ongoing_search' => 'Matching', 'ongoing_page' => 2,
            'record_search' => 'unrelated', 'search' => 'unrelated', 'type' => 'Document']))->assertOk();
        Excel::assertDownloaded('/^MyLearningOngoing_Content_\d{8}_\d{6}\.xlsx$/', function ($export) {
            $rows = $export->array();
            $this->assertCount(14, $rows);
            $this->assertNotContains('Matching private course', array_column($rows, 1));
            return true;
        });
    }

    public function test_empty_export_and_guest_access_are_safe(): void
    {
        $data = (new ExportMyLearningOngoing(collect(), $this->learner))->array();
        $this->assertCount(3, $data);
        $this->assertEquals(0, $data[2][6]);
        auth()->logout();
        $this->get(route('user.learning.ongoing.export'))->assertRedirect();
    }

    public function test_export_link_preserves_autocomplete_query_and_playlist_mode(): void
    {
        $this->get('/my-learning/partial/ongoing?ongoing_mode=playlist&q=Safety&ongoing_page=2')
            ->assertOk()->assertSee('ongoing_search=Safety', false)
            ->assertSee('/my-learning/ongoing/export?', false);
    }

    public function test_download_menu_uses_the_record_button_style_and_keeps_active_filters_for_all_modes(): void
    {
        foreach (['content', 'playlist', 'mooc'] as $mode) {
            $response = $this->get('/my-learning/partial/ongoing?ongoing_mode=' . $mode . '&ongoing_search=Filter%20me');

            $response->assertOk()
                ->assertDontSee('Export Excel')
                ->assertDontSee('Download PDF (.pdf)')
                ->assertDontSee('Download CSV (.csv)')
                ->assertSee('btn-outline-success btn-learning-success ongoing-filter-action-btn', false)
                ->assertSee('ongoing-download-button', false)
                ->assertSee('format=excel', false)
                ->assertSee('ongoing_mode=' . $mode, false)
                ->assertSee('ongoing_search=Filter%20me', false);

            $html = $response->getContent();
            $this->assertGreaterThan(
                strpos($html, 'title="Reset Filter"'),
                strpos($html, 'ongoing-download-button'),
                'The Download menu should follow Reset in the filter actions.'
            );
        }
    }

    public function test_csv_and_pdf_download_with_shared_ongoing_schema(): void
    {
        $this->progress($this->content('Format course'));

        $csv = $this->get(route('user.learning.ongoing.export', [
            'ongoing_mode' => 'content', 'ongoing_search' => 'Format course', 'format' => 'csv',
        ]));
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('.csv', $csv->headers->get('content-disposition'));
        $csvRows = array_map('str_getcsv', explode("\n", substr($csv->streamedContent(), 3)));
        $this->assertSame(['No', 'Course', 'Type', 'Category', 'Classification', 'Duration',
            'Learning Hour', 'Learning Progress', 'Start Date', 'Finish Date', 'Attendance',
            'Assessment Score', 'Certificate'], $csvRows[0]);
        $this->assertSame('01:00:00', $csvRows[1][5]);
        $this->assertSame('25%', $csvRows[1][7]);
        $this->assertSame('Absent', $csvRows[1][10]);

        $pdf = $this->get(route('user.learning.ongoing.export', [
            'ongoing_mode' => 'content', 'ongoing_search' => 'Format course', 'format' => 'pdf',
        ]));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $pdf->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    public function test_mooc_mode_exports_its_filtered_content_as_csv(): void
    {
        $mooc = Content::create(['title' => 'MOOC export course', 'type' => 'YouTube', 'duration' => '00:30:00',
            'active' => 'Active', 'playlistonly' => 'N']);
        $this->progress($mooc);
        $this->progress($this->content('Different regular course'));

        $response = $this->get(route('user.learning.ongoing.export', [
            'ongoing_mode' => 'mooc', 'ongoing_search' => 'MOOC export', 'format' => 'csv',
        ]));
        $response->assertOk();
        $rows = array_map('str_getcsv', explode("\n", substr($response->streamedContent(), 3)));
        $this->assertSame('MOOC export course', $rows[1][1]);
        $this->assertSame('MOOC', $rows[1][2]);
        $this->assertSame('Absent', $rows[1][10]);
        $this->assertNotContains('Different regular course', array_column($rows, 1));
    }
}
