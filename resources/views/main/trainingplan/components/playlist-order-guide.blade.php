@php
    $trainingplan = $trainingplan ?? null;
    $isLocked     = false;
@endphp

<div class="card owl-main-card">
    <div class="card-header py-2 px-3">
        <h3 class="card-title fs-4 fw-bold">How to Reorder</h3>
    </div>
    <div class="card-body p-3">
        <ol class="text-secondary small mb-3 ps-3 lh-lg">
            <li>Click and hold the drag handle (<i class="ti ti-grip-vertical text-muted"></i>) on any material item.</li>
            <li>Drag the material item up or down to your desired playback sequence.</li>
            <li>Review the updated sequence numbers on the circular badges.</li>
            <li>Click <strong>Save Order</strong> to persist the sequence.</li>
        </ol>

        <div class="alert alert-info py-2 px-3 d-flex align-items-start gap-2 mb-3">
            <i class="ti ti-info-circle fs-3 text-info flex-shrink-0 mt-0.5"></i>
            <div class="small text-secondary">
                Reordering materials determines the exact course sequence for learners. Total duration and learning hours remain unchanged.
            </div>
        </div>
    </div>
    <div class="card-footer bg-light p-2 text-center border-top">
        <a href="{{ route('playlist.edit', $trainingplan->slug) }}" class="btn btn-outline-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1">
            <i class="ti ti-edit"></i>
            <span>Edit Playlist Details</span>
        </a>
    </div>
</div>