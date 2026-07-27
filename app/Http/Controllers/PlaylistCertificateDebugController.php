<?php

namespace App\Http\Controllers;

use App\Models\CertificateSetting;
use App\Models\Content;
use App\Models\TrainingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlaylistCertificateDebugController extends Controller
{
    public function show(Request $request, TrainingPlan $trainingplan): View
    {
        $this->authorizeDebugAccess();

        $debugState = $request->session()->get(
            $this->sessionKey($trainingplan),
            []
        );

        abort_unless(
            ($debugState['certificate'] ?? null) === 'available',
            403,
            'Certificate debug preview is not active.'
        );

        $certificateSetting = CertificateSetting::query()
            ->with([
                'assessment',
                'content',
                'trainingPlan.trainingplandetail.content',
            ])
            ->where('certificate_type', 'playlist')
            ->where('trainingplan_id', $trainingplan->getKey())
            ->where('is_active', true)
            ->firstOrFail();

        $previewContent = $this->resolvePlaylistContent(
            $trainingplan,
            $request->query('content')
        );

        return view('certificate.show', [
            'certificate' => null,
            'certificateSetting' => $certificateSetting,
            'debugParticipant' => auth()->user(),
            'isDebugCertificatePreview' => true,
            'playlistReturnUrl' => $this->playlistReturnUrl($trainingplan, $previewContent),
            'previewContentSlug' => $previewContent?->slug,
        ]);
    }

    public function destroy(Request $request, TrainingPlan $trainingplan): RedirectResponse
    {
        $this->authorizeDebugAccess();

        $sessionKey = $this->sessionKey($trainingplan);
        $debugState = $request->session()->get($sessionKey, []);
        $debugState = is_array($debugState) ? $debugState : [];

        unset($debugState['certificate']);

        if ($debugState === []) {
            $request->session()->forget($sessionKey);
        } else {
            $request->session()->put($sessionKey, $debugState);
        }

        $previewContent = $this->resolvePlaylistContent(
            $trainingplan,
            $request->input('content')
        );

        return redirect($this->playlistReturnUrl($trainingplan, $previewContent))
            ->with('success', 'Certificate debug preview exited.');
    }

    private function authorizeDebugAccess(): void
    {
        abort_unless(
            auth()->user()?->canAccessManageModule('playlist_debug_tools'),
            403
        );
    }

    private function resolvePlaylistContent(
        TrainingPlan $trainingplan,
        ?string $contentSlug
    ): ?Content {
        $contents = $trainingplan
            ->loadMissing('trainingplandetail.content')
            ->trainingplandetail
            ->pluck('content')
            ->filter(fn (Content $content) => $content->exists);

        if ($contentSlug) {
            $selected = $contents->first(
                fn (Content $content) => $content->slug === $contentSlug
            );

            if ($selected) {
                return $selected;
            }
        }

        return $contents->first();
    }

    private function playlistReturnUrl(
        TrainingPlan $trainingplan,
        ?Content $content
    ): string {
        if (!$content) {
            return route('playlist.user.index');
        }

        return route('playlist.user.view', [
            'slug' => $trainingplan->slug,
            'vidslug' => $content->slug,
        ]);
    }

    private function sessionKey(TrainingPlan $trainingplan): string
    {
        return 'playlist_debug.'.$trainingplan->getKey();
    }
}
