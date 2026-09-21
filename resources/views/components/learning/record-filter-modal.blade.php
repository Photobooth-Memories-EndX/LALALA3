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
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal" role="document">
        <div class="modal-content rounded-4 border-0 shadow record-filter-modal">
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title fw-semibold d-flex align-items-center gap-1" id="recordMoreFiltersTitle" style="font-size: 1.1rem;">
                    <i class="ti ti-adjustments-horizontal text-primary me-1"></i>
                    All Filters
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2 record-more-filters" id="recordMoreFilters">
                <p class="text-muted extra-small mb-0">
                    All filters can be combined with the search &amp; the filter chip above.
                </p>

                <div class="record-filter-sections d-flex flex-column">

                    {{-- 1. Learning Program --}}
                    <div class="learning-filter-group">
                        <div class="fw-semibold text-dark small mb-2">Learning Program</div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            @foreach (['all' => 'All Learning', 'mandatory' => 'Mandatory Learning', 'optional' => 'Optional Learning'] as $value => $label)
                                <label class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalProgram" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <hr class="my-3 text-light-subtle">

                    {{-- 2. Completion Status --}}
                    <div class="learning-filter-group">
                        <div class="fw-semibold text-dark small mb-2">Completion Status</div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="completed" data-record-modal-filter>
                                <span class="form-check-label">Completed</span>
                            </label>
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="passed" data-record-modal-filter>
                                <span class="form-check-label">Passed</span>
                            </label>
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="failed" data-record-modal-filter>
                                <span class="form-check-label">Failed</span>
                            </label>
                        </div>
                    </div>

                    <hr class="my-3 text-light-subtle">

                    {{-- 3. Completion Date --}}
                    <div class="learning-filter-group">
                        <div class="fw-semibold text-dark small mb-2">Completion Date</div>
                        <div class="mb-3">
                            <div class="small text-muted mb-1">Year</div>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <label class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalYear" value="all" data-record-modal-filter checked>
                                    <span class="form-check-label">All</span>
                                </label>
                                @foreach ($years as $year)
                                    <label class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="recordModalYear" value="{{ $year }}" data-record-modal-filter>
                                        <span class="form-check-label">{{ $year }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div class="small text-muted mb-1">Month</div>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                @foreach ($months as $num => $name)
                                    <label class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="recordModalMonth[]" value="{{ $num }}" data-record-modal-filter>
                                        <span class="form-check-label">{{ substr($name, 0, 3) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <hr class="my-3 text-light-subtle">

                    {{-- 4. Sort By --}}
                    <div class="learning-filter-group">
                        <label class="form-label fw-semibold text-dark small mb-2" for="recordModalActivitySortSelect">Sort By</label>
                        <div class="w-100">
                            <select class="form-select rounded-3 record-filter-select w-100" id="recordModalActivitySortSelect" onchange="
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

                    <hr class="my-3 text-light-subtle">

                    {{-- 5. Duration --}}
                    <div class="learning-filter-group">
                        <div class="fw-semibold text-dark small mb-2">Duration</div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="0-600" data-record-modal-filter onchange="
                                    var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                    var val = checked.length ? checked[0] : 'all';
                                    var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                    if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                ">
                                <span class="form-check-label">&lt; 10 Minutes</span>
                            </label>
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="600-1800" data-record-modal-filter onchange="
                                    var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                    var val = checked.length ? checked[0] : 'all';
                                    var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                    if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                ">
                                <span class="form-check-label">10 - 30 Minutes</span>
                            </label>
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="1800-3600" data-record-modal-filter onchange="
                                    var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                    var val = checked.length ? checked[0] : 'all';
                                    var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                    if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                ">
                                <span class="form-check-label">30 - 60 Minutes</span>
                            </label>
                            <label class="form-check mb-0">
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

                    <hr class="my-3 text-light-subtle">

                    {{-- 6. Learning Hour --}}
                    <div class="learning-filter-group">
                        <div class="fw-semibold text-dark small mb-2">Learning Hour</div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            @foreach (['all' => 'All', '0-1' => '< 1 Hour', '1-3' => '1 - 3 Hours', 'gt3' => '> 3 Hours'] as $value => $label)
                                <label class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalLearningHour" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <hr class="my-3 text-light-subtle">

                    {{-- 7. Score Test --}}
                    <div class="learning-filter-group">
                        <div class="fw-semibold text-dark small mb-2">Score Test</div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            @foreach (['all' => 'All', 'none' => 'No Assessment', 'lt60' => '< 60', '60-79' => '60 - 79', '80-100' => '80 - 100'] as $value => $label)
                                <label class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalScoreTest" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <hr class="my-3 text-light-subtle">

                    {{-- 8. Certificate --}}
                    <div class="learning-filter-group">
                        <div class="fw-semibold text-dark small mb-2">Certificate</div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            @foreach (['all' => 'All', 'ready' => 'Available', 'none' => 'Not Available'] as $value => $label)
                                <label class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="recordModalCertificate" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer border-top pt-3 d-flex justify-content-end align-items-center gap-2">
                <!-- Reset Button (Placed right next to Apply Filter) -->
                <button type="button" id="btn-reset-record-modal" class="btn btn-outline-secondary px-3 rounded-3 d-inline-flex align-items-center gap-1 record-clear-filter">
                    <i class="ti ti-refresh fs-2"></i> Reset
                </button>

                <!-- Apply Filter Button -->
                <button type="submit" class="btn btn-primary px-4 rounded-3 d-inline-flex align-items-center gap-1 record-apply-filter" id="recordApplyFilter" style="background-color: #003b6d; border-color: #003b6d;" data-bs-dismiss="modal">
                    <i class="ti ti-search fs-2"></i> Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>
