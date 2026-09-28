@props([
    // Mode aktif Learning On Going: 'content' | 'playlist' | 'mooc'.
    'mode' => 'content',
])

{{-- Dua mode independen — klik ditangani via AJAX (dashboard-script.blade.php). --}}
<div class="learning-mode-switch ongoing-mode-switch" role="group" aria-label="Learning On Going mode">
    <button type="button"
            class="btn learning-mode-btn ongoing-mode-btn {{ $mode === 'content' ? 'btn-primary active' : 'btn-outline-primary' }}"
            data-mode="content"
            title="Content"
            aria-label="Content"
            aria-pressed="{{ $mode === 'content' ? 'true' : 'false' }}">
        <i class="ti ti-file-text" aria-hidden="true"></i>
        <span>Content</span>
    </button>
    <button type="button"
            class="btn learning-mode-btn ongoing-mode-btn {{ $mode === 'playlist' ? 'btn-primary active' : 'btn-outline-primary' }}"
            data-mode="playlist"
            title="Playlist"
            aria-label="Playlist"
            aria-pressed="{{ $mode === 'playlist' ? 'true' : 'false' }}">
        <i class="ti ti-list-details" aria-hidden="true"></i>
        <span>Playlist</span>
    </button>
    <button type="button"
            class="btn learning-mode-btn ongoing-mode-btn {{ $mode === 'mooc' ? 'btn-primary active' : 'btn-outline-primary' }}"
            data-mode="mooc"
            title="MOOC"
            aria-label="MOOC"
            aria-pressed="{{ $mode === 'mooc' ? 'true' : 'false' }}">
        <i class="ti ti-world" aria-hidden="true"></i>
        <span>MOOC</span>
    </button>
</div>
