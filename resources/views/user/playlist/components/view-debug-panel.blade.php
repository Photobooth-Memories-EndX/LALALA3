<section class="card border border-danger-subtle shadow-sm rounded-4 mb-3 bg-light"
         aria-labelledby="playlistDebugPanelTitle">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 bg-transparent">
        <div>
            <h3 class="card-title mb-1" id="playlistDebugPanelTitle">
                <i class="ti ti-bug me-2 text-danger" aria-hidden="true"></i>
                Developer Debug Panel
            </h3>
            <div class="small text-muted">
                Placeholder controls for authorized development and QA users.
            </div>
        </div>

        <span class="badge bg-danger text-white">
            DEBUG ONLY
        </span>
    </div>

    <div class="card-body p-0">
        @php
            $debugSections = [
                [
                    'title' => 'Learning Progress',
                    'icon' => 'ti-progress-check',
                    'status' => 'Current Status',
                    'statusClass' => 'bg-blue-lt text-blue',
                    'actions' => ['Force Pass', 'Force Fail'],
                ],
                [
                    'title' => 'Assessment Content',
                    'icon' => 'ti-clipboard-data',
                    'status' => 'Current Status',
                    'statusClass' => 'bg-blue-lt text-blue',
                    'actions' => ['Force Available', 'Force Not Available'],
                ],
                [
                    'title' => 'Playlist Assessment',
                    'icon' => 'ti-clipboard-check',
                    'status' => 'Current Status',
                    'statusClass' => 'bg-blue-lt text-blue',
                    'actions' => ['Force Pass', 'Force Fail'],
                ],
                [
                    'title' => 'My Task',
                    'icon' => 'ti-list-check',
                    'status' => 'Coming Soon',
                    'statusClass' => 'bg-secondary-lt text-secondary',
                    'actions' => ['Unavailable'],
                ],
                [
                    'title' => 'Certificate',
                    'icon' => 'ti-certificate',
                    'status' => 'Current Status',
                    'statusClass' => 'bg-blue-lt text-blue',
                    'actions' => ['Force Eligible', 'Force Not Eligible'],
                ],
            ];
        @endphp

        @foreach ($debugSections as $section)
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 px-3 px-md-4 py-3 @unless($loop->last) border-bottom @endunless">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar avatar-sm rounded bg-white text-secondary border" aria-hidden="true">
                        <i class="ti {{ $section['icon'] }}"></i>
                    </span>

                    <div>
                        <div class="fw-semibold">{{ $section['title'] }}</div>
                        <span class="badge {{ $section['statusClass'] }} mt-1">
                            {{ $section['status'] }}
                        </span>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @foreach ($section['actions'] as $action)
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                disabled
                                aria-disabled="true"
                                title="Placeholder only">
                            {{ $action }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
