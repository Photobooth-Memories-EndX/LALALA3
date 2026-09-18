@php
    $trainingplan = $trainingplan ?? null;
    $stepNumber   = $stepNumber ?? 1;
    $isLocked     = $isLocked ?? ($trainingplan && $trainingplan->active === 'Active');
@endphp

<div class="card owl-main-card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">{{ $stepNumber }}</span>
            <h3 class="card-title mb-0">Publish Status</h3>
        </div>
        <div>
            @if ($isLocked)
                <span class="badge bg-success-lt">Active</span>
            @else
                <span class="badge bg-warning-lt">{{ $trainingplan->active ?? 'Draft' }}</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if ($trainingplan)
            @if ($isLocked)
                <div class="alert alert-warning d-flex align-items-start mb-3" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon me-2 flex-shrink-0" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 9v4" />
                        <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z" />
                        <path d="M12 16h.01" />
                    </svg>
                    <div>
                        <h4 class="alert-title mb-1">Playlist is Currently Published</h4>
                        <div class="text-muted">
                            This playlist is currently published and accessible to learners. To modify protected details such as the title, external link, thumbnail, duration, deadline, or assigned materials, you must first unpublish this playlist. Once unpublished, you can safely make changes and publish it again.
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" form="trainingPlanForm" class="btn btn-warning" name="action" value="unpublish">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                            <path d="M9 12h6"></path>
                        </svg>
                        Unpublish This Playlist
                    </button>
                </div>
            @else
                <div class="alert alert-info d-flex align-items-start mb-0" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon me-2 flex-shrink-0" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                        <path d="M12 9h.01" />
                        <path d="M11 12h1v4h1" />
                    </svg>
                    <div>
                        <h4 class="alert-title mb-1">Playlist is Currently Unpublished (Draft Mode)</h4>
                        <div class="text-muted">
                            This playlist is currently in draft/unpublished mode. You can safely make changes to all fields below and publish it when ready.
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
