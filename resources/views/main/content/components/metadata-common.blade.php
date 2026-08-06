@php
    $content = $content ?? null;
    $showContentDeadline = old('has_deadline') !== null
        ? (bool) old('has_deadline')
        : (bool) ($content?->has_deadline ?? false);

    $learningHoursVal = old('learning_hours');
    if ($learningHoursVal === null && isset($content->learning_hours)) {
        $lh = (float) $content->learning_hours;
        $learningHoursVal = ($lh == (int) $lh) ? (string) (int) $lh : (string) $lh;
    }
    if (empty($learningHoursVal) || (float) $learningHoursVal <= 0) {
        $learningHoursVal = '0.5';
    }
@endphp

@once
<script>
document.addEventListener("DOMContentLoaded", function () {
    var lhInput = document.getElementById('learning_hours');
    if (!lhInput) return;

    function validateLH() {
        var val = lhInput.value.trim();
        var feedback = document.getElementById('learning_hours_feedback');

        if (val === '') {
            lhInput.classList.remove('is-invalid', 'is-valid');
            return true;
        }

        var num = parseFloat(val);
        var isValid = !isNaN(num) && num >= 0.5 && /^(?:\d+|\d+\.5)$/.test(val);

        if (isValid) {
            lhInput.classList.remove('is-invalid');
            lhInput.classList.add('is-valid');
        } else {
            lhInput.classList.remove('is-valid');
            lhInput.classList.add('is-invalid');
            if (feedback) {
                feedback.style.display = 'block';
            }
        }
        return isValid;
    }

    function normalizeLH(forceFallback) {
        var val = lhInput.value.trim();

        if (forceFallback || val === '' || val === '0' || val === '0.0' || parseFloat(val) <= 0 || isNaN(parseFloat(val))) {
            if (val === '' || val === '0' || val === '0.0' || parseFloat(val) <= 0 || isNaN(parseFloat(val))) {
                lhInput.value = '0.5';
                val = '0.5';
            }
        }

        if (/^\d+\.0+$/.test(val)) {
            var intVal = parseInt(val, 10);
            lhInput.value = intVal <= 0 ? '0.5' : intVal.toString();
        } else if (/^\d+\.50+$/.test(val)) {
            lhInput.value = parseFloat(val).toString();
        }

        validateLH();
    }

    lhInput.addEventListener('input', function () {
        var val = lhInput.value.trim();
        if (val === '0' || val === '0.0') {
            lhInput.value = '0.5';
        }
        validateLH();
    });

    lhInput.addEventListener('blur', function () {
        normalizeLH(true);
    });

    lhInput.addEventListener('change', function () {
        normalizeLH(true);
    });

    var form = lhInput.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            normalizeLH(true);
        });
    }

    // Initial validation check
    validateLH();
});
</script>
@endonce

<div class="row g-3 mb-3">
    {{-- Learning Hours --}}
    <div class="col-md-6">
        <label class="form-label" for="learning_hours">Learning Hours</label>
        <input type="text"
               class="form-control @error('learning_hours') is-invalid @enderror"
               placeholder="Example: 0.5, 1, 1.5, 2, 2.5"
               name="learning_hours"
               id="learning_hours"
               value="{{ $learningHoursVal }}"
               autocomplete="off">
        @error('learning_hours')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="invalid-feedback" id="learning_hours_feedback">Minimum value: 0.5 hours.</div>
        @enderror
        <small class="form-hint">Learning credit awarded after completing this content. The value must be a multiple of 0.5 (e.g., 0.5, 1, 1.5, 2, 2.5).</small>
    </div>

    {{-- Publish Date --}}
    <div class="col-md-6">
        <label class="form-label required" for="datepicker-icon-prepend">Publish Date</label>
        <div class="input-icon">
            <span class="input-icon-addon">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                    <path d="M16 3v4" />
                    <path d="M8 3v4" />
                    <path d="M4 11h16" />
                    <path d="M11 15h1" />
                    <path d="M12 15v3" />
                </svg>
            </span>
            <input class="form-control" placeholder="Select a date"
                id="datepicker-icon-prepend" name="date"
                value="{{ old('date', $content?->date ?? date('Y-m-d')) }}" required>
        </div>
    </div>
</div>

{{-- Deadline Content --}}
<div class="border-top pt-3 mt-3">
    <div class="mb-3">
        <label class="form-check cursor-pointer">
            <input type="hidden" name="has_deadline" value="0">
            <input class="form-check-input" type="checkbox" id="has_deadline_content"
                name="has_deadline" value="1"
                @checked($showContentDeadline)>
            <span class="form-check-label fw-medium">Set Deadline?</span>
        </label>
        <small class="form-hint d-block mt-1">Optional. If enabled, specify the date range.</small>
    </div>
    <div id="content-deadline-fields" style="{{ $showContentDeadline ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="content_from_date">From Date</label>
                <input type="date" class="form-control" name="from_date" id="content_from_date"
                    value="{{ old('from_date', $content?->from_date ? $content->from_date->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="content_to_date">To Date</label>
                <input type="date" class="form-control" name="to_date" id="content_to_date"
                    value="{{ old('to_date', $content?->to_date ? $content->to_date->format('Y-m-d') : '') }}">
            </div>
        </div>
    </div>
</div>
