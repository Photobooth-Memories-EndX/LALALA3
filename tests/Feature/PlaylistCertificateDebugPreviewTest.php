<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Models\UserAccessPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaylistCertificateDebugPreviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_open_debug_certificate_preview(): void
    {
        $playlist = $this->createPlaylist();

        $this->get(route('playlist.debug.certificate.preview', $playlist))
            ->assertRedirect();
    }

    public function test_user_without_debug_permission_cannot_open_preview(): void
    {
        $user = $this->createUser(User::ROLE_USER);
        $playlist = $this->createPlaylist();
        $this->createCertificateSetting($playlist);

        UserAccessPermission::create([
            'user_id' => $user->id,
            'module_key' => 'playlist_debug_tools',
            'can_access' => false,
        ]);

        $this
            ->actingAs($user)
            ->withSession($this->availableDebugSession($playlist))
            ->get(route('playlist.debug.certificate.preview', $playlist))
            ->assertForbidden();
    }

    public function test_preview_requires_available_certificate_debug_session(): void
    {
        $user = $this->createUser(User::ROLE_SUPER_ADMIN);
        $playlist = $this->createPlaylist();
        $this->createCertificateSetting($playlist);

        $this
            ->actingAs($user)
            ->get(route('playlist.debug.certificate.preview', $playlist))
            ->assertForbidden();
    }

    public function test_preview_requires_an_active_playlist_certificate_setting(): void
    {
        $user = $this->createUser(User::ROLE_SUPER_ADMIN);
        $playlist = $this->createPlaylist();
        $this->createCertificateSetting($playlist, false);

        $this
            ->actingAs($user)
            ->withSession($this->availableDebugSession($playlist))
            ->get(route('playlist.debug.certificate.preview', $playlist))
            ->assertNotFound();
    }

    public function test_available_debug_session_renders_preview_without_creating_certificate(): void
    {
        $user = $this->createUser(User::ROLE_SUPER_ADMIN);
        $playlist = $this->createPlaylist();
        $this->createCertificateSetting($playlist);
        $certificateCount = Certificate::count();

        $response = $this
            ->actingAs($user)
            ->withSession($this->availableDebugSession($playlist))
            ->get(route('playlist.debug.certificate.preview', $playlist));

        $response
            ->assertOk()
            ->assertViewIs('certificate.show')
            ->assertViewHas('isDebugCertificatePreview', true)
            ->assertSee('DEBUG CERTIFICATE PREVIEW')
            ->assertSee('No Certificate Record has been created.')
            ->assertSee('No Credential ID exists.')
            ->assertSee('No Certificate Number exists.')
            ->assertSee('playlist_debug_tools')
            ->assertSee('Preview Mode')
            ->assertSee('DEBUG SESSION ACTIVE')
            ->assertSee($playlist->title)
            ->assertSee($user->name)
            ->assertSee('DEBUG PREVIEW')
            ->assertSee('Not created (debug preview)')
            ->assertSee('Disabled in Debug Preview.')
            ->assertSee('Credential URL')
            ->assertSee('LinkedIn')
            ->assertSee('Back to Playlist')
            ->assertSee('Exit Debug Preview')
            ->assertSee('Production Generate Flow remains unchanged.')
            ->assertSee('Production actions are disabled')
            ->assertDontSee('Certificate Generated');

        $this->assertSame($certificateCount, Certificate::count());
    }

    public function test_navigation_uses_debug_preview_link_when_override_is_available(): void
    {
        $playlist = $this->createPlaylist();
        $setting = $this->createCertificateSetting($playlist);

        $html = $this->renderNavigation($playlist, $setting, [
            'certificate' => 'available',
        ]);

        $this->assertStringContainsString(
            'href="'.route('playlist.debug.certificate.preview', [
                'trainingplan' => $playlist,
                'content' => 'debug-content',
            ]).'"',
            $html
        );
        $this->assertStringContainsString('Preview Certificate', $html);
        $this->assertStringContainsString('ti-eye', $html);
        $this->assertStringContainsString('DEBUG', $html);
        $this->assertStringNotContainsString('Generate Certificate', $html);
        $this->assertStringNotContainsString(
            'action="'.route('certificate.generate.setting', $setting).'"',
            $html
        );
    }

    public function test_navigation_uses_production_generate_form_when_debug_is_off(): void
    {
        $playlist = $this->createPlaylist();
        $setting = $this->createCertificateSetting($playlist);

        $html = $this->renderNavigation($playlist, $setting);

        $this->assertStringContainsString(
            'action="'.route('certificate.generate.setting', $setting).'"',
            $html
        );
        $this->assertStringContainsString('Generate Certificate', $html);
        $this->assertStringNotContainsString('Preview Certificate', $html);
        $this->assertStringNotContainsString(
            'href="'.route('playlist.debug.certificate.preview', [
                'trainingplan' => $playlist,
                'content' => 'debug-content',
            ]).'"',
            $html
        );
    }

    public function test_preview_remains_available_until_debug_session_is_reset(): void
    {
        $user = $this->createUser(User::ROLE_SUPER_ADMIN);
        $playlist = $this->createPlaylist();
        $this->createCertificateSetting($playlist);

        $this
            ->actingAs($user)
            ->withSession($this->availableDebugSession($playlist))
            ->get(route('playlist.debug.certificate.preview', $playlist))
            ->assertOk();

        $this
            ->get(route('playlist.debug.certificate.preview', $playlist))
            ->assertOk();

        $this
            ->delete(route('playlist.debug.reset', $playlist))
            ->assertRedirect();

        $this
            ->get(route('playlist.debug.certificate.preview', $playlist))
            ->assertForbidden();
    }

    public function test_exit_preview_only_removes_certificate_override(): void
    {
        $user = $this->createUser(User::ROLE_SUPER_ADMIN);
        $playlist = $this->createPlaylist();
        $this->createCertificateSetting($playlist);
        $sessionKey = 'playlist_debug.'.$playlist->getKey();

        $response = $this
            ->actingAs($user)
            ->withSession([
                $sessionKey => [
                    'learning_progress' => 'pass',
                    'content_assessment' => 'available',
                    'playlist_assessment' => 'available',
                    'certificate' => 'available',
                ],
            ])
            ->delete(route('playlist.debug.certificate.preview.exit', $playlist));

        $response->assertRedirect(route('playlist.user.index'));

        $remainingState = session($sessionKey);

        $this->assertSame('pass', $remainingState['learning_progress']);
        $this->assertSame('available', $remainingState['content_assessment']);
        $this->assertSame('available', $remainingState['playlist_assessment']);
        $this->assertArrayNotHasKey('certificate', $remainingState);

        $this
            ->get(route('playlist.debug.certificate.preview', $playlist))
            ->assertForbidden();
    }

    public function test_existing_production_certificate_page_keeps_its_actions(): void
    {
        $user = $this->createUser(User::ROLE_USER);
        $playlist = $this->createPlaylist();
        $setting = $this->createCertificateSetting($playlist);
        $credentialId = 'OWL-'.strtoupper(Str::random(11));

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'certificate_setting_id' => $setting->id,
            'certificate_type' => 'playlist',
            'source_id' => $playlist->id,
            'certificate_number' => 'OWL-CERT-TEST-'.strtoupper(Str::random(8)),
            'credential_id' => $credentialId,
            'credential_url' => route('certificate.verify', $credentialId),
            'title' => $playlist->title,
            'participant_name' => $user->name,
            'issued_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->get(route('certificate.show', $certificate->credential_id))
            ->assertOk()
            ->assertSee('Certificate Generated')
            ->assertSee($certificate->certificate_number)
            ->assertSee(route('certificate.download.pdf', $credentialId), false)
            ->assertSee(route('certificate.verify', $credentialId), false)
            ->assertDontSee('DEBUG CERTIFICATE PREVIEW');
    }

    private function createUser(int $role): User
    {
        return User::factory()->create([
            'team' => 'Quality Assurance',
            'department' => 'Technology',
            'division' => 'Engineering',
            'ssohash' => 'certificate-debug-'.Str::uuid(),
            'sap_code' => 'CERT-'.Str::upper(Str::random(8)),
            'role' => $role,
        ]);
    }

    private function createPlaylist(): TrainingPlan
    {
        return TrainingPlan::create([
            'title' => 'Debug Certificate Playlist '.Str::random(8),
            'active' => 'Active',
        ]);
    }

    private function createCertificateSetting(
        TrainingPlan $playlist,
        bool $active = true
    ): CertificateSetting {
        return CertificateSetting::create([
            'certificate_type' => 'playlist',
            'trainingplan_id' => $playlist->id,
            'is_assessment_required' => true,
            'minimum_progress' => 100,
            'minimum_score' => 80,
            'issuer_name' => 'OWL Training Management',
            'use_default_template' => true,
            'is_active' => $active,
        ]);
    }

    private function availableDebugSession(TrainingPlan $playlist): array
    {
        return [
            'playlist_debug.'.$playlist->getKey() => [
                'certificate' => 'available',
            ],
        ];
    }

    private function renderNavigation(
        TrainingPlan $playlist,
        CertificateSetting $setting,
        array $debugState = []
    ): string {
        return view('user.playlist.components.view-navigation', [
            'playlist' => $playlist,
            'playlistDebugState' => $debugState,
            'playlistUiState' => [
                'certificate_available' => true,
                'playlist_assessment_available' => false,
            ],
            'playlistCertificateEligibility' => null,
            'playlistCertificateSetting' => $setting,
            'hasPlaylistAssessment' => false,
            'assesment' => null,
            'content' => (object) ['slug' => 'debug-content'],
        ])->render();
    }
}
