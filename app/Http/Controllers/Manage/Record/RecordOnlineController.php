<?php

namespace App\Http\Controllers\Manage\Record;

use App\Exports\LearningExport;
use App\Exports\OnlineRecordCsvExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningManageController;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Services\Learning\LearningDatatableService;
use App\Support\DurationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordOnlineController extends Controller
{
    protected LearningManageController $learningManageController;

    public function __construct(LearningManageController $learningManageController)
    {
        $this->learningManageController = $learningManageController;
    }

    public function index(Request $request)
    {
        $viewData = [
            'title' => 'KP OWL',
            'subtitle' => 'Online Learning Record',
            'years' => LearningManageController::learningYears(),
            'entries' => (int) $request->input('entries', 25),
            'search' => (string) $request->input('search', ''),
            'downloadslug' => '',
            'content' => collect(),
        ];

        return view('main.record.online', $viewData);
    }

    public function ajaxSummary(Request $request)
    {
        $kpi = $this->learningManageController->getKpiSummary();
        $totalLearningHours = $this->learningManageController->getTotalLearningHours();

        $data = [
            'total_learning' => $kpi['total_learning'],
            'ongoing' => $kpi['ongoing'],
            'finished' => $kpi['finished'],
            'completion_rate' => $kpi['completion_rate'],
            'total_learning_hours' => $totalLearningHours,
            'totalLearning' => $kpi['total_learning'],
            'finish' => $kpi['finished'],
            'completionRate' => $kpi['completion_rate'],
            'learningHours' => $totalLearningHours,
        ];

        if ($request->input('format') === 'html') {
            return view('main.record.components.online-kpi-summary', $data);
        }

        return response()->json([
            'success' => true,
            'total_learning' => $data['total_learning'],
            'ongoing' => $data['ongoing'],
            'finished' => $data['finished'],
            'completion_rate' => $data['completion_rate'],
            'total_learning_hours' => $data['total_learning_hours'],
            'data' => [
                'total_learning' => $data['total_learning'],
                'ongoing' => $data['ongoing'],
                'finished' => $data['finished'],
                'completion_rate' => $data['completion_rate'],
                'total_learning_hours' => $data['total_learning_hours'],
            ],
            'html' => view('main.record.components.online-kpi-summary', $data)->render(),
        ]);
    }

    public function ajaxClassification(Request $request)
    {
        $classification = $this->learningManageController->getClassificationSummary();

        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json([
                'success' => true,
                'data' => $classification,
            ]);
        }

        return view('main.record.components.online-classification-summary', [
            'classification' => $classification,
        ]);
    }

    public function datatable(Request $request, LearningDatatableService $datatableService)
    {
        return $datatableService->make($request);
    }

    public function searchEmployees(Request $request)
    {
        return $this->learningManageController->searchEmployees($request);
    }

    public function searchTitles(Request $request)
    {
        return $this->learningManageController->searchTitles($request);
    }

    public function export(Request $request)
    {
        return app(\App\Services\Record\ManageOnlineRecordExportService::class)->download($request->all());
    }

    public function download(Request $request)
    {
        return $this->export($request);
    }

    public function exportCsv(Request $request)
    {
        $export = new OnlineRecordCsvExport(
            $request->all(),
            (string) $request->input('type', ''),
            (string) $request->input('slug', '')
        );

        return $export->download();
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->all();
        $query = LearningDatatableService::buildBaseQuery(
            $filters,
            (string) $request->input('type', ''),
            (string) $request->input('slug', '')
        );

        if (empty($filters['sort'])) {
            $query->orderBy('unified.created_at', 'desc')
                  ->orderBy('unified.source_type', 'desc')
                  ->orderBy('unified.source_id', 'desc');
        }

        $totalRecords = (clone $query)->count();

        // Safe limit for DomPDF rendering to prevent memory exhaustion
        $limit = 500;
        $records = $query->limit($limit)->get();

        $pdf = Pdf::loadView('main.record.online.export-pdf', [
            'records'       => $records,
            'totalRecords'  => $totalRecords,
            'limit'         => $limit,
            'filters'       => $filters,
            'generatedAt'   => now()->format('d M Y H:i:s'),
        ]);

        $fileName = sprintf('kp-owl-online-records-%s.pdf', now()->format('Y-m-d'));

        return $pdf->setPaper('a4', 'landscape')->download($fileName);
    }

    /**
     * Return detailed online learning record JSON for modal view.
     */
    public function detail(string $type, $id): JsonResponse
    {
        $validTypes = ['content', 'playlist'];
        if (!in_array($type, $validTypes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid online learning type.',
            ], 404);
        }

        if ($type === 'content') {
            $record = ContentUser::with(['user', 'content.classification'])
                ->where('id', $id)
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Online learning record not found.',
                ], 404);
            }

            $user = $record->user;
            $content = $record->content;
            $classification = $content?->classification;

            $durationFormatted = DurationFormatter::format($content?->duration, '-');
            $currentTimeSec = $record->current_time !== null ? (int) $record->current_time : null;
            $actualWatchSec = $record->actual_watch_seconds !== null ? (int) $record->actual_watch_seconds : null;
            $afkSec = $record->afk_duration_seconds !== null ? (int) $record->afk_duration_seconds : null;

            $progress = (int) ($record->progress ?? 0);
            $isFinished = $record->finish === '1' || $progress >= 100;

            return response()->json([
                'success' => true,
                'data' => [
                    'source_type' => 'content',
                    'source_id'   => $record->id,
                    'employee'    => [
                        'id_emp'      => $user?->id_emp ?: '-',
                        'name'        => $user?->name ?: '-',
                        'email'       => $user?->email ?: '-',
                        'department'  => $user?->department ?: '-',
                        'division'    => $user?->division ?: '-',
                        'team'        => $user?->team ?: '-',
                        'company'     => $user?->company_name ?: 'PT Krakatau Posco',
                    ],
                    'learning'    => [
                        'title'          => $content?->title ?: '-',
                        'type'           => $content?->type ?: 'Document',
                        'classification' => $classification?->classification_name ?: 'Unclassified',
                        'enrollment'     => 'All Content',
                        'duration'       => $durationFormatted,
                        'learning_hours' => $content?->learning_hours !== null ? number_format((float) $content->learning_hours, 2) . ' Hours' : '-',
                        'author'         => $content?->author ?: ($content?->partner ?: '-'),
                        'playlist'       => null,
                    ],
                    'progress'    => [
                        'progress'             => $progress,
                        'is_finished'          => $isFinished,
                        'finish_status'        => $isFinished ? 'Finished' : ($progress > 0 ? 'Ongoing' : 'Not Started'),
                        'current_time'         => $currentTimeSec !== null ? DurationFormatter::fromSeconds($currentTimeSec) . " ({$currentTimeSec}s)" : '-',
                        'tracked_duration'     => $durationFormatted,
                        'actual_watch'         => $actualWatchSec !== null ? DurationFormatter::fromSeconds($actualWatchSec) . " ({$actualWatchSec}s)" : '-',
                        'afk_duration'         => $afkSec !== null ? DurationFormatter::fromSeconds($afkSec) . " ({$afkSec}s)" : '-',
                        'pages'                => $record->last_page ?? ($record->total_pages ?? null),
                        'credited_hours'       => number_format((float) ($record->learning_hours ?? 0), 2) . ' Hours',
                        'learning_status'      => $record->learning_status ? ucfirst(str_replace('_', ' ', $record->learning_status)) : 'Valid',
                    ],
                    'timestamps'  => [
                        'enrolled_at'   => $record->created_at ? $record->created_at->format('d M Y, H:i') : '-',
                        'completed_at'  => $record->completed_at ? Carbon::parse($record->completed_at)->format('d M Y, H:i') : '-',
                        'last_activity' => $record->updated_at ? $record->updated_at->format('d M Y, H:i') : '-',
                    ],
                ],
            ]);
        }

        // type === 'playlist'
        $record = PlaylistContentUser::with(['user', 'content.classification', 'trainingPlan'])
            ->where('id', $id)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Online playlist record not found.',
            ], 404);
        }

        $user = $record->user;
        $content = $record->content;
        $classification = $content?->classification;
        $trainingPlan = $record->trainingPlan;

        $durationFormatted = DurationFormatter::format($content?->duration, '-');
        $currentTimeSec = $record->current_time !== null ? (int) $record->current_time : null;
        $actualWatchSec = $record->actual_watch_seconds !== null ? (int) $record->actual_watch_seconds : null;
        $afkSec = $record->afk_duration_seconds !== null ? (int) $record->afk_duration_seconds : null;

        $progress = (int) ($record->progress ?? 0);
        $isFinished = $record->finish === '1' || $progress >= 100;

        return response()->json([
            'success' => true,
            'data' => [
                'source_type' => 'playlist',
                'source_id'   => $record->id,
                'employee'    => [
                    'id_emp'      => $user?->id_emp ?: '-',
                    'name'        => $user?->name ?: '-',
                    'email'       => $user?->email ?: '-',
                    'department'  => $user?->department ?: '-',
                    'division'    => $user?->division ?: '-',
                    'team'        => $user?->team ?: '-',
                    'company'     => $user?->company_name ?: 'PT Krakatau Posco',
                ],
                'learning'    => [
                    'title'          => $content?->title ?: ($trainingPlan?->title ?: '-'),
                    'type'           => $content?->type ?: 'Playlist',
                    'classification' => $classification?->classification_name ?: 'Unclassified',
                    'enrollment'     => 'Playlist',
                    'duration'       => $durationFormatted,
                    'learning_hours' => $trainingPlan?->learning_hours !== null ? number_format((float) $trainingPlan->learning_hours, 2) . ' Hours' : ($content?->learning_hours !== null ? number_format((float) $content->learning_hours, 2) . ' Hours' : '-'),
                    'author'         => $content?->author ?: ($trainingPlan?->instructor ?: '-'),
                    'playlist'       => [
                        'title'    => $trainingPlan?->title ?: '-',
                        'duration' => DurationFormatter::format($trainingPlan?->duration ?? null, '-'),
                    ],
                ],
                'progress'    => [
                    'progress'             => $progress,
                    'is_finished'          => $isFinished,
                    'finish_status'        => $isFinished ? 'Finished' : ($progress > 0 ? 'Ongoing' : 'Not Started'),
                    'current_time'         => $currentTimeSec !== null ? DurationFormatter::fromSeconds($currentTimeSec) . " ({$currentTimeSec}s)" : '-',
                    'tracked_duration'     => $durationFormatted,
                    'actual_watch'         => $actualWatchSec !== null ? DurationFormatter::fromSeconds($actualWatchSec) . " ({$actualWatchSec}s)" : '-',
                    'afk_duration'         => $afkSec !== null ? DurationFormatter::fromSeconds($afkSec) . " ({$afkSec}s)" : '-',
                    'pages'                => null,
                    'credited_hours'       => number_format((float) ($record->learning_hour ?? 0), 2) . ' Hours',
                    'learning_status'      => $record->learning_status ? ucfirst(str_replace('_', ' ', $record->learning_status)) : 'Valid',
                ],
                'timestamps'  => [
                    'enrolled_at'   => $record->created_at ? $record->created_at->format('d M Y, H:i') : '-',
                    'completed_at'  => $record->completed_at ? Carbon::parse($record->completed_at)->format('d M Y, H:i') : '-',
                    'last_activity' => $record->updated_at ? $record->updated_at->format('d M Y, H:i') : '-',
                ],
            ],
        ]);
    }

    /**
     * Delete an online learning record (ContentUser or PlaylistContentUser).
     */
    public function destroy(Request $request, string $type, $id): JsonResponse
    {
        if (!in_array($type, ['content', 'playlist'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid learning source type.',
            ], 404);
        }

        $user = auth()->user();
        if (!$user || (!$user->canAccessManageModule('record_management') && !$user->canAccessManageModule('learning_management'))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to delete this learning record.',
            ], 403);
        }

        $record = $type === 'content'
            ? ContentUser::find($id)
            : PlaylistContentUser::find($id);

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Learning record not found.',
            ], 404);
        }

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Learning record deleted successfully.',
        ]);
    }
}

