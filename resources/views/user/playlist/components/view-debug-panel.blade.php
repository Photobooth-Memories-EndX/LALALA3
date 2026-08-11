<section class="card border border-danger shadow-sm rounded-4 mb-3 bg-light"
         aria-labelledby="playlistDebugPanelTitle">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 bg-transparent">
        <div>
            <h3 class="card-title mb-1" id="playlistDebugPanelTitle">
                <i class="ti ti-bug me-2 text-danger" aria-hidden="true"></i>
                Developer Debug Panel
            </h3>
            <div class="small text-muted">
                Session-only UI overrides for authorized development and QA users.
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if ($playlistDebugActive)
                <span class="badge bg-warning text-dark">
                    <i class="ti ti-alert-triangle me-1" aria-hidden="true"></i>
                    OVERRIDE ACTIVE
                </span>
            @endif

            <span class="badge bg-danger text-white">
                DEBUG ONLY
            </span>
        </div>
    </div>

    <div class="card-body p-0">
        @php
            $debugSections = [
                [
                    'title' => 'Learning Progress',
                    'icon' => 'ti-progress-check',
                    'key' => 'learning_progress',
                    'status' => number_format($playlistUiState['learning_progress'], 0) . '%',
                    'statusClass' => 'bg-blue-lt text-blue',
                    'actions' => [
                        ['label' => 'Force 100%', 'value' => 'pass', 'activeClass' => 'btn-success'],
                        ['label' => 'Force 0%', 'value' => 'fail', 'activeClass' => 'btn-danger'],
                    ],
                ],
                [
                    'title' => 'Assessment Content',
                    'icon' => 'ti-clipboard-data',
                    'key' => 'content_assessment',
                    'status' => $playlistUiState['content_assessment_available']
                        ? 'Available'
                        : 'Not Available',
                    'statusClass' => 'bg-blue-lt text-blue',
                    'actions' => [
                        ['label' => 'Available', 'value' => 'available', 'activeClass' => 'btn-success'],
                        ['label' => 'Not Available', 'value' => 'not_available', 'activeClass' => 'btn-danger'],
                    ],
                ],
                [
                    'title' => 'Playlist Assessment',
                    'icon' => 'ti-clipboard-check',
                    'key' => 'playlist_assessment',
                    'status' => $playlistUiState['playlist_assessment_available']
                        ? 'Available'
                        : 'Not Available',
                    'statusClass' => 'bg-blue-lt text-blue',
                    'actions' => [
                        ['label' => 'Available', 'value' => 'available', 'activeClass' => 'btn-success'],
                        ['label' => 'Not Available', 'value' => 'not_available', 'activeClass' => 'btn-danger'],
                    ],
                ],
                [
                    'title' => 'My Task',
                    'icon' => 'ti-list-check',
                    'key' => null,
                    'status' => 'Coming Soon',
                    'statusClass' => 'bg-secondary-lt text-secondary',
                    'actions' => [],
                ],
                [
                    'title' => 'Certificate',
                    'icon' => 'ti-certificate',
                    'key' => 'certificate',
                    'status' => $playlistUiState['certificate_available']
                        ? 'Available'
                        : 'Not Available',
                    'statusClass' => 'bg-blue-lt text-blue',
                    'actions' => [
                        ['label' => 'Available', 'value' => 'available', 'activeClass' => 'btn-success'],
                        ['label' => 'Not Available', 'value' => 'not_available', 'activeClass' => 'btn-danger'],
                    ],
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
                    @forelse ($section['actions'] as $action)
                        @php
                            $isSelected = ($playlistDebugState[$section['key']] ?? null)
                                === $action['value'];
                        @endphp

                        <form method="POST"
                              action="{{ route('playlist.debug.update', $playlist) }}"
                              class="m-0">
                            @csrf
                            <input type="hidden" name="key" value="{{ $section['key'] }}">
                            <input type="hidden" name="value" value="{{ $action['value'] }}">

                            <button type="submit"
                                    class="btn btn-sm {{ $isSelected ? $action['activeClass'] : 'btn-outline-secondary' }}"
                                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
                                {{ $action['label'] }}
                            </button>
                        </form>
                    @empty
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                disabled
                                aria-disabled="true">
                            Coming Soon
                        </button>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div class="card-footer bg-transparent">
        <form method="POST"
              action="{{ route('playlist.debug.reset', $playlist) }}"
              class="m-0">
            @csrf
            @method('DELETE')

            <button type="submit"
                    class="btn btn-outline-danger w-100"
                    @disabled(!$playlistDebugActive)>
                <i class="ti ti-refresh me-2" aria-hidden="true"></i>
                Reset Debug State
            </button>
        </form>
    </div>
</section>
