<?php

namespace App\Http\Controllers\Manage\LearningHours;

use App\Http\Controllers\Controller;
use App\Services\LearningHours\HistoricalPlaylistProgressExecutionService;
use App\Services\LearningHours\PlaylistProgressDryRunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlaylistProgressDryRunController extends Controller
{
    public function __construct(
        protected PlaylistProgressDryRunService $dryRunService,
        protected HistoricalPlaylistProgressExecutionService $executionService
    ) {}

    /**
     * Display the HTTP Dry Run Preview Dashboard.
     * STRICTLY READ-ONLY with zero mutations.
     */
    public function index(Request $request): View
    {
        $cutoff = (string) $request->input('cutoff', PlaylistProgressDryRunService::DEFAULT_CUTOFF);
        $playlistId = $request->filled('playlist_id') && $request->input('playlist_id') !== 'all'
            ? (int) $request->input('playlist_id')
            : null;
        $search = $request->input('search');
        $perPage = max(10, min(100, (int) $request->input('per_page', PlaylistProgressDryRunService::DEFAULT_PER_PAGE)));
        $page = max(1, (int) $request->input('page', 1));

        $fresh = $request->boolean('fresh');
        $summary = $this->dryRunService->getSummaryMetrics($cutoff, $playlistId, fresh: $fresh);
        $validation = $this->dryRunService->getSourceValidationMetrics($cutoff, $playlistId);
        $candidates = $this->dryRunService->getCandidatesPaginated($cutoff, $playlistId, $perPage, $page, $search);
        $playlists = $this->dryRunService->getPlaylistsForFilter($cutoff);
        $activeExecution = $this->executionService->getActiveExecution();

        // If new candidates exist in the database, do not display a stale past completed execution card
        if ($activeExecution !== null && $activeExecution['status'] === 'completed' && ($summary['to_insert_count'] ?? 0) > 0) {
            $activeExecution = null;
        }

        return view('manage.learning-hours.dry-run.index', [
            'summary'            => $summary,
            'validation'         => $validation,
            'candidates'         => $candidates,
            'playlists'          => $playlists,
            'cutoff'             => $cutoff,
            'selectedPlaylistId' => $playlistId,
            'search'             => $search,
            'perPage'            => $perPage,
            'activeExecution'    => $activeExecution,
        ]);
    }

    /**
     * Start a migration execution session.
     */
    public function executeStart(Request $request): JsonResponse
    {
        $request->validate([
            'cutoff'      => 'nullable|string',
            'playlist_id' => 'nullable',
            'chunk_size'  => 'nullable|integer|min:50|max:5000',
        ]);

        $cutoff = (string) $request->input('cutoff', PlaylistProgressDryRunService::DEFAULT_CUTOFF);
        $playlistId = $request->filled('playlist_id') && $request->input('playlist_id') !== 'all'
            ? (int) $request->input('playlist_id')
            : null;
        $chunkSize = (int) $request->input('chunk_size', HistoricalPlaylistProgressExecutionService::DEFAULT_CHUNK_SIZE);

        try {
            $execution = $this->executionService->startExecution($cutoff, $playlistId, $chunkSize, Auth::id());

            return response()->json([
                'success'   => true,
                'message'   => 'Migration execution session initialized.',
                'execution' => $execution,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize execution: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process one chunk step of migration execution via AJAX.
     */
    public function executeStep(Request $request): JsonResponse
    {
        $request->validate([
            'execution_id' => 'required|string',
        ]);

        $executionId = (string) $request->input('execution_id');

        $result = $this->executionService->executeStep($executionId);

        $status = $result['success'] ? 200 : 500;
        return response()->json($result, $status);
    }

    /**
     * Read-only status polling endpoint.
     */
    public function status(string $executionId): JsonResponse
    {
        $status = $this->executionService->getStatus($executionId);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Execution session not found.',
            ], 404);
        }

        return response()->json([
            'success'   => true,
            'execution' => $status,
        ]);
    }
}
