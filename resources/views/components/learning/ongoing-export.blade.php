@props(['mode'])
@php
    $exportParams = collect(request()->query())->filter(fn ($value, $key) => str_starts_with($key, 'ongoing_'))
        ->except('ongoing_page')->put('ongoing_mode', $mode)->all();
    $exportParams['ongoing_search'] = request('ongoing_search', request('q', ''));
@endphp
<a href="{{ route('user.learning.ongoing.export', array_merge($exportParams, ['format' => 'excel'])) }}"
   class="btn btn-white my-task-btn-square my-task-btn-export ongoing-filter-action-btn ongoing-download-button"
   title="Download Excel"
   aria-label="Download {{ ucfirst($mode) }} learning records as Excel">
    <i class="ti ti-download fs-3 text-secondary" aria-hidden="true"></i>
    <span class="visually-hidden">Download Excel</span>
</a>
