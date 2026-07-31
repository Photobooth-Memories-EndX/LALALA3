@props([
    // Mode aktif Learning On Going: 'content' | 'playlist'.
    'mode' => 'content',
])

{{-- Dua mode independen — klik ditangani via AJAX (dashboard-script.blade.php). --}}
<div class="ongoing-mode-switch" role="group" aria-label="Learning On Going mode">
    <button type="button"
            class="btn ongoing-mode-btn {{ $mode === 'content' ? 'btn-primary active' : 'btn-outline-primary' }}"
            data-mode="content"
            title="Content"
            aria-label="Content"
            aria-pressed="{{ $mode === 'content' ? 'true' : 'false' }}">
        <i class="ti ti-file-description" aria-hidden="true"></i>
        <span class="visually-hidden">Content</span>
    </button>
    <button type="button"
            class="btn ongoing-mode-btn {{ $mode === 'playlist' ? 'btn-primary active' : 'btn-outline-primary' }}"
            data-mode="playlist"
            title="Playlist"
            aria-label="Playlist"
            aria-pressed="{{ $mode === 'playlist' ? 'true' : 'false' }}">
        <i class="ti ti-playlist" aria-hidden="true"></i>
        <span class="visually-hidden">Playlist</span>
    </button>
</div>
