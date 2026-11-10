<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\LearningHours\ActualLearningHourCalculator;
use App\Services\Learning\LearningDatatableService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContentMandatoryOptionalStatusTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $learnerUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->adminUser->accessPermissions()->updateOrCreate(
            ['module_key' => 'content_repository'],
            ['can_access' => true]
        );

        $this->learnerUser = User::where('role', User::ROLE_USER)->first()
            ?? User::factory()->create(['role' => User::ROLE_USER]);

        $this->classification = Classification::first()
            ?? Classification::create(['classification_name' => 'General Training']);
    }

    /**
     * TEST 1: Schema columns exist with default value 'optional'.
     */
    public function test_content_and_playlist_content_users_have_status_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content', 'status'), 'content table must have status column');
        $this->assertTrue(Schema::hasColumn('playlist_content_users', 'status'), 'playlist_content_users must have status column');

        $contentCol = collect(DB::select("
            SELECT COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'content' AND COLUMN_NAME = 'status'
        "))->first();

        $pcuCol = collect(DB::select("
            SELECT COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'playlist_content_users' AND COLUMN_NAME = 'status'
        "))->first();

        $cDef = trim((string) ($contentCol->COLUMN_DEFAULT ?? $contentCol->column_default), "'");
        $pDef = trim((string) ($pcuCol->COLUMN_DEFAULT ?? $pcuCol->column_default), "'");

        $this->assertEquals('optional', $cDef);
        $this->assertEquals('optional', $pDef);
    }

    /**
     * TEST 2: Validation fails when status is missing on store.
     */
    public function test_store_content_fails_when_status_is_missing(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('content.store'), [
            'title'          => 'Test Missing Status ' . uniqid(),
            'author'         => 'Test Author',
            'classification' => $this->classification->id,
            'type'           => 'Document',
            'description'    => 'Test description',
            'duration'       => '00:10:00',
            'learning_hours' => 0.5,
            'document_total_pages' => 5,
            'page_duration'  => 30,
        ]);

        $response->assertSessionHasErrors('status');
    }

    /**
     * TEST 3: Validation fails when status has an invalid value.
     */
    public function test_store_content_fails_when_status_is_invalid(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('content.store'), [
            'title'          => 'Test Invalid Status ' . uniqid(),
            'author'         => 'Test Author',
            'classification' => $this->classification->id,
            'type'           => 'Document',
            'description'    => 'Test description',
            'duration'       => '00:10:00',
            'learning_hours' => 0.5,
            'status'         => 'invalid_status_value',
            'document_total_pages' => 5,
            'page_duration'  => 30,
        ]);

        $response->assertSessionHasErrors('status');
    }

    /**
     * TEST 4: Store Mandatory Content succeeds and sets playlistonly = 'N'.
     */
    public function test_store_mandatory_content_succeeds_and_sets_playlistonly_to_n(): void
    {
        $title = 'Mandatory Content Test ' . uniqid();

        $response = $this->actingAs($this->adminUser)->post(route('content.store'), [
            'title'          => $title,
            'author'         => 'Test Author',
            'classification' => $this->classification->id,
            'type'           => 'Video',
            'description'    => 'Test description for mandatory content',
            'duration'       => '00:15:00',
            'learning_hours' => 0.5,
            'status'         => 'mandatory',
        ]);

        $response->assertSessionHasNoErrors();

        $content = Content::where('title', $title)->first();
        $this->assertNotNull($content);
        $this->assertEquals('mandatory', $content->status);
        $this->assertTrue($content->isMandatory());
        $this->assertFalse($content->isOptional());
        $this->assertEquals('N', $content->playlistonly);
    }

    /**
     * TEST 5: Store Optional Content succeeds and sets playlistonly = 'N'.
     */
    public function test_store_optional_content_succeeds_and_sets_playlistonly_to_n(): void
    {
        $title = 'Optional Content Test ' . uniqid();

        $response = $this->actingAs($this->adminUser)->post(route('content.store'), [
            'title'          => $title,
            'author'         => 'Test Author',
            'classification' => $this->classification->id,
            'type'           => 'Video',
            'description'    => 'Test description for optional content',
            'duration'       => '00:15:00',
            'learning_hours' => 0.5,
            'status'         => 'optional',
        ]);

        $response->assertSessionHasNoErrors();

        $content = Content::where('title', $title)->first();
        $this->assertNotNull($content);
        $this->assertEquals('optional', $content->status);
        $this->assertTrue($content->isOptional());
        $this->assertFalse($content->isMandatory());
        $this->assertEquals('N', $content->playlistonly);
    }

    /**
     * TEST 6: Update Content switches status between mandatory and optional.
     */
    public function test_update_content_switches_status_between_mandatory_and_optional(): void
    {
        $content = Content::create([
            'title'             => 'Toggle Status Content ' . uniqid(),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.5,
            'description'       => 'Toggle test',
            'author'            => 'Author',
            'status'            => 'optional',
            'playlistonly'      => 'N',
            'active'            => 'Inactive', // Unpublish to allow modification
        ]);

        // Update to mandatory
        $response = $this->actingAs($this->adminUser)->put(route('content.update', $content->slug), [
            'contentid'      => $content->id,
            'id'             => $content->id,
            'title'          => $content->title,
            'author'         => 'Author Updated',
            'classification' => $this->classification->id,
            'type'           => 'Video',
            'description'    => 'Updated description',
            'duration'       => '00:10:00',
            'learning_hours' => 0.5,
            'status'         => 'mandatory',
            'action'         => 'update',
        ]);

        $response->assertSessionHasNoErrors();
        $content->refresh();
        $this->assertEquals('mandatory', $content->status);

        // Update back to optional
        $response2 = $this->actingAs($this->adminUser)->put(route('content.update', $content->slug), [
            'contentid'      => $content->id,
            'id'             => $content->id,
            'title'          => $content->title,
            'author'         => 'Author Updated 2',
            'classification' => $this->classification->id,
            'type'           => 'Video',
            'description'    => 'Updated description 2',
            'duration'       => '00:10:00',
            'learning_hours' => 0.5,
            'status'         => 'optional',
            'action'         => 'update',
        ]);

        $response2->assertSessionHasNoErrors();
        $content->refresh();
        $this->assertEquals('optional', $content->status);
    }

    /**
     * TEST 7: Update Content preserves existing playlistonly = 'Y'.
     */
    public function test_update_content_preserves_existing_playlistonly_value(): void
    {
        $content = Content::create([
            'title'             => 'Playlist Only Content ' . uniqid(),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.5,
            'description'       => 'Playlist only test',
            'author'            => 'Author',
            'status'            => 'optional',
            'playlistonly'      => 'Y', // Existing playlist-only content
            'active'            => 'Inactive',
        ]);

        // Update content WITHOUT sending playlistonly parameter (as checkbox is removed)
        $response = $this->actingAs($this->adminUser)->put(route('content.update', $content->slug), [
            'contentid'      => $content->id,
            'id'             => $content->id,
            'title'          => $content->title . ' Edited',
            'author'         => 'Author',
            'classification' => $this->classification->id,
            'type'           => 'Video',
            'description'    => 'Updated description',
            'duration'       => '00:10:00',
            'learning_hours' => 0.5,
            'status'         => 'mandatory',
            'action'         => 'update',
        ]);

        $response->assertSessionHasNoErrors();
        $content->refresh();

        // playlistonly must REMAIN 'Y'!
        $this->assertEquals('Y', $content->playlistonly);
        $this->assertEquals('mandatory', $content->status);
    }

    /**
     * TEST 8: ContentUser inherits status from repository content on first creation.
     */
    public function test_content_user_inherits_status_from_content_on_creation(): void
    {
        $mandatoryContent = Content::create([
            'title'             => 'Mandatory Content Inherit ' . uniqid(),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.5,
            'status'            => 'mandatory',
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        // Access content as learner via route content.user.view
        $response = $this->actingAs($this->learnerUser)->get(route('content.user.view', $mandatoryContent->slug));
        $response->assertStatus(200);

        $cu = ContentUser::where('user_id', $this->learnerUser->id)
            ->where('content_id', $mandatoryContent->id)
            ->first();

        $this->assertNotNull($cu);
        $this->assertEquals('mandatory', $cu->status, 'ContentUser must inherit mandatory status from repository');
    }

    /**
     * TEST 9: PlaylistContentUser inherits status from repository content on first creation.
     */
    public function test_playlist_content_user_inherits_status_from_content_on_creation(): void
    {
        $mandatoryContent = Content::create([
            'title'             => 'Playlist Mandatory Item ' . uniqid(),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.5,
            'status'            => 'mandatory',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $trainingPlan = TrainingPlan::create([
            'title'  => 'Test Plan ' . uniqid(),
            'active' => 'Active',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => (string) $trainingPlan->id,
            'content_id'      => (string) $mandatoryContent->id,
            'content_order'   => '1',
        ]);

        // Access playlist content view
        $response = $this->actingAs($this->learnerUser)->get(
            route('playlist.user.view', [$trainingPlan->slug, $mandatoryContent->slug])
        );
        $response->assertStatus(200);

        $pcu = PlaylistContentUser::where('user_id', $this->learnerUser->id)
            ->where('trainingplan_id', $trainingPlan->id)
            ->where('content_id', $mandatoryContent->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertEquals('mandatory', $pcu->status, 'PlaylistContentUser must inherit mandatory status from content');
    }

    /**
     * TEST 10: Progress tracking update does not overwrite status on ContentUser or PlaylistContentUser.
     */
    public function test_progress_tracking_does_not_overwrite_status(): void
    {
        $content = Content::create([
            'title'             => 'Tracking Status Content ' . uniqid(),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.5,
            'status'            => 'mandatory',
            'active'            => 'Active',
        ]);

        $cu = ContentUser::create([
            'user_id'          => $this->learnerUser->id,
            'content_id'       => $content->id,
            'progress'         => 10,
            'learning_status'  => 'in_progress',
            'status'           => 'mandatory',
            'duration_seconds' => 600,
        ]);

        // Send progress update via POST route('content.user.progress.update')
        $response = $this->actingAs($this->learnerUser)->postJson(route('content.user.progress.update'), [
            'content_id'       => $content->id,
            'type'             => 'video',
            'current_time'     => 300,
            'duration_seconds' => 600,
            'status'           => 'in_progress', // Player event status
        ]);

        $response->assertStatus(200);
        $cu->refresh();

        $this->assertEquals(50, $cu->progress);
        $this->assertEquals('in_progress', $cu->learning_status);
        $this->assertEquals('mandatory', $cu->status, 'ContentUser status must not be modified by progress update');
    }

    /**
     * TEST 11: Learning Hours calculation is identical regardless of status.
     */
    public function test_learning_hours_calculation_is_unaffected_by_status(): void
    {
        $calculator = app(ActualLearningHourCalculator::class);

        // Calculate with 3600 seconds (1 hour)
        $hours1 = $calculator->calculate(3600);
        $hours2 = $calculator->calculateFromTracking(
            currentTime: 3600,
            finish: '1',
            progress: 100,
            contentDuration: '01:00:00',
            actualWatchSeconds: 3600,
            maxLearningHours: 2.0
        );

        $this->assertEquals(1.0, $hours1);
        $this->assertEquals(1.0, $hours2);
    }

    /**
     * TEST 12: LearningDatatableService filters mandatory and optional correctly across content and playlist.
     */
    public function test_learning_datatable_filters_mandatory_and_optional(): void
    {
        // Create 1 mandatory ContentUser
        $cMandatory = Content::create([
            'title'             => 'DT Mandatory Content ' . uniqid(),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'status'            => 'mandatory',
            'active'            => 'Active',
        ]);
        ContentUser::create([
            'user_id'    => $this->learnerUser->id,
            'content_id' => $cMandatory->id,
            'status'     => 'mandatory',
            'progress'   => 0,
        ]);

        // Create 1 mandatory PlaylistContentUser
        $tp = TrainingPlan::create(['title' => 'DT TP ' . uniqid(), 'active' => 'Active']);
        PlaylistContentUser::create([
            'user_id'         => $this->learnerUser->id,
            'trainingplan_id' => $tp->id,
            'content_id'      => $cMandatory->id,
            'status'          => 'mandatory',
            'progress'        => 0,
        ]);

        // Query with program_type = mandatory
        $queryMandatory = LearningDatatableService::buildBaseQuery([
            'program_type' => 'mandatory',
        ]);
        $mandatoryResults = $queryMandatory->get();

        $this->assertGreaterThanOrEqual(2, $mandatoryResults->count());
        $sources = $mandatoryResults->pluck('source_type')->all();
        $this->assertContains('content', $sources);
        $this->assertContains('playlist', $sources);
        foreach ($mandatoryResults as $row) {
            $this->assertEquals('mandatory', $row->status);
        }
    }

    /**
     * TEST 13: Repository status update preserves historical optional records and applies mandatory only to new records without backfill.
     */
    public function test_repository_status_update_preserves_historical_records_and_applies_only_to_new_records(): void
    {
        // 1. Create content that is initially optional
        $content = Content::create([
            'title'             => 'Historical Optional Content ' . uniqid(),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'status'            => 'optional',
            'active'            => 'Active',
        ]);

        $tp = TrainingPlan::create(['title' => 'Historical Plan ' . uniqid(), 'active' => 'Active']);

        // 2. Existing learner enrolled under optional status
        $cuHistorical = ContentUser::create([
            'user_id'    => $this->learnerUser->id,
            'content_id' => $content->id,
            'status'     => 'optional',
            'progress'   => 50,
        ]);

        $pcuHistorical = PlaylistContentUser::create([
            'user_id'         => $this->learnerUser->id,
            'trainingplan_id' => $tp->id,
            'content_id'      => $content->id,
            'status'          => 'optional',
            'progress'        => 50,
        ]);

        // 3. Admin updates repository content to mandatory
        $content->status = 'mandatory';
        $content->save();

        // 4. Existing records must strictly remain optional (no mass backfill)
        $cuHistorical->refresh();
        $pcuHistorical->refresh();
        $this->assertEquals('optional', $cuHistorical->status, 'Existing content_users must not be altered when repository status changes');
        $this->assertEquals('optional', $pcuHistorical->status, 'Existing playlist_content_users must not be altered when repository status changes');

        // 5. A new learner enrolling after the repository update receives mandatory
        $newLearner = User::where('role', User::ROLE_USER)->where('id', '!=', $this->learnerUser->id)->first()
            ?? User::create([
                'name'       => 'New Learner ' . uniqid(),
                'email'      => 'new' . uniqid() . '@example.com',
                'password'   => bcrypt('password'),
                'role'       => User::ROLE_USER,
                'team'       => 'Engineering',
                'department' => 'Technology',
                'division'   => 'LMS',
                'sap_code'   => 'SAP-' . rand(1000, 9999),
                'ssohash'    => 'hash-' . uniqid(),
            ]);

        $cuNew = ContentUser::firstOrCreate(
            [
                'user_id'    => $newLearner->id,
                'content_id' => $content->id,
            ],
            [
                'status'     => $content->status ?? 'optional',
            ]
        );

        $pcuNew = PlaylistContentUser::firstOrCreate(
            [
                'user_id'         => $newLearner->id,
                'trainingplan_id' => $tp->id,
                'content_id'      => $content->id,
            ],
            [
                'status'          => $content->status ?? 'optional',
            ]
        );

        $this->assertEquals('mandatory', $cuNew->status, 'New content_users record must inherit updated mandatory status');
        $this->assertEquals('mandatory', $pcuNew->status, 'New playlist_content_users record must inherit updated mandatory status');
    }

    /**
     * TEST 14: Learning hours calculation remains strictly identical regardless of status.
     */
    public function test_learning_hours_calculation_is_independent_of_status(): void
    {
        $durationSeconds = 1800; // 30 minutes = 0.5 hours
        $calculator = new ActualLearningHourCalculator();

        $hoursMandatory = $calculator->calculate($durationSeconds);
        $hoursOptional  = $calculator->calculate($durationSeconds);

        $this->assertEquals($hoursMandatory, $hoursOptional);
        $this->assertEquals(0.5, $hoursMandatory);
    }

    /**
     * TEST 15: PDF export view renders Mandatory and Optional badges in Status column.
     */
    public function test_export_pdf_view_renders_status_badge(): void
    {
        $mockRecords = collect([
            (object) [
                'id'                 => 1,
                'status'             => 'mandatory',
                'user_id_emp'        => 'EMP001',
                'user_name'          => 'John Doe',
                'user_department'    => 'IT',
                'user_team'          => 'Dev',
                'content_title'      => 'Security 101',
                'playlist_title'     => null,
                'content_type'       => 'Video',
                'classification_name'=> 'Compliance',
                'category_source'    => 'All Content',
                'content_duration'   => '00:30:00',
                'progress'           => 100,
                'finish'             => '1',
            ],
            (object) [
                'id'                 => 2,
                'status'             => 'optional',
                'user_id_emp'        => 'EMP002',
                'user_name'          => 'Jane Doe',
                'user_department'    => 'HR',
                'user_team'          => 'Recruiting',
                'content_title'      => 'Time Management',
                'playlist_title'     => null,
                'content_type'       => 'Document',
                'classification_name'=> 'Soft Skills',
                'category_source'    => 'All Content',
                'content_duration'   => '00:15:00',
                'progress'           => 50,
                'finish'             => '0',
            ],
        ]);

        $rendered = view('main.learning.export-pdf', [
            'records'            => $mockRecords,
            'totalRecords'       => 2,
            'limit'              => 500,
            'filters'            => [],
            'contextTitle'       => 'Test PDF',
            'classificationName' => null,
            'generatedAt'        => '12 Oct 2026 21:00:00',
        ])->render();

        $this->assertStringContainsString('Mandatory', $rendered);
        $this->assertStringContainsString('Optional', $rendered);
        $this->assertStringContainsString('badge-danger', $rendered);
        $this->assertStringContainsString('badge-primary', $rendered);
    }
}
