@php
    $user = auth()->user();
    $ongoingCourseVal = (int) ($ongoingCourse ?? 84);
    $completedCourseVal = (int) ($completedCourse ?? 58);
    $totalCourseVal = $ongoingCourseVal + $completedCourseVal;
    $learningHours = $learningHours ?? 20.8;

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
    'ongoingCourseVal' => $ongoingCourseVal,
    'completedCourseVal' => $completedCourseVal,
    'totalCourseVal' => $totalCourseVal,
    'learningHours' => $learningHours,
    'compactSummary' => true,
])
