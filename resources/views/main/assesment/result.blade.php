@extends('layouts.sidebar-master')

@section('content')
    @include('main.assesment.partials.style')

    @include('main.assesment.partials.subpage-header', [
        'breadcrumb' => ['Result'],
        'title'      => 'Assessment Result',
    ])

    <div class="page-body">
        <div class="container-xl">

            <x-alert type="success" />
            <x-alert type="warning" />
            <x-alert type="danger" />

            <div class="card owl-main-card">
                <div class="card-header bg-white border-0">
                    <div>
                        <h3 class="card-title fw-bold mb-1">
                            <i class="fas fa-clipboard-check me-2 text-primary"></i>
                            Assessment Result
                        </h3>

                        <div class="text-muted small">
                            {{ $assesment->title }}
                        </div>
                    </div>
                </div>

                <div class="card-body border-top border-bottom py-3">
                    @php
                        $advancedCount = collect([
                            request('batch') !== null && request('batch') !== '',
                            request('result') !== null && request('result') !== '',
                            request('team') !== null && request('team') !== '',
                            request('department') !== null && request('department') !== '',
                            request('year') !== null && request('year') !== '',
                            request('month') !== null && request('month') !== '',
                            request('score') !== null && request('score') !== '',
                            request('min_score') !== null && request('min_score') !== '',
                            request('max_score') !== null && request('max_score') !== '',
                            filled(request('start_date_from')),
                            filled(request('start_date_to')),
                            filled(request('finish_date_from')),
                            filled(request('finish_date_to')),
                            request('show_entries') !== null && request('show_entries') !== '' && (int) request('show_entries') !== 25,
                        ])->filter()->count();

                        $isFilterActive = $advancedCount > 0 || (request('search_user') !== null && request('search_user') !== '');
                    @endphp

                    {{-- Form Download Assessment Result --}}
                    <form name="form1" id="form1" method="POST" action="{{ route('assesment.result.download') }}" class="d-none">
                        @csrf
                        <input type="hidden" name="assessment_id" id="assessment_id" value="{{ $assesment->id }}">
                        <input type="hidden" name="slug" id="slug" value="{{ $assesment->slug }}">
                    </form>

                    <form method="GET" action="{{ route('assesment.result', $assesment) }}" id="resultFilterForm">
                        {{-- Hidden inputs for preserving Modal / Advanced Filters --}}
                        <input type="hidden" name="batch" id="resultQuickBatch" value="{{ request('batch') }}">
                        <input type="hidden" name="result" id="resultQuickResult" value="{{ request('result') }}">
                        <input type="hidden" name="score" id="resultQuickScore" value="{{ request('score') }}">
                        <input type="hidden" name="min_score" id="resultQuickMinScore" value="{{ request('min_score') }}">
                        <input type="hidden" name="max_score" id="resultQuickMaxScore" value="{{ request('max_score') }}">
                        <input type="hidden" name="start_date_from" id="resultQuickStartDateFrom" value="{{ request('start_date_from') }}">
                        <input type="hidden" name="start_date_to" id="resultQuickStartDateTo" value="{{ request('start_date_to') }}">
                        <input type="hidden" name="finish_date_from" id="resultQuickFinishDateFrom" value="{{ request('finish_date_from') }}">
                        <input type="hidden" name="finish_date_to" id="resultQuickFinishDateTo" value="{{ request('finish_date_to') }}">
                        <input type="hidden" name="show_entries" id="resultQuickEntries" value="{{ $entries }}">

                        <div class="assessment-fast-filter-section">
                            <label class="form-label small text-muted mb-2 assessment-fast-filter-label" for="resultSearchInput">
                                Quick Filter
                            </label>

                            <div class="assessment-filter-toolbar">
                                {{-- 1. All Team --}}
                                <div class="assessment-filter-field assessment-filter-field-team">
                                    <select name="team" id="resultQuickTeam" class="form-select form-control assessment-filter-control" aria-label="Team">
                                        <option value="">All Team</option>
                                        @foreach ($available_teams as $teamItem)
                                            <option value="{{ $teamItem }}" {{ request('team') === $teamItem ? 'selected' : '' }}>
                                                {{ $teamItem }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 2. All Department --}}
                                <div class="assessment-filter-field assessment-filter-field-department">
                                    <select name="department" id="resultQuickDepartment" class="form-select form-control assessment-filter-control" aria-label="Department">
                                        <option value="">All Department</option>
                                        @foreach ($available_departments as $deptItem)
                                            <option value="{{ $deptItem }}" {{ request('department') === $deptItem ? 'selected' : '' }}>
                                                {{ $deptItem }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 3. All Year --}}
                                <div class="assessment-filter-field assessment-filter-field-year">
                                    <select name="year" id="resultQuickYear" class="form-select form-control assessment-filter-control" aria-label="Year">
                                        <option value="">All Year</option>
                                        @foreach ($years as $yearItem)
                                            <option value="{{ $yearItem }}" {{ (string) request('year') === (string) $yearItem ? 'selected' : '' }}>
                                                {{ $yearItem }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 4. All Month --}}
                                <div class="assessment-filter-field assessment-filter-field-month">
                                    <select name="month" id="resultQuickMonth" class="form-select form-control assessment-filter-control" aria-label="Month">
                                        <option value="">All Month</option>
                                        @foreach ($months as $monthNumber => $monthName)
                                            <option value="{{ $monthNumber }}" {{ request('month') !== null && request('month') !== '' && (int) request('month') === (int) $monthNumber ? 'selected' : '' }}>
                                                {{ $monthName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 5. Search User --}}
                                <div class="assessment-filter-search">
                                    <label class="sr-only visually-hidden" for="resultSearchInput">Search user</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-search"></i>
                                        </span>
                                        <input type="search"
                                               name="search_user"
                                               id="resultSearchInput"
                                               class="form-control assessment-search-control"
                                               value="{{ request('search_user', $search ?? '') }}"
                                               placeholder="Search user..."
                                               autocomplete="off">
                                    </div>
                                </div>

                                {{-- 6. Actions (Search, Filter, Refresh, Download) --}}
                                <div class="assessment-filter-actions">
                                    {{-- 1. Search Button --}}
                                    <button type="submit"
                                            class="btn btn-primary assessment-filter-action-btn"
                                            id="resultSearchAction"
                                            title="Search / Apply Filter"
                                            aria-label="Search">
                                        <i class="ti ti-search"></i>
                                    </button>

                                    {{-- 2. Filter Modal Button --}}
                                    <button type="button"
                                            class="btn btn-outline-primary assessment-filter-action-btn position-relative"
                                            id="resultMoreFiltersBtn"
                                            data-bs-toggle="modal" data-bs-target="#resultMoreFiltersModal"
                                            data-toggle="modal" data-target="#resultMoreFiltersModal"
                                            title="More Filters"
                                            aria-label="More Filters">
                                        <i class="ti ti-adjustments-horizontal"></i>
                                        <span class="badge bg-primary text-white assessment-filter-count {{ $advancedCount > 0 ? '' : 'd-none' }}" id="resultMoreFiltersCount">
                                            {{ $advancedCount > 0 ? $advancedCount : '' }}
                                        </span>
                                    </button>

                                    {{-- 3. Reset Button --}}
                                    <a href="{{ route('assesment.result', $assesment) }}"
                                       class="btn btn-outline-primary assessment-filter-action-btn {{ $isFilterActive ? 'is-active' : '' }}"
                                       id="resultResetFilter"
                                       title="Refresh / Reset Filter"
                                       aria-label="Refresh / Reset Filter">
                                        <i class="ti ti-refresh"></i>
                                    </a>

                                    {{-- 4. Download Button --}}
                                    <button type="submit"
                                            form="form1"
                                            class="btn btn-success assessment-filter-action-btn"
                                            id="resultDownloadAction"
                                            title="Download"
                                            aria-label="Download assessment result">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Modal Filter Assessment Result --}}
                        <div class="modal fade" id="resultMoreFiltersModal" tabindex="-1" aria-labelledby="resultMoreFiltersModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered assessment-filter-modal-dialog">
                                <div class="modal-content rounded-4 border-0 shadow">
                                    <div class="modal-header border-0 pb-0">
                                        <h3 class="modal-title fw-bold" id="resultMoreFiltersModalLabel">
                                            <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                                            Filter Assessment Result
                                        </h3>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body pt-3 pb-2">
                                        {{-- 1. Batch --}}
                                        <div class="assessment-filter-group">
                                            <label class="form-label fw-semibold mb-1" for="modalFilterBatch">
                                                Batch
                                            </label>
                                            <select id="modalFilterBatch" class="form-select form-control rounded-3">
                                                <option value="">All Batch</option>
                                                @foreach ($available_batches as $batchItem)
                                                    <option value="{{ $batchItem }}" {{ (string) request('batch') === (string) $batchItem ? 'selected' : '' }}>
                                                        Batch {{ $batchItem }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <hr class="my-3">

                                        {{-- 2. Result --}}
                                        <div class="assessment-filter-group">
                                            <label class="form-label fw-semibold mb-1" for="modalFilterResult">
                                                Result
                                            </label>
                                            <select id="modalFilterResult" class="form-select form-control rounded-3">
                                                <option value="">All Result</option>
                                                <option value="pass" {{ strtolower(request('result', '')) === 'pass' ? 'selected' : '' }}>Pass</option>
                                                <option value="failed" {{ strtolower(request('result', '')) === 'failed' ? 'selected' : '' }}>Failed</option>
                                            </select>
                                        </div>

                                        <hr class="my-3">

                                        {{-- 3. Team --}}
                                        <div class="assessment-filter-group">
                                            <label class="form-label fw-semibold mb-1" for="modalFilterTeam">
                                                Team
                                            </label>
                                            <select id="modalFilterTeam" class="form-select form-control rounded-3">
                                                <option value="">All Team</option>
                                                @foreach ($available_teams as $teamItem)
                                                    <option value="{{ $teamItem }}" {{ request('team') === $teamItem ? 'selected' : '' }}>
                                                        {{ $teamItem }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <hr class="my-3">

                                        {{-- 4. Department --}}
                                        <div class="assessment-filter-group">
                                            <label class="form-label fw-semibold mb-1" for="modalFilterDepartment">
                                                Department
                                            </label>
                                            <select id="modalFilterDepartment" class="form-select form-control rounded-3">
                                                <option value="">All Department</option>
                                                @foreach ($available_departments as $deptItem)
                                                    <option value="{{ $deptItem }}" {{ request('department') === $deptItem ? 'selected' : '' }}>
                                                        {{ $deptItem }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <hr class="my-3">

                                        {{-- 5. Year & Month --}}
                                        <div class="assessment-filter-group">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold mb-1" for="modalFilterYear">Year</label>
                                                    <select id="modalFilterYear" class="form-select form-control rounded-3">
                                                        <option value="">All Year</option>
                                                        @foreach ($years as $yearItem)
                                                            <option value="{{ $yearItem }}" {{ (string) request('year') === (string) $yearItem ? 'selected' : '' }}>
                                                                {{ $yearItem }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold mb-1" for="modalFilterMonth">Month</label>
                                                    <select id="modalFilterMonth" class="form-select form-control rounded-3">
                                                        <option value="">All Month</option>
                                                        @foreach ($months as $monthNumber => $monthName)
                                                            <option value="{{ $monthNumber }}" {{ request('month') !== null && request('month') !== '' && (int) request('month') === (int) $monthNumber ? 'selected' : '' }}>
                                                                {{ $monthName }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-3">

                                        {{-- 6. Score --}}
                                        <div class="assessment-filter-group">
                                            <div class="fw-semibold mb-2">Score</div>
                                            <div class="row g-2 align-items-center mb-2">
                                                <div class="col-12">
                                                    <label class="form-label small text-muted mb-1" for="modalFilterScorePreset">Score Preset</label>
                                                    <select id="modalFilterScorePreset" class="form-select form-control rounded-3">
                                                        <option value="">All Score</option>
                                                        <option value="passing" {{ strtolower(request('score', '')) === 'passing' ? 'selected' : '' }}>Passing @if(!empty($assesment->passing_grade)) (>= {{ $assesment->passing_grade }}) @endif</option>
                                                        <option value="below_passing" {{ strtolower(request('score', '')) === 'below_passing' ? 'selected' : '' }}>Below Passing @if(!empty($assesment->passing_grade)) (< {{ $assesment->passing_grade }}) @endif</option>
                                                        <option value="90-100" {{ request('score') === '90-100' ? 'selected' : '' }}>90 - 100</option>
                                                        <option value="80-89" {{ request('score') === '80-89' ? 'selected' : '' }}>80 - 89</option>
                                                        <option value="70-79" {{ request('score') === '70-79' ? 'selected' : '' }}>70 - 79</option>
                                                        <option value="60-69" {{ request('score') === '60-69' ? 'selected' : '' }}>60 - 69</option>
                                                        <option value="0-59" {{ request('score') === '0-59' ? 'selected' : '' }}>0 - 59</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label small text-muted mb-1" for="modalFilterMinScore">Minimum Score</label>
                                                    <input type="number" id="modalFilterMinScore" class="form-control rounded-3" min="0" max="100" placeholder="0" value="{{ request('min_score') }}">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small text-muted mb-1" for="modalFilterMaxScore">Maximum Score</label>
                                                    <input type="number" id="modalFilterMaxScore" class="form-control rounded-3" min="0" max="100" placeholder="100" value="{{ request('max_score') }}">
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-3">

                                        {{-- 7. Start Date (Start Date Time) --}}
                                        <div class="assessment-filter-group">
                                            <div class="fw-semibold mb-2">Start Date (Start Date Time)</div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label small text-muted mb-1" for="modalFilterStartDateFrom">From Date</label>
                                                    <div class="input-icon">
                                                        <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                                        <input type="date" id="modalFilterStartDateFrom" class="form-control rounded-3" value="{{ request('start_date_from') }}">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small text-muted mb-1" for="modalFilterStartDateTo">To Date</label>
                                                    <div class="input-icon">
                                                        <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                                        <input type="date" id="modalFilterStartDateTo" class="form-control rounded-3" value="{{ request('start_date_to') }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-3">

                                        {{-- 8. Finish Date (Finish Date Time) --}}
                                        <div class="assessment-filter-group">
                                            <div class="fw-semibold mb-2">Finish Date (Finish Date Time)</div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label small text-muted mb-1" for="modalFilterFinishDateFrom">From Date</label>
                                                    <div class="input-icon">
                                                        <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                                        <input type="date" id="modalFilterFinishDateFrom" class="form-control rounded-3" value="{{ request('finish_date_from') }}">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small text-muted mb-1" for="modalFilterFinishDateTo">To Date</label>
                                                    <div class="input-icon">
                                                        <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                                        <input type="date" id="modalFilterFinishDateTo" class="form-control rounded-3" value="{{ request('finish_date_to') }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-3">

                                        {{-- 9. Search User --}}
                                        <div class="assessment-filter-group">
                                            <label class="form-label fw-semibold mb-1" for="modalFilterSearchUser">
                                                Search User
                                            </label>
                                            <div class="input-icon">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="search"
                                                       id="modalFilterSearchUser"
                                                       class="form-control rounded-3"
                                                       placeholder="Search user by name, ID, team, or department"
                                                       value="{{ request('search_user', $search ?? '') }}">
                                            </div>
                                        </div>

                                        <hr class="my-3">

                                        {{-- 10. Show Entries --}}
                                        <div class="assessment-filter-group">
                                            <label class="form-label fw-semibold mb-1" for="modalFilterEntries">
                                                Show Entries
                                            </label>
                                            <select id="modalFilterEntries" class="form-select form-control rounded-3">
                                                <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10 Entries</option>
                                                <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25 Entries</option>
                                                <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50 Entries</option>
                                                <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100 Entries</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-outline-secondary rounded-3" id="resultResetMoreFilters">
                                            <i class="ti ti-refresh me-1"></i>
                                            Reset
                                        </button>
                                        <button type="button" class="btn btn-primary rounded-3" id="resultApplyMoreFilters">
                                            <i class="ti ti-check me-1"></i>
                                            Apply Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    @unless ($assesment_result->count() == 0)
                        <table class="table table-hover table-vcenter owl-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">No</th>
                                    <th class="text-center" style="width: 90px;">ID</th>
                                    <th class="text-start">Name</th>
                                    <th class="text-start">Team</th>
                                    <th class="text-start">Department</th>
                                    <th class="text-center" style="width: 80px;">Score</th>
                                    <th class="text-center" style="width: 80px;">Batch</th>
                                    <th class="text-center" style="width: 100px;">Result</th>
                                    <th class="text-end" style="width: 150px;">Start Date Time</th>
                                    <th class="text-end" style="width: 150px;">Finish Date Time</th>
                                    <th class="text-center" style="width: 90px;">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($assesment_result as $list)
                                    <tr>
                                        <td class="text-center text-muted">
                                            {{ ++$no }}
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-secondary-lt text-secondary">
                                                {{ $list->user->id_emp ?? $list->id_emp }}
                                            </span>
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                {{ $list->user->name ?? $list->name }}
                                            </div>
                                        </td>

                                        <td>
                                            {{ $list->user->team ?? $list->team }}
                                        </td>

                                        <td>
                                            {{ $list->user->department ?? $list->department }}
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-blue-lt text-blue">
                                                {{ $list->score }}
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            {{ $list->batch }}
                                        </td>

                                        <td class="text-center">
                                            <a
                                                href="#"
                                                class="wrongcheck"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="click to check wrong answers"
                                                data-url="{{ route('assesment.wrong', ['slug' => $assesment->slug, 'id' => $list->id]) }}">

                                                @if ($list->pass)
                                                    <span class="badge bg-green-lt text-green">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Pass
                                                    </span>
                                                @else
                                                    <span class="badge bg-red-lt text-red">
                                                        <i class="fas fa-times-circle me-1"></i>
                                                        Failed
                                                    </span>
                                                @endif
                                            </a>
                                        </td>

                                        <td class="text-end text-muted">
                                            {{ $list->created_at ? date("d/m/y H:i:s", strtotime($list->created_at)) : '-' }}
                                        </td>

                                        <td class="text-end text-muted">
                                            {{ $list->updated_at ? date("d/m/y H:i:s", strtotime($list->updated_at)) : '-' }}
                                        </td>

                                        <td class="text-center">
                                            @if (auth()->user()->canAccessManageModule('assessment_management'))
                                                <button type="button"
                                                        class="btn btn-sm btn-danger owl-action-btn btn-delete-result"
                                                        data-url="{{ route('assesment.result.destroy', ['assesment' => $assesment->slug, 'id' => $list->id]) }}"
                                                        data-id="{{ $list->id }}"
                                                        data-bs-toggle="tooltip"
                                                        data-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Delete"
                                                        aria-label="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty py-5">
                            <div class="empty-icon">
                                <i class="fas fa-chart-bar fa-3x text-muted"></i>
                            </div>

                            <p class="empty-title">
                                No assessment score data found
                            </p>

                            <p class="empty-subtitle text-muted">
                                There is no user result data available for this assessment yet.
                            </p>
                        </div>
                    @endunless
                </div>

                @if ($assesment_result->count() > 0)
                    <div class="card-footer bg-white border-top">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                            <div class="text-muted small mb-2 mb-md-0">
                                Showing
                                <strong>{{ $assesment_result->firstItem() }}</strong>
                                to
                                <strong>{{ $assesment_result->lastItem() }}</strong>
                                of
                                <strong>{{ $assesment_result->total() }}</strong>
                                results
                            </div>

                            <div>
                                {{ $assesment_result->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Modal Check Wrong Answers --}}
    <div class="modal modal-blur fade" id="modal-answer" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header bg-primary-lt">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">
                            <i class="fas fa-list-check me-2 text-primary"></i>
                            Answer List <span id="amount-of-answer"></span>
                        </h5>

                        <div class="text-muted small">
                            Review correct answers and user answers.
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="d-none modal-blur" id="overlay">
                        <div class="text-center mb-3">
                            <div class="text-muted mb-3">
                                Getting data
                            </div>

                            <div class="progress">
                                <div class="progress-bar progress-bar-indeterminate"></div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover" style="width: 100%">
                            <thead>
                                <tr class="text-center">
                                    <th class="text-start">Question</th>
                                    <th>Correct Answer</th>
                                    <th>User Answer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody id="result">
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Reusable Delete Confirmation Modal --}}
    <x-shared.delete-confirmation-modal
        id="modal-delete-result"
        form-id="form-delete-result"
        title="Delete Assessment Result"
        message="Are you sure you want to delete this assessment result?"
        warning="Deleted data cannot be recovered."
        confirm-text="Delete"
        cancel-text="Cancel"
    />
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
            $('[data-toggle="tooltip"]').tooltip();

            // Quick filter dropdown changes
            $('#resultQuickTeam, #resultQuickDepartment, #resultQuickYear, #resultQuickMonth').on('change', function() {
                $('#resultFilterForm').submit();
            });

            // Enter key in search input
            $('#resultSearchInput').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#resultFilterForm').submit();
                }
            });

            // Sync modal values on show
            $('#resultMoreFiltersModal').on('show.bs.modal', function() {
                $('#modalFilterBatch').val($('#resultQuickBatch').val() || '');
                $('#modalFilterResult').val($('#resultQuickResult').val() || '');
                $('#modalFilterTeam').val($('#resultQuickTeam').val() || '');
                $('#modalFilterDepartment').val($('#resultQuickDepartment').val() || '');
                $('#modalFilterYear').val($('#resultQuickYear').val() || '');
                $('#modalFilterMonth').val($('#resultQuickMonth').val() || '');
                $('#modalFilterScorePreset').val($('#resultQuickScore').val() || '');
                $('#modalFilterMinScore').val($('#resultQuickMinScore').val() || '');
                $('#modalFilterMaxScore').val($('#resultQuickMaxScore').val() || '');
                $('#modalFilterStartDateFrom').val($('#resultQuickStartDateFrom').val() || '');
                $('#modalFilterStartDateTo').val($('#resultQuickStartDateTo').val() || '');
                $('#modalFilterFinishDateFrom').val($('#resultQuickFinishDateFrom').val() || '');
                $('#modalFilterFinishDateTo').val($('#resultQuickFinishDateTo').val() || '');
                $('#modalFilterSearchUser').val($('#resultSearchInput').val() || '');
                $('#modalFilterEntries').val($('#resultQuickEntries').val() || '25');
                updateResultAdvancedFilterBadge();
            });

            function updateResultAdvancedFilterBadge() {
                let batch = $('#modalFilterBatch').val();
                let result = $('#modalFilterResult').val();
                let team = $('#modalFilterTeam').val();
                let dept = $('#modalFilterDepartment').val();
                let year = $('#modalFilterYear').val();
                let month = $('#modalFilterMonth').val();
                let score = $('#modalFilterScorePreset').val();
                let minScore = $('#modalFilterMinScore').val();
                let maxScore = $('#modalFilterMaxScore').val();
                let startFrom = $('#modalFilterStartDateFrom').val();
                let startTo = $('#modalFilterStartDateTo').val();
                let finishFrom = $('#modalFilterFinishDateFrom').val();
                let finishTo = $('#modalFilterFinishDateTo').val();
                let entries = $('#modalFilterEntries').val();

                let count = 0;
                if (batch && batch !== '') count++;
                if (result && result !== '') count++;
                if (team && team !== '') count++;
                if (dept && dept !== '') count++;
                if (year && year !== '') count++;
                if (month && month !== '') count++;
                if (score && score !== '') count++;
                if (minScore && minScore !== '') count++;
                if (maxScore && maxScore !== '') count++;
                if (startFrom && startFrom !== '') count++;
                if (startTo && startTo !== '') count++;
                if (finishFrom && finishFrom !== '') count++;
                if (finishTo && finishTo !== '') count++;
                if (entries && entries != 25) count++;

                let $badge = $('#resultMoreFiltersCount');
                if (count > 0) {
                    $badge.text(count).removeClass('d-none');
                } else {
                    $badge.text('').addClass('d-none');
                }
            }

            $('#modalFilterBatch, #modalFilterResult, #modalFilterTeam, #modalFilterDepartment, #modalFilterYear, #modalFilterMonth, #modalFilterScorePreset, #modalFilterMinScore, #modalFilterMaxScore, #modalFilterStartDateFrom, #modalFilterStartDateTo, #modalFilterFinishDateFrom, #modalFilterFinishDateTo, #modalFilterEntries').on('change input', function() {
                updateResultAdvancedFilterBadge();
            });

            // Modal Apply Filter
            $('#resultApplyMoreFilters').on('click', function(e) {
                e.preventDefault();
                $('#resultQuickBatch').val($('#modalFilterBatch').val());
                $('#resultQuickResult').val($('#modalFilterResult').val());
                $('#resultQuickTeam').val($('#modalFilterTeam').val());
                $('#resultQuickDepartment').val($('#modalFilterDepartment').val());
                $('#resultQuickYear').val($('#modalFilterYear').val());
                $('#resultQuickMonth').val($('#modalFilterMonth').val());
                $('#resultQuickScore').val($('#modalFilterScorePreset').val());
                $('#resultQuickMinScore').val($('#modalFilterMinScore').val());
                $('#resultQuickMaxScore').val($('#modalFilterMaxScore').val());
                $('#resultQuickStartDateFrom').val($('#modalFilterStartDateFrom').val());
                $('#resultQuickStartDateTo').val($('#modalFilterStartDateTo').val());
                $('#resultQuickFinishDateFrom').val($('#modalFilterFinishDateFrom').val());
                $('#resultQuickFinishDateTo').val($('#modalFilterFinishDateTo').val());
                $('#resultSearchInput').val($('#modalFilterSearchUser').val());
                $('#resultQuickEntries').val($('#modalFilterEntries').val());
                $('#resultFilterForm').submit();
            });

            // Modal Reset Filter
            $('#resultResetMoreFilters').on('click', function(e) {
                e.preventDefault();
                window.location.href = "{{ route('assesment.result', $assesment) }}";
            });

            // Download Action Click Handler
            $('#resultDownloadAction').on('click', function(e) {
                e.preventDefault();
                $('#form1').submit();
            });

            // Modal wrong answers
            $(document).on('click', '.wrongcheck', function(e) {
                e.preventDefault();

                $("#overlay").removeClass('d-none');
                $('#modal-answer').modal('show');

                var col = '';

                $.ajax({
                    type: "GET",
                    url: $(this).data('url'),
                    dataType: "json",
                    success: function(response) {
                        if (response.status == 404) {
                            alert(response.message);
                            $('#modal-answer').modal('hide');
                        } else {
                            $('#amount-of-answer').html(response.amountOfAnswer);

                            $.each(response.wrong_answer, function(i, item) {
                                let badge = '';

                                if (item.answer == item.user_answer) {
                                    badge = '<span class="badge bg-success">Correct</span>';
                                } else {
                                    badge = '<span class="badge bg-danger">Wrong</span>';
                                }

                                col = col + '<tr><td class="p-2">' + item.question + '</td><td class="p-2">' + item.answer + '</td><td class="p-2">' + item.user_answer + '</td><td class="text-center p-2">' + badge + '</td></tr>';
                            });

                            $('#result').html(col);

                            $("#overlay").addClass('d-none');
                        }
                    }
                });
            });

            // Delete assessment result confirmation modal handler
            $(document).on('click', '.btn-delete-result', function(e) {
                e.preventDefault();
                var url = $(this).data('url');
                $('#form-delete-result').attr('action', url);
                $('#modal-delete-result').modal('show');
            });
        });
    </script>
@endsection