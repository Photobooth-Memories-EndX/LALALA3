<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LearningManagementDownloadPermissionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $superAdmin;
    protected User $regularUser;

    protected string $contentSlug = '5s-whiteboard-animation';
    protected int $playlistId = 1;
    protected string $classificationSlug = 'leadership';
    protected string $userSsoHash = '3f698139675fa4f39f13cb67dfe5a863baade409';

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::factory()->create(['role' => 2]);
        $this->superAdmin = User::where('role', '3')->first() ?? User::factory()->create(['role' => 3]);
        $this->regularUser = User::where('role', '1')->first() ?? User::factory()->create(['role' => 1]);

        $content = Content::first();
        if ($content) {
            $this->contentSlug = $content->slug;
        }

        $playlist = TrainingPlan::first();
        if ($playlist) {
            $this->playlistId = (int) $playlist->id;
        }

        $class = Classification::first();
        if ($class) {
            $this->classificationSlug = $class->slug;
        }

        $userWithHash = User::whereNotNull('ssohash')->where('ssohash', '!=', '')->first();
        if ($userWithHash) {
            $this->userSsoHash = $userWithHash->ssohash;
        }
    }

    /**
     * Test 1: Access Permission Edit page displays Learning Management checkbox
     * at the very bottom of the Debugger Developer Tools section.
     */
    public function test_access_permission_edit_page_renders_learning_management_at_bottom_of_debugger_tools(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', 3));

        $response->assertOk();
        $content = $response->getContent();

        // Checkbox exists with correct value
        $this->assertMatchesRegularExpression(
            '/<input[^>]*type="checkbox"[^>]*value="learning_management"/i',
            $content,
            'Learning Management checkbox must be rendered in Edit Access Permission'
        );

        $response->assertSee('Learning Management');
        $response->assertSee('learning_management');

        // Verify order: learning_hours_management, playlist_debug_tools, learning_management
        $posHours = strpos($content, 'value="learning_hours_management"');
        $posDebug = strpos($content, 'value="playlist_debug_tools"');
        $posLearning = strpos($content, 'value="learning_management"');

        $this->assertNotFalse($posHours, 'learning_hours_management must be present');
        $this->assertNotFalse($posDebug, 'playlist_debug_tools must be present');
        $this->assertNotFalse($posLearning, 'learning_management must be present');

        $this->assertTrue(
            $posLearning > $posDebug && $posDebug > $posHours,
            'learning_management must be rendered after all existing Debugger Developer Tools'
        );
    }

    /**
     * Test 2: Access Permission save can toggle learning_management (1 when checked, 0 when unchecked).
     */
    public function test_access_permission_save_toggles_learning_management_permission(): void
    {
        // 1. Uncheck learning_management
        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', 2), [
                'permissions' => [
                    'admin_management',
                    'record_management',
                    'content_repository',
                    'playlist_learning_path',
                ],
            ]);

        $response->assertRedirect(route('manage.access-permission.index'));

        $permDisabled = RoleAccessPermission::where('role', 2)
            ->where('module_key', 'learning_management')
            ->first();

        $this->assertNotNull($permDisabled);
        $this->assertFalse((bool) $permDisabled->can_access);

        // 2. Check learning_management
        $response2 = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', 2), [
                'permissions' => [
                    'admin_management',
                    'record_management',
                    'content_repository',
                    'playlist_learning_path',
                    'learning_management',
                ],
            ]);

        $response2->assertRedirect(route('manage.access-permission.index'));

        $permEnabled = RoleAccessPermission::where('role', 2)
            ->where('module_key', 'learning_management')
            ->first();

        $this->assertNotNull($permEnabled);
        $this->assertTrue((bool) $permEnabled->can_access);
    }

    /**
     * Test 3: Download buttons are VISIBLE when learning_management = 1.
     */
    public function test_download_buttons_visible_when_permission_is_active(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'learning_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'record_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'user_management'],
            ['can_access' => true]
        );

        // A.1 All Record
        $resAll = $this->actingAs($this->admin)->get(route('manage.record.all'));
        $resAll->assertOk();
        $resAll->assertSee('id="btn-export-all-combined"', false);
        $resAll->assertSee('id="btn-download-dropdown"', false);

        // A.2 Online Record
        $resOnline = $this->actingAs($this->admin)->get(route('manage.record.online'));
        $resOnline->assertOk();
        $resOnline->assertSee('id="btn-download-dropdown"', false);

        // A.3 Offline Record
        $resOffline = $this->actingAs($this->admin)->get(route('manage.record.offline'));
        $resOffline->assertOk();
        $resOffline->assertSee('id="btn-download-dropdown"', false);

        // B.1 User Profile
        $resProfile = $this->actingAs($this->admin)->get(route('user.manage.profile', $this->userSsoHash));
        $resProfile->assertOk();
        $resProfile->assertSee('id="btn-profile-download-dropdown"', false);

        // B.2 Online Classification
        $resOnlineClass = $this->actingAs($this->admin)->get(route('manage.record.online.classification', $this->classificationSlug));
        $resOnlineClass->assertOk();
        $resOnlineClass->assertSee('id="btn-download-online-export"', false);

        // B.3 Learning Content Detail
        $resContent = $this->actingAs($this->admin)->get(route('learning.manage.content', $this->contentSlug));
        $resContent->assertOk();
        $resContent->assertSee('id="btn-download-dropdown"', false);

        // B.4 Playlist Detail
        $resPlaylist = $this->actingAs($this->admin)->get(route('learning.manage.playlist', $this->playlistId));
        $resPlaylist->assertOk();
        $resPlaylist->assertSee('id="btn-download-dropdown"', false);

        // C.1 Offline Classification
        $resOfflineClass = $this->actingAs($this->admin)->get(route('manage.record.offline.classification', $this->classificationSlug));
        $resOfflineClass->assertOk();
        $resOfflineClass->assertSee('id="btn-download-export"', false);

        // D.1 All Record Classification
        $resAllClass = $this->actingAs($this->admin)->get(route('manage.record.all.classification', $this->classificationSlug));
        $resAllClass->assertOk();
        $resAllClass->assertSee('id="btn-all-download"', false);

        // D.3 Content Detail from All Record
        $resContentAll = $this->actingAs($this->admin)->get(route('learning.manage.content', [
            'slug' => $this->contentSlug,
            'from' => 'all',
            'classification' => $this->classificationSlug,
        ]));
        $resContentAll->assertOk();
        $resContentAll->assertSee('id="btn-download-dropdown"', false);
    }

    /**
     * Test 4: Download buttons are HIDDEN when learning_management = 0,
     * BUT the main pages remain accessible (200 OK) because record_management / user_management are active.
     */
    public function test_download_buttons_hidden_and_pages_accessible_when_permission_is_disabled(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'learning_management'],
            ['can_access' => false]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'record_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'user_management'],
            ['can_access' => true]
        );

        // A.1 All Record
        $resAll = $this->actingAs($this->admin)->get(route('manage.record.all'));
        $resAll->assertOk();
        $resAll->assertDontSee('id="btn-export-all-combined"', false);
        $resAll->assertDontSee('id="btn-all-export-excel"', false);

        // A.2 Online Record
        $resOnline = $this->actingAs($this->admin)->get(route('manage.record.online'));
        $resOnline->assertOk();
        $resOnline->assertDontSee('id="btn-export-online-xlsx"', false);

        // A.3 Offline Record
        $resOffline = $this->actingAs($this->admin)->get(route('manage.record.offline'));
        $resOffline->assertOk();
        $resOffline->assertDontSee('id="btn-export-offline-xlsx"', false);

        // B.1 User Profile
        $resProfile = $this->actingAs($this->admin)->get(route('user.manage.profile', $this->userSsoHash));
        $resProfile->assertOk();
        $resProfile->assertDontSee('id="btn-profile-download-dropdown"', false);
        $resProfile->assertDontSee('id="btn-profile-download-excel"', false);

        // B.2 Online Classification
        $resOnlineClass = $this->actingAs($this->admin)->get(route('manage.record.online.classification', $this->classificationSlug));
        $resOnlineClass->assertOk();
        $resOnlineClass->assertDontSee('id="btn-download-online-export"', false);
        $resOnlineClass->assertDontSee('id="btn-export-online-excel"', false);

        // B.3 Learning Content Detail
        $resContent = $this->actingAs($this->admin)->get(route('learning.manage.content', $this->contentSlug));
        $resContent->assertOk();
        $resContent->assertDontSee('id="btn-download-export"', false);

        // B.4 Playlist Detail
        $resPlaylist = $this->actingAs($this->admin)->get(route('learning.manage.playlist', $this->playlistId));
        $resPlaylist->assertOk();
        $resPlaylist->assertDontSee('id="btn-download-export"', false);

        // C.1 Offline Classification
        $resOfflineClass = $this->actingAs($this->admin)->get(route('manage.record.offline.classification', $this->classificationSlug));
        $resOfflineClass->assertOk();
        $resOfflineClass->assertDontSee('id="btn-download-export"', false);
        $resOfflineClass->assertDontSee('id="btn-export-classification-excel"', false);

        // D.1 All Record Classification
        $resAllClass = $this->actingAs($this->admin)->get(route('manage.record.all.classification', $this->classificationSlug));
        $resAllClass->assertOk();
        $resAllClass->assertDontSee('id="btn-all-download"', false);
    }

    /**
     * Test 5: Direct export URLs are rejected with HTTP 403 Forbidden when learning_management = 0.
     */
    public function test_direct_export_urls_rejected_with_403_when_permission_is_disabled(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'learning_management'],
            ['can_access' => false]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'record_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'user_management'],
            ['can_access' => true]
        );

        $exportUrls = [
            // All Record exports
            route('manage.record.all.export'),
            route('manage.record.all.export.pdf'),
            route('manage.record.all.export.csv'),

            // Online Record exports
            route('manage.record.online.export'),
            route('manage.record.online.export.pdf'),
            route('manage.record.online.export.csv'),
            route('manage.record.online.download'),

            // Offline Record exports
            route('manage.record.offline.export'),
            route('manage.record.offline.export.pdf'),
            route('manage.record.offline.export.csv'),

            // User profile exports
            route('user.manage.profile.export.excel', $this->userSsoHash),
            route('user.manage.profile.export.csv', $this->userSsoHash),
            route('user.manage.profile.export.pdf', $this->userSsoHash),

            // Online Classification exports
            route('manage.record.online.classification.export.excel', $this->classificationSlug),
            route('manage.record.online.classification.export.csv', $this->classificationSlug),
            route('manage.record.online.classification.export.pdf', $this->classificationSlug),
            route('manage.record.online.classification.download', $this->classificationSlug),

            // Offline Classification exports
            route('manage.record.offline.classification.export.excel', $this->classificationSlug),
            route('manage.record.offline.classification.export.csv', $this->classificationSlug),
            route('manage.record.offline.classification.export.pdf', $this->classificationSlug),
            route('manage.record.offline.classification.download', $this->classificationSlug),

            // All Classification exports
            route('manage.record.all.classification.export', $this->classificationSlug),
            route('manage.record.all.classification.download', $this->classificationSlug),

            // Export All Center
            route('manage.record.export-all.download'),

            // Learning exports
            route('manage.learning.download'),
            route('manage.learning.download.pdf'),
            route('manage.learning.download.csv'),

            // Content Detail direct download action
            route('learning.manage.content', ['slug' => $this->contentSlug, 'action' => 'download']),

            // Playlist Detail direct download action
            route('learning.manage.playlist', ['id' => $this->playlistId, 'action' => 'download']),
        ];

        foreach ($exportUrls as $url) {
            $resp = $this->actingAs($this->admin)->get($url);
            $this->assertEquals(
                403,
                $resp->getStatusCode(),
                "Direct export URL [{$url}] must return HTTP 403 Forbidden when learning_management = 0"
            );
        }
    }

    /**
     * Test 6: Re-enabling learning_management restores download button visibility and authorization.
     */
    public function test_reactivating_permission_restores_download_buttons(): void
    {
        // First disable
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'learning_management'],
            ['can_access' => false]
        );

        $resDisabled = $this->actingAs($this->admin)->get(route('manage.record.all'));
        $resDisabled->assertDontSee('id="btn-export-all-combined"', false);

        // Reactivate
        RoleAccessPermission::updateOrCreate(
            ['role' => 2, 'module_key' => 'learning_management'],
            ['can_access' => true]
        );

        $resEnabled = $this->actingAs($this->admin)->get(route('manage.record.all'));
        $resEnabled->assertSee('id="btn-export-all-combined"', false);
    }
}
