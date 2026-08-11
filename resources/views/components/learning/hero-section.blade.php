@props([
    'user' => null,
    'learningHours' => 0,
    'totalLearning' => 0,
    'completedLearning' => 0,
    'ongoingLearning' => 0,
    'passedAssessment' => 0,
])

@php
    $user = $user ?? auth()->user();
@endphp

<x-shared.hero-learning-ongoing
    :stats="[
        ['label' => 'Total Learning', 'value' => number_format($totalLearning)],
        ['label' => 'Completed', 'value' => number_format($completedLearning)],
        ['label' => 'On Going', 'value' => number_format($ongoingLearning)],
        ['label' => 'Learning Hours', 'value' => number_format($learningHours, 1)],
    ]"
    {{ $attributes }}
/>
