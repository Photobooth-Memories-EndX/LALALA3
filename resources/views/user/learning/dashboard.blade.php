@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title mb-1">Learning Dashboard</h2>
                    <div class="text-muted">
                        Manage your learning, learning records, assessments, and certificates.
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
