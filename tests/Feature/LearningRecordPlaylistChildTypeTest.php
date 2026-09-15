<?php

namespace Tests\Feature;

use App\Exports\ExportMyLearningRecord;
use App\Http\Controllers\LearningRecordController;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordPlaylistChildTypeTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;
    protected LearningRecordController $controller;
    protected CertificateEligibilityService $eligibilityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = app(LearningRecordController::class);
        $this->eligibilityService = app(CertificateEligibilityService::class);

        $this->user = User::factory()->create([
            'name'       => 'Test Learner Child Type',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-child-type-' . Str::uuid(),
            'sap_code'   => 'SAP-CT-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Child Type Test Classification'],
            ['slug' => 'child-type-test-classification']
        );
    }

    /**
     * Test that Content outside playlist, Playlist parent, and Playlist children have correct Type.
     */
    public function test_playlist_child_and_parent_types_in_web_and_export(): void
    {
        // 1. Direct Content (Document) outside playlist
        $cDoc = Content::create([
            'title'             => 'Direct Document Course',
            'slug'              => 'direct-doc-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $cDoc->id,
            'progress'         => 100,
            'finish'           => '1',
            'status'           => 'optional',
            'duration_seconds' => 600,
            'current_time'     => 600,
        ]);

        // 2. Playlist with 3 children: Video, Document, YouTube (MOOC)
        $plan = TrainingPlan::create([
            'title'                  => 'Parent Playlist Track',
            'slug'                   => 'parent-pl-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
        ]);

        $chVideo = Content::create([
            'title'             => 'Child Video Content',
            'slug'              => 'ch-vid-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.25,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        $chDoc = Content::create([
            'title'             => 'Child Document Content',
            'slug'              => 'ch-doc-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.25,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        $chMooc = Content::create([
            'title'             => 'Child MOOC Content',
            'slug'              => 'ch-mooc-' . rand(1000, 9999),
            'type'              => 'YouTube',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.25,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $chVideo->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $chDoc->id, 'content_order' => 2]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $chMooc->id, 'content_order' => 3]);

        // Complete the playlist modules via PCU and CU
        foreach ([$chVideo, $chDoc, $chMooc] as $ch) {
            PlaylistContentUser::create([
                'user_id'          => $this->user->id,
                'trainingplan_id'  => $plan->id,
                'content_id'       => $ch->id,
                'progress'         => 100,
                'finish'           => '1',
                'duration_seconds' => 600,
                'current_time'     => 600,
            ]);
            ContentUser::create([
                'user_id'          => $this->user->id,
                'content_id'       => $ch->id,
                'progress'         => 100,
                'finish'           => '1',
                'status'           => 'optional',
                'duration_seconds' => 600,
                'current_time'     => 600,
            ]);
        }

        $this->actingAs($this->user);

        // A. Verify Web Learning Record rows
        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];

        $playlistRow = $rows->firstWhere('title', 'Parent Playlist Track');
        $this->assertNotNull($playlistRow);
        $this->assertEquals('Playlist', $playlistRow['type']);
        $this->assertEquals('playlist', $playlistRow['row_kind']);

        // Check playlist_items inside the playlist row
        $items = collect($playlistRow['playlist_items'])->keyBy('title');
        $this->assertEquals('Video', $items['Child Video Content']['type']);
        $this->assertEquals('Document', $items['Child Document Content']['type']);
        $this->assertEquals('MOOC', $items['Child MOOC Content']['type']);

        // Check standalone and child content rows
        $videoRow = $rows->firstWhere('title', 'Child Video Content');
        $this->assertNotNull($videoRow);
        $this->assertEquals('Video', $videoRow['type']);

        $docRow = $rows->firstWhere('title', 'Child Document Content');
        $this->assertNotNull($docRow);
        $this->assertEquals('Document', $docRow['type']);

        $moocRow = $rows->firstWhere('title', 'Child MOOC Content');
        $this->assertNotNull($moocRow);
        $this->assertEquals('MOOC', $moocRow['type']);

        $directDocRow = $rows->firstWhere('title', 'Direct Document Course');
        $this->assertNotNull($directDocRow);
        $this->assertEquals('Document', $directDocRow['type']);

        // B. Verify Excel Export
        $export = new ExportMyLearningRecord();
        $array = $export->array();
        $dataRows = collect($array);

        $plExport = $dataRows->firstWhere(1, 'Parent Playlist Track');
        $this->assertNotNull($plExport);
        $this->assertEquals('Playlist', $plExport[2]); // Type column is index 2

        $chVideoExport = $dataRows->firstWhere(1, 'Child Video Content');
        $this->assertNotNull($chVideoExport);
        $this->assertEquals('Video', $chVideoExport[2], 'Child Video in export must have Type = Video, NOT Content');

        $chDocExport = $dataRows->firstWhere(1, 'Child Document Content');
        $this->assertNotNull($chDocExport);
        $this->assertEquals('Document', $chDocExport[2], 'Child Document in export must have Type = Document, NOT Content');

        $chMoocExport = $dataRows->firstWhere(1, 'Child MOOC Content');
        $this->assertNotNull($chMoocExport);
        $this->assertEquals('MOOC', $chMoocExport[2], 'Child MOOC in export must have Type = MOOC, NOT Content');
    }
}
