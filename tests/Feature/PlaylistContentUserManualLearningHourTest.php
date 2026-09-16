<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaylistContentUserManualLearningHourTest extends TestCase
{
    protected User $admin;
    protected User $regularUser;
    protected ?User $testUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $this->admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::first();

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? User::factory()->make(['role' => User::ROLE_USER]);

        $baseUser = User::first();
        $userAttrs = $baseUser->toArray();
        unset($userAttrs['id']);
        $userAttrs['password'] = bcrypt('secret');
        $userAttrs['email'] = 'test_feat_lh_' . uniqid() . '@example.com';
        $userAttrs['id_emp'] = 'TLH' . rand(10000, 99999);
        $userAttrs['ssohash'] = md5(uniqid());
        $userAttrs['name'] = 'Test Feature LH User ' . uniqid();
        $this->testUser = User::create($userAttrs);
    }

    protected function tearDown(): void
    {
        if ($this->testUser) {
            DB::table('playlist_content_users')->where('user_id', $this->testUser->id)->delete();
            $this->testUser->delete();
        }
        parent::tearDown();
    }

    public function test_authorized_user_can_see_bulk_calculate_button_and_modal(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('id="btn-bulk-calculate"', $html);
        $this->assertStringContainsString('Bulk Calculate Learning Hours', $html);
        $this->assertStringContainsString('id="modal-bulk-calculate"', $html);
        $this->assertStringContainsString('Calculate Playlist Content User Learning Hours?', $html);
        $this->assertStringContainsString('calc-dry-run', $html);
        $this->assertStringContainsString('btn-start-calculate', $html);
    }

    public function test_guest_is_redirected_from_calculate_endpoint(): void
    {
        $response = $this->postJson('/manage/learning-hours/playlist-content-user/calculate', [
            'mode' => 'all',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthorized_user_cannot_access_calculate_endpoint(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/manage/learning-hours/playlist-content-user/calculate', [
            'mode' => 'all',
        ]);

        $response->assertStatus(403);
    }

    public function test_calculate_validation_fails_on_invalid_mode(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/manage/learning-hours/playlist-content-user/calculate', [
            'mode' => 'invalid_mode',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mode']);
    }

    public function test_calculate_dry_run_via_post_endpoint(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $response = $this->actingAs($this->admin)->postJson('/manage/learning-hours/playlist-content-user/calculate', [
            'mode'    => 'filtered',
            'dry_run' => true,
            'user_id' => $this->testUser->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'summary' => [
                'dry_run' => true,
                'mode'    => 'filtered',
            ],
        ]);

        // Ensure DB was NOT mutated
        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertNull($row->learning_hour);
    }

    public function test_calculate_updates_database_and_preserves_current_time(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $response = $this->actingAs($this->admin)->postJson('/manage/learning-hours/playlist-content-user/calculate', [
            'mode'    => 'filtered',
            'dry_run' => false,
            'user_id' => $this->testUser->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'summary' => [
                'dry_run' => false,
                'mode'    => 'filtered',
            ],
        ]);

        // Database updated to 0.3417
        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.3417', number_format((float) $row->learning_hour, 4, '.', ''));
        // current_time, progress, finish MUST NOT CHANGE
        $this->assertEquals(1230, (int) $row->current_time);
        $this->assertEquals(50, (int) $row->progress);
        $this->assertEquals('0', (string) $row->finish);
    }
}
