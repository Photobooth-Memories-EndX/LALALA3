@php
    $user = auth()->user();
    $owlCourseCompleted = (int) ($owlCourseCompleted ?? ($completedCourse ?? 0));
    $nonOwlCourseCompleted = (int) ($nonOwlCourseCompleted ?? 0);
    $completedCourseVal = (int) ($completedCourse ?? ($owlCourseCompleted + $nonOwlCourseCompleted));
    $totalOngoingVal = (int) ($totalOngoing ?? ($ongoingCourse ?? 0));
    $ongoingCourseVal = $totalOngoingVal;
    $totalCourseVal = (int) ($totalCourse ?? ($ongoingCourseVal + $completedCourseVal));
    $learningHours = $totalLearningHours ?? ($learningHours ?? 0);

    $teamName = $user?->team ?? $user?->department ?? '';
    $empId = $user?->id_emp ?? $user?->id ?? '';
    if ($teamName && $empId) {
        $userDetail = "{$teamName} / {$empId}";
    } elseif ($teamName || $empId) {
        $userDetail = $teamName ?: $empId;
    } else {
        $userDetail = 'Training Team / 104921';
    }
@endphp

@include('components.shared.hero-section', [
    'user' => $user,
    'heroLabel' => '',
    'heroTitle' => '',
    'heroName' => $user?->name ?? 'Learner',
    'heroSubtitle' => $userDetail,
    'owlCourseCompleted' => $owlCourseCompleted,
    'nonOwlCourseCompleted' => $nonOwlCourseCompleted,
    'totalOngoing' => $totalOngoingVal,
    'ongoingCourseVal' => $ongoingCourseVal,
    'completedCourseVal' => $completedCourseVal,
    'totalCourseVal' => $totalCourseVal,
    'learningHours' => $learningHours,
    'totalLearningHours' => $learningHours,
    'compactSummary' => true,
])
