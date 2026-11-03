@extends('layouts.sidebar-master')

@section('content')

@php
    $learningTabs = ['ongoing', 'record', 'my-task', 'certificate'];
    $rawTab = request('learning_tab');
    if ($rawTab === 'assessment') {
        $rawTab = 'my-task';
    }
    $initialLearningTab = in_array($rawTab, $learningTabs, true)
        ? $rawTab
        : 'ongoing';
@endphp

<style>
    /* Force rounded pill styling on all Quick Filter tab states in Learning Record */
    .quick-filter-btn,
    .quick-filter-btn:hover,
    .quick-filter-btn:focus,
    .quick-filter-btn:active,
    .quick-filter-btn:disabled,
    .quick-filter-btn.loading,
    .quick-filter-btn.disabled,
    .quick-filter-skeleton,
    .record-mode-switch .record-mode-btn,
    .record-mode-switch .record-mode-btn:hover,
    .record-mode-switch .record-mode-btn:focus,
    .record-mode-switch .record-mode-btn:active,
    .record-mode-switch .record-mode-btn:disabled,
    .record-mode-switch .record-mode-btn.loading,
    .record-mode-switch .record-mode-btn.disabled,
    .record-mode-switch-wrapper .record-mode-btn,
    .learning-mode-switch .record-mode-btn {
        border-radius: 9999px !important; /* Force fully rounded pill shape at all times */
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
</style>

<div class="page-body owl-unified-theme-page learning-dashboard-page">
    <div class="container-xl">

        <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title mb-1">My Learning Dashboard</h2>
                    <div class="text-muted">
                        Manage your learning, learning records, assessments, and certificates.
                    </div>
                </div>
            </div>
        </div>

        <div id="learning-hero-region">
            <div data-learning-hero="ongoing"
                class="{{ $initialLearningTab === 'ongoing' ? '' : 'd-none' }}"
                 aria-hidden="{{ $initialLearningTab === 'ongoing' ? 'false' : 'true' }}">
                <x-shared.hero-learning-ongoing
                    :mode="request('ongoing_mode', 'content')"
                    :count="in_array(request('ongoing_mode'), ['non-kp-owl', 'ongoing-non-kp-owl', 'offline'], true) ? count($ongoingNonKpOwl ?? []) : 0"
                />
            </div>

            <div data-learning-hero="record"
                 class="{{ $initialLearningTab === 'record' ? '' : 'd-none' }}"
                 aria-hidden="{{ $initialLearningTab === 'record' ? 'false' : 'true' }}">
                <x-shared.hero-learning-record
                    :recordMode="$recordMode ?? request('record_mode', 'online')"
                    :stats="$recordStats ?? [
                        ['label' => 'Course Completed', 'value' => number_format($completedContentLearning ?? 0)],
                        ['label' => 'Playlist Completed', 'value' => number_format($completedPlaylistLearning ?? 0)],
                        ['label' => 'Learning Hours', 'value' => number_format($learningHours ?? 0, 2)],
                    ]"
                />
            </div>

            <div data-learning-hero="assessment"
                 class="{{ $initialLearningTab === 'assessment' ? '' : 'd-none' }}"
                 aria-hidden="{{ $initialLearningTab === 'assessment' ? 'false' : 'true' }}">
                <x-shared.hero-learning-assessment />
            </div>

            <div data-learning-hero="my-task"
     class="{{ $initialLearningTab === 'my-task' ? '' : 'd-none' }}"
     aria-hidden="{{ $initialLearningTab === 'my-task' ? 'false' : 'true' }}">
    <x-shared.hero-learning-my-task />
</div>


            <div data-learning-hero="certificate"
                 class="{{ $initialLearningTab === 'certificate' ? '' : 'd-none' }}"
                 aria-hidden="{{ $initialLearningTab === 'certificate' ? 'false' : 'true' }}">
                <x-shared.hero-learning-certificate
                    :stats="[
                        ['label' => 'Total Certificate', 'value' => number_format(data_get($certificateStats, 'total', 0))],
                        ['label' => 'Playlist Certificate', 'value' => number_format(data_get($certificateStats, 'playlist', 0))],
                        ['label' => 'Content Certificate', 'value' => number_format(data_get($certificateStats, 'content', 0))],
                    ]"
                />
            </div>
        </div>

        <x-learning.tab-navigation :active="$initialLearningTab" />

        <div id="learning-tab-content">
            @if (request('record_mode') === 'ongoing-non-kp-owl' || in_array(request('ongoing_mode'), ['non-kp-owl', 'ongoing-non-kp-owl', 'offline'], true))
                @include('user.learning.dashboard-partials.ongoing-non-kp-owl', [
                    'ongoingNonKpOwl' => $ongoingNonKpOwl ?? collect(),
                    'filterOptions'   => $filterOptions ?? [],
                ])
            @else
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="placeholder-glow">
                        <span class="placeholder col-6 mb-3 rounded-3" style="height:20px"></span>
                        <span class="placeholder col-12 mb-2 rounded-3" style="height:14px"></span>
                        <span class="placeholder col-10 mb-4 rounded-3" style="height:14px"></span>
                        <div class="row g-3">
                            <div class="col-md-4"><span class="placeholder col-12 rounded-4" style="height:200px"></span></div>
                            <div class="col-md-4"><span class="placeholder col-12 rounded-4" style="height:200px"></span></div>
                            <div class="col-md-4"><span class="placeholder col-12 rounded-4" style="height:200px"></span></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

@endsection

@include('user.learning.partials.dashboard-style')
@if (session('task_submitted_id'))
    <script>
        window._taskSubmittedId = {{ (int) session('task_submitted_id') }};
        window._taskSubmittedTitle = @json(session('task_submitted_title', ''));
    </script>
@endif
@include('user.learning.partials.dashboard-script')
