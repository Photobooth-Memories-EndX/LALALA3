@props(['mode' => 'content'])
@php
    $currentMode = strtolower((string) $mode);
    if (in_array($currentMode, ['non-kp-owl', 'ongoing-non-kp-owl', 'offline'], true)) {
        $downloadUrl = route('user.learning.record.export', array_merge(request()->all(), ['source' => 'offline']));
    } else {
        $exportParams = collect(request()->query())->filter(fn ($value, $key) => str_starts_with($key, 'ongoing_'))
            ->except('ongoing_page')->put('ongoing_mode', $currentMode)->all();
        $exportParams['ongoing_search'] = request('ongoing_search', request('q', ''));
        $downloadUrl = route('user.learning.ongoing.export', array_merge($exportParams, ['format' => 'excel']));
    }
@endphp
<a href="{{ $downloadUrl }}"
   class="btn btn-outline-success btn-learning-success ongoing-filter-action-btn ongoing-download-button my-task-btn-square my-task-btn-export"
   title="Download Excel"
   aria-label="Download {{ ucfirst($mode) }} learning records as Excel">
    <i class="ti ti-download fs-3" aria-hidden="true"></i>
    <span class="visually-hidden">Download Excel</span>
</a>
