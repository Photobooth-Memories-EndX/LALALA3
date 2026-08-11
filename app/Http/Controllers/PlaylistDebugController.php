<?php

namespace App\Http\Controllers;

use App\Models\TrainingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlaylistDebugController extends Controller
{
    private const ALLOWED_VALUES = [
        'learning_progress' => ['pass', 'fail'],
        'content_assessment' => ['available', 'not_available'],
        'playlist_assessment' => ['available', 'not_available'],
        'certificate' => ['available', 'not_available'],
    ];

    public function update(Request $request, TrainingPlan $trainingplan): RedirectResponse
    {
        $this->authorizeDebugAccess();

        $validated = $request->validate([
            'key' => ['required', 'string', Rule::in(array_keys(self::ALLOWED_VALUES))],
            'value' => ['required', 'string'],
        ]);

        abort_unless(
            in_array($validated['value'], self::ALLOWED_VALUES[$validated['key']], true),
            422,
            'Invalid playlist debug state.'
        );

        $sessionKey = $this->sessionKey($trainingplan);
        $state = $request->session()->get($sessionKey, []);
        $state[$validated['key']] = $validated['value'];
        $request->session()->put($sessionKey, $state);

        return back()->with('success', 'Playlist debug override updated.');
    }

    public function reset(Request $request, TrainingPlan $trainingplan): RedirectResponse
    {
        $this->authorizeDebugAccess();
        $request->session()->forget($this->sessionKey($trainingplan));

        return back()->with('success', 'Playlist debug override reset.');
    }

    private function authorizeDebugAccess(): void
    {
        abort_unless(
            auth()->user()?->canAccessManageModule('playlist_debug_tools'),
            403
        );
    }

    private function sessionKey(TrainingPlan $trainingplan): string
    {
        return 'playlist_debug.'.$trainingplan->getKey();
    }
}
