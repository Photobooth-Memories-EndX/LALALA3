@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        {{-- Header Section with Back Navigation & Hero Card --}}
        @include('main.manage-training-schedule.components.schedule-header')

        {{-- Quick Filter Bar --}}
        @include('main.manage-training-schedule.components.schedule-filter')

        {{-- Main Training Schedule Data Table --}}
        @include('main.manage-training-schedule.components.schedule-table')

        {{-- Add Training Schedule Modal --}}
        @include('main.manage-training-schedule.components.add-training-modal')

        {{-- More Filters Modal --}}
        @include('main.manage-training-schedule.modals.schedule-filter-modal')
    </div>
</div>
@endsection

@section('style')
<style>
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
