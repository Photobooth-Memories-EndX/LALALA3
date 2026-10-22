{{-- Shared Training Record Detail Modal (Used by KP OWL Online, Non KP OWL Offline, and All Record) --}}
@props(['record' => []])
@php
    $record = $record ?? [];
@endphp
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-file-text text-primary fs-2"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="recordDetailModalLabel">Training Record Detail</h5>
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
                    <!-- Course Title Header -->
                    <div class="border-bottom pb-3 mb-4">
                        <span class="badge bg-primary-subtle text-primary mb-2" id="modal-detail-source-badge">{{ $record['source'] ?? 'Training Record' }}</span>
                        <h4 class="fw-bold text-dark mb-0" id="modal-detail-course-name">{{ $record['title'] ?? ($record['course_name'] ?? '-') }}</h4>
                    </div>

                    <!-- Other Information Section -->
                    <div class="card border-0 bg-light-subtle rounded-3 p-3">
                        <h6 class="fw-bold text-uppercase text-muted text-xs mb-3">
                            <i class="ti ti-info-circle me-1"></i> Other Information
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
                                    {{ !empty($record['bond_amount_usd']) && (float) $record['bond_amount_usd'] > 0 ? '$' . number_format((float) $record['bond_amount_usd'], 2) : (!empty($record['bond_amount_idr']) && (float) $record['bond_amount_idr'] > 0 ? 'Rp ' . number_format((float) $record['bond_amount_idr'], 0, ',', '.') : (!empty($record['bond_amount']) && (float) $record['bond_amount'] > 0 ? 'Rp ' . number_format((float) $record['bond_amount'], 0, ',', '.') : '-')) }}
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

        var isOnline = (source === 'online' || (d.source && d.source === 'online') || (!d.offline_training_id && !d.batch && (d.category === 'All Content' || d.category === 'Content' || d.category === 'Playlist')));

        // Course Title & Source Header
        var title = fallback(d.title, fallback(d.course_name, '-'));
        $('#modal-detail-course-name').text(title);

        var sourceLabel = isOnline ? 'KP OWL' : (d.source ? d.source : 'Non KP OWL');
        $('#modal-detail-source-badge').text(sourceLabel);

        // 1. Delivery Method
        $('#modal-detail-delivery-method').text(normalizeDeliveryMethod(d.delivery_method, isOnline));

        // 2. Provider
        $('#modal-detail-provider').text(fallback(d.provider, isOnline ? 'Internal' : '-'));

        // 3. Provider Name
        $('#modal-detail-provider-name').text(fallback(d.provider_name, isOnline ? 'KP-OWL' : '-'));

        // 4. Venue
        $('#modal-detail-venue').text(fallback(d.venue, isOnline ? 'KP-OWL' : '-'));

        // 5. Bond Status
        $('#modal-detail-bond-status').text(fallback(d.bond_status, 'No'));

        // 6. Bond Duration
        var bondDur = fallback(d.bond_duration);
        if (bondDur !== '-' && bondDur !== null && bondDur !== undefined && String(bondDur).trim() !== '' && !isNaN(bondDur) && parseInt(bondDur) > 0) {
            $('#modal-detail-bond-duration').text(bondDur + ' Months');
        } else {
            $('#modal-detail-bond-duration').text('-');
        }

        // 7. Bond End Date
        $('#modal-detail-bond-end-date').text(fallback(d.bond_end_date, fallback(d.bond_end_date_display)));

        // 8. Bond Amount (IDR / USD)
        var bondAmountText = '-';
        var idrVal = d.bond_amount_idr !== undefined ? d.bond_amount_idr : d.bond_amount;
        var hasIdr = idrVal !== '-' && idrVal !== null && idrVal !== undefined && idrVal !== '' && !isNaN(idrVal) && parseFloat(idrVal) > 0;
        var usdVal = d.bond_amount_usd;
        var hasUsd = usdVal !== '-' && usdVal !== null && usdVal !== undefined && usdVal !== '' && !isNaN(usdVal) && parseFloat(usdVal) > 0;

        if (hasUsd && hasIdr) {
            bondAmountText = '$' + parseFloat(usdVal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' / Rp ' + parseFloat(idrVal).toLocaleString('id-ID');
        } else if (hasUsd) {
            bondAmountText = '$' + parseFloat(usdVal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else if (hasIdr) {
            bondAmountText = 'Rp ' + parseFloat(idrVal).toLocaleString('id-ID');
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
