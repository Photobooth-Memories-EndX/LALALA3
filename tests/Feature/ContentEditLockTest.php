<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContentEditLockTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'team'       => 'Management',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'role'       => User::ROLE_SUPER_ADMIN,
            'ssohash'    => 'test-admin-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ADMIN',
        ]);
        $this->actingAs($this->adminUser);

        $this->classification = Classification::create([
            'classification_name' => 'Engineering',
            'slug'                => 'engineering',
        ]);
    }

    public function test_content_model_helpers_work_correctly(): void
    {
        $published = Content::create([
            'title'             => 'Published Content Model Test',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $unpublished = Content::create([
            'title'             => 'Unpublished Content Model Test',
            'type'              => 'Document',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
        ]);

        $this->assertTrue($published->isPublished());
        $this->assertFalse($published->isUnpublished());

        $this->assertFalse($unpublished->isPublished());
        $this->assertTrue($unpublished->isUnpublished());
    }

    public function test_edit_view_renders_locked_state_when_content_is_published(): void
    {
        $content = Content::create([
            'title'             => 'Published Locked Document',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'page_duration'     => 30,
            'total_page'        => 20,
            'learning_hours'    => 0.5,
        ]);

        $response = $this->get(route('content.edit', $content->slug));

        $response->assertOk();
        $response->assertSee('Content is Currently Published');
        $response->assertSee('Unpublish This Content');
        // Save Content button should not be rendered in locked state
        $response->assertDontSee('value="update"', false);
    }

    public function test_edit_view_renders_editable_state_when_content_is_unpublished(): void
    {
        $content = Content::create([
            'title'             => 'Unpublished Editable Document',
            'type'              => 'Document',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'page_duration'     => 30,
            'total_page'        => 20,
            'learning_hours'    => 0.5,
        ]);

        $response = $this->get(route('content.edit', $content->slug));

        $response->assertOk();
        $response->assertDontSee('Content is Currently Published');
        $response->assertDontSee('Unpublish This Content');
        $response->assertSee('Save Content');
        $response->assertSee('Save & Publish', false);
    }

    public function test_direct_put_update_is_rejected_when_content_is_published(): void
    {
        $content = Content::create([
            'title'             => 'Original Published Title',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'author'            => 'Original Author',
            'description'       => 'Original Description',
        ]);

        $response = $this->put(route('content.update', $content->slug), [
            'contentid'      => $content->id,
            'title'          => 'Hacked Title Attempt',
            'author'         => 'Hacked Author',
            'classification' => $this->classification->id,
            'type'           => 'Document',
            'description'    => 'Hacked Description',
            'action'         => 'update',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $content->refresh();
        $this->assertSame('Original Published Title', $content->title);
        $this->assertSame('Original Author', $content->author);
        $this->assertSame('Active', $content->active);
    }

    public function test_unpublish_action_via_put_is_allowed_when_content_is_published(): void
    {
        $content = Content::create([
            'title'             => 'Content to Unpublish',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $response = $this->put(route('content.update', $content->slug), [
            'contentid' => $content->id,
            'action'    => 'unpublish',
        ]);

        $response->assertRedirect(route('content.edit', $content->slug));
        $response->assertSessionHas('success');

        $content->refresh();
        $this->assertSame('Inactive', $content->active);
        $this->assertTrue($content->isUnpublished());
    }

    public function test_put_update_succeeds_when_content_is_unpublished(): void
    {
        $content = Content::create([
            'title'             => 'Draft Content',
            'type'              => 'Document',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'author'            => 'Draft Author',
            'description'       => 'Draft Description',
            'total_page'        => 5,
            'page_duration'     => 30,
            'duration'          => '00:02:30',
            'learning_hours'    => 0.5,
        ]);

        $response = $this->put(route('content.update', $content->slug), [
            'contentid'              => $content->id,
            'title'                  => 'Updated Draft Title',
            'author'                 => 'Updated Author',
            'classification'         => $this->classification->id,
            'type'                   => 'Document',
            'description'            => 'Updated Description',
            'total_page'             => 10,
            'page_duration'          => 30,
            'require_page_duration'  => 1,
            'duration'               => '00:05:00',
            'learning_hours'         => 0.5,
            'date'                   => '2026-08-27',
            'action'                 => 'update',
        ]);

        $response->assertRedirect(route('content.edit', $content->slug));
        $response->assertSessionHas('success');

        $content->refresh();
        $this->assertSame('Updated Draft Title', $content->title);
        $this->assertSame('Updated Author', $content->author);
    }

    public function test_assign_is_blocked_when_content_is_published(): void
    {
        $content = Content::create([
            'title'             => 'Published Content For Assign',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $targetUser = User::factory()->create([
            'team'       => 'Operations',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-target-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-TARGET',
        ]);

        $response = $this->post(route('content.assign', $content->slug), [
            'content_id' => $content->id,
            'slug'       => $content->slug,
            'employee'   => [$targetUser->id],
            'action'     => 'assign',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $this->assertDatabaseMissing('content_users', [
            'content_id' => $content->id,
            'user_id'    => $targetUser->id,
        ]);
    }

    public function test_ajax_sync_learning_hours_is_rejected_when_content_is_published(): void
    {
        $content = Content::create([
            'title'             => 'Published Content For LH Sync',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'duration'          => '01:00:00',
            'learning_hours'    => 1.0,
        ]);

        $response = $this->postJson(route('content.synchronize-learning-hours', $content->slug));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_ajax_sync_total_page_is_rejected_when_content_is_published(): void
    {
        $content = Content::create([
            'title'             => 'Published Content For Page Sync',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'total_page'        => 10,
        ]);

        $response = $this->postJson(route('content.sync-total-page', $content->slug), [
            'id'         => $content->id,
            'total_page' => 25,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_ajax_delete_media_is_rejected_when_content_is_published(): void
    {
        $content = Content::create([
            'title'             => 'Published Content For Delete File',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'file'              => json_encode(['test_file.pdf']),
        ]);

        $response = $this->putJson(route('contentdelete.deletecontentajax', $content->slug));

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 422,
        ]);

        $content->refresh();
        $this->assertSame(json_encode(['test_file.pdf']), $content->file);
    }

    public function test_ajax_delete_thumbnail_is_rejected_when_content_is_published(): void
    {
        $content = Content::create([
            'title'             => 'Published Content For Delete Thumb',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $response = $this->putJson(route('contentdelete.deletethumbajax', $content->slug));

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 422,
        ]);
    }
}
