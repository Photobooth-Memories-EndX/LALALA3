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

@include('components.shared.hero-section', [
    'user' => $user,
    'heroLabel' => 'My Learning',
    'heroTitle' => 'Learning Dashboard',
    'heroName' => $user?->name ?? 'Learner',
    'heroSubtitle' => 'Track your ongoing learning, review records, assessments, and certificates in one place.',
    'ongoingCourseVal' => (int) $ongoingLearning,
    'completedCourseVal' => (int) $completedLearning,
    'totalCourseVal' => (int) $totalLearning,
    'learningHours' => $learningHours,
])
