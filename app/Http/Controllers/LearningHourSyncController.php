<?php

namespace App\Http\Controllers;

use App\Services\LearningHours\LearningHourSynchronizeService;
use Illuminate\Http\RedirectResponse;

class LearningHourSyncController extends Controller
{
    public function sync(LearningHourSynchronizeService $service): RedirectResponse
    {
        $result = $service->synchronize();

        $message = "Learning Hours synchronized successfully.\n"
            . "Updated Content : {$result['content_updated']}\n"
            . "Updated Playlist : {$result['playlist_updated']}\n"
            . "Skipped : {$result['total_skipped']}";

        return redirect()->back()->with('success', $message);
    }
}
