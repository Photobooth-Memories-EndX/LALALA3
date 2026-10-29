@props([
    // Mode aktif Learning Record: 'online' | 'offline' | 'all'.
    'mode' => 'online',
])

@php
    $validModes = ['online', 'offline', 'all'];
    $currentMode = in_array(strtolower((string) $mode), $validModes, true)
        ? strtolower((string) $mode)
        : 'online';
    $baseExportParams = request()->except(['source']);
@endphp

@once
<style>
.record-mode-switch .record-mode-btn {
    border-radius: 9999px !important;
    border: 0 !important;
    background-color: #f1f5f9 !important;
    color: #475569 !important;
    padding: 0.5rem 1.25rem !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    transition: all 0.15s ease-in-out !important;
    box-shadow: none !important;
    white-space: nowrap !important;
}
.record-mode-switch .record-mode-btn:hover {
    background-color: #e2e8f0 !important;
    color: #1e293b !important;
}
.record-mode-switch .record-mode-btn.active,
.record-mode-switch .record-mode-btn.btn-primary {
    background-color: #2563eb !important;
    color: #ffffff !important;
    box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25) !important;
}
[data-bs-theme="dark"] .record-mode-switch .record-mode-btn,
body.dark-mode .record-mode-switch .record-mode-btn,
body.theme-dark .record-mode-switch .record-mode-btn {
    background-color: #1e293b !important;
    color: #94a3b8 !important;
}
[data-bs-theme="dark"] .record-mode-switch .record-mode-btn.active,
body.dark-mode .record-mode-switch .record-mode-btn.active,
body.theme-dark .record-mode-switch .record-mode-btn.active {
    background-color: #2563eb !important;
    color: #ffffff !important;
}
</style>
@endonce

{{-- Quick Filter Tab Row: Left = Mode Tabs (All Record, KP OWL, Non KP OWL), Right = Unified Download Dropdown --}}
<div class="d-flex align-items-center justify-content-between gap-2 w-100 mt-3 mb-0 quick-filter-row record-mode-switch-wrapper">
    <!-- Left Side: Quick Filter Tabs (Sequence: All Record -> KP OWL -> Non KP OWL) -->
    <div class="learning-mode-switch record-mode-switch d-flex align-items-center gap-2" role="group" aria-label="Learning Record mode">
        <button type="button"
                class="btn learning-mode-btn record-mode-btn rounded-pill px-4 py-2 border-0 {{ $currentMode === 'all' ? 'active' : '' }}"
                data-mode="all"
                title="All Record"
                aria-label="All Record"
                aria-pressed="{{ $currentMode === 'all' ? 'true' : 'false' }}">
            <i class="ti ti-stack-2" aria-hidden="true"></i>
            <span>All Record</span>
        </button>
        <button type="button"
                class="btn learning-mode-btn record-mode-btn rounded-pill px-4 py-2 border-0 {{ $currentMode === 'online' ? 'active' : '' }}"
                data-mode="online"
                title="KP OWL"
                aria-label="KP OWL"
                aria-pressed="{{ $currentMode === 'online' ? 'true' : 'false' }}">
            <i class="ti ti-device-laptop" aria-hidden="true"></i>
            <span>KP OWL</span>
        </button>
        <button type="button"
                class="btn learning-mode-btn record-mode-btn rounded-pill px-4 py-2 border-0 {{ $currentMode === 'offline' ? 'active' : '' }}"
                data-mode="offline"
                title="Non KP OWL"
                aria-label="Non KP OWL"
                aria-pressed="{{ $currentMode === 'offline' ? 'true' : 'false' }}">
            <i class="ti ti-certificate" aria-hidden="true"></i>
            <span>Non KP OWL</span>
        </button>
    </div>

    <!-- Right Side: Consolidated Download Dropdown (Above Search/Filter) -->
    <div class="dropdown record-unified-download-dropdown dropdown-download-record ms-auto" id="downloadRecordDropdown">
        <button type="button"
                class="btn btn-success btn-learning-success dropdown-toggle d-flex align-items-center gap-1 fw-semibold"
                id="recordUnifiedDownloadBtn"
                data-bs-toggle="dropdown"
                data-export-url="{{ route('user.learning.record.export') }}"
                aria-expanded="false"
                title="Download Excel">
            <i class="ti ti-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3" aria-labelledby="recordUnifiedDownloadBtn">
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2 record-export-option"
                   href="{{ route('user.learning.record.export', array_merge($baseExportParams, ['source' => 'all'])) }}"
                   data-export-url="{{ route('user.learning.record.export') }}"
                   data-export-source="all">
                    <i class="ti ti-stack-2 text-azure" aria-hidden="true"></i>
                    <span>All Record</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2 record-export-option"
                   href="{{ route('user.learning.record.export', array_merge($baseExportParams, ['source' => 'online'])) }}"
                   data-export-url="{{ route('user.learning.record.export') }}"
                   data-export-source="online">
                    <i class="ti ti-device-laptop text-primary" aria-hidden="true"></i>
                    <span>KP OWL</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2 record-export-option"
                   href="{{ route('user.learning.record.export', array_merge($baseExportParams, ['source' => 'offline'])) }}"
                   data-export-url="{{ route('user.learning.record.export') }}"
                   data-export-source="offline">
                    <i class="ti ti-certificate text-warning" aria-hidden="true"></i>
                    <span>Non KP OWL</span>
                </a>
            </li>
        </ul>
    </div>
</div>
