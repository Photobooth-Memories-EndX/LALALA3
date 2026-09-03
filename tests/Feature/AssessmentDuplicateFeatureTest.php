<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\User;
use Tests\TestCase;

class AssessmentDuplicateFeatureTest extends TestCase
{
    public function test_admin_can_duplicate_assessment_via_post(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $original = Assesment::create([
            'title'   => 'Feature Test Assessment ' . uniqid(),
            'status'  => 'OK',
            'type'    => 'global',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug));

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assessment duplicated successfully.');

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);

        // Cleanup
        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_admin_can_duplicate_assessment_via_clone_route_backward_compatibility(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $original = Assesment::create([
            'title'   => 'Backward Compat Test ' . uniqid(),
            'status'  => 'OK',
            'type'    => 'global',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('assesment.clone', $original->slug));

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assessment duplicated successfully.');

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);

        // Cleanup
        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_assessment_index_renders_duplicate_action_and_modal(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $response = $this->actingAs($admin)->get(route('assesment.index'));

        $response->assertStatus(200);
        $response->assertSee('Duplicate Assessment');
        $response->assertSee('modalDuplicateAssessment');
        $response->assertSee('Are you sure you want to duplicate this assessment?');
        $response->assertSee('Assessment Target:');
        $response->assertDontSee('duplicateContextSection');
        $response->assertDontSee('duplicate_target_content');
        $response->assertDontSee('duplicate_target_playlist');
        $response->assertDontSee('duplicate_trainingplan_id');
        $response->assertDontSee('onclick="return confirm(\'Clone this assessment ?\')"');
    }

    public function test_admin_can_duplicate_content_as_direct(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $original = Assesment::create([
            'title'           => 'Feature Direct Test ' . uniqid(),
            'status'          => 'OK',
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'content',
            'trainingplan_id' => null,
        ]);

        $contentCountBefore = \App\Models\Content::count();

        $response = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), []);

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assessment duplicated successfully.');

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);
        $this->assertSame('content', $duplicate->type);
        $this->assertNull($duplicate->trainingplan_id);
        $this->assertNull($duplicate->content_id);

        // No new Content record is created
        $this->assertSame($contentCountBefore, \App\Models\Content::count());

        // Original remains untouched
        $original->refresh();
        $this->assertSame('content', $original->content_for);
        $this->assertNull($original->trainingplan_id);
        $this->assertSame(45, (int) $original->content_id);

        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_admin_can_duplicate_content_as_playlist(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();
        $tp = \App\Models\TrainingPlan::first();

        $original = Assesment::create([
            'title'           => 'Feature Playlist Test ' . uniqid(),
            'status'          => 'OK',
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'content',
            'trainingplan_id' => null,
        ]);

        $contentCountBefore = \App\Models\Content::count();

        $response = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $tp->id,
            ]);

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assessment duplicated successfully.');

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);
        $this->assertSame('content', $duplicate->type);
        $this->assertSame('playlist', $duplicate->content_for);
        $this->assertSame((int) $tp->id, (int) $duplicate->trainingplan_id);
        $this->assertNull($duplicate->content_id);

        // No new Content record is created
        $this->assertSame($contentCountBefore, \App\Models\Content::count());

        // Original remains untouched
        $original->refresh();
        $this->assertSame('content', $original->content_for);
        $this->assertNull($original->trainingplan_id);
        $this->assertSame(45, (int) $original->content_id);

        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_duplicate_content_as_playlist_requires_valid_trainingplan_id(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $original = Assesment::create([
            'title'      => 'Feature Missing TP ' . uniqid(),
            'status'     => 'OK',
            'type'       => 'content',
            'content_id' => 45,
        ]);

        // Case 1: trainingplan_id missing
        $response1 = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), [
                'target_context' => 'playlist',
            ]);
        $response1->assertSessionHasErrors(['trainingplan_id']);

        // Case 2: trainingplan_id invalid / non-existent
        $response2 = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), [
                'target_context'  => 'playlist',
                'trainingplan_id' => 99999999,
            ]);
        $response2->assertSessionHasErrors(['trainingplan_id']);

        $original->delete();
    }

    public function test_duplicate_global_rejects_target_context_playlist(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();
        $tp = \App\Models\TrainingPlan::first();

        $original = Assesment::create([
            'title'  => 'Feature Global Reject ' . uniqid(),
            'status' => 'OK',
            'type'   => 'global',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $tp->id,
            ]);

        $response->assertSessionHasErrors(['target_context']);

        $original->delete();
    }

    public function test_duplicate_trainingplan_rejects_target_context_playlist(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();
        $tp = \App\Models\TrainingPlan::first();

        $original = Assesment::create([
            'title'           => 'Feature TP Reject ' . uniqid(),
            'status'          => 'OK',
            'type'            => 'trainingplan',
            'trainingplan_id' => $tp->id,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $tp->id,
            ]);

        $response->assertSessionHasErrors(['target_context']);

        $original->delete();
    }

    public function test_admin_can_duplicate_direct_content_assessment_without_target_context_parameters(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $original = Assesment::create([
            'title'           => 'Direct Content No Params ' . uniqid(),
            'status'          => 'OK',
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'content',
            'trainingplan_id' => null,
        ]);

        $contentCountBefore = \App\Models\Content::count();

        // Post without target_context parameters (direct duplicate flow like Global)
        $response = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), []);

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assessment duplicated successfully.');

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);
        $this->assertSame('content', $duplicate->type);
        $this->assertNull($duplicate->trainingplan_id);
        $this->assertNull($duplicate->content_id);

        // No new Content record is created
        $this->assertSame($contentCountBefore, \App\Models\Content::count());

        // Original remains untouched
        $original->refresh();
        $this->assertSame('content', $original->content_for);
        $this->assertNull($original->trainingplan_id);
        $this->assertSame(45, (int) $original->content_id);

        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_admin_can_duplicate_playlist_assessment_with_target_playlist(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();
        $tp = \App\Models\TrainingPlan::first();

        $original = Assesment::create([
            'title'           => 'Playlist Assessment Test ' . uniqid(),
            'status'          => 'OK',
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'playlist',
            'trainingplan_id' => $tp->id,
        ]);

        $contentCountBefore = \App\Models\Content::count();

        $response = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $tp->id,
            ]);

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assessment duplicated successfully.');

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);
        $this->assertSame('content', $duplicate->type);
        $this->assertSame('playlist', $duplicate->content_for);
        $this->assertSame((int) $tp->id, (int) $duplicate->trainingplan_id);
        $this->assertSame(45, (int) $duplicate->content_id);

        // No new Content record is created
        $this->assertSame($contentCountBefore, \App\Models\Content::count());

        // Original remains untouched
        $original->refresh();
        $this->assertSame('playlist', $original->content_for);
        $this->assertSame((int) $tp->id, (int) $original->trainingplan_id);
        $this->assertSame(45, (int) $original->content_id);

        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_admin_can_duplicate_playlist_assessment_without_parameters_preserves_playlist_and_content(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();
        $tp = \App\Models\TrainingPlan::first();

        $original = Assesment::create([
            'title'           => 'Playlist Standard Modal ' . uniqid(),
            'status'          => 'OK',
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'playlist',
            'trainingplan_id' => $tp->id,
        ]);

        $contentCountBefore = \App\Models\Content::count();

        // Post without parameters (standard modal submit)
        $response = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), []);

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assessment duplicated successfully.');

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);
        $this->assertSame('content', $duplicate->type);
        $this->assertSame('playlist', $duplicate->content_for);
        $this->assertSame((int) $tp->id, (int) $duplicate->trainingplan_id);
        $this->assertSame(45, (int) $duplicate->content_id);

        // No new Content record is created
        $this->assertSame($contentCountBefore, \App\Models\Content::count());

        // TrainingPlanDetail exists
        $this->assertTrue(\App\Models\TrainingPlanDetail::where('trainingplan_id', $tp->id)->where('content_id', 45)->exists());

        // Idempotency check: second duplicate does not duplicate TrainingPlanDetail
        $pivotCountBefore = \App\Models\TrainingPlanDetail::where('trainingplan_id', $tp->id)->where('content_id', 45)->count();
        $this->actingAs($admin)->post(route('assesment.duplicate', $original->slug), []);
        $pivotCountAfter = \App\Models\TrainingPlanDetail::where('trainingplan_id', $tp->id)->where('content_id', 45)->count();
        $this->assertSame($pivotCountBefore, $pivotCountAfter);

        $expectedTitle2 = $original->title . ' Copy 2';
        $duplicate2 = Assesment::where('title', $expectedTitle2)->first();

        // Original remains untouched
        $original->refresh();
        $this->assertSame('playlist', $original->content_for);
        $this->assertSame((int) $tp->id, (int) $original->trainingplan_id);
        $this->assertSame(45, (int) $original->content_id);

        if ($duplicate2) {
            $duplicate2->delete();
        }
        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_duplicate_assessment_edit_page_shows_retained_playlist_and_learning_content(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();
        $tp = \App\Models\TrainingPlan::first();

        $original = Assesment::create([
            'title'           => 'Edit Page Retained Test ' . uniqid(),
            'status'          => 'OK',
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'playlist',
            'trainingplan_id' => $tp->id,
        ]);

        // Duplicate assessment
        $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), []);

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);

        // Access edit page
        $editResponse = $this->actingAs($admin)
            ->get(route('assesment.edit', $duplicate->slug));

        $editResponse->assertStatus(200);

        // Both playlist and content should be retained
        $this->assertSame((int) $tp->id, (int) $duplicate->trainingplan_id);
        $this->assertSame(45, (int) $duplicate->content_id);
        $this->assertSame('playlist', $duplicate->content_for);

        // Verify playlist option is selected
        $editResponse->assertSee('value="' . $tp->id . '" selected', false);

        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_duplicate_playlist_assessment_preserves_questions_and_question_banks(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();
        $tp = \App\Models\TrainingPlan::first();

        $original = Assesment::create([
            'title'           => 'Playlist Questions Test ' . uniqid(),
            'status'          => 'OK',
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'playlist',
            'trainingplan_id' => $tp->id,
            'passing_grade'   => 85,
        ]);

        $question = \App\Models\AssesmentQuestion::create([
            'assesment_id' => $original->id,
            'question'     => 'Sample Question 1',
            'option'       => ['A' => 'Opt A', 'B' => 'Opt B'],
            'answer'       => 'A',
            'status'       => '1',
        ]);

        $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug), []);

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);

        // Configuration preserved
        $this->assertSame('playlist', $duplicate->content_for);
        $this->assertSame((int) $tp->id, (int) $duplicate->trainingplan_id);
        $this->assertSame(45, (int) $duplicate->content_id);
        $this->assertSame(85, (int) $duplicate->passing_grade);

        // Questions duplicated
        $dupQuestions = \App\Models\AssesmentQuestion::where('assesment_id', $duplicate->id)->get();
        $this->assertCount(1, $dupQuestions);
        $this->assertSame('Sample Question 1', $dupQuestions->first()->question);
        $this->assertNotEquals($question->id, $dupQuestions->first()->id);

        // Cleanup
        \App\Models\AssesmentQuestion::where('assesment_id', $duplicate->id)->delete();
        \App\Models\AssesmentQuestion::where('assesment_id', $original->id)->delete();
        $duplicate->delete();
        $original->delete();
    }
}
