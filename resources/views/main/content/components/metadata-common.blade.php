@php
    $content = $content ?? null;
    $mode = $mode ?? ($content && $content->exists ? 'edit' : 'create');
    $isEdit = ($mode === 'edit' && $content && $content->exists);
    $isLocked = $isLocked ?? ($content && $content->isPublished());

    $showContentDeadline = old('has_deadline') !== null
        ? (bool) old('has_deadline')
        : (bool) ($content?->has_deadline ?? false);

    $learningHoursVal = old('learning_hours');
    if ($learningHoursVal === null && isset($content->learning_hours)) {
        $lh = (float) $content->learning_hours;
        $learningHoursVal = ($lh == (int) $lh) ? (string) (int) $lh : (string) $lh;
    }
    if ($isEdit && (empty($learningHoursVal) || (float) $learningHoursVal <= 0)) {
        $learningHoursVal = '0.5';
    }
@endphp

<div class="row g-3 mb-3">
    {{-- Learning Hours --}}
    <div class="col-md-6">
        <label class="form-label" for="learning_hours">Learning Hours</label>
        @if($isEdit)
            <div class="row g-2 align-items-center">
                <div class="col-12 col-sm-7 col-md-8">
                    <input type="text"
                           class="form-control bg-light @error('learning_hours') is-invalid @enderror"
                           placeholder="Auto-calculated"
                           name="learning_hours"
                           id="learning_hours"
                           value="{{ $learningHoursVal }}"
                           readonly
                           autocomplete="off"
                           {{ $isLocked ? 'disabled' : '' }}>
                </div>
                <div class="col-12 col-sm-5 col-md-4">
                    <button type="button"
                            class="btn btn-outline-primary w-100"
                            id="btn-sync-content-lh"
                            data-url="{{ route('content.synchronize-learning-hours', $content->slug ?? $content->id) }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        <i class="ti ti-refresh me-1 icon-sync"></i>
                        <span class="btn-text">Synchronize</span>
                    </button>
                </div>
            </div>
            @error('learning_hours')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="form-hint mt-1">Learning Hours are calculated automatically from duration.</small>
        @else
            <div class="row g-2 align-items-center">
                <div class="col-12 col-sm-7 col-md-8">
                    <input type="number"
                           min="0"
                           step="0.0001"
                           class="form-control @error('learning_hours') is-invalid @enderror"
                           placeholder="e.g. 1.0000"
                           name="learning_hours"
                           id="learning_hours"
                           value="{{ $learningHoursVal }}"
                           required
                           autocomplete="off">
                </div>
                <div class="col-12 col-sm-5 col-md-4">
                    <button type="button"
                            class="btn btn-outline-primary w-100"
                            id="btn-sync-content-lh"
                            data-url="">
                        <i class="ti ti-refresh me-1 icon-sync"></i>
                        <span class="btn-text">Synchronize</span>
                    </button>
                </div>
            </div>
            @error('learning_hours')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="form-hint mt-1">Learning Hours can be entered manually or synchronized from duration.</small>
        @endif
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
                value="{{ old('date', $content?->date ?? date('Y-m-d')) }}"
                {{ $isLocked ? 'disabled' : 'required' }}>
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
                @checked($showContentDeadline)
                {{ $isLocked ? 'disabled' : '' }}>
            <span class="form-check-label fw-medium">Set Deadline?</span>
        </label>
        <small class="form-hint d-block mt-1">Optional. If enabled, specify the date range.</small>
    </div>
    <div id="content-deadline-fields" style="{{ $showContentDeadline ? '' : 'display:none;' }}">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="content_from_date">From Date</label>
                <input type="date" class="form-control" name="from_date" id="content_from_date"
                    value="{{ old('from_date', $content?->from_date ? $content->from_date->format('Y-m-d') : '') }}"
                    {{ $isLocked ? 'disabled' : '' }}>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="content_to_date">To Date</label>
                <input type="date" class="form-control" name="to_date" id="content_to_date"
                    value="{{ old('to_date', $content?->to_date ? $content->to_date->format('Y-m-d') : '') }}"
                    {{ $isLocked ? 'disabled' : '' }}>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    var lhInput = document.getElementById('learning_hours');
    if (lhInput && lhInput.hasAttribute('readonly')) {
        function validateLH() {
            var val = lhInput.value.trim();
            var feedback = document.getElementById('learning_hours_feedback');

            if (val === '') {
                lhInput.classList.remove('is-invalid', 'is-valid');
                return true;
            }

            var num = parseFloat(val);
            var isValid = !isNaN(num) && num >= 0;

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

            if (forceFallback && (val === '' || isNaN(parseFloat(val)) || parseFloat(val) < 0)) {
                lhInput.value = '0';
            }

            validateLH();
        }

        lhInput.addEventListener('input', function () {
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
    }
});

