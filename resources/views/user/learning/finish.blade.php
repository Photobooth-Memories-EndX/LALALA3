@extends('layouts.sidebar-master')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
@endif

<div class="page-body">
    <div class="container">

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Learning Record</h3>
            </div>

            <div class="card-body">
                <div id="table-default" class="table-responsive">

                    @unless (count($content_user) == 0)

                        <table class="table table-bordered table-striped" id="MyTable">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-center">Title</th>
                                    <th class="text-center">Classification</th>
                                    <th class="text-center">Duration</th>
                                    <th class="text-center">Progress</th>
                                    <th class="text-center">Finish Date</th>
                                    <th class="text-center">Score Test</th>
                                    <th class="text-center">Certificate</th>
                                </tr>
                            </thead>

                            <tbody>
                               @foreach ($content_user as $list)
                                    @php
                                        $progress = (int) ($list->progress ?? 0);
                                        $isCompleted = $progress >= 100;
                                        $content = $list->content ?? null;
                                        $score = null;

                                        if (!blank($list->assesment ?? null)) {
                                            $datas = $list->assesment->scores ?? collect();

                                            $filter = $datas->filter(function ($assesment_result) use($list) {
                                                return $assesment_result->user_id == $list->user_id;
                                            })->sortByDesc('score')->first();

                                            if (!blank($filter)) {
                                                $score = $filter->score;
                                            }
                                        }

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Certificate Logic
                                        |--------------------------------------------------------------------------
                                        */

                                        $certificateSetting = collect($certificateSettings ?? [])
                                            ->where('certificate_type', 'content')
                                            ->where('content_id', $content->id ?? null)
                                            ->first();

                                        $minimumProgress = $certificateSetting->minimum_progress ?? 100;
                                        $minimumScore = $certificateSetting->minimum_score ?? null;

                                        $isProgressPassed = $progress >= $minimumProgress;

                                        $isAssessmentPassed = true;

                                        if ($certificateSetting && $certificateSetting->is_assessment_required) {
                                            $isAssessmentPassed =
                                                !is_null($score)
                                                && !is_null($minimumScore)
                                                && $score >= $minimumScore;
                                        }

                                        $canGenerateCertificate =
                                            $certificateSetting
                                            && $certificateSetting->is_active
                                            && $isProgressPassed
                                            && $isAssessmentPassed;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>

                                        <td>
                                            @if ($list->assesment != null)
                                                Content
                                            @elseif ($list->assesmentPlaylist != null)
                                                @php
                                                    $playlistAssessment = $list->assesmentPlaylist
                                                        ->where('content_id', $list->content_id)
                                                        ->first();
                                                @endphp

                                                @if ($playlistAssessment && $playlistAssessment->trainingplan && $playlistAssessment->trainingplan->deleted_at == null)
                                                    <div class="badge bg-green-lt">Playlist active</div>
                                                    <div style="font-size: 1.75vh;">
                                                        {{ $playlistAssessment->trainingplan->title ?? '#N/A' }}
                                                    </div>
                                                @else
                                                    <div class="badge bg-red-lt">Playlist has been deleted</div>
                                                    <div style="font-size: 1.75vh;">
                                                        {{ $playlistAssessment->trainingplan->title ?? '#N/A' }}
                                                    </div>
                                                @endif
                                            @else
                                                #N/A
                                            @endif
                                        </td>

                                        <td>
                                            <div class="row align-items-center">
                                                <div class="col-auto">

                                                    @if (in_array($list->content->type ?? '', ['Video','YouTube']))
                                                        <span class="bg-red-lt avatar">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-video" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                <path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z"></path>
                                                                <path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                            </svg>
                                                        </span>
                                                    @else
                                                        <span class="bg-yellow-lt avatar">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-type-pdf" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                                                                <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4"></path>
                                                                <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6"></path>
                                                                <path d="M17 18h2"></path>
                                                                <path d="M20 15h-3v6"></path>
                                                                <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z"></path>
                                                            </svg>
                                                        </span>
                                                    @endif

                                                </div>

                                                <div class="col">
                                                    <a href="{{ route('content.user.view', $list->content->slug ?? '') }}"
                                                       class="text-reset">
                                                        {{ $list->content->title ?? '' }}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            {{ $list->id }}{{ $list->content->classification->classification_name ?? '' }}
                                        </td>

                                        <td>
                                            {{ $list->content->duration ?? '' }}
                                        </td>

                                        <td>
                                            {{ ($list->progress ?? 0) . '%' }}
                                        </td>

                                        <td>
                                            @if ($list->progress >= 100)
                                                {{ $list->created_at }}
                                            @else
                                                #N/A
                                            @endif
                                        </td>

                                        <td>
                                            {{ $score ?? '-' }}
                                        </td>

                                        <td class="text-center">
                                            @if($canGenerateCertificate)
                                                <a href="{{ route('certificate.generate', $list->id) }}"
                                                   class="btn btn-sm btn-primary">
                                                    <i class="ti ti-certificate mr-1"></i>
                                                    Generate
                                                </a>
                                            @else
                                                <span class="badge bg-secondary-lt text-secondary">
                                                    Not Available
                                                </span>
                                            @endif
                                        </td>
                                    </tr>

                                @endforeach
                            </tbody>
                        </table>

                    @else
                        <p>No content data found</p>
                    @endunless

                </div>
            </div>
        </div>

    </div>
</div>

<div class="page-body pt-0">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Certificates</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr><th>Type</th><th>Certificate</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    @forelse($certificateOpportunities ?? [] as $item)
                        @php
                            $setting = $item['setting'];
                            $certificate = $item['certificate'];
                            $eligible = $item['eligibility']['eligible'];
                        @endphp
                        <tr>
                            <td><span class="badge bg-blue-lt">{{ ucfirst($setting->certificate_type) }}</span></td>
                            <td>{{ $setting->sourceTitle() }}</td>
                            <td>
                                @if($certificate)
                                    <span class="badge bg-success-lt">Issued</span>
                                @elseif($eligible)
                                    <span class="badge bg-green-lt">Eligible</span>
                                @else
                                    <span class="text-muted">{{ $item['eligibility']['reason'] }}</span>
                                @endif
                            </td>
                            <td>
                                @if($certificate)
                                    <a class="btn btn-sm btn-primary" href="{{ route('certificate.download', $certificate->credential_id) }}">Download</a>
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('certificate.verify', $certificate->credential_id) }}">Verify</a>
                                    <a class="btn btn-sm btn-outline-info" target="_blank" rel="noopener" href="{{ $certificate->linkedin_url }}">LinkedIn</a>
                                @elseif($eligible)
                                    <form method="POST" action="{{ route('certificate.generate.setting', $setting) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-primary" type="submit">Generate Certificate</button>
                                    </form>
                                @else
                                    <span class="badge bg-secondary-lt">Not Available</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No certificate setting available.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(function() {
            $("#MyTable").DataTable({
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All'],
                ],
                "pageLength": 25,
                "autoWidth": false,
            });
        });
    </script>
@endsection
