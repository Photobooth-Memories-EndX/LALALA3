<?php

namespace App\Http\Controllers;

use App\Services\LearningHours\LearningHourSynchronizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LearningHourSyncController extends Controller
{
    public const LOCK_KEY = 'learning_hours_sync_lock';
    public const LOCK_TTL = 300; // 5 minutes

    public function sync(Request $request, LearningHourSynchronizeService $service): JsonResponse|RedirectResponse
    {
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        if (!$lock->get()) {
            $message = 'Another synchronization is currently in progress. Please wait until it completes.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 429);
            }

            return redirect()->back()->with('error', $message);
        }

        try {
            @set_time_limit(300);

            $result = $service->synchronize();

            $message = "Learning Hours synchronized successfully.\n"
                . "Content — Updated: {$result['content_updated']}, Skipped: {$result['content_skipped']}, Total: {$result['content_total']}\n"
                . "Playlist — Updated: {$result['playlist_updated']}, Skipped: {$result['playlist_skipped']}, Total: {$result['playlist_total']}";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Learning Hours synchronized successfully.',
                    'data'    => $result,
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Learning Hours synchronization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = 'Synchronization failed: ' . $e->getMessage();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            }

            return redirect()->back()->with('error', $errorMessage);
        } finally {
            $lock->release();
        }
    }
}
