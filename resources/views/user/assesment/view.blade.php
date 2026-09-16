@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        @include('user.assesment.partials.view-header')

        @include('user.assesment.partials.view-timing')

        <div id="afkAlertBanner" class="alert alert-warning border-0 rounded-4 shadow-sm mb-3 d-none align-items-center justify-content-between p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="ti ti-clock-pause fs-2"></i>
                </div>
                <div>
                    <strong class="d-block text-dark fw-bold">Status: Away From Keyboard (AFK)</strong>
                    <span class="small text-muted">Aktivitas terhenti melebihi ambang batas. Waktu AFK sedang dihitung. Gerakkan mouse atau ketik untuk kembali aktif.</span>
                </div>
            </div>
            <span class="badge bg-warning text-dark font-monospace px-3 py-2 fs-6 fw-bold" id="afkElapsedCounter">0s</span>
        </div>

        @include('user.assesment.partials.view-instruction')

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <x-form action="{{ route('assesment.user.store', $assesment->slug) }}" method="post" id="formAssesment">
                    <input type="hidden" name="submission_token" value="{{ $assessmentSubmissionToken }}">
                    <input type="hidden" name="afk_seconds" id="inputAfkSeconds" value="{{ (int) ($assesment_result_draft->afk_duration_seconds ?? 0) }}">
                    @unless(count($assesment_question) == 0)

                        @include('user.assesment.partials.view-question-navigation')

                        @include('user.assesment.partials.view-submit-footer')

                    @else

                        @include('user.assesment.partials.view-empty-state')

                    @endunless
                </x-form>
            </div>
        </div>

    </div>
</div>

@include('user.assesment.partials.view-leave-modal')

@endsection

@section('style')
    @include('user.assesment.partials.view-style')
@endsection

@section('script')
    @include('user.assesment.partials.view-script')
@endsection