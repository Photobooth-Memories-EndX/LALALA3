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
<div class="learning-mode-switch record-mode-switch" role="group" aria-label="Learning Record mode">
    <button type="button"
            class="btn learning-mode-btn record-mode-btn {{ $currentMode === 'online' ? 'btn-primary active' : 'btn-outline-primary' }}"
            data-mode="online"
            title="Online Learning Record"
            aria-label="Online Learning Record"
            aria-pressed="{{ $currentMode === 'online' ? 'true' : 'false' }}">
        <i class="ti ti-device-laptop" aria-hidden="true"></i>
        <span>Online</span>
    </button>
    <button type="button"
            class="btn learning-mode-btn record-mode-btn {{ $currentMode === 'offline' ? 'btn-primary active' : 'btn-outline-primary' }}"
            data-mode="offline"
            title="Offline Training Record"
            aria-label="Offline Training Record"
            aria-pressed="{{ $currentMode === 'offline' ? 'true' : 'false' }}">
        <i class="ti ti-certificate" aria-hidden="true"></i>
        <span>Offline</span>
    </button>
    <button type="button"
            class="btn learning-mode-btn record-mode-btn {{ $currentMode === 'all' ? 'btn-primary active' : 'btn-outline-primary' }}"
            data-mode="all"
            title="All Learning Record (Online & Offline)"
            aria-label="All Learning Record (Online & Offline)"
            aria-pressed="{{ $currentMode === 'all' ? 'true' : 'false' }}">
        <i class="ti ti-stack-2" aria-hidden="true"></i>
        <span>All</span>
    </button>
</div>
