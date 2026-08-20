@php
    $isEdit = isset($assesment) && $assesment;
    $type = old('type', $isEdit ? $assesment->type : '');
    $titleVal = old('title', $isEdit ? $assesment->title : '');
    $contentId = old('content_id', $isEdit ? $assesment->content_id : '');
    $trainingplanId = old('trainingplan_id', $isEdit ? $assesment->trainingplan_id : '');
    $uniqueCode = old('unique_code', $isEdit ? $assesment->unique_code : '');
    $passingGrade = old('passing_grade', $isEdit ? $assesment->passing_grade : '');
    $hasAccessPeriod = old('has_access_period', $isEdit ? ($assesment->hasAccessPeriod() ? '1' : '0') : '1');
    $timer = old('timer', $isEdit ? $assesment->timer : 'n');
    $fromdate = old('fromdate', $isEdit ? $assesment->fromdate : '');
    $todate = old('todate', $isEdit ? $assesment->todate : '');
    $timeLimitMinutes = old('time_limit_minutes', $isEdit ? ($assesment->time_limit_minutes ?? 60) : 60);
    $totalRemedials = old('total_remedials', $isEdit ? (($type === 'global') ? 0 : ($assesment->total_remedials ?? 0)) : 0);
@endphp

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-light-lt py-3">
        <h3 class="card-title fw-bold mb-0">
            <i class="fas fa-cogs me-2 text-primary"></i>
            Assessment Configuration
        </h3>
    </div>

    <div class="card-body">
        {{-- Row 1: Assessment Type & Title --}}
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label required">Assessment Type</label>
                <x-select name="type" onchange="setRequired(this);">
                    <option value="">-choose-</option>
                    <option value="content" {{ ($type == 'content') ? 'selected' : '' }}>Assesment for content</option>
                    <option value="trainingplan" {{ ($type == 'trainingplan') ? 'selected' : '' }}>Assesment for playlist</option>
                    <option value="global" {{ ($type == 'global') ? 'selected' : '' }}>Global Assesment</option>
                </x-select>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Title</label>
                <x-input type="text" name="title" placeholder="Enter title" value="{{ $titleVal }}" />
            </div>
        </div>

        {{-- Conditional Inputs: Content / Trainingplan --}}
        <div class="mb-3 {{ ($type == 'content') ? '' : 'd-none' }}" id="content_id_wrapper">
            <label class="form-label">Learning Content</label>
            <x-select name="content_id" id="content_id">
                <option value="">-choose-</option>
                @foreach ($contents as $item)
                    <option value="{{ $item->id }}" {{ ($contentId == $item->id) ? 'selected' : '' }}>{{ $item->title }}</option>
                @endforeach
            </x-select>
        </div>

        <div class="mb-3 {{ ($type == 'trainingplan') ? '' : 'd-none' }}" id="trainingplan_id_wrapper">
            <label class="form-label">Training Plan</label>
            <x-select name="trainingplan_id" id="trainingplan_id">
                <option value="">-choose-</option>
                @foreach ($trainingplan as $item)
                    <option value="{{ $item->id }}" {{ ($trainingplanId == $item->id) ? 'selected' : '' }}>{{ $item->title }}</option>
                @endforeach
            </x-select>
        </div>

        {{-- Row 2: Unique Code & Passing Grade --}}
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Unique Code (Optional)</label>
                <x-input type="text" name="unique_code" placeholder="Enter unique code" value="{{ $uniqueCode }}" />
            </div>

            <div class="col-md-6">
                <label class="form-label required">Passing Grade</label>
                <x-input type="number" name="passing_grade" placeholder="Enter passing grade" value="{{ $passingGrade }}" />
            </div>
        </div>

        {{-- Row 3: Access Period? & Time Limit? --}}
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label required">Access Period?</label>
                <x-select name="has_access_period" id="has_access_period" onchange="toggleAccessPeriod(this);">
                    <option value="1" {{ ($hasAccessPeriod == '1' || $hasAccessPeriod === true) ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ ($hasAccessPeriod === '0' || $hasAccessPeriod === false) ? 'selected' : '' }}>No</option>
                </x-select>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Time Limit?</label>
                <x-select name="timer" id="timer" onchange="toggleTimeLimit(this);">
                    <option value="y" {{ ($timer == 'y') ? 'selected' : '' }}>Yes</option>
                    <option value="n" {{ ($timer == 'n') ? 'selected' : '' }}>No</option>
                </x-select>
            </div>
        </div>

        {{-- Access Period Section --}}
        <div id="section_period" class="mb-3 {{ ($hasAccessPeriod == '1' || $hasAccessPeriod === true) ? '' : 'd-none' }}">
            <div class="card card-sm border bg-light-lt owl-sub-card">
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

        {{-- Time Limit Section --}}
        <div id="time_limit_wrapper" class="mb-3 {{ ($timer == 'y') ? '' : 'd-none' }}">
            <div class="card card-sm border bg-light-lt owl-sub-card">
                <div class="card-header bg-transparent py-2 border-bottom-0">
                    <h4 class="card-title text-uppercase text-primary fw-bold small mb-0">
                        <i class="fas fa-clock me-2"></i> TIME LIMIT
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

        {{-- Total Remedials --}}
        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label required">Total Remedials</label>
                <input type="number" name="total_remedials" id="total-remedials" class="form-control @error('total_remedials') is-invalid @enderror" placeholder="0" min="0" value="{{ $totalRemedials }}">
                <small class="form-hint text-muted" id="total-remedials-hint">
                    {{ ($type === 'global') ? 'Global Assessment allows 1 attempt per batch.' : 'Number of additional remedial attempts after the initial assessment.' }}
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
