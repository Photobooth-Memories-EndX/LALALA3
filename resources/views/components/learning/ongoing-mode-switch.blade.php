@props([
    // Mode aktif Learning On Going: 'content' | 'playlist'.
    'mode' => 'content',
])

{{-- Dua mode independen — klik ditangani via AJAX (dashboard-script.blade.php). --}}
<div class="btn-group learning-mode-switch mb-3" role="group" aria-label="Learning On Going mode">
    <button type="button"
            class="btn rounded-start-3 ongoing-mode-btn {{ $mode === 'content' ? 'btn-primary' : 'btn-outline-primary' }}"
            data-mode="content">
        <i class="ti ti-file-description me-1"></i>
        Content
    </button>
    <button type="button"
            class="btn rounded-end-3 ongoing-mode-btn {{ $mode === 'playlist' ? 'btn-primary' : 'btn-outline-primary' }}"
            data-mode="playlist">
        <i class="ti ti-playlist me-1"></i>
        Playlist
    </button>
</div>
