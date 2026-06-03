@extends('layouts.sidebar-master')

@php
    $totalLearning = count($content);
    $totalProgress = 0;
    $totalOngoing = 0;
    $totalFinished = 0;

    foreach ($content as $item) {
        $totalProgress += (int) ($item->progress ?? 0);

        if ((int) ($item->finish ?? 0) === 1) {
            $totalFinished++;
        } else {
            $totalOngoing++;
        }
    }

    $percentage = $totalLearning > 0
        ? round($totalProgress / $totalLearning, 2)
        : 0;
@endphp

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('main.profile.components.profile-hero')

        @include('main.profile.components.profile-stats')

        @include('main.profile.components.profile-table')

    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    .rounded-4 {
        border-radius: 20px !important;
    }

    #MyTable {
        font-size: 13px;
    }

    #MyTable thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    #MyTable tbody td {
        vertical-align: middle;
        padding-top: 14px;
        padding-bottom: 14px;
    }

    .avatar-xl {
        width: 96px;
        height: 96px;
        font-size: 32px;
    }

    .profile-hero-card {
    background:
        radial-gradient(circle at top right, rgba(32, 107, 196, .12), transparent 35%),
        linear-gradient(135deg, rgba(32, 107, 196, .05), rgba(255, 255, 255, 1));
}

    .avatar-2xl {
        width: 120px !important;
        height: 120px !important;
        font-size: 46px !important;
    }

    .profile-progress-box {
        min-width: 260px;
        padding: 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .75);
        border: 1px solid rgba(226, 232, 240, .9);
    }

    .profile-summary-card {
        border-radius: 16px !important;
        transition: all .2s ease;
    }

    .profile-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .10) !important;
    }

    .min-w-0 {
        min-width: 0;
    }

    @media (max-width: 991.98px) {
        .profile-progress-box {
            min-width: 100%;
        }

        .avatar-2xl {
            width: 96px !important;
            height: 96px !important;
            font-size: 36px !important;
        }
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        if ($('#MyTable').length) {
            $('#MyTable').DataTable({
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All']
                ],
                pageLength: 25,
                autoWidth: false,
                order: [[0, 'asc']]
            });
        }
    });
</script>
@endsection