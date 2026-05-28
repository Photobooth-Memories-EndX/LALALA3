@extends('layouts.sidebar-master')

@section('content')

<x-learning.dashboard-shell 
    active="assessment"
    title="Learning Dashboard"
    subtitle="Daftar assessment dan hasil evaluasi pembelajaran Anda."
>
    @php
        $assessmentItems = collect($assessments ?? []);
        $resultItems = collect($result ?? []);

        $totalAssessment = $assessmentItems->count();
        $completedAssessment = $resultItems->where('final', 1)->count();
        $passedAssessment = $resultItems->where('pass', 1)->count();
        $notStartedAssessment = max($totalAssessment - $completedAssessment, 0);
    @endphp

    <x-learning.assessment-summary
        :total-assessment="$totalAssessment"
        :completed-assessment="$completedAssessment"
        :passed-assessment="$passedAssessment"
        :not-started-assessment="$notStartedAssessment"
    />

    <x-learning.assessment-list :assessments="$assessments" />

    <x-learning.assessment-result :result="$result" />

</x-learning.dashboard-shell>

@endsection

@include('user.learning.partials.assessment-style')
@include('user.learning.partials.assessment-script')