$(document).ready(function() {
    function showToast(message, type) {
        let textClass = type === 'warning' ? 'text-dark' : 'text-white';
        let bgClass = type === 'success' ? 'bg-success' : (type === 'warning' ? 'bg-warning' : 'bg-danger');
        let icon = type === 'success' ? 'ti ti-check' : (type === 'warning' ? 'ti ti-alert-triangle' : 'ti ti-alert-circle');

        let toastHtml = `
            <div class="toast owl-toast ${bgClass} ${textClass} align-items-center mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-body d-flex align-items-center justify-content-between p-3">
                    <div class="d-flex align-items-center">
                        <i class="${icon} me-2 fs-3"></i>
                        <span>${message}</span>
                    </div>
                </div>
            </div>
        `;

        if ($('#owl-toast-wrapper').length === 0) {
            $('body').append('<div id="owl-toast-wrapper" style="position: fixed; top: 1.5rem; right: 1.5rem; z-index: 9999; width: 320px;"></div>');
        }

        let $toast = $(toastHtml);
        $('#owl-toast-wrapper').append($toast);
        $toast.fadeIn(200).delay(3000).fadeOut(400, function() { $(this).remove(); });
    }

    $(document)
        .off('click.learningHours', '#btn-sync-content-lh')
        .on('click.learningHours', '#btn-sync-content-lh', function(e) {
            e.preventDefault();
            let $btn = $(this);
            let url = $btn.data('url');

            if ($btn.data('loading')) return;

            let $form = $btn.closest('form');
            if (!$form.length) {
                $form = $('form#contentEditForm, form#contentCreateForm, form').first();
            }

            $btn.data('loading', true);
            $btn.prop('disabled', true);
            $btn.find('.icon-sync').attr('class', 'spinner-border spinner-border-sm me-1 icon-sync');
            $btn.find('.btn-text').text('Synchronizing...');
            $('body').css('cursor', 'wait');

            let csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
            let typeVal = $.trim($form.find('[name="type"]').val() || $('#type').val() || '');

            // Find the active duration input 100% deterministically based on Content Type
            let getActiveDuration = function() {
                // For Document type, if total_page and page_duration are set, calculate estimated duration
                if (typeVal === 'Document') {
                    let totalPage = parseInt($form.find('[name="total_page"], [name="document_total_pages"], #total_page, #document_total_pages').val() || '0', 10);
                    let pageDuration = parseInt($form.find('[name="page_duration"], #page_duration').val() || '0', 10);
                    if (totalPage > 0 && pageDuration > 0) {
                        let totalSec = totalPage * pageDuration;
                        let h = Math.floor(totalSec / 3600);
                        let m = Math.floor((totalSec % 3600) / 60);
                        let s = totalSec % 60;
                        let formatted = [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
                        $form.find('#doc_duration').val(formatted);
                        return formatted;
                    }
                }

                let durationSelector = null;

                switch (typeVal) {
                    case 'Video':
                        durationSelector = '#video_duration';
                        break;
                    case 'YouTube':
                        durationSelector = '#youtube_duration';
                        break;
                    case 'Default':
                    case 'SOP':
                    case 'SOP/Regulation':
                        durationSelector = '#default_duration';
                        break;
                    case 'Document':
                        durationSelector = '#doc_duration';
                        break;
                }

                if (durationSelector) {
                    let $duration = $form.find(durationSelector);
                    if ($duration.length && $.trim($duration.val() || '') !== '') {
                        return $.trim($duration.val());
                    }
                }

                // Fallback: check input[name="duration"]
                let $activeInput = $form.find('input[name="duration"]');
                if ($activeInput.length && $.trim($activeInput.val() || '') !== '') {
                    return $.trim($activeInput.val());
                }

                // Fallback check if typeVal is unassigned or selector is not found
                let $inputs = $form.find('[name="duration"]');
                let $visible = $inputs.filter(function() {
                    return $(this).is(':visible');
                });

                if ($visible.length) {
                    let value = $.trim($visible.first().val() || '');
                    if (value !== '') {
                        return value;
                    }
                }

                return $.trim($inputs.first().val() || '');
            };

            let durationVal = getActiveDuration();
            let pageDurationVal = $form.find('[name="page_duration"], #page_duration').val() || '';
            let totalPageVal = $form.find('[name="total_page"], [name="document_total_pages"], #total_page, #document_total_pages').val() || '';

            let restoreButton = function() {
                $btn.find('.icon-sync').attr('class', 'ti ti-refresh me-1 icon-sync');
                $btn.find('.btn-text').text('Synchronize');
                $btn.prop('disabled', false);
                $btn.data('loading', false);
                $('body').css('cursor', 'default');
            };

            // If URL exists, invoke the server-side sync endpoint (Edit Content)
            if (url) {
                let payload = {
                    _token: csrfToken,
                    duration: durationVal,
                    page_duration: pageDurationVal,
                    document_total_pages: totalPageVal,
                    total_page: totalPageVal,
                    type: typeVal
                };

                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: payload,
                    success: function(res) {
                        if (res && res.success) {
                            if (res.learning_hours !== undefined) {
                                $form.find('[name="learning_hours"], #learning_hours').val(res.learning_hours);
                            }
                            if (res.duration !== undefined) {
                                // Update all duration input fields across metadata components
                                $form.find('[name="duration"], [name="_disabled_duration"]').val(res.duration);
                                $form.find('#doc_duration, #video_duration, #youtube_duration, #default_duration').val(res.duration);
                            }
                            showToast(res.message || 'Learning Hours synchronized successfully.', 'success');
                        } else {
                            showToast(res && res.message ? res.message : 'Unable to synchronize Learning Hours.', 'danger');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Unable to synchronize Learning Hours.';
                        if (xhr && xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            } else if (xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                        }
                        showToast(message, 'danger');
                    },
                    complete: function() {
                        restoreButton();
                    }
                });
            } else {
                // Client-side synchronization for Create Content (matches ActualLearningHourCalculator)
                let parseDurationSeconds = function(str) {
                    if (!str) return 0;
                    let s = $.trim(String(str));
                    if (!s) return 0;
                    let parts = s.split(':');
                    if (parts.length === 2) {
                        let m = parseInt(parts[0], 10) || 0;
                        let sSec = parseInt(parts[1], 10) || 0;
                        return (m * 60) + sSec;
                    }
                    if (parts.length === 3) {
                        let h = parseInt(parts[0], 10) || 0;
                        let m = parseInt(parts[1], 10) || 0;
                        let sSec = parseInt(parts[2], 10) || 0;
                        return (h * 3600) + (m * 60) + sSec;
                    }
                    if (!isNaN(Number(s))) {
                        return Math.max(0, parseInt(s, 10) || 0);
                    }
                    return 0;
                };

                let totalSeconds = parseDurationSeconds(durationVal);
                if (totalSeconds <= 0 && typeVal === 'Document') {
                    let tp = parseInt(totalPageVal, 10) || 0;
                    let pd = parseInt(pageDurationVal, 10) || 0;
                    if (tp > 0 && pd > 0) {
                        totalSeconds = tp * pd;
                    }
                }

                setTimeout(function() {
                    if (totalSeconds <= 0) {
                        restoreButton();
                        showToast('Duration is not available yet to calculate Learning Hours.', 'warning');
                        return;
                    }

                    // Formula: round(seconds / 3600, 4)
                    let calculatedHours = (Math.round((totalSeconds / 3600) * 10000) / 10000).toFixed(4);
                    $form.find('[name="learning_hours"], #learning_hours').val(calculatedHours);

                    if (durationVal) {
                        $form.find('[name="duration"], [name="_disabled_duration"]').val(durationVal);
                        $form.find('#doc_duration, #video_duration, #youtube_duration, #default_duration').filter(':visible').val(durationVal);
                    }

                    restoreButton();
                    showToast('Learning Hours synchronized successfully: ' + calculatedHours + ' h', 'success');
                }, 200);
            }
        });
});
</script>
@endpush
@endonce