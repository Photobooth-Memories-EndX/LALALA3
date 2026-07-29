@extends('layouts.sidebar-master')

@section('content')

@php
    $learningTabs = ['ongoing', 'record', 'assessment', 'certificate'];
    $initialLearningTab = in_array(request('learning_tab'), $learningTabs, true)
        ? request('learning_tab')
        : 'ongoing';

@endphp

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
                    :stats="[
                        ['label' => 'Total Learning', 'value' => number_format($totalLearning)],
                        ['label' => 'Completed', 'value' => number_format($completedLearning)],
                        ['label' => 'On Going', 'value' => number_format($ongoingLearning)],
                        ['label' => 'Learning Hours', 'value' => number_format($learningHours ?? 0, 1)],
                    ]"
                />
            </div>

            <div data-learning-hero="record"
                 class="{{ $initialLearningTab === 'record' ? '' : 'd-none' }}"
                 aria-hidden="{{ $initialLearningTab === 'record' ? 'false' : 'true' }}">
                <x-shared.hero-learning-record
                    :stats="[
                        ['label' => 'Course Completed', 'value' => number_format($completedContentLearning ?? 0)],
                        ['label' => 'Playlist Completed', 'value' => number_format($completedPlaylistLearning ?? 0)],
                        ['label' => 'Learning Hours', 'value' => number_format($learningHours ?? 0, 1)],
                    ]"
                />
            </div>

            <div data-learning-hero="assessment"
                 class="{{ $initialLearningTab === 'assessment' ? '' : 'd-none' }}"
                 aria-hidden="{{ $initialLearningTab === 'assessment' ? 'false' : 'true' }}">
                <x-shared.hero-learning-assessment />
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
        </div>

    </div>
</div>

@endsection

@include('user.learning.partials.dashboard-style')
@include('user.learning.partials.dashboard-script')
