@extends('layouts.sidebar-master')

@section('content')

@php
    $user = Auth::user();

    $contentItems = collect($content_user ?? []);
    $assessmentItems = collect($assessments ?? []);
    $resultItems = collect($result ?? []);

    $totalLearning = $contentItems->count();
    $completedLearning = $contentItems
        ->filter(fn($item) => ($item->progress ?? 0) >= 100 || ($item->finish ?? '0') == '1')
        ->count();

    $ongoingLearning = $contentItems
        ->filter(fn($item) => ($item->finish ?? '0') == '0')
        ->count();

    $averageProgress = $totalLearning > 0 ? round($contentItems->avg('progress')) : 0;

    $totalAssessment = $assessmentItems->count();
    $completedAssessment = $resultItems->where('final', 1)->count();
    $passedAssessment = $resultItems->where('pass', 1)->count();
@endphp

<div class="page-body">
    <div class="container-xl">

        <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title mb-1">Learning Dashboard</h2>
                    <div class="text-muted">
                        Kelola learning, record, assessment, dan sertifikat Anda.
                    </div>
                </div>
            </div>
        </div>

        <x-learning.profile-card 
            :user="$user" 
            :learning-hours="$learningHours ?? 0" 
        />



        <x-learning.tab-navigation />

        <x-learning.summary-cards
            :total-learning="$totalLearning"
            :completed-learning="$completedLearning"
            :ongoing-learning="$ongoingLearning"
            :passed-assessment="$passedAssessment"
        />

        <div id="tab-ongoing" class="learning-tab-content">
            <x-learning.ongoing-section
                :ongoing-content-user="$ongoing_content_user"
                :ongoing-learning="$ongoingLearning"
                :average-progress="$averageProgress"
            />
        </div>

        <div id="tab-record" class="learning-tab-content d-none">
            <x-learning.record-section :content-user="$content_user" :average-progress="$averageProgress"  :certificate-settings="$certificateSettings" />
        </div>

        <div id="tab-assessment" class="learning-tab-content d-none">
            <x-learning.assessment-section :assessments="$assessments" :result="$result" />
        </div>

        <div id="tab-claim" class="learning-tab-content d-none">
            <x-learning.empty-tab title="Klaim Learning Hour" description="Halaman ini masih kosong dan bisa dikembangkan nanti." />
        </div>

        <div id="tab-certificate" class="learning-tab-content d-none">
            <x-learning.certificate-section
                :user-certificates="$userCertificates" />
        </div>

        <div id="tab-monitoring" class="learning-tab-content d-none">
            <x-learning.empty-tab title="Monitoring" description="Halaman monitoring masih kosong dan bisa dikembangkan nanti." />
        </div>

    </div>
</div>

@endsection

@include('user.learning.partials.dashboard-style')
@include('user.learning.partials.dashboard-script')