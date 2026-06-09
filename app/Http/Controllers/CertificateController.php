<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\CertificateSetting;
use App\Models\Content;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CertificateController extends Controller
{
    public function index()
    {
        $settings = CertificateSetting::with([
            'content',
            'trainingPlan',
            'assessment'
        ])
            ->orderByRaw('certificate_type = "content" DESC')
            ->orderByRaw('certificate_type = "playlist" DESC')
            ->orderByRaw('certificate_type = "global" DESC')
            ->latest()
            ->paginate(15);

        return view('main.certificate.index', compact('settings'));
    }

public function store(Request $request)
{
    $request->validate([
        'certificate_type' => 'required|in:content,playlist,global',
        'item_id' => 'required',
        'assessment_id' => 'nullable',
        'is_assessment_required' => 'nullable|boolean',
        'minimum_progress' => 'required|integer|min:0|max:100',
        'minimum_score' => 'nullable|integer|min:0|max:100',
        'issuer_name' => 'required|string|max:255',
        'issuer_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'certificate_template' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        'is_active' => 'required|boolean',
    ]);

    $data = $request->only([
        'certificate_type',
        'assessment_id',
        'minimum_progress',
        'minimum_score',
        'issuer_name',
        'is_active',
    ]);

    $data['is_assessment_required'] = $request->has('is_assessment_required') ? 1 : 0;

    if ($data['is_assessment_required'] && empty($data['minimum_score'])) {
        return back()
            ->withInput()
            ->withErrors([
                'minimum_score' => 'Minimum score wajib diisi jika assessment diwajibkan.'
            ]);
    }

    $data = $this->mapCertificateTarget($request, $data);

    if ($request->hasFile('issuer_logo')) {
        $data['issuer_logo'] = $this->uploadFile(
            $request->file('issuer_logo'),
            'uploads/certificates/logos'
        );
    }

    if ($request->hasFile('certificate_template')) {
        $data['certificate_template'] = $this->uploadFile(
            $request->file('certificate_template'),
            'uploads/certificates/templates'
        );
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
        'assessment_id' => 'nullable',
        'is_assessment_required' => 'nullable|boolean',
        'minimum_progress' => 'required|integer|min:0|max:100',
        'minimum_score' => 'nullable|integer|min:0|max:100',
        'issuer_name' => 'required|string|max:255',
        'issuer_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'certificate_template' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        'is_active' => 'required|boolean',
    ]);

    $data = $request->only([
        'certificate_type',
        'assessment_id',
        'minimum_progress',
        'minimum_score',
        'issuer_name',
        'is_active',
    ]);

    $data['is_assessment_required'] = $request->has('is_assessment_required') ? 1 : 0;

    if ($data['is_assessment_required'] && empty($data['minimum_score'])) {
        return back()
            ->withInput()
            ->withErrors([
                'minimum_score' => 'Minimum score wajib diisi jika assessment diwajibkan.'
            ]);
    }

    $data = $this->mapCertificateTarget($request, $data);

    if ($request->hasFile('issuer_logo')) {
        $data['issuer_logo'] = $this->uploadFile(
            $request->file('issuer_logo'),
            'uploads/certificates/logos'
        );
    }

    if ($request->hasFile('certificate_template')) {
        $data['certificate_template'] = $this->uploadFile(
            $request->file('certificate_template'),
            'uploads/certificates/templates'
        );
    }

    $certificateSetting->update($data);

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
            $items = TrainingPlan::select('id', 'title')
                ->orderBy('title')
                ->get();
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

        $query = Assesment::select('id', 'title', 'passing_grade', 'content_id', 'trainingplan_id', 'type');

        if ($type === 'content') {
            $query->where('content_id', $itemId);
        } elseif ($type === 'playlist') {
            $query->where('trainingplan_id', $itemId);
        } elseif ($type === 'global') {
            $query->where('type', 'global');
        }

        return response()->json(
            $query->orderBy('title')->get()
        );
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

    if ($request->certificate_type === 'global') {
        $data['assessment_id'] = $request->item_id;
    }

    return $data;
}

public function previewTemplate(CertificateSetting $certificateSetting)
{
    if (!$certificateSetting->certificate_template) {
        return back()->withErrors([
            'certificate_template' => 'Template sertifikat belum diupload.'
        ]);
    }

    return response()->file(public_path($certificateSetting->certificate_template));
}

public function previewGenerated(CertificateSetting $certificateSetting)
{
    return view('main.certificate.preview-generated', [
        'setting' => $certificateSetting->load([
            'content',
            'trainingPlan',
            'assessment'
        ])
    ]);
}
}