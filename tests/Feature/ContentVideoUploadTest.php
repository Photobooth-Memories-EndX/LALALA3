<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ContentVideoUploadTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('id', 1)->first() ?: User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $this->actingAs($this->adminUser);

        $this->classification = Classification::first() ?: Classification::create([
            'classification_name' => 'Testing',
            'slug' => 'testing',
        ]);
    }

    public function test_upload_video_ajax_saves_file_to_public_storage_tmp(): void
    {
        $fakeVideo = UploadedFile::fake()->create('sample_lesson.mp4', 1024, 'video/mp4');

        $response = $this->postJson(route('content.uploadfileajax'), [
            'file' => $fakeVideo,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['name', 'original_name']);

        $savedName = $response->json('name.0');
        $this->assertNotEmpty($savedName);

        $expectedPath = public_path('storage/files/tmp/' . $savedName);
        $this->assertTrue(File::exists($expectedPath), "File should exist at: {$expectedPath}");

        if (File::exists($expectedPath)) {
            File::delete($expectedPath);
        }
    }

    public function test_upload_video_ajax_rejects_unsupported_extensions(): void
    {
        $fakeFile = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');

        $response = $this->postJson(route('content.uploadfileajax'), [
            'file' => $fakeFile,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Only PDF, MP4, AVI, MOV, MKV, and WEBM files are allowed.',
        ]);
    }

    public function test_upload_video_ajax_rejects_oversized_video(): void
    {
        // 501 MB (exceeds 500 MB limit)
        $fakeVideo = UploadedFile::fake()->create('huge_video.mp4', 501 * 1024, 'video/mp4');

        $response = $this->postJson(route('content.uploadfileajax'), [
            'file' => $fakeVideo,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Maximum file size is 500 MB.',
        ]);
    }

    public function test_update_content_with_new_video_saves_and_updates_database(): void
    {
        $content = Content::create([
            'title'             => 'Test Video Content For Upload',
            'type'              => 'Video',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00',
            'learning_hours'    => 0.0833,
            'status'            => 'non mandatory',
            'file'              => null,
        ]);

        // Put a fake video in public/storage/files/tmp
        $tmpDir = public_path('storage/files/tmp');
        if (!File::isDirectory($tmpDir)) {
            File::makeDirectory($tmpDir, 0755, true);
        }
        $filename = 'new_lesson_' . time() . '.mp4';
        File::put($tmpDir . '/' . $filename, 'fake video content bytes');

        $response = $this->put(route('content.update', $content->slug), [
            'contentid'      => $content->id,
            'title'          => 'Test Video Content For Upload',
            'type'           => 'Video',
            'classification' => $this->classification->id,
            'status'         => 'non mandatory',
            'duration'       => '00:05:00',
            'action'         => 'update',
            'documents'      => [$filename],
        ]);

        $response->assertRedirect(route('content.edit', $content->slug));

        $content->refresh();
        $this->assertNotNull($content->file);
        $files = json_decode($content->file, true);
        $this->assertContains($filename, $files);

        $savedLocation = public_path('storage/files/content/' . $content->id . '/' . $filename);
        $this->assertTrue(File::exists($savedLocation), "Target file should exist at: {$savedLocation}");

        // Clean up
        if (File::exists($savedLocation)) {
            File::delete($savedLocation);
        }
        $contentDir = public_path('storage/files/content/' . $content->id);
        if (File::isDirectory($contentDir)) {
            File::deleteDirectory($contentDir);
        }
    }

    public function test_update_content_without_replacing_video_preserves_existing_video(): void
    {
        $existingFilename = 'existing_lecture.mp4';
        $content = Content::create([
            'title'             => 'Content with Existing Video',
            'type'              => 'Video',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00',
            'learning_hours'    => 0.0833,
            'status'            => 'non mandatory',
            'file'              => json_encode([$existingFilename]),
        ]);

        $targetDir = public_path('storage/files/content/' . $content->id);
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }
        File::put($targetDir . '/' . $existingFilename, 'existing video bytes');

        // Submit update WITHOUT documents
        $response = $this->put(route('content.update', $content->slug), [
            'contentid'      => $content->id,
            'title'          => 'Updated Title Only',
            'type'           => 'Video',
            'classification' => $this->classification->id,
            'status'         => 'non mandatory',
            'duration'       => '00:05:00',
            'action'         => 'update',
        ]);

        $response->assertRedirect(route('content.edit', $content->slug));

        $content->refresh();
        $this->assertSame('Updated Title Only', $content->title);
        $this->assertSame(json_encode([$existingFilename]), $content->file);
        $this->assertTrue(File::exists($targetDir . '/' . $existingFilename));

        // Clean up
        File::deleteDirectory($targetDir);
    }

    public function test_update_content_safely_replaces_old_video_when_new_video_uploaded(): void
    {
        $oldFilename = 'old_video_to_replace.mp4';
        $content = Content::create([
            'title'             => 'Content to Replace Video',
            'type'              => 'Video',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00',
            'learning_hours'    => 0.0833,
            'status'            => 'non mandatory',
            'file'              => json_encode([$oldFilename]),
        ]);

        $targetDir = public_path('storage/files/content/' . $content->id);
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }
        File::put($targetDir . '/' . $oldFilename, 'old video bytes');

        // New video uploaded to tmp
        $tmpDir = public_path('storage/files/tmp');
        if (!File::isDirectory($tmpDir)) {
            File::makeDirectory($tmpDir, 0755, true);
        }
        $newFilename = 'replacement_video_' . time() . '.mp4';
        File::put($tmpDir . '/' . $newFilename, 'brand new video content bytes');

        $response = $this->put(route('content.update', $content->slug), [
            'contentid'      => $content->id,
            'title'          => 'Content with Replaced Video',
            'type'           => 'Video',
            'classification' => $this->classification->id,
            'status'         => 'non mandatory',
            'duration'       => '00:05:00',
            'action'         => 'update',
            'documents'      => [$newFilename],
        ]);

        $response->assertRedirect(route('content.edit', $content->slug));

        $content->refresh();
        $files = json_decode($content->file, true);
        $this->assertContains($newFilename, $files);
        $this->assertNotContains($oldFilename, $files);

        // New file exists on disk
        $this->assertTrue(File::exists($targetDir . '/' . $newFilename));
        // Old file has been cleaned up
        $this->assertFalse(File::exists($targetDir . '/' . $oldFilename));

        // Clean up
        File::deleteDirectory($targetDir);
    }

    public function test_update_content_when_tmp_file_missing_preserves_old_video(): void
    {
        $oldFilename = 'preserved_video.mp4';
        $content = Content::create([
            'title'             => 'Content with Protected Old Video',
            'type'              => 'Video',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00',
            'learning_hours'    => 0.0833,
            'status'            => 'non mandatory',
            'file'              => json_encode([$oldFilename]),
        ]);

        $targetDir = public_path('storage/files/content/' . $content->id);
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }
        File::put($targetDir . '/' . $oldFilename, 'preserved bytes');

        // Submit with a document that does NOT exist in tmp
        $response = $this->put(route('content.update', $content->slug), [
            'contentid'      => $content->id,
            'title'          => 'Attempted Failed Update',
            'type'           => 'Video',
            'classification' => $this->classification->id,
            'status'         => 'non mandatory',
            'duration'       => '00:05:00',
            'action'         => 'update',
            'documents'      => ['ghost_nonexistent_file.mp4'],
        ]);

        $response->assertRedirect(route('content.edit', $content->slug));

        $content->refresh();
        // Old file must NOT be cleared or overwritten with []
        $this->assertSame(json_encode([$oldFilename]), $content->file);
        $this->assertTrue(File::exists($targetDir . '/' . $oldFilename));

        // Clean up
        File::deleteDirectory($targetDir);
    }

    public function test_delete_content_ajax_removes_actual_file_and_nulls_database(): void
    {
        $existingFilename = 'video_to_delete.mp4';
        $content = Content::create([
            'title'             => 'Content to Delete Media',
            'type'              => 'Video',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00',
            'learning_hours'    => 0.0833,
            'status'            => 'non mandatory',
            'file'              => json_encode([$existingFilename]),
        ]);

        $targetDir = public_path('storage/files/content/' . $content->id);
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }
        File::put($targetDir . '/' . $existingFilename, 'bytes to delete');

        $response = $this->putJson(route('contentdelete.deletecontentajax', $content->slug));

        $response->assertOk();
        $response->assertJson(['status' => 200]);

        $content->refresh();
        $this->assertNull($content->file);
        $this->assertFalse(File::exists($targetDir . '/' . $existingFilename));

        // Clean up
        File::deleteDirectory($targetDir);
    }

    public function test_dafa_rizy_content_can_be_updated_with_video(): void
    {
        $content = Content::where('slug', 'dafa-rizy')->first();
        if (!$content) {
            $this->markTestSkipped('dafa-rizy content not found in database.');
        }

        $targetDir = public_path('storage/files/content/' . $content->id);
        $existingFiles = $content->file ? (json_decode($content->file, true) ?: []) : [];
        $backedUpFiles = [];
        foreach ($existingFiles as $ef) {
            $path = $targetDir . '/' . $ef;
            if (File::exists($path)) {
                $backedUpFiles[$ef] = File::get($path);
            }
        }

        $tmpDir = public_path('storage/files/tmp');
        if (!File::isDirectory($tmpDir)) {
            File::makeDirectory($tmpDir, 0755, true);
        }
        $filename = 'dafa_rizy_test_upload_' . time() . '.mp4';
        File::put($tmpDir . '/' . $filename, 'dafa rizy test video bytes');

        try {
            $response = $this->put(route('content.update', $content->slug), [
                'contentid'      => $content->id,
                'title'          => $content->title,
                'author'         => $content->author,
                'classification' => $content->classification_id,
                'type'           => 'Video',
                'duration'       => '00:03:03',
                'status'         => 'non mandatory',
                'action'         => 'update',
                'documents'      => [$filename],
            ]);

            $response->assertRedirect(route('content.edit', $content->slug));
            $response->assertSessionHas('success');

            $content->refresh();
            $this->assertNotNull($content->file);
            $files = json_decode($content->file, true);
            $this->assertContains($filename, $files);

            $targetPath = public_path('storage/files/content/' . $content->id . '/' . $filename);
            $this->assertTrue(File::exists($targetPath));

            // Clean up uploaded test file
            if (File::exists($targetPath)) {
                File::delete($targetPath);
            }
        } finally {
            // Restore any backed up existing files
            foreach ($backedUpFiles as $ef => $contentBytes) {
                File::put($targetDir . '/' . $ef, $contentBytes);
            }
        }
    }

    public function test_edit_content_page_renders_video_preview_for_dafa_rizy(): void
    {
        $content = Content::where('slug', 'dafa-rizy')->first();
        if (!$content) {
            $this->markTestSkipped('dafa-rizy content not found in database.');
        }

        $response = $this->get(route('content.edit', $content->slug));
        $response->assertOk();
        $response->assertSee('OwlPreview.setServerVideoFile', false);
        $response->assertSee('dafa-rizy', false);
    }
}
