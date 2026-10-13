<?php

namespace Tests\Feature;

use App\Exports\ExportMyLearningRecord;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordExportDurationAndLearningHourFormatTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Test Learner Export Format',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-export-fmt-' . Str::uuid(),
            'sap_code'   => 'SAP-EF-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Format Test Classification'],
            ['slug' => 'format-test-classification']
        );
    }

    public function test_duration_and_learning_hour_export_format(): void
    {
        // 1. Video Content: 4 minutes duration (240s) -> 0.07h
        $video = Content::create([
            'title'             => 'Test Video Duration Course',
            'slug'              => 'test-video-dur-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $video->id,
            'current_time'     => 0,
            'duration_seconds' => 240,
            'progress'         => 100,
            'finish'           => '1',
        ]);

        // 2. Playlist: 15 minutes (900s) -> 0.25h
        $plan = TrainingPlan::create([
            'title'                  => 'Test Playlist Track',
            'slug'                   => 'test-pl-dur-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 900,
            'learning_hours'         => 0.25,
            'last_finished_at'       => now(),
        ]);

        $childVideo = Content::create([
            'title'             => 'Playlist Child Video',
            'slug'              => 'pl-child-dur-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:15:00',
            'learning_hours'    => 0.25,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $childVideo->id,
            'content_order'   => 0,
        ]);

        PlaylistContentUser::create([
            'user_id'          => $this->user->id,
            'trainingplan_id'  => $plan->id,
            'content_id'       => $childVideo->id,
            'current_time'     => 0,
            'duration_seconds' => 900,
            'progress'         => 100,
            'finish'           => '1',
        ]);

        $this->actingAs($this->user);

        $export = new ExportMyLearningRecord();
        $array = $export->array();
        $rows = collect($array);

        // A. Video Row Verification
        $videoRow = $rows->firstWhere(1, 'Test Video Duration Course');
        $this->assertNotNull($videoRow, 'Video row must exist in export');
        // Kolom F (index 5) = Duration must be hh:mm:ss format
        $this->assertEquals('00:04:00', $videoRow[5]);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $videoRow[5]);
        // Kolom G (index 6) = Learning Hour formatted as '0.07 hrs'
        $this->assertEquals('0.07 hrs', $videoRow[6]);

        // B. Playlist Row Verification
        $playlistRow = $rows->firstWhere(1, 'Test Playlist Track');
        $this->assertNotNull($playlistRow, 'Playlist row must exist in export');
        // Kolom F (index 5) = Duration must be hh:mm:ss format
        $this->assertEquals('00:15:00', $playlistRow[5]);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $playlistRow[5]);
        // Kolom G (index 6) = Learning Hour formatted as '0.25 hrs'
        $this->assertEquals('0.25 hrs', $playlistRow[6]);

        // C. Standardized Export: NO TOTAL Row (Exact Table Data)
        $totalRow = $rows->firstWhere(0, 'TOTAL');
        $this->assertNull($totalRow, 'Standardized export must NOT contain TOTAL summary row');
    }
}
