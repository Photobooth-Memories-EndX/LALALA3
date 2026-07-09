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

        <form method="GET" action="{{ route('assesment.index') }}" id="assessmentFilterForm">

            <div class="row align-items-end">

                <div class="col-md-2 mb-2">
                    <label class="form-label small text-muted mb-1">
                        Show
                    </label>

                    <select name="entries" class="form-control form-control-sm">
                        <option value="10" {{ request('entries', 15) == 10 ? 'selected' : '' }}>10</option>
                        <option value="15" {{ request('entries', 15) == 15 ? 'selected' : '' }}>15</option>
                        <option value="20" {{ request('entries', 15) == 20 ? 'selected' : '' }}>20</option>
                        <option value="25" {{ request('entries', 15) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('entries', 15) == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label class="form-label small text-muted mb-1">
                        Type
                    </label>

                    <select name="type" class="form-control form-control-sm">
                        <option value="">All Type</option>

                        @foreach ($types as $type)
                            <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                {{ ucwords($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                    <div class="col-md-2 mb-2">

                        <label class="form-label small text-muted mb-1">
                            Questions
                        </label>

                        <select name="questions" class="form-control form-control-sm">

                            <option value="">
                                All Questions
                            </option>

                            @foreach ($questionFilters as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    {{ request('questions') == $value ? 'selected' : '' }}>

                                    {{ $label }}

                                </option>
                            @endforeach

                        </select>

                    </div>

                <div class="col-md-2 mb-2">
                    <label class="form-label small text-muted mb-1">
                        Timer
                    </label>

                    <select name="timer" class="form-control form-control-sm">
                        <option value="">All Timer</option>

                        @foreach ($timers as $timer)
                            <option value="{{ $timer }}" {{ request('timer') == $timer ? 'selected' : '' }}>
                                {{ $timer == 'y' ? 'With Timer' : 'No Timer' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label class="form-label small text-muted mb-1">
                        Status
                    </label>

                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>

                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ $status == 'OK' ? 'Active' : 'Inactive' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label class="form-label small text-muted mb-1">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        value="{{ request('search') }}"
                        placeholder="Search title/code...">
                </div>

            </div>

            <div class="row mt-2">
                <div class="col-md-12 text-right">

                    <button type="submit" class="btn btn-sm btn-primary owl-action-btn">
                        <i class="fas fa-filter mr-1"></i>
                        Filter
                    </button>

                    <a href="{{ route('assesment.index') }}" class="btn btn-sm btn-light owl-action-btn">
                        <i class="fas fa-undo mr-1"></i>
                        Reset
                    </a>

                </div>
            </div>

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
