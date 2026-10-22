{{-- Shared Training Record Detail Modal (Used by KP OWL Online, Non KP OWL Offline, and All Record) --}}
@props(['record' => []])
@php
    $record = $record ?? [];
    $rawSource = strtolower(trim((string) ($record['source'] ?? '')));
    $isOnlineSource = in_array($rawSource, ['online', 'kp-owl', 'kp owl'], true)
        || (!empty($record['category']) && in_array($record['category'], ['All Content', 'Content', 'Playlist'], true));
    $recordSource = !empty($record['source'])
        ? (in_array($rawSource, ['kp owl', 'online', 'kp-owl'], true) ? 'KP OWL' : 'Non-KP OWL')
        : 'KP OWL';
@endphp
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-file-text text-primary fs-2"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="recordDetailModalLabel">More Information</h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4" id="recordDetailModalBody">
                <!-- Loading State (for async fetches) -->
                <div class="py-5 text-center text-muted" id="record-detail-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small">Loading training details...</div>
                </div>

                <!-- Content Container -->
                <div id="record-detail-content">
                    <!-- Header & Title -->
                    <div class="pb-3 mb-3 border-bottom">
                        <span class="badge {{ $recordSource === 'KP OWL' ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning' }} mb-2" id="modal-detail-source-badge">
                            {{ $recordSource }}
                        </span>
                        <h4 class="fw-bold text-dark mb-0" id="modal-detail-course-name">{{ $record['title'] ?? ($record['course_name'] ?? '-') }}</h4>
                    </div>

                    <!-- 1. Employee Information Card -->
                    <div class="card border-0 bg-light-subtle rounded-3 p-3 mb-3">
                        <h6 class="fw-bold text-uppercase text-muted text-xs mb-3">
                            <i class="ti ti-user me-1 text-primary"></i> Employee Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-xs text-muted text-uppercase">Employee Name</div>
                                <div class="fw-semibold text-dark" id="modal-detail-employee-name">{{ $record['user_name'] ?? ($record['participant_name'] ?? (auth()->user()->name ?? '-')) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-xs text-muted text-uppercase">Employee ID</div>
                                <div class="fw-semibold text-dark" id="modal-detail-employee-id">{{ $record['employee_id'] ?? (auth()->user()->employee_id ?? (auth()->user()->nip ?? (auth()->user()->id_emp ?? '-'))) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-xs text-muted text-uppercase">Team</div>
                                <div class="fw-semibold text-dark" id="modal-detail-team">{{ $record['team'] ?? (auth()->user()->team ?? '-') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Training Information Card -->
                    <div class="card border-0 bg-light-subtle rounded-3 p-3 mb-3">
                        <h6 class="fw-bold text-uppercase text-muted text-xs mb-3">
                            <i class="ti ti-info-circle me-1 text-primary"></i> Training Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-xs text-muted text-uppercase">Delivery Method</div>
                                <div class="fw-semibold text-dark" id="modal-detail-delivery-method">{{ \App\Models\OfflineTraining::normalizeDeliveryMethod($record['delivery_method'] ?? '-') }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-xs text-muted text-uppercase">Provider</div>
                                <div class="fw-semibold text-dark" id="modal-detail-provider">{{ $record['provider'] ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-xs text-muted text-uppercase">Provider Name</div>
                                <div class="fw-semibold text-dark" id="modal-detail-provider-name">{{ $record['provider_name'] ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-xs text-muted text-uppercase">Venue</div>
                                <div class="fw-semibold text-dark" id="modal-detail-venue">{{ $record['venue'] ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Bond Information Card -->
                    <div class="card border-0 bg-light-subtle rounded-3 p-3">
                        <h6 class="fw-bold text-uppercase text-muted text-xs mb-3">
                            <i class="ti ti-file-text me-1 text-primary"></i> Bond Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-xs text-muted text-uppercase">Bond Status</div>
                                <div class="fw-semibold text-dark" id="modal-detail-bond-status">{{ $record['bond_status'] ?? '-' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-xs text-muted text-uppercase">Bond Duration</div>
                                <div class="fw-semibold text-dark" id="modal-detail-bond-duration">{{ !empty($record['bond_duration']) ? $record['bond_duration'] . ' Months' : '-' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-xs text-muted text-uppercase">Bond End Date</div>
                                <div class="fw-semibold text-dark" id="modal-detail-bond-end-date">{{ $record['bond_end_date'] ?? '-' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-xs text-muted text-uppercase">Bond Amount</div>
                                <div class="fw-semibold text-dark" id="modal-detail-bond-amount">
                                    {{ (!empty($record['bond_amount_usd']) && (float) $record['bond_amount_usd'] > 0)
                                        ? '$' . number_format((float) $record['bond_amount_usd'], 2)
                                        : ((!empty($record['bond_amount_idr']) && (float) $record['bond_amount_idr'] > 0)
                                            ? 'Rp ' . number_format((float) $record['bond_amount_idr'], 0, ',', '.')
                                            : ((!empty($record['bond_amount']) && is_numeric($record['bond_amount']) && (float) $record['bond_amount'] > 0)
                                                ? 'Rp ' . number_format((float) $record['bond_amount'], 0, ',', '.')
                                                : '-')) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top py-2 px-4 bg-light">
                <button type="button" class="btn btn-outline-secondary px-3 rounded-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function fallback(val, def) {
        if (val === undefined || val === null || val === '') return (def !== undefined ? def : '-');
        return val;
    }

    function normalizeDeliveryMethod(method, isOnline) {
        if (!method || method === '-' || method === '') {
            return isOnline ? 'Online' : 'Offline';
        }
        var raw = String(method).toLowerCase().trim();
        if (['in class', 'classroom', 'on-site', 'off-site', 'offline'].indexOf(raw) !== -1) {
            return 'Offline';
        }
        if (['e-learning', 'webinar', 'online', 'kp-owl', 'kp owl'].indexOf(raw) !== -1) {
            return 'Online';
        }
        if (['hybrid', 'blended'].indexOf(raw) !== -1) {
            return 'Blended';
        }
        return method.charAt(0).toUpperCase() + method.slice(1);
    }

    function populateRecordDetailModal(d, source) {
        if (!d) return;

        var rawSource = (source || d.source || '').toString().toLowerCase().trim();
        var isOnline = (rawSource === 'online' || rawSource === 'kp owl' || rawSource === 'kp-owl' || (!d.offline_training_id && !d.batch && (d.category === 'All Content' || d.category === 'Content' || d.category === 'Playlist')));

        // 1. Header & Source Badge
        var sourceLabel = isOnline ? 'KP OWL' : 'Non-KP OWL';
        $('#modal-detail-source-badge')
            .text(sourceLabel)
            .removeClass('bg-primary-subtle text-primary bg-warning-subtle text-warning')
            .addClass(isOnline ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning');

        var title = fallback(d.title, fallback(d.course_name, '-'));
        $('#modal-detail-course-name').text(title);

        // 2. Card 1: Employee Information
        var authName = '{{ auth()->user()->name ?? '-' }}';
        var authEmpId = '{{ auth()->user()->employee_id ?? (auth()->user()->nip ?? (auth()->user()->id_emp ?? '-')) }}';
        var authTeam = '{{ auth()->user()->team ?? '-' }}';

        var employeeName = fallback(d.user_name, fallback(d.participant_name, fallback(d.name, authName)));
        var employeeId = fallback(d.employee_id, fallback(d.nip, authEmpId));
        var team = fallback(d.team, authTeam);

        $('#modal-detail-employee-name').text(employeeName);
        $('#modal-detail-employee-id').text(employeeId);
        $('#modal-detail-team').text(team);

        // 3. Card 2: Training Information
        $('#modal-detail-delivery-method').text(normalizeDeliveryMethod(d.delivery_method, isOnline));
        $('#modal-detail-provider').text(fallback(d.provider, isOnline ? 'Internal' : 'External'));
        $('#modal-detail-provider-name').text(fallback(d.provider_name, isOnline ? 'KP-OWL' : '-'));
        $('#modal-detail-venue').text(fallback(d.venue, isOnline ? 'KP-OWL' : '-'));

        // 4. Card 3: Bond Information
        $('#modal-detail-bond-status').text(fallback(d.bond_status, 'No'));

        var bondDur = fallback(d.bond_duration);
        if (bondDur !== '-' && bondDur !== null && bondDur !== undefined && String(bondDur).trim() !== '' && !isNaN(bondDur) && parseInt(bondDur) > 0) {
            $('#modal-detail-bond-duration').text(parseInt(bondDur) + ' Months');
        } else {
            $('#modal-detail-bond-duration').text('-');
        }

        $('#modal-detail-bond-end-date').text(fallback(d.bond_end_date, fallback(d.bond_end_date_display, '-')));

        var bondAmountText = '-';
        var idrVal = d.bond_amount_idr !== undefined && d.bond_amount_idr !== null ? d.bond_amount_idr : d.bond_amount;
        var hasIdr = idrVal !== '-' && idrVal !== null && idrVal !== undefined && idrVal !== '' && !isNaN(idrVal) && parseFloat(idrVal) > 0;
        var usdVal = d.bond_amount_usd;
        var hasUsd = usdVal !== '-' && usdVal !== null && usdVal !== undefined && usdVal !== '' && !isNaN(usdVal) && parseFloat(usdVal) > 0;

        if (hasUsd) {
            bondAmountText = '$' + parseFloat(usdVal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else if (hasIdr) {
            bondAmountText = 'Rp ' + Math.round(parseFloat(idrVal)).toLocaleString('id-ID');
        } else if (d.bond_amount_formatted && d.bond_amount_formatted !== '-') {
            bondAmountText = d.bond_amount_formatted;
        }
        $('#modal-detail-bond-amount').text(bondAmountText);
    }

    $(document).on('click', '[data-bs-target="#recordDetailModal"], .btn-record-detail', function(e) {
        var $el = $(this);
        var recordId = $el.data('record-id');
        var source = ($el.data('record-source') || 'offline').toString().toLowerCase();

        // 1. Try finding local JSON element
        var dataEl = document.getElementById('record-data-' + recordId)
                  || document.getElementById('record-offline-data-' + recordId)
                  || document.getElementById('record-online-data-' + recordId);

        if (dataEl) {
            try {
                var d = JSON.parse(dataEl.textContent);
                populateRecordDetailModal(d, source);
                return;
            } catch (err) {
                console.warn('Failed parsing local record JSON:', err);
            }
        }

        // 2. Fallback to AJAX endpoint
        if (recordId) {
            $('#record-detail-loading').show();
            $('#record-detail-content').hide();

            var url = '/my-learning/learning-record/detail/' + encodeURIComponent(source) + '/' + encodeURIComponent(recordId);
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    $('#record-detail-loading').hide();
                    $('#record-detail-content').show();
                    if (res && res.success && res.data) {
                        populateRecordDetailModal(res.data, source);
                    }
                },
                error: function() {
                    $('#record-detail-loading').hide();
                    $('#record-detail-content').show();
                }
            });
        }
    });
})();
</script>
