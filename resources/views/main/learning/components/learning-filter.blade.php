@php
    $currentStatus = request('status', 'all');
    $currentYear = request('year', 'all');
    $currentEntries = (int) ($entries ?? 25);
    $currentSearch = $search ?? '';
    $routeSlug = request()->segment(4);
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form id="learning-filter-form" action="{{ url()->current() }}" method="GET">
            <input type="hidden" id="classification-slug" value="{{ request()->routeIs('learning.manage.category') ? $routeSlug : '' }}">
            <input type="hidden" id="content-slug" value="{{ request()->routeIs('learning.manage.content') ? $routeSlug : '' }}">
            <input type="hidden" name="downloadslug" value="{{ $downloadslug ?? '' }}">

            <div class="row g-3 align-items-end">
                <div class="col-md-2 col-lg-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="learn-status" name="status">
                        <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All</option>
                        <option value="mandatory" {{ $currentStatus === 'mandatory' ? 'selected' : '' }}>Mandatory</option>
                        <option value="optional" {{ $currentStatus === 'optional' ? 'selected' : '' }}>Optional</option>
                    </select>
                </div>

                <div class="col-md-2 col-lg-2">
                    <label class="form-label">Year</label>
                    <select class="form-select" id="learn-year" name="year">
                        <option value="all" {{ $currentYear === 'all' ? 'selected' : '' }}>All</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" {{ (string) $currentYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-lg-2">
                    <label class="form-label">Entries</label>
                    <select class="form-select" id="learn-entries" name="entries">
                        @foreach ([10, 25, 50, 100] as $option)
                            <option value="{{ $option }}" {{ $currentEntries === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-lg-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="learn-search" name="search" value="{{ $currentSearch }}" placeholder="Search employee, title, or classification">
                </div>

                <div class="col-md-3 col-lg-3 d-flex flex-wrap gap-2 justify-content-md-end">
                    <button type="button" id="btn-apply-filter" class="btn btn-primary">
                        <i class="ti ti-adjustments-horizontal me-1"></i>
                        Apply Filter
                    </button>
                    <button type="button" id="btn-reset-filter" class="btn btn-outline-secondary">
                        <i class="ti ti-filter-x me-1"></i>
                        Reset
                    </button>
                    <button type="submit" name="action" value="download" formtarget="_blank" class="btn btn-success">
                        <i class="ti ti-download me-1"></i>
                        Download
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>