@props([
    'filterOptions' => [],
])

@php
    $years = collect($filterOptions['years'] ?? []);
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
@endphp

<div class="modal fade" id="recordMoreFiltersModal" tabindex="-1" role="dialog" aria-labelledby="recordMoreFiltersTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content record-filter-modal">
            <div class="modal-header border-bottom px-4 py-3 align-items-center">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="recordMoreFiltersTitle">More Filters</h5>
                    <div class="text-muted small">Refine Learning Record</div>
                </div>
                <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body record-more-filters p-4" id="recordMoreFilters">
                <div class="record-filter-sections d-flex flex-column">

                    {{-- 1. LEARNING PROGRAM --}}
                    <div class="record-filter-divider-section">
                        <div class="record-filter-section-title">LEARNING PROGRAM</div>
                        <div class="d-flex flex-wrap align-items-center gap-4">
                            @foreach (['all' => 'All Learning', 'mandatory' => 'Mandatory Learning', 'optional' => 'Optional Learning'] as $value => $label)
                                <label class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalProgram" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- 2. COMPLETION STATUS --}}
                    <div class="record-filter-divider-section">
                        <div class="record-filter-section-title">COMPLETION STATUS</div>
                        <div class="d-flex flex-wrap align-items-center gap-4">
                            <label class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="completed" data-record-modal-filter>
                                <span class="form-check-label">Completed</span>
                            </label>
                            <label class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="passed" data-record-modal-filter>
                                <span class="form-check-label">Passed</span>
                            </label>
                            <label class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="failed" data-record-modal-filter>
                                <span class="form-check-label">Failed</span>
                            </label>
                        </div>
                    </div>

                    {{-- 3. COMPLETION DATE --}}
                    <div class="record-filter-divider-section">
                        <div class="record-filter-section-title">COMPLETION DATE</div>
                        <div class="mb-3">
                            <div class="record-filter-sublabel">Year</div>
                            <div class="d-flex flex-wrap align-items-center gap-4">
                                <label class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalYear" value="all" data-record-modal-filter checked>
                                    <span class="form-check-label">All</span>
                                </label>
                                @foreach ($years as $year)
                                    <label class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="recordModalYear" value="{{ $year }}" data-record-modal-filter>
                                        <span class="form-check-label">{{ $year }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div class="record-filter-sublabel">Month</div>
                            <div class="d-flex flex-wrap align-items-center gap-4">
                                @foreach ($months as $num => $name)
                                    <label class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="checkbox" name="recordModalMonth[]" value="{{ $num }}" data-record-modal-filter>
                                        <span class="form-check-label">{{ substr($name, 0, 3) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- 4. SORT BY --}}
                    <div class="record-filter-divider-section">
                        <div class="record-filter-section-title">SORT BY</div>
                        <div class="w-100">
                            <select class="form-select record-filter-select w-100" id="recordModalActivitySortSelect" onchange="
                                var val = this.value;
                                var radio = document.querySelector('input[name=\'recordModalActivitySort\'][value=\'' + val + '\']');
                                if (radio) { radio.checked = true; $(radio).trigger('change'); }
                            ">
                                <option value="all">All</option>
                                <option value="oldest">Oldest</option>
                                <option value="newest">Newest</option>
                                <option value="ascending">Ascending</option>
                                <option value="descending">Descending</option>
                            </select>
                            <div class="d-none">
                                <input type="radio" name="recordModalActivitySort" value="all" checked data-record-modal-filter>
                                <input type="radio" name="recordModalActivitySort" value="oldest" data-record-modal-filter>
                                <input type="radio" name="recordModalActivitySort" value="newest" data-record-modal-filter>
                                <input type="radio" name="recordModalActivitySort" value="ascending" data-record-modal-filter>
                                <input type="radio" name="recordModalActivitySort" value="descending" data-record-modal-filter>
                            </div>
                        </div>
                    </div>

                    {{-- 5. DURATION --}}
                    <div class="record-filter-divider-section">
                        <div class="record-filter-section-title">DURATION</div>
                        <div class="d-flex flex-wrap align-items-center gap-4">
                            <label class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="0-600" data-record-modal-filter onchange="
                                    var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                    var val = checked.length ? checked[0] : 'all';
                                    var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                    if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                ">
                                <span class="form-check-label">&lt; 10 Minutes</span>
                            </label>
                            <label class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="600-1800" data-record-modal-filter onchange="
                                    var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                    var val = checked.length ? checked[0] : 'all';
                                    var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                    if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                ">
                                <span class="form-check-label">10 - 30 Minutes</span>
                            </label>
                            <label class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="1800-3600" data-record-modal-filter onchange="
                                    var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                    var val = checked.length ? checked[0] : 'all';
                                    var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                    if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                ">
                                <span class="form-check-label">30 - 60 Minutes</span>
                            </label>
                            <label class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="gt3600" data-record-modal-filter onchange="
                                    var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                    var val = checked.length ? checked[0] : 'all';
                                    var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                    if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                ">
                                <span class="form-check-label">&gt; 1 Hour</span>
                            </label>
                        </div>
                        <div class="d-none">
                            <input type="radio" name="recordModalDuration" value="all" checked data-record-modal-filter>
                            <input type="radio" name="recordModalDuration" value="0-600" data-record-modal-filter>
                            <input type="radio" name="recordModalDuration" value="600-1800" data-record-modal-filter>
                            <input type="radio" name="recordModalDuration" value="1800-3600" data-record-modal-filter>
                            <input type="radio" name="recordModalDuration" value="gt3600" data-record-modal-filter>
                        </div>
                    </div>

                    {{-- 6. LEARNING HOUR --}}
                    <div class="record-filter-divider-section">
                        <div class="record-filter-section-title">LEARNING HOUR</div>
                        <div class="d-flex flex-wrap align-items-center gap-4">
                            @foreach (['all' => 'All', '0-1' => '< 1 Hour', '1-3' => '1 - 3 Hours', 'gt3' => '> 3 Hours'] as $value => $label)
                                <label class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalLearningHour" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- 7. SCORE TEST --}}
                    <div class="record-filter-divider-section">
                        <div class="record-filter-section-title">SCORE TEST</div>
                        <div class="d-flex flex-wrap align-items-center gap-4">
                            @foreach (['all' => 'All', 'none' => 'No Assessment', 'lt60' => '< 60', '60-79' => '60 - 79', '80-100' => '80 - 100'] as $value => $label)
                                <label class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalScoreTest" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- 8. CERTIFICATE --}}
                    <div class="record-filter-divider-section border-0 pb-0">
                        <div class="record-filter-section-title">CERTIFICATE</div>
                        <div class="d-flex flex-wrap align-items-center gap-4">
                            @foreach (['all' => 'All', 'ready' => 'Available', 'none' => 'Not Available'] as $value => $label)
                                <label class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalCertificate" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer record-filter-modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-learning-outline" id="recordClearFilter">
                    <i class="ti ti-refresh" aria-hidden="true"></i><span>Reset</span>
                </button>
                <button type="button" class="btn btn-primary btn-learning-primary" id="recordApplyFilter">
                    <i class="ti ti-search" aria-hidden="true"></i><span>Apply Filter</span>
                </button>
            </div>
        </div>
    </div>
</div>
