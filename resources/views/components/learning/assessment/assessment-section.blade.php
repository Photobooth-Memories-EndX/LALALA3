@props([
    'assessments',
])

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <x-learning.assessment.assessment-header />
    <x-learning.assessment.assessment-table :assessments="$assessments" />
</div>
