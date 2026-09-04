<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Content;
use App\Models\TrainingPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Services\CertificateSyncService;
use Yajra\DataTables\Facades\DataTables;

class CertificateController extends Controller
{
    /**
     * AJAX live autocomplete search for user's owned certificate items.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->get('q', ''));
        if ($query === '') {
            return response()->json([]);
        }

        $userId = (int) Auth::id();
        $keyword = '%' . $query . '%';

        $certificates = Certificate::with(['certificateSetting'])
            ->where('user_id', $userId)
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', $keyword)
                    ->orWhere('certificate_number', 'like', $keyword)
                    ->orWhere('credential_id', 'like', $keyword)
                    ->orWhere('participant_name', 'like', $keyword)
                    ->orWhere('certificate_type', 'like', $keyword)
                    ->orWhereHas('certificateSetting', fn ($setting) => $setting->where('issuer_name', 'like', $keyword));
            })
            ->latest('issued_at')
            ->limit(10)
            ->get();

        $results = $certificates->map(function ($certificate) {
            $rawType = strtolower((string) ($certificate->certificate_type ?? 'content'));
            $typeLabel = match ($rawType) {
                'trainingplan', 'playlist' => 'Playlist',
                'global'                   => 'Global',
                default                    => ucwords(str_replace('_', ' ', $rawType)),
            };

            return [
                'id'                 => $certificate->id,
                'title'              => $certificate->title,
                'certificate_number' => $certificate->certificate_number,
                'credential_id'      => $certificate->credential_id,
                'type'               => $rawType,
                'type_label'         => $typeLabel,
                'issuer'             => $certificate->certificateSetting->issuer_name ?? null,
                'issued_at'          => optional($certificate->issued_at)->format('d M Y'),
            ];
        });

        return response()->json($results->values());
    }

    public function index(CertificateSyncService $syncService)
    {
        $syncService->sync();
        $summary = [
            'total' => CertificateSetting::count(),
            'content' => CertificateSetting::where('certificate_type', 'content')->count(),
            'playlist' => CertificateSetting::where('certificate_type', 'playlist')->count(),
            'global' => CertificateSetting::where('certificate_type', 'global')->count(),
            'active' => CertificateSetting::where('is_active', 1)->count(),
            'inactive' => CertificateSetting::where('is_active', 0)->count(),
            'required' => CertificateSetting::where('is_assessment_required', 1)->count(),
            'optional' => CertificateSetting::where('is_assessment_required', 0)->count(),
        ];

        return view('main.certificate.index', compact('summary'));
    }

    public function datatable(Request $request)
    {
       $query = CertificateSetting::with([
        'content',
        'trainingPlan',
        'assessment'
        ])
        ->select('certificate_settings.*')
        ->orderByDesc('minimum_progress')
        ->orderByDesc('minimum_score')
        ->orderByDesc('is_assessment_required')
        ->orderByDesc('is_active')
        ->latest();

        foreach ([
            'certificate_type' => 'type',
            'issuer_name' => 'issuer',
            'minimum_progress' => 'minimum_progress',
            'minimum_score' => 'minimum_score',
        ] as $column => $filter) {
             if ($request->filled($filter) && $request->input($filter) !== 'all') {
            $query->where($column, $request->input($filter));
        }
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', (int) $request->status);
        }

        if ($request->filled('assessment_required') && $request->assessment_required !== 'all') {
            $query->where('is_assessment_required', (int) $request->assessment_required);
        }

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value'));
                if ($search === '') {
                    return;
                }

                $query->where(function ($q) use ($search) {
                    $q->where('certificate_type', 'like', "%{$search}%")
                        ->orWhere('issuer_name', 'like', "%{$search}%")
                        ->orWhereHas('content', fn ($relation) => $relation->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('trainingPlan', fn ($relation) => $relation->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('assessment', fn ($relation) => $relation->where('title', 'like', "%{$search}%"));
                });
            })
            ->addColumn('type', function ($row) {
                $colors = ['content' => 'blue', 'playlist' => 'purple', 'global' => 'green'];
                $color = $colors[$row->certificate_type] ?? 'secondary';
                return '<span class="badge bg-' . $color . '-lt text-' . $color . '">' . e(ucfirst($row->certificate_type)) . '</span>';
            })
            ->addColumn('name', function ($row) {
                $title = match ($row->certificate_type) {
                    'content' => optional($row->content)->title,
                    'playlist' => optional($row->trainingPlan)->title,
                    'global' => optional($row->assessment)->title,
                    default => null,
                };

                return e($title ?: '-');
            })
            ->addColumn('assessment', function ($row) {
                $title = $row->assessment ? '<div>' . e($row->assessment->title) . '</div>' : '';
                $required = $row->is_assessment_required;
                $badge = $required
                    ? '<span class="badge bg-green-lt text-green">Required</span>'
                    : '<span class="badge bg-yellow-lt text-yellow">Optional</span>';
                return $title . $badge;
            })
            ->addColumn('minimum_progress', fn ($row) => '<span class="badge bg-blue-lt text-blue">' . (int) $row->minimum_progress . '%</span>')
            ->addColumn('minimum_score', fn ($row) => $row->is_assessment_required
                ? '<span class="badge bg-green-lt text-green">' . (int) $row->minimum_score . '</span>'
                : '<span class="text-muted">-</span>')
            ->addColumn('issuer', function ($row) {
                return '<span>' . e($row->issuer_name) . '</span>';
            })
            ->addColumn('status', fn ($row) => $row->is_active
                ? '<span class="badge bg-green-lt text-green">ON</span>'
                : '<span class="badge bg-red-lt text-red">OFF</span>')
            ->addColumn('action', fn ($row) => $this->certificateActionColumn($row))
            ->rawColumns(['type', 'assessment', 'minimum_progress', 'minimum_score', 'issuer', 'status', 'action'])
            ->make(true);
    }

    private function certificateActionColumn(CertificateSetting $setting): string
    {
        $payload = e(json_encode([
            'id' => $setting->id,
            'certificate_type' => $setting->certificate_type,
            'content_id' => $setting->content_id,
            'trainingplan_id' => $setting->trainingplan_id,
            'assessment_id' => $setting->assessment_id,
            'is_assessment_required' => (bool) $setting->is_assessment_required,
            'minimum_progress' => $setting->minimum_progress,
            'minimum_score' => $setting->minimum_score,
            'issuer_name' => $setting->issuer_name,
            'use_default_template' => (bool) $setting->use_default_template,
            'custom_background' => $setting->custom_background,
            'is_active' => (bool) $setting->is_active,
        ]));

        $buttons = '<div class="btn-list flex-nowrap">'
            . '<button type="button" class="btn btn-sm btn-outline-primary certificate-edit" data-setting="' . $payload . '" title="Edit"><i class="ti ti-edit"></i></button>';

        if ($setting->certificate_template) {
            $buttons .= '<a href="' . route('certificate.setting.preview.template', $setting) . '" target="_blank" class="btn btn-sm btn-outline-info" title="Preview Template"><i class="ti ti-file-search"></i></a>';
        }

        if (!$setting->use_default_template && $setting->custom_background) {
            $buttons .= '<a href="' . route('certificate.setting.preview.custom-template', $setting) . '" target="_blank" class="btn btn-sm btn-outline-warning" title="Preview Custom Template"><i class="ti ti-photo-search"></i></a>';
        }

        $buttons .= '<a href="' . route('certificate.setting.preview.generated', $setting) . '" target="_blank" class="btn btn-sm btn-outline-success" title="Preview Generated Certificate"><i class="ti ti-certificate"></i></a>'
            . '<form method="POST" action="' . route('certificate.setting.destroy', $setting) . '" onsubmit="return confirm(\'Delete certificate setting?\')">'
            . csrf_field() . method_field('DELETE')
            . '<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="ti ti-trash"></i></button></form></div>';

        return $buttons;
    }

public function store(Request $request)
{
    $request->validate([
        'certificate_type' => 'required|in:content,playlist,global',
        'item_id' => 'required',
        'assessment_id' => 'nullable|required_if:is_assessment_required,1|exists:assesments,id',
        'is_assessment_required' => 'nullable|boolean',
        'minimum_progress' => 'required|integer|min:0|max:100',
        'minimum_score' => 'nullable|integer|min:0|max:100',
        'issuer_name' => 'required|string|max:255',
        'use_default_template' => 'nullable|boolean',
        'certificate_template' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        'custom_background' => array_filter([
            $request->has('use_default_template') ? 'nullable' : 'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
        ]),
        'is_active' => 'required|boolean',
    ]);

    $data = $request->only([
        'certificate_type',
        'assessment_id',
        'minimum_progress',
        'minimum_score',
        'issuer_name',
        'is_active',
        'use_default_template',
    ]);

    $data['is_assessment_required'] = $request->has('is_assessment_required') ? 1 : 0;
    $data['use_default_template'] = $request->has('use_default_template');
    $data['minimum_score'] = $data['is_assessment_required'] ? $request->minimum_score : null;
    $data['assessment_id'] = $data['is_assessment_required'] ? $request->assessment_id : null;
    $data['minimum_progress'] = $request->certificate_type === 'global' ? 0 : $request->minimum_progress;

    if ($data['is_assessment_required'] && empty($data['minimum_score'])) {
        return back()
            ->withInput()
            ->withErrors([
                'minimum_score' => 'Minimum score wajib diisi jika assessment diwajibkan.'
            ]);
    }

    $data = $this->mapCertificateTarget($request, $data);

    if ($request->hasFile('certificate_template')) {
        $data['certificate_template'] = $this->uploadFile(
            $request->file('certificate_template'),
            'uploads/certificates/templates'
        );
    }
    if ($request->hasFile('custom_background')) {
        $data['custom_background'] = $this->uploadFile($request->file('custom_background'), 'uploads/certificates/backgrounds');
    }

    CertificateSetting::create($data);

    return redirect()
        ->route('certificate.setting.index')
        ->with('success', 'Certificate setting berhasil ditambahkan.');
}

public function update(Request $request, CertificateSetting $certificateSetting)
{
    $request->validate([
        'certificate_type' => 'required|in:content,playlist,global',
        'item_id' => 'required',
        'assessment_id' => 'nullable|required_if:is_assessment_required,1|exists:assesments,id',
        'is_assessment_required' => 'nullable|boolean',
        'minimum_progress' => 'required|integer|min:0|max:100',
        'minimum_score' => 'nullable|integer|min:0|max:100',
        'issuer_name' => 'required|string|max:255',
        'use_default_template' => 'nullable|boolean',
        'certificate_template' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        'custom_background' => array_filter([
            ($request->has('use_default_template') || $certificateSetting->custom_background) ? 'nullable' : 'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
        ]),
        'is_active' => 'required|boolean',
    ]);

    $data = $request->only([
        'certificate_type',
        'assessment_id',
        'minimum_progress',
        'minimum_score',
        'issuer_name',
        'is_active',
        'use_default_template',
    ]);

    $data['is_assessment_required'] = $request->has('is_assessment_required') ? 1 : 0;
    $data['use_default_template'] = $request->has('use_default_template');
    $data['minimum_score'] = $data['is_assessment_required'] ? $request->minimum_score : null;
    $data['assessment_id'] = $data['is_assessment_required'] ? $request->assessment_id : null;
    $data['minimum_progress'] = $request->certificate_type === 'global' ? 0 : $request->minimum_progress;

    if ($data['is_assessment_required'] && empty($data['minimum_score'])) {
        return back()
            ->withInput()
            ->withErrors([
                'minimum_score' => 'Minimum score wajib diisi jika assessment diwajibkan.'
            ]);
    }

    $data = $this->mapCertificateTarget($request, $data);

    $oldCertificateTemplate = $certificateSetting->certificate_template;
    $oldCustomBackground = $certificateSetting->custom_background;
    $newCertificateTemplate = null;
    $newCustomBackground = null;

    if ($request->hasFile('certificate_template')) {
        $newCertificateTemplate = $this->uploadFile(
            $request->file('certificate_template'),
            'uploads/certificates/templates'
        );
        $data['certificate_template'] = $newCertificateTemplate;
    }
    if ($request->hasFile('custom_background')) {
        $newCustomBackground = $this->uploadFile($request->file('custom_background'), 'uploads/certificates/backgrounds');
        $data['custom_background'] = $newCustomBackground;
    }

    try {
        $certificateSetting->update($data);
    } catch (\Throwable $e) {
        // Roll back newly uploaded files if the database update fails, so no orphan file is left behind.
        $this->deleteUploadedFile($newCertificateTemplate);
        $this->deleteUploadedFile($newCustomBackground);
        throw $e;
    }

    // Delete old custom certificate template after successful update when a new file is uploaded.
    if ($newCertificateTemplate && $oldCertificateTemplate && $oldCertificateTemplate !== $newCertificateTemplate) {
        $this->deleteUploadedFile($oldCertificateTemplate);
    }
    if ($newCustomBackground && $oldCustomBackground && $oldCustomBackground !== $newCustomBackground) {
        $this->deleteUploadedFile($oldCustomBackground);
    }

    return redirect()
        ->route('certificate.setting.index')
        ->with('success', 'Certificate setting berhasil diperbarui.');
}

    public function destroy(CertificateSetting $certificateSetting)
    {
        $certificateSetting->delete();

        return redirect()
            ->route('certificate.setting.index')
            ->with('success', 'Certificate setting berhasil dihapus.');
    }

    public function getItemsByType(Request $request)
    {
        $type = $request->get('type');

        if ($type === 'content') {
            $items = Content::select('id', 'title')
                ->orderBy('title')
                ->get();
        } elseif ($type === 'playlist') {
            $items = TrainingPlan::with('trainingplandetail.content:id,title')
                ->select('id', 'title')
                ->orderBy('title')
                ->get()
                ->map(fn ($playlist) => [
                    'id' => $playlist->id,
                    'title' => $playlist->title,
                    'contents' => $playlist->trainingplandetail->pluck('content')->filter()->values(),
                ]);
        } else {
            $items = Assesment::select('id', 'title')
                ->where('type', 'global')
                ->orderBy('title')
                ->get();
        }

        return response()->json($items);
    }

    public function getAssessmentsByType(Request $request)
    {
        $type = $request->get('type');
        $itemId = $request->get('item_id');

        if ($type === 'global') {
            return response()->json(
                Assesment::where('type', 'global')->orderBy('title')->get(['id', 'title'])
            );
        }

        $target = $type === 'content'
            ? Content::find($itemId)
            : TrainingPlan::find($itemId);

        if (!$target) {
            return response()->json([]);
        }

        $foreignKey = $type === 'content' ? 'content_id' : 'trainingplan_id';
        $assessmentType = $type === 'content' ? 'content' : 'trainingplan';
        $direct = Assesment::where($foreignKey, $itemId)->orderBy('title')->get(['id', 'title']);

        if ($direct->isNotEmpty()) {
            return response()->json($direct);
        }

        $typed = Assesment::where('type', $assessmentType)->orderBy('title')->get(['id', 'title']);
        if ($typed->isNotEmpty()) {
            return response()->json($typed);
        }

        $normalizedTarget = $this->normalizeAssessmentTitle($target->title);
        $matches = Assesment::orderBy('title')->get(['id', 'title'])->filter(function ($assessment) use ($normalizedTarget) {
            $normalizedAssessment = $this->normalizeAssessmentTitle($assessment->title);
            return $normalizedAssessment !== ''
                && (str_contains($normalizedAssessment, $normalizedTarget)
                    || str_contains($normalizedTarget, $normalizedAssessment));
        })->map(fn ($assessment) => [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'matched_by_title' => true,
        ])->values();

        return response()->json($matches);
    }

    private function uploadFile($file, $path)
    {
        $destination = public_path($path);

        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $file->move($destination, $filename);

        return $path . '/' . $filename;
    }

    private function deleteUploadedFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    private function normalizeAssessmentTitle(string $title): string
    {
        $title = strtolower($title);
        $title = preg_replace('/[^a-z0-9\s]/', ' ', $title);
        $title = preg_replace('/\b(post test|pre test|assessment|training|course|batch|remedial)\b/', ' ', $title);
        return trim(preg_replace('/\s+/', ' ', $title));
    }

    private function mapCertificateTarget(Request $request, array $data)
{
    $data['content_id'] = null;
    $data['trainingplan_id'] = null;

    if ($request->certificate_type === 'content') {
        $data['content_id'] = $request->item_id;
    }

    if ($request->certificate_type === 'playlist') {
        $data['trainingplan_id'] = $request->item_id;
    }

    if ($request->certificate_type === 'global' && !$data['is_assessment_required']) {
        $data['assessment_id'] = $request->item_id;
    }

    return $data;
}

public function previewTemplate(CertificateSetting $certificateSetting)
{
    $setting = $certificateSetting->load([
        'content',
        'trainingPlan.trainingplandetail.content',
        'assessment'
    ]);

    return view(
        'certificate.templates.preview-template',
        compact('setting')
    );
}

public function previewGenerated(CertificateSetting $certificateSetting)
{
    return view('main.certificate.preview-generated', [
        'setting' => $certificateSetting->load([
            'content',
            'trainingPlan.trainingplandetail.content',
            'assessment'
        ])
    ]);
}

public function previewCustomTemplate(CertificateSetting $certificateSetting)
{
    return view('main.certificate.templates.preview-custom-template', [
        'setting' => $certificateSetting->load([
            'content',
            'trainingPlan.trainingplandetail.content',
            'assessment'
        ])
    ]);
}
}
