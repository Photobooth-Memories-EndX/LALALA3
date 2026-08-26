<div class="assessment-page-header page-header d-print-none mb-3">
    <div class="container-xl">

        {{-- Top Navigation Row: Back Button (Left) and Breadcrumb (Right) --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="assessment-back">
                <a href="{{ route('manage.dashboard') }}" class="assessment-back-link">
                    <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                    Back
                </a>
            </div>

            <div class="assessment-breadcrumb">
                <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                    <li class="breadcrumb-item"><a href="{{ route('manage.dashboard') }}">Manage</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Assessment</li>
                </ol>
            </div>
        </div>

        {{-- Main Page Title and Actions Row --}}
        <div class="row align-items-center">

            <div class="col">
                <div class="page-pretitle text-muted">
                    Learning Management System
                </div>

                <h2 class="owl-page-title mb-1">
                    Assessment Management
                </h2>

                <div class="owl-page-subtitle">
                    Manage assessments, question banks, user assignments, and assessment results.
                </div>
            </div>

            <div class="col-auto ms-auto">
                <div class="btn-list">

                    <a href="{{ route('assesment.edit.score.index') }}"
                       class="btn btn-warning d-inline-flex align-items-center">
                        <i class="fas fa-pen me-2"></i>
                        <span>Edit Score</span>
                    </a>

                    <a href="{{ route('assesment.create') }}"
                       class="btn btn-primary d-inline-flex align-items-center">
                        <i class="fas fa-plus me-2"></i>
                        <span>Add Assessment</span>
                    </a>

                </div>
            </div>

        </div>

    </div>
</div>
