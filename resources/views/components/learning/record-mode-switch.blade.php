@props([
    // Mode aktif Learning Record: 'online' | 'offline' | 'all'.
    'mode' => 'online',
])

@php
    $validModes = ['online', 'offline', 'all'];
    $currentMode = in_array(strtolower((string) $mode), $validModes, true)
        ? strtolower((string) $mode)
        : 'online';
@endphp

{{-- Secondary navigation Learning Record — perpindahan mode via AJAX (dashboard-script.blade.php). --}}
<div class="d-flex align-items-center flex-wrap gap-2 my-task-pills-row" role="group" aria-label="Learning Record mode">
    <button type="button"
            class="my-task-category-pill record-mode-btn {{ $currentMode === 'online' ? 'active' : '' }}"
            data-mode="online"
            title="KP OWL"
            aria-label="KP OWL"
            aria-pressed="{{ $currentMode === 'online' ? 'true' : 'false' }}">
        <i class="ti ti-device-laptop" aria-hidden="true"></i>
        <span>KP OWL</span>
    </button>
    <button type="button"
            class="my-task-category-pill record-mode-btn {{ $currentMode === 'offline' ? 'active' : '' }}"
            data-mode="offline"
            title="Non KP OWL"
            aria-label="Non KP OWL"
            aria-pressed="{{ $currentMode === 'offline' ? 'true' : 'false' }}">
        <i class="ti ti-certificate" aria-hidden="true"></i>
        <span>Non KP OWL</span>
    </button>
    <button type="button"
            class="my-task-category-pill record-mode-btn {{ $currentMode === 'all' ? 'active' : '' }}"
            data-mode="all"
            title="All Record"
            aria-label="All Record"
            aria-pressed="{{ $currentMode === 'all' ? 'true' : 'false' }}">
        <i class="ti ti-stack-2" aria-hidden="true"></i>
        <span>All Record</span>
    </button>
</div>
