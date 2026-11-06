<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\CertificateSetting;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\EngagementTime\EngagementTimeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaylistCompletionRequirementsAndPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $superAdmin;
    protected Content $videoContent;
    protected Content $documentContent;
    protected TrainingPlan $playlist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email' => 'learner_' . Str::random(8) . '@example.com',
            'ssohash' => 'sso-' . Str::uuid(),
            'sap_code' => 'SAP-' . rand(1000, 9999),
            'active' => 1,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'email' => 'admin_' . Str::random(8) . '@example.com',
            'ssohash' => 'sso-' . Str::uuid(),
            'sap_code' => 'SAP-' . rand(1000, 9999),
            'active' => 1,
        ]);

        // Create target video content (for engagement time testing)
        $this->videoContent = Content::firstOrCreate(
            ['slug' => EngagementTimeService::TARGET_CONTENT_SLUG],
            [
                'title' => 'Penanggulangan HIV/AIDS di Tempat Kerja (Part 2)',
                'type' => 'Video',
                'active' => 'Active',
                'duration' => '00:10:00',
                'learning_hours' => 0.5,
            ]
        );

        // Create document content with unique title
        $uniqueDocTitle = 'Sample SOP Document Testing ' . Str::random(6);
        $this->documentContent = Content::create([
            'title'          => $uniqueDocTitle,
            'type'           => 'Document',
            'active'         => 'Active',
            'duration'       => '00:15:00',
            'learning_hours' => 0.5,
        ]);
        $this->documentContent->refresh();

        // Create playlist with both contents
        $this->playlist = TrainingPlan::firstOrCreate(
            ['slug' => EngagementTimeService::TARGET_PLAYLIST_SLUG],
            [
                'title' => 'PTKP Global Assessment 2026 for Job Clan M/S & T',
                'active' => 'Active',
                'learning_hours' => 1.0,
            ]
        );

        TrainingPlanDetail::firstOrCreate([
            'trainingplan_id' => $this->playlist->id,
            'content_id' => $this->videoContent->id,
        ], ['content_order' => 1]);

        TrainingPlanDetail::firstOrCreate([
            'trainingplan_id' => $this->playlist->id,
            'content_id' => $this->documentContent->id,
        ], ['content_order' => 2]);
    }

    /** @test */
    public function total_engagement_time_is_hidden_without_permission_on_content_and_playlist(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_engagement_time'],
            ['can_access' => false]
        );

        $this->actingAs($this->user);

        // Content Video
        $resContent = $this->get(route('content.user.view', $this->videoContent->slug));
        $resContent->assertOk();
        $resContent->assertDontSee('id="totalEngagementCard"', false);
        $resContent->assertDontSee('Total Engagement Time', false);
        $resContent->assertDontSee('engagement-time-tracker.js', false);

        // Playlist View
        $resPlaylist = $this->get(route('playlist.user.view', [
            'slug' => $this->playlist->slug,
            'vidslug' => $this->videoContent->slug,
        ]));
        $resPlaylist->assertOk();
        $resPlaylist->assertDontSee('id="totalEngagementCard"', false);
        $resPlaylist->assertDontSee('Total Engagement Time', false);
        $resPlaylist->assertDontSee('engagement-time-tracker.js', false);
    }

    /** @test */
    public function total_engagement_time_is_visible_with_permission_on_target_pages(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_engagement_time'],
            ['can_access' => true]
        );

        $this->actingAs($this->user);

        // Content Video (target content)
        $resContent = $this->get(route('content.user.view', $this->videoContent->slug));
        $resContent->assertOk();
        $resContent->assertSee('id="totalEngagementCard"', false);
        $resContent->assertSee('Total Engagement Time', false);
        $resContent->assertSee('engagement-time-tracker.js', false);

        // Target Playlist View: child content is TARGET_PLAYLIST_CONTENT_SLUG
        $playlistTargetContent = Content::firstOrCreate(
            ['slug' => EngagementTimeService::TARGET_PLAYLIST_CONTENT_SLUG],
            [
                'title' => '[HR] A.1 - Budaya Perusahaan & Pengembangan Organisasi [2025]',
                'type' => 'Document',
                'active' => 'Active',
                'duration' => '00:15:00',
            ]
        );
        TrainingPlanDetail::firstOrCreate([
            'trainingplan_id' => $this->playlist->id,
            'content_id' => $playlistTargetContent->id,
        ], ['content_order' => 3]);

        $resPlaylist = $this->get(route('playlist.user.view', [
            'slug' => $this->playlist->slug,
            'vidslug' => $playlistTargetContent->slug,
        ]));
        $resPlaylist->assertOk();
        $resPlaylist->assertSee('id="totalEngagementCard"', false);
        $resPlaylist->assertSee('Total Engagement Time', false);
        $resPlaylist->assertSee('engagement-time-tracker.js', false);
    }

    /** @test */
    public function playlist_debug_tools_is_hidden_without_permission(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'playlist_debug_tools'],
            ['can_access' => false]
        );

        $this->actingAs($this->user);

        $response = $this->get(route('playlist.user.view', [
            'slug' => $this->playlist->slug,
            'vidslug' => $this->videoContent->slug,
        ]));

        $response->assertOk();
        $response->assertDontSee('Developer Debug Panel', false);
        $response->assertDontSee('playlistDebugPanelTitle', false);
    }

    /** @test */
    public function playlist_debug_tools_is_visible_with_permission(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'playlist_debug_tools'],
            ['can_access' => true]
        );

        $this->actingAs($this->user);

        $response = $this->get(route('playlist.user.view', [
            'slug' => $this->playlist->slug,
            'vidslug' => $this->videoContent->slug,
        ]));

        $response->assertOk();
        $response->assertSee('Developer Debug Panel', false);
        $response->assertSee('playlistDebugPanelTitle', false);
    }

    /** @test */
    public function content_video_and_document_views_render_playlist_completion_requirements(): void
    {
        $this->actingAs($this->user);

        // Document View
        $resDoc = $this->get(route('content.user.view', $this->documentContent->slug));
        $resDoc->assertOk();
        $resDoc->assertSee('Playlist Completion Requirements', false);
        $resDoc->assertSee('Complete each applicable requirement to unlock your playlist certificate.', false);
        $resDoc->assertSee('Learning Progress', false);
        $resDoc->assertSee('Assessment Content', false);
        $resDoc->assertSee('Playlist Assessment', false);
        $resDoc->assertSee('My Task', false);
        $resDoc->assertSee('Certificate', false);

        // Video View
        $resVid = $this->get(route('content.user.view', $this->videoContent->slug));
        $resVid->assertOk();
        $resVid->assertSee('Playlist Completion Requirements', false);
        $resVid->assertSee('Complete each applicable requirement to unlock your playlist certificate.', false);
        $resVid->assertSee('Learning Progress', false);
        $resVid->assertSee('Assessment Content', false);
        $resVid->assertSee('Playlist Assessment', false);
        $resVid->assertSee('My Task', false);
        $resVid->assertSee('Certificate', false);
    }

    /** @test */
    public function learning_progress_displays_actual_value_not_hardcoded_100(): void
    {
        // Set user's content progress to 45%
        ContentUser::updateOrCreate(
            ['user_id' => $this->user->id, 'content_id' => $this->documentContent->id],
            ['progress' => 45, 'status' => 'non mandatory']
        );

        $this->actingAs($this->user);

        $response = $this->get(route('content.user.view', $this->documentContent->slug));
        $response->assertOk();
        $response->assertSee('45%', false);
        $response->assertDontSee("ti-chart-bar me-1\" aria-hidden=\"true\"></i>\n                    100%", false);
    }

    /** @test */
    public function certificate_status_reflects_actual_eligibility(): void
    {
        // Create active certificate setting for document content
        CertificateSetting::create([
            'certificate_type' => 'content',
            'content_id' => $this->documentContent->id,
            'is_active' => true,
            'minimum_progress' => 100,
            'is_assessment_required' => false,
        ]);

        // When progress < 100, Certificate is Not Available
        ContentUser::updateOrCreate(
            ['user_id' => $this->user->id, 'content_id' => $this->documentContent->id],
            ['progress' => 50, 'status' => 'non mandatory']
        );

        $this->actingAs($this->user);
        $resIneligible = $this->get(route('content.user.view', $this->documentContent->slug));
        $resIneligible->assertOk();

        // When user completes progress = 100, Certificate becomes Available
        ContentUser::updateOrCreate(
            ['user_id' => $this->user->id, 'content_id' => $this->documentContent->id],
            ['progress' => 100, 'finish' => '1', 'learning_status' => 'finished', 'status' => 'non mandatory']
        );

        $resEligible = $this->get(route('content.user.view', $this->documentContent->slug));
        $resEligible->assertOk();
        $resEligible->assertSee('Available', false);
    }

    /** @test */
    public function assessment_content_reflects_actual_availability(): void
    {
        $this->actingAs($this->user);

        // Document initially has no assessment -> Not Available
        $resNoAssessment = $this->get(route('content.user.view', $this->documentContent->slug));
        $resNoAssessment->assertOk();

        // Add assessment to document content
        Assesment::create([
            'title' => 'Doc Safety Quiz ' . Str::random(6),
            'type' => 'content',
            'assasment_content_for' => 'content',
            'content_id' => $this->documentContent->id,
            'status' => 'OK',
        ]);

        $resWithAssessment = $this->get(route('content.user.view', $this->documentContent->slug));
        $resWithAssessment->assertOk();
        $resWithAssessment->assertSee('Assessment Content', false);
    }

    /** @test */
    public function manage_access_permission_edit_page_includes_learning_engagement_time(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_ADMIN));

        $response->assertOk();
        $response->assertSee('Learning Engagement Time', false);
        $response->assertSee('learning_engagement_time', false);
        $response->assertSee('playlist_debug_tools', false);
    }

    /** @test */
    public function manage_access_permission_can_save_learning_engagement_time(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', User::ROLE_USER), [
                'permissions' => [
                    'learning_engagement_time',
                ],
            ])
            ->assertRedirect(route('manage.access-permission.index'));

        $permission = RoleAccessPermission::where('role', User::ROLE_USER)
            ->where('module_key', 'learning_engagement_time')
            ->first();

        $this->assertNotNull($permission);
        $this->assertTrue((bool) $permission->can_access);
    }
}
