@props([
    // Mode aktif Learning On Going: 'content' | 'playlist' | 'mooc' | 'non-kp-owl'.
    'mode' => 'content',
    'showDownload' => false,
])

@php
    $validModes = ['content', 'playlist', 'mooc', 'non-kp-owl', 'ongoing-non-kp-owl', 'offline'];
    $currentMode = in_array(strtolower((string) $mode), ['playlist', 'mooc', 'non-kp-owl', 'ongoing-non-kp-owl', 'offline'], true)
        ? strtolower((string) $mode)
        : 'content';
    if (in_array($currentMode, ['ongoing-non-kp-owl', 'offline'], true)) {
        $currentMode = 'non-kp-owl';
    }

    $isNonKpOwl = in_array($currentMode, ['non-kp-owl', 'ongoing-non-kp-owl', 'offline'], true);
    // Download button is only allowed for Non KP OWL tab; Content, Playlist, and MOOC tabs do not show download button.
    $canExport = (bool) ($showDownload && $isNonKpOwl);
@endphp

@once
<style>
.ongoing-filter-header-bar {
    width: 100% !important;
}
.ongoing-mode-switch {
    display: inline-flex !important;
    justify-content: flex-start !important;
    align-items: center !important;
    gap: 0.5rem !important; /* gap-2 */
    flex-wrap: wrap !important;
    width: auto !important;
    margin: 0 !important;
}
.ongoing-mode-switch::before,
.ongoing-mode-switch::after,
.ongoing-mode-switch .ongoing-mode-btn::before,
.ongoing-mode-switch .ongoing-mode-btn::after {
    display: none !important;
    content: none !important;
}
.ongoing-mode-switch .ongoing-mode-btn {
    width: auto !important;
    min-width: auto !important;
    flex: 0 0 auto !important;
    border-radius: 9999px !important; /* rounded-full */
    border: 0 !important;
    border-right: none !important;
    border-left: none !important;
    background-color: #f1f5f9 !important; /* bg-slate-100 */
    color: #475569 !important; /* text-slate-600 */
    padding: 0.625rem 1rem !important; /* px-4 py-2.5 */
    font-size: 0.875rem !important; /* text-sm */
    font-weight: 500 !important; /* font-medium */
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important; /* gap-2 */
    white-space: nowrap !important; /* whitespace-nowrap */
    transition: all 0.15s ease-in-out !important; /* transition-all */
    box-shadow: none !important;
    line-height: 1.25 !important;
    height: auto !important;
    min-height: auto !important;
    cursor: pointer !important;
}
.ongoing-mode-switch .ongoing-mode-btn:hover {
    background-color: #e2e8f0 !important; /* hover:bg-slate-200 */
    color: #1e293b !important;
}
.ongoing-mode-switch .ongoing-mode-btn.active,
.ongoing-mode-switch .ongoing-mode-btn.btn-primary {
    background-color: #2563eb !important; /* bg-blue-600 */
    color: #ffffff !important; /* text-white */
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; /* shadow-sm */
}
[data-bs-theme="dark"] .ongoing-mode-switch .ongoing-mode-btn,
body.dark-mode .ongoing-mode-switch .ongoing-mode-btn,
body.theme-dark .ongoing-mode-switch .ongoing-mode-btn {
    background-color: #1e293b !important;
    color: #94a3b8 !important;
}
[data-bs-theme="dark"] .ongoing-mode-switch .ongoing-mode-btn:hover,
body.dark-mode .ongoing-mode-switch .ongoing-mode-btn:hover,
body.theme-dark .ongoing-mode-switch .ongoing-mode-btn:hover {
    background-color: #334155 !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] .ongoing-mode-switch .ongoing-mode-btn.active,
body.dark-mode .ongoing-mode-switch .ongoing-mode-btn.active,
body.theme-dark .ongoing-mode-switch .ongoing-mode-btn.active {
    background-color: #2563eb !important;
    color: #ffffff !important;
}
.ongoing-download-button {
    padding: 0.625rem !important;
    width: 42px !important;
    height: 42px !important;
    min-width: 42px !important;
    min-height: 42px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 0.5rem !important;
    background-color: #059669 !important; /* emerald-600 */
    border-color: #059669 !important;
    color: #ffffff !important;
    transition: all 0.15s ease-in-out !important;
}
.ongoing-download-button:hover,
.ongoing-download-button:focus {
    background-color: #047857 !important; /* emerald-700 */
    border-color: #047857 !important;
    color: #ffffff !important;
}
</style>
@endonce

<div class="d-flex justify-content-between align-items-center w-100 mb-4 gap-3 flex-wrap ongoing-filter-header-bar">
    <!-- Left Side: Quick Filter Buttons -->
    <div class="learning-mode-switch ongoing-mode-switch d-flex align-items-center gap-2 flex-wrap" role="group" aria-label="Learning On Going mode">
        <button type="button"
                class="btn learning-mode-btn ongoing-mode-btn px-4 py-2.5 rounded-pill rounded-full font-medium text-sm d-inline-flex align-items-center gap-2 whitespace-nowrap text-nowrap transition-all border-0 {{ $currentMode === 'content' ? 'active bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                data-mode="content"
                title="Content"
                aria-label="Content"
                aria-pressed="{{ $currentMode === 'content' ? 'true' : 'false' }}">
            <i class="ti ti-file-text bi bi-file-earmark-text" aria-hidden="true"></i>
            <span>Content</span>
        </button>
        <button type="button"
                class="btn learning-mode-btn ongoing-mode-btn px-4 py-2.5 rounded-pill rounded-full font-medium text-sm d-inline-flex align-items-center gap-2 whitespace-nowrap text-nowrap transition-all border-0 {{ $currentMode === 'playlist' ? 'active bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                data-mode="playlist"
                title="Playlist"
                aria-label="Playlist"
                aria-pressed="{{ $currentMode === 'playlist' ? 'true' : 'false' }}">
            <i class="ti ti-list-details bi bi-list-task" aria-hidden="true"></i>
            <span>Playlist</span>
        </button>
        <button type="button"
                class="btn learning-mode-btn ongoing-mode-btn px-4 py-2.5 rounded-pill rounded-full font-medium text-sm d-inline-flex align-items-center gap-2 whitespace-nowrap text-nowrap transition-all border-0 {{ $currentMode === 'mooc' ? 'active bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                data-mode="mooc"
                title="MOOC"
                aria-label="MOOC"
                aria-pressed="{{ $currentMode === 'mooc' ? 'true' : 'false' }}">
            <i class="ti ti-world bi bi-globe" aria-hidden="true"></i>
            <span>MOOC</span>
        </button>
        <button type="button"
                class="btn learning-mode-btn ongoing-mode-btn px-4 py-2.5 rounded-pill rounded-full font-medium text-sm d-inline-flex align-items-center gap-2 whitespace-nowrap text-nowrap transition-all border-0 {{ $currentMode === 'non-kp-owl' ? 'active bg-blue-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                data-mode="non-kp-owl"
                title="Non KP OWL"
                aria-label="Non KP OWL"
                aria-pressed="{{ $currentMode === 'non-kp-owl' ? 'true' : 'false' }}">
            <i class="ti ti-certificate bi bi-award" aria-hidden="true"></i>
            <span>Non KP OWL</span>
        </button>
    </div>

    <!-- Right Side: Download Action Button (Only for tabs that retain export like Non KP OWL) -->
    @if ($canExport)
        <div class="d-flex align-items-center ms-auto">
            <x-learning.ongoing-export :mode="$currentMode" />
        </div>
    @endif
</div>
