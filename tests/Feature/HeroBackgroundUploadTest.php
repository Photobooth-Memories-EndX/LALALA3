<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroBackgroundUploadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_upload_and_persist_a_hero_background(): void
    {
        $user = User::factory()->create([
            'team' => 'Quality Assurance',
            'department' => 'Technology',
            'division' => 'Engineering',
            'ssohash' => 'hero-background-test',
            'sap_code' => 'HERO-TEST',
            'role' => User::ROLE_USER,
        ]);
        $storedPath = null;

        try {
            $response = $this
                ->actingAs($user)
                ->postJson(route('home.user.hero-background.update'), [
                    'hero_background' => UploadedFile::fake()
                        ->image('hero-background.jpg', 1600, 900)
                        ->size(180),
                ]);

            $response
                ->assertOk()
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'image_url',
                    'image_path',
                ]);

            $storedPath = $response->json('image_path');

            $this->assertNotEmpty($storedPath);
            Storage::disk('public')->assertExists($storedPath);
            $this->assertSame($storedPath, $user->fresh()->hero_background);
        } finally {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
        }
    }
}
