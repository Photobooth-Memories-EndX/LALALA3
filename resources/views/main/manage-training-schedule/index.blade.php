@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        {{-- Header Section with Back Navigation & Hero Card --}}
        @include('main.manage-training-schedule.components.schedule-header')

        @if (session('success'))
            <div class="alert alert-success alert-dismissible rounded-4 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-check fs-2 text-success"></i>
                    <div class="fw-semibold">{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible rounded-4 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-alert-triangle fs-2 text-warning"></i>
                    <div class="fw-semibold">{{ session('warning') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible rounded-4 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-alert-circle fs-2 text-danger"></i>
                    <div class="fw-semibold">{{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Top Navigation Toggle Pills --}}
        <div class="mb-4">
            <ul class="nav nav-pills gap-2 bg-white p-1-5 rounded-4 shadow-sm border border-light-subtle d-inline-flex" id="scheduleMainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2" 
                            id="tab-manage-schedules-btn" data-bs-toggle="pill" data-bs-target="#tab-manage-schedules-pane" type="button" role="tab" aria-selected="true">
                        <i class="ti ti-calendar-event fs-2"></i>
                        <span>Manage Schedules</span>
                    </button>
                </li>
                <!-- <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2" 
                            id="tab-participant-tracking-btn" data-bs-toggle="pill" data-bs-target="#tab-participant-tracking-pane" type="button" role="tab" aria-selected="false">
                        <i class="ti ti-users-group fs-2"></i>
                        <span>Participant Tracking & Approvals</span>
                    </button>
                </li> -->
            </ul>
        </div>

        {{-- Main Tab Panes --}}
        <div class="tab-content" id="scheduleMainTabsContent">
            {{-- PANE 1: MANAGE SCHEDULES --}}
            <div class="tab-pane fade show active" id="tab-manage-schedules-pane" role="tabpanel">
                {{-- Quick Filter Bar --}}
                @include('main.manage-training-schedule.components.schedule-filter')

                {{-- Main Training Schedule Data Table --}}
                @include('main.manage-training-schedule.components.schedule-table')
            </div>

            <!-- {{-- PANE 2: PARTICIPANT TRACKING & APPROVALS --}}
            <div class="tab-pane fade" id="tab-participant-tracking-pane" role="tabpanel">
                @include('main.manage-training-schedule.partials.tracking-section')
            </div> -->
        </div>

        {{-- More Filters Modal --}}
        @include('main.manage-training-schedule.modals.schedule-filter-modal')

        {{-- Syllabus Details Modal --}}
        @include('main.manage-training-schedule.modals.syllabus-modal')

        {{-- Delete Confirmation Modal --}}
        @include('main.manage-training-schedule.modals.delete-confirm-modal')
    </div>
</div>
@endsection

@section('style')
<style>
    /* Table Sticky Freeze Header & Scrollable Container */
    .table-responsive.manage-schedule-scroll,
    .manage-schedule-scroll {
        max-height: 70vh !important;
        overflow-y: auto !important;
        overflow-x: auto !important;
        position: relative !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
    }

    #manageScheduleTable thead th,
    #tableInlineByTraining thead th,
    #tableInlineByEmployee thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
        background-color: #f8fafc !important;
        box-shadow: inset 0 -1px 0 #e2e8f0, 0 1px 2px rgba(0, 0, 0, 0.05) !important;
    }

    [data-bs-theme="dark"] #manageScheduleTable thead th,
    [data-bs-theme="dark"] #tableInlineByTraining thead th,
    [data-bs-theme="dark"] #tableInlineByEmployee thead th,
    body.dark-mode #manageScheduleTable thead th,
    body.dark-mode #tableInlineByTraining thead th,
    body.dark-mode #tableInlineByEmployee thead th,
    body.theme-dark #manageScheduleTable thead th,
    body.theme-dark #tableInlineByTraining thead th,
    body.theme-dark #tableInlineByEmployee thead th {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }

    /* Manage Training Schedule Custom Styling */
    .schedule-table-row {
        transition: background-color 0.15s ease;
    }

    .schedule-table-row:hover {
        background-color: rgba(0, 59, 109, 0.02) !important;
    }

    .btn-icon {
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .training-title-text {
        letter-spacing: -0.01em;
        line-height: 1.3;
    }

    /* Modal Form Customizations */
    .required:after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }
</style>
@endsection

@push('scripts')
    @include('main.manage-training-schedule.components.schedule-table-script')
@endpush
