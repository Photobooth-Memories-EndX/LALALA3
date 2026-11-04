<div class="table-responsive" style="max-height: 70vh; overflow-y: auto; position: relative;">
    <style>
        .all-classification-summary-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
            background-color: #f8fafc !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        [data-bs-theme="dark"] .all-classification-summary-table thead th,
        body.dark-mode .all-classification-summary-table thead th,
        body.theme-dark .all-classification-summary-table thead th {
            background-color: #1e293b !important;
            color: #94a3b8 !important;
        }

        /* Non KP OWL Badge */
        .badge-non-kp-owl {
            background-color: rgba(255, 152, 0, 0.12) !important;
            color: #ef8b00 !important;
            border: 1px solid rgba(255, 152, 0, 0.18) !important;
            border-radius: 5px !important;
            padding: 3px 7px !important;
            font-weight: 500 !important;
            display: inline-block !important;
            line-height: 1.2 !important;
        }

        [data-bs-theme="dark"] .badge-non-kp-owl,
        body.dark-mode .badge-non-kp-owl,
        body.theme-dark .badge-non-kp-owl {
            background-color: rgba(255, 152, 0, 0.18) !important;
            color: #ffb74d !important;
            border-color: rgba(255, 152, 0, 0.25) !important;
        }

        /* GRAND TOTAL Styling */
        .all-summary-grand-total {
            position: sticky !important;
            bottom: 0 !important;
            z-index: 10 !important;
        }
        .all-summary-grand-total tr {
            background-color: #f0f7fc !important;
            border-top: 2px solid #93c5fd !important;
            border-bottom: 2px solid #cbd5e1 !important;
        }
        .all-summary-grand-total td {
            background-color: #f0f7fc !important;
            padding-top: 12px !important;
            padding-bottom: 12px !important;
            font-size: 0.95rem;
        }
        [data-bs-theme="dark"] .all-summary-grand-total tr,
        body.dark-mode .all-summary-grand-total tr,
        body.theme-dark .all-summary-grand-total tr {
            background-color: #162a45 !important;
            border-top: 2px solid #1d4ed8 !important;
            border-bottom: 2px solid #334155 !important;
        }
        [data-bs-theme="dark"] .all-summary-grand-total td,
        body.dark-mode .all-summary-grand-total td,
        body.theme-dark .all-summary-grand-total td {
            background-color: #162a45 !important;
        }
        [data-bs-theme="dark"] .all-summary-grand-total .text-dark,
        body.dark-mode .all-summary-grand-total .text-dark,
        body.theme-dark .all-summary-grand-total .text-dark {
            color: #f1f5f9 !important;
        }
    </style>
    <table class="table table-vcenter table-hover align-middle mb-0 all-classification-summary-table">
        <thead class="bg-light text-muted small text-uppercase">
            <tr>
                <th style="width: 50px;">No</th>
                <th>Classification</th>
                <th class="text-center" style="color: #0284c7;">KP OWL</th>
                <th class="text-center" style="color: #d97706;">Non KP OWL</th>
                <th class="text-center">Total Learning</th>
                <th class="text-center">Learning Hours</th>
                <th class="text-center text-warning">Ongoing</th>
                <th class="text-center text-success">Finished</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($classification as $list)
                <tr>
                    <td class="text-muted fw-semibold">{{ $list->no ?? $loop->iteration }}</td>
                    <td>
                        <a href="{{ route('manage.record.all.classification', $list->slug ?? (\Illuminate\Support\Str::slug($list->classification_name) ?: 'unclassified')) }}"
                           class="fw-bold text-primary text-decoration-underline all-classification-link"
                           data-slug="{{ $list->slug ?? (\Illuminate\Support\Str::slug($list->classification_name) ?: 'unclassified') }}">
                            {{ $list->classification_name }}
                        </a>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-blue-lt px-2.5 py-1 fw-semibold">{{ number_format((int) ($list->kp_owl ?? 0)) }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge-non-kp-owl">{{ number_format((int) ($list->non_kp_owl ?? 0)) }}</span>
                    </td>
                    <td class="text-center fw-bold text-dark">{{ number_format((int) ($list->total_learning ?? 0)) }}</td>
                    <td class="text-center fw-semibold text-dark">{{ number_format((float) ($list->learning_hours ?? 0), 2) }} Hours</td>
                    <td class="text-center text-warning fw-semibold">{{ number_format((int) ($list->ongoing ?? 0)) }}</td>
                    <td class="text-center text-success fw-semibold">{{ number_format((int) ($list->finished ?? 0)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No classification data found.</td>
                </tr>
            @endforelse
        </tbody>
        @if(isset($classification) && $classification->isNotEmpty())
        @php
            $gtKpOwl = (int) ($grandTotal['kp_owl'] ?? $classification->sum('kp_owl'));
            $gtNonKpOwl = (int) ($grandTotal['non_kp_owl'] ?? $classification->sum('non_kp_owl'));
            $gtTotalLearning = (int) ($grandTotal['total_learning'] ?? $classification->sum('total_learning'));
            $gtHours = (float) ($grandTotal['learning_hours'] ?? round($classification->sum('learning_hours'), 2));
            $gtOngoing = (int) ($grandTotal['ongoing'] ?? $classification->sum('ongoing'));
            $gtFinished = (int) ($grandTotal['finished'] ?? $classification->sum('finished'));
        @endphp
        <tfoot class="all-summary-grand-total">
            <tr>
                <td class="text-center fw-bold text-dark"></td>
                <td class="fw-bold text-dark text-uppercase">
                    <strong>GRAND TOTAL</strong>
                </td>
                <td class="text-center fw-bold" style="color: #0284c7;">{{ number_format($gtKpOwl) }}</td>
                <td class="text-center fw-bold" style="color: #d97706;">{{ number_format($gtNonKpOwl) }}</td>
                <td class="text-center fw-bold text-dark">{{ number_format($gtTotalLearning) }}</td>
                <td class="text-center fw-bold text-dark">{{ ($gtHours == 0 ? '0.00' : (fmod($gtHours, 1) == 0 ? (int) $gtHours : number_format($gtHours, 2))) }} Hours</td>
                <td class="text-center text-warning fw-bold">{{ number_format($gtOngoing) }}</td>
                <td class="text-center text-success fw-bold">{{ number_format($gtFinished) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
