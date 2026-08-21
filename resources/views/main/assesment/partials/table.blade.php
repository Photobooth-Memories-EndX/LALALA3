<div class="card owl-main-card">

    <div class="card-header bg-white border-0">
        <div>
            <h3 class="card-title mb-1">
                Assessment List
            </h3>

            <div class="text-muted small">
                View all assessments available in OWL LMS.
            </div>
        </div>
    </div>

    <div class="card-body border-top border-bottom py-3">
        @php
            $advancedCount = collect([
                request('questions') !== null && request('questions') !== '',
                request('passing_grade') !== null && request('passing_grade') !== '',
                filled(request('created_from')),
                filled(request('created_to')),
                filled(request('schedule_from')),
                filled(request('schedule_to')),
                request('sort') && !in_array(request('sort'), ['default', 'newest', '']),
                request('entries') && (int) request('entries') !== 15,
            ])->filter()->count();
        @endphp

        <form method="GET" action="{{ route('assesment.index') }}" id="assessmentFilterForm">
            <div class="assessment-fast-filter-section">
                <label class="form-label small text-muted mb-2 assessment-fast-filter-label" for="assessmentSearchInput">
                    Quick Filter
                </label>

                <div class="assessment-filter-toolbar">
                    <div class="assessment-filter-field assessment-filter-field-type">
                        <select name="type" id="assessmentQuickType" class="form-select form-control assessment-filter-control" aria-label="Type">
                            <option value="">All Type</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                    {{ $type === 'trainingplan' ? 'Playlist' : ucwords($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="assessment-filter-field assessment-filter-field-status">
                        <select name="status" id="assessmentQuickStatus" class="form-select form-control assessment-filter-control" aria-label="Status">
                            <option value="">All Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                    {{ $status == 'OK' ? 'Active' : 'Inactive' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="assessment-filter-field assessment-filter-field-timer">
                        <select name="timer" id="assessmentQuickTimer" class="form-select form-control assessment-filter-control" aria-label="Timer">
                            <option value="">All Timer</option>
                            @foreach ($timers as $timer)
                                <option value="{{ $timer }}" {{ request('timer') == $timer ? 'selected' : '' }}>
                                    {{ $timer == 'y' ? 'With Timer' : 'No Timer' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="assessment-filter-search">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search"></i>
                            </span>
                            <input type="search"
                                   name="search"
                                   id="assessmentSearchInput"
                                   class="form-control assessment-search-control"
                                   value="{{ request('search') }}"
                                   placeholder="Search assessment..."
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="assessment-filter-actions">
                        <button type="submit"
                                class="btn btn-primary assessment-filter-action-btn"
                                id="assessmentSearchAction"
                                title="Search / Apply Filter"
                                aria-label="Search">
                            <i class="ti ti-search"></i>
                        </button>

                        <button type="button"
                                class="btn btn-outline-primary assessment-filter-action-btn position-relative"
                                id="assessmentMoreFiltersBtn"
                                data-bs-toggle="modal" data-bs-target="#assessmentMoreFiltersModal"
                                data-toggle="modal" data-target="#assessmentMoreFiltersModal"
                                title="More Filters"
                                aria-label="More Filters">
                            <i class="ti ti-adjustments-horizontal"></i>
                            <span class="badge bg-primary text-white assessment-filter-count {{ $advancedCount > 0 ? '' : 'd-none' }}" id="assessmentMoreFiltersCount">
                                {{ $advancedCount > 0 ? $advancedCount : '' }}
                            </span>
                        </button>

                        <a href="{{ route('assesment.index') }}"
                           class="btn btn-outline-primary assessment-filter-action-btn {{ ($advancedCount > 0 || request('type') || request('status') || request('timer') || request('search')) ? 'is-active' : '' }}"
                           id="assessmentResetFilter"
                           title="Reset Filter"
                           aria-label="Reset Filter">
                            <i class="ti ti-refresh"></i>
                        </a>
                    </div>
                </div>
            </div>

            @include('main.assesment.partials.modal-filter')
        </form>
    </div>

    <div class="table-responsive">
        @unless ($assesment->count() == 0)

            <table class="table table-hover table-vcenter owl-table mb-0" id="MyTable">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th>Assessment</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Questions</th>
                        <th class="text-center">PG</th>
                        <th>Schedule</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Assign</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($assesment as $list)
                        @include('main.assesment.partials.table-row', [
                            'list' => $list,
                            'iteration' => $assesment->firstItem() + $loop->index
                        ])
                    @endforeach
                </tbody>
            </table>

        @else

            @include('main.assesment.partials.empty')

        @endunless
    </div>

    @if ($assesment->count() > 0)
        <div class="card-footer bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                <div class="text-muted small mb-2 mb-md-0">
                    Showing
                    <strong>{{ $assesment->firstItem() }}</strong>
                    to
                    <strong>{{ $assesment->lastItem() }}</strong>
                    of
                    <strong>{{ $assesment->total() }}</strong>
                    assessments
                </div>

                <div>
                    {{ $assesment->links() }}
                </div>

            </div>
        </div>
    @endif

</div>
