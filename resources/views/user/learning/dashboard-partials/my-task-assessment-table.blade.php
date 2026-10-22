{{-- Assessment table matching My Learning Assessment 8-column layout --}}
@php
    $assessments = collect($assessmentGroups)->map(function ($group) {
        if ($group instanceof \App\Models\Assesment) {
            return $group;
        }
        if (is_array($group) && isset($group['assessment'])) {
            return $group['assessment'];
        }
        if (is_array($group) && isset($group['tasks']) && $group['tasks']->isNotEmpty()) {
            $first = $group['tasks']->first();
            return $first->assessment ?? null;
        }
        return null;
    })->filter()->values();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <x-learning.assessment.assessment-table :assessments="$assessments" />
    <x-shared.assessment-confirmation-modal />
    <x-shared.assessment-continue-modal />
</div>

<script>
    if (typeof initLearningAssessment === 'function') {
        initLearningAssessment();
    }
</script>
