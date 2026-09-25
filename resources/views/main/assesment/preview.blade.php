@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        {{-- Mandatory Preview Mode Banner --}}
        <div class="alert alert-info border-0 rounded-4 shadow-sm mb-3 d-flex flex-wrap align-items-center justify-content-between p-3 gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; min-width: 44px;">
                    <i class="ti ti-eye fs-1"></i>
                </div>
                <div>
                    <strong class="d-block text-dark fw-bold fs-3">THIS ASSESSMENT IS IN PREVIEW MODE</strong>
                    <span class="small text-muted">Responses and results will not be saved.</span>
                </div>
            </div>
            <div>
                <a href="{{ route('assesment.index') }}" class="btn btn-outline-primary rounded-3 px-3 py-2 fw-semibold">
                    <i class="ti ti-arrow-left me-1"></i> Back to Assessment Management
                </a>
            </div>
        </div>

        {{-- Assessment Info & Visual Timer (Reusing presentation component) --}}
        @include('user.assesment.partials.view-timing')

        {{-- Instructions (Reusing presentation component) --}}
        @include('user.assesment.partials.view-instruction')

        {{-- Assessment Question Card (No Form Action, Pure Client-Side Container) --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div id="previewQuestionsContainer">
                    @unless(count($assesment_question) == 0)

                        @include('user.assesment.partials.view-question-navigation')

                        {{-- Preview Footer with Finish Preview button --}}
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted small">
                                <i class="ti ti-info-circle me-1"></i> Preview mode: You can test answering and navigating questions freely.
                            </div>
                            <div>
                                <button type="button" 
                                        id="btnFinishPreview" 
                                        class="btn text-white fw-semibold rounded-3 px-4 py-2 shadow-sm" 
                                        style="background-color: #28be58 !important; border-color: #28be58 !important;">
                                    Finish Preview
                                </button>
                            </div>
                        </div>

                    @else

                        @include('user.assesment.partials.view-empty-state')

                    @endunless
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Finish Preview Modal (No Database Writes) --}}
<div class="modal fade" id="finishPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: #28be58 !important;">
                    <i class="ti ti-circle-check me-1"></i> Preview Completed
                </h5>
                <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 assessment-modal-body fw-normal">
                Preview completed. No responses or results were saved.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 text-secondary" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                <a href="{{ route('assesment.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">Back to Assessment Management</a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
    @include('user.assesment.partials.view-style')
@endsection

@section('script')
    @include('main.assesment.preview.scripts')
@endsection
