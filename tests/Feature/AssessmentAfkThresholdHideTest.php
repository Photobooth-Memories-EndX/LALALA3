<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentAfkThresholdHideTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'       => 'Admin Tester ' . uniqid(),
            'email'      => 'admin_' . uniqid() . '@example.com',
            'password'   => bcrypt('secret'),
            'role'       => 2,
            'team'       => 'TEST-TEAM',
            'department' => 'TEST-DEPT',
            'division'   => 'TEST-DIV',
            'id_emp'     => 'EMP' . rand(10000, 99999),
            'ssohash'    => Str::random(32),
            'sap_code'   => 'SAP' . rand(1000, 9999),
            'active'     => 1,
        ]);

        $this->regularUser = User::create([
            'name'       => 'Regular Tester ' . uniqid(),
            'email'      => 'user_' . uniqid() . '@example.com',
            'password'   => bcrypt('secret'),
            'role'       => 1,
            'team'       => 'TEST-TEAM',
            'department' => 'TEST-DEPT',
            'division'   => 'TEST-DIV',
            'id_emp'     => 'EMP' . rand(10000, 99999),
            'ssohash'    => Str::random(32),
            'sap_code'   => 'SAP' . rand(1000, 9999),
            'active'     => 1,
        ]);
    }

    public function test_manage_assessment_create_view_does_not_contain_afk_threshold_field(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.create'));

        $response->assertStatus(200);
        $response->assertDontSee('AFK Inactivity Threshold');
        $response->assertDontSee('afk_threshold_seconds');
        $response->assertDontSee('afk-threshold-seconds');
        $response->assertDontSee('afk_section_wrapper');
    }

    public function test_manage_assessment_edit_view_does_not_contain_afk_threshold_field(): void
    {
        $assessment = Assesment::create([
            'title'           => 'Edit Test Assessment ' . uniqid(),
            'type'            => 'global',
            'status'          => 'OK',
            'passing_grade'   => 70,
            'batch'           => 1,
            'afk_threshold_seconds' => 6,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.edit', $assessment->slug));

        $response->assertStatus(200);
        $response->assertDontSee('AFK Inactivity Threshold');
        $response->assertDontSee('afk_threshold_seconds');
        $response->assertDontSee('afk-threshold-seconds');
        $response->assertDontSee('afk_section_wrapper');
    }

    public function test_store_assessment_sets_default_afk_threshold_without_request_input(): void
    {
        $payload = [
            'title'                 => 'New Assessment ' . uniqid(),
            'type'                  => 'global',
            'passing_grade'         => 80,
            'has_access_period'     => '0',
            'timer'                 => 'n',
            'assasment_content_for' => 'content',
        ];

        $response = $this->actingAs($this->admin)->post(route('assesment.store'), $payload);

        $response->assertSessionHasNoErrors();
        $assessment = Assesment::where('title', $payload['title'])->firstOrFail();

        $this->assertEquals(60, $assessment->getRawOriginal('afk_threshold_seconds'));
        $this->assertEquals(60, $assessment->afk_threshold_seconds);
    }

    public function test_update_assessment_normalizes_afk_threshold_to_60(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Legacy Assessment ' . uniqid(),
            'type'                  => 'global',
            'status'                => 'OK',
            'passing_grade'         => 70,
            'batch'                 => 1,
            'afk_threshold_seconds' => 6, // Legacy custom value
        ]);

        $payload = [
            'title'                 => 'Updated Title ' . uniqid(),
            'type'                  => 'global',
            'passing_grade'         => 75,
            'has_access_period'     => '0',
            'timer'                 => 'n',
            'assasment_content_for' => 'content',
        ];

        $response = $this->actingAs($this->admin)->put(route('assesment.update', $assessment->slug), $payload);

        $response->assertSessionHasNoErrors();
        $assessment->refresh();

        $this->assertEquals(60, $assessment->getRawOriginal('afk_threshold_seconds'));
        $this->assertEquals(60, $assessment->afk_threshold_seconds);
    }

    public function test_legacy_assessment_with_custom_threshold_normalizes_to_60_at_runtime(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Old Legacy Assessment ' . uniqid(),
            'type'                  => 'global',
            'status'                => 'OK',
            'passing_grade'         => 70,
            'batch'                 => 1,
            'afk_threshold_seconds' => 6,
        ]);

        // Accessor normalizes to canonical default 60
        $this->assertEquals(60, $assessment->afk_threshold_seconds);
    }

    public function test_candidate_assessment_view_uses_60_seconds_effective_afk_threshold(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Candidate View Test ' . uniqid(),
            'type'                  => 'global',
            'status'                => 'OK',
            'passing_grade'         => 70,
            'batch'                 => 1,
            'afk_threshold_seconds' => 6, // Even if raw DB had 6
        ]);

        AssesmentQuestion::create([
            'assesment_id'   => $assessment->id,
            'question'       => 'Test question 1',
            'answer'         => 'a',
            'option'         => ['a' => 'Option A', 'b' => 'Option B'],
        ]);

        $response = $this->actingAs($this->regularUser)->get(route('assesment.user.view', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('const afkThresholdSeconds = 60;', false);
    }
}
