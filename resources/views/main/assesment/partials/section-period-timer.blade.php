@php
    $isEdit = isset($assesment) && $assesment;
    $type = old('type', $isEdit ? $assesment->type : '');
    $isGlobal = $type === 'global';
    $hasAccessPeriod = old('has_access_period', $isEdit ? ($assesment->hasAccessPeriod() ? '1' : '0') : '1');
    $timer = old('timer', $isEdit ? $assesment->timer : 'n');
    $fromdate = old('fromdate', $isEdit ? $assesment->fromdate : '');
    $todate = old('todate', $isEdit ? $assesment->todate : '');
    $timeLimitMinutes = old('time_limit_minutes', $isEdit ? ($assesment->time_limit_minutes ?? 60) : 60);
    $totalRemedials = old('total_remedials', $isEdit ? ($isGlobal ? 0 : ($assesment->total_remedials ?? 0)) : 0);

    $showPeriod = ($hasAccessPeriod == '1' || $hasAccessPeriod === true || $hasAccessPeriod === 1);
    $showTimer = !$isGlobal && ($timer == 'y' || $timer === '1' || $timer === true);
    $hideRemedials = $hideRemedials ?? $isGlobal;
@endphp

<div class="owl-section-block mb-4 pt-3 border-top">
    <div class="owl-section-header mb-3 pb-2 border-bottom">
        <div class="d-flex align-items-center">
            <span class="owl-section-number me-2">2</span>
            <h3 class="owl-section-title mb-0 fw-bold" id="period-timer-section-title">{{ $isGlobal ? 'Access Period' : 'Access Period & Test Duration' }}</h3>
        </div>
        <div class="text-muted small ms-4 ps-1" id="period-timer-section-subtitle">
            {{ $isGlobal ? 'Schedule window configuration.' : 'Schedule window, duration limits, and remedial attempt configuration.' }}
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

    {{-- Time Limit Control & Section Container --}}
    <div id="time_limit_section_wrapper" class="{{ $isGlobal ? 'd-none' : '' }}">
        <div class="mb-3">
            <label class="form-label required">Test Duration?</label>
            <x-select name="timer" id="timer" onchange="toggleTimeLimit(this);">
                <option value="y" {{ $showTimer ? 'selected' : '' }}>Yes</option>
                <option value="n" {{ !$showTimer ? 'selected' : '' }}>No</option>
            </x-select>
        </div>

        <div id="time_limit_wrapper" class="mb-3 {{ $showTimer ? '' : 'd-none' }}">
            <div class="card card-sm border owl-sub-card">
                <div class="card-header bg-transparent py-2 border-bottom-0">
                    <h4 class="card-title text-uppercase text-primary fw-bold small mb-0">
                        <i class="fas fa-clock me-2"></i> TEST DURATION
                    </h4>
                </div>
                <div class="card-body pt-1 pb-3">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label required">Duration (Minutes)</label>
                            <div class="input-group">
                                <input type="number" name="time_limit_minutes" id="time_limit_minutes" class="form-control" placeholder="e.g. 60" value="{{ $timeLimitMinutes }}" min="1">
                                <span class="input-group-text">minutes</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Total Remedials Section Container --}}
    <div id="remedial_section_wrapper" class="{{ $hideRemedials ? 'd-none' : '' }}">
        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label required">Total Remedials</label>
                <input type="number" name="total_remedials" id="total-remedials" class="form-control @error('total_remedials') is-invalid @enderror" placeholder="0" min="0" value="{{ $totalRemedials }}">
                <small class="form-hint text-muted" id="total-remedials-hint">
                    {{ $isGlobal ? 'Global Assessment allows 1 attempt per batch.' : 'Number of additional remedial attempts after the initial assessment.' }}
                </small>
                @error('total_remedials')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>
    </div>
</div>
