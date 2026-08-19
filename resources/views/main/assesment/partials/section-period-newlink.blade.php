@php
    $isEdit = isset($assesment) && $assesment;
    $hasAccessPeriod = old('has_access_period', $isEdit ? ($assesment->hasAccessPeriod() ? '1' : '0') : '1');
    $fromdate = old('fromdate', $isEdit ? $assesment->fromdate : '');
    $todate = old('todate', $isEdit ? $assesment->todate : '');

    $showPeriod = ($hasAccessPeriod == '1' || $hasAccessPeriod === true || $hasAccessPeriod === 1);
@endphp

<div class="owl-section-block mb-4 pt-3 border-top">
    <div class="owl-section-header mb-3 pb-2 border-bottom">
        <div class="d-flex align-items-center">
            <span class="owl-section-number me-2">2</span>
            <h3 class="owl-section-title mb-0 fw-bold" id="period-timer-section-title">Access Period</h3>
        </div>
        <div class="text-muted small ms-4 ps-1" id="period-timer-section-subtitle">
            Schedule when this assessment link can be accessed.
        </div>
    </div>

    {{-- Access Period Control --}}
    <div class="mb-3">
        <label class="form-label required">Access Period?</label>
        <x-select name="has_access_period" id="has_access_period" onchange="toggleAccessPeriod(this);">
            <option value="1" {{ $showPeriod ? 'selected' : '' }}>Yes</option>
            <option value="0" {{ !$showPeriod ? 'selected' : '' }}>No</option>
        </x-select>
    </div>

    {{-- Access Period Section Container --}}
    <div id="section_period" class="mb-3 {{ $showPeriod ? '' : 'd-none' }}">
        <div class="card card-sm border owl-sub-card">
            <div class="card-header bg-transparent py-2 border-bottom-0">
                <h4 class="card-title text-uppercase text-primary fw-bold small mb-0">
                    <i class="fas fa-calendar-alt me-2"></i> ACCESS PERIOD
                </h4>
            </div>
            <div class="card-body pt-1 pb-3">
                <div class="row g-3" id="date_wrapper">
                    <div class="col-md-6">
                        <label class="form-label required">From Date</label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <x-input placeholder="Select a date" id="datepicker-fromdate" name="fromdate" value="{{ $fromdate }}" class="input-date" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">To Date</label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <x-input placeholder="Select a date" id="datepicker-todate" name="todate" value="{{ $todate }}" class="input-date" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
