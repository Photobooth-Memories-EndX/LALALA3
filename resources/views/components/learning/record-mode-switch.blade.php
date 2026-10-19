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

{{-- Quick Filter Tab Row: Left = Mode Tabs (KP OWL, Non KP OWL, All Record), Right = Unified Download Dropdown --}}
<div class="d-flex align-items-center justify-content-between gap-2 w-100 mt-3 mb-0 quick-filter-row record-mode-switch-wrapper">
    <!-- Left Side: Quick Filter Tabs -->
    <div class="learning-mode-switch record-mode-switch d-flex align-items-center gap-2" role="group" aria-label="Learning Record mode">
        <button type="button"
                class="btn learning-mode-btn record-mode-btn {{ $currentMode === 'online' ? 'btn-primary active' : 'btn-outline-primary' }}"
                data-mode="online"
                title="KP OWL"
                aria-label="KP OWL"
                aria-pressed="{{ $currentMode === 'online' ? 'true' : 'false' }}">
            <i class="ti ti-device-laptop" aria-hidden="true"></i>
            <span>KP OWL</span>
        </button>
        <button type="button"
                class="btn learning-mode-btn record-mode-btn {{ $currentMode === 'offline' ? 'btn-primary active' : 'btn-outline-primary' }}"
                data-mode="offline"
                title="Non KP OWL"
                aria-label="Non KP OWL"
                aria-pressed="{{ $currentMode === 'offline' ? 'true' : 'false' }}">
            <i class="ti ti-certificate" aria-hidden="true"></i>
            <span>Non KP OWL</span>
        </button>
        <button type="button"
                class="btn learning-mode-btn record-mode-btn {{ $currentMode === 'all' ? 'btn-primary active' : 'btn-outline-primary' }}"
                data-mode="all"
                title="All Record"
                aria-label="All Record"
                aria-pressed="{{ $currentMode === 'all' ? 'true' : 'false' }}">
            <i class="ti ti-stack-2" aria-hidden="true"></i>
            <span>All Record</span>
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
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2 record-export-option"
                   href="{{ route('user.learning.record.export', array_merge($baseExportParams, ['source' => 'all'])) }}"
                   data-export-url="{{ route('user.learning.record.export') }}"
                   data-export-source="all">
                    <i class="ti ti-stack-2 text-azure" aria-hidden="true"></i>
                    <span>All Record</span>
                </a>
            </li>
        </ul>
    </div>
</div>
