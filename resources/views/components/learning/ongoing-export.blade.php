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
   class="btn btn-success bg-emerald-600 text-white hover:bg-emerald-700 d-inline-flex align-items-center justify-content-center p-2.5 rounded-3 shadow-sm ongoing-download-button"
   title="Download"
   aria-label="Download {{ ucfirst($mode) }} learning records as Excel">
    <i class="bi bi-download ti ti-download" aria-hidden="true"></i>
    <span class="visually-hidden">Download</span>
</a>
