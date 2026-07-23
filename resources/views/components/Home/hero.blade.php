@php
    $user = auth()->user();
    $ongoingCourseVal = (int) ($ongoingCourse ?? 84);
    $completedCourseVal = (int) ($completedCourse ?? 58);
    $totalCourseVal = $ongoingCourseVal + $completedCourseVal;
    $learningHours = $learningHours ?? 20.8;
@endphp

@include('components.shared.hero-section', [
    'user' => $user,
    'heroLabel' => 'Home',
    'heroTitle' => 'Learning Dashboard',
    'heroName' => $user?->name ?? 'Learner',
    'heroSubtitle' => 'Continue your learning journey',
    'ongoingCourseVal' => $ongoingCourseVal,
    'completedCourseVal' => $completedCourseVal,
    'totalCourseVal' => $totalCourseVal,
    'learningHours' => $learningHours,
    'compactSummary' => true,
])
