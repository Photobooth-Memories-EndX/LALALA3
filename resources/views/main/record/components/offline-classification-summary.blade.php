<div class="table-responsive">
    <table class="table table-vcenter table-hover align-middle mb-0 offline-classification-table">
        <thead class="bg-light text-muted small text-uppercase">
            <tr>
                <th style="width: 50px;">No</th>
                <th>Classification</th>
                <th class="text-center">Programs</th>
                <th class="text-center">Total Participants</th>
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
                        <a href="{{ route('manage.record.offline.classification', $list->slug) }}" class="fw-bold text-primary text-decoration-underline offline-classification-link" data-slug="{{ $list->slug }}">
                            {{ $list->classification_name }}
                        </a>
                    </td>
                    <td class="text-center fw-semibold text-dark">{{ number_format((int) ($list->programs ?? 0)) }}</td>
                    <td class="text-center fw-semibold text-dark">{{ number_format((int) ($list->total_participants ?? 0)) }}</td>
                    <td class="text-center fw-semibold text-dark">{{ ((float) ($list->learning_hours ?? 0) == 0 ? '0.0' : (fmod((float) ($list->learning_hours ?? 0), 1) == 0 ? (int) ($list->learning_hours ?? 0) : round((float) ($list->learning_hours ?? 0), 2))) }} Hours</td>
                    <td class="text-center text-warning fw-semibold">{{ number_format((int) ($list->ongoing ?? 0)) }}</td>
                    <td class="text-center text-success fw-semibold">{{ number_format((int) ($list->finished ?? 0)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No offline training classifications found.</td>
                </tr>
            @endforelse
        </tbody>
        @if(isset($classification) && $classification->isNotEmpty())
        @php
            $gtPrograms = (int) ($grandTotal['programs'] ?? $classification->sum('programs'));
            $gtParticipants = (int) ($grandTotal['total_participants'] ?? $classification->sum('total_participants'));
            $gtHours = (float) ($grandTotal['learning_hours'] ?? round($classification->sum('learning_hours'), 2));
            $gtOngoing = (int) ($grandTotal['ongoing'] ?? $classification->sum('ongoing'));
            $gtFinished = (int) ($grandTotal['finished'] ?? $classification->sum('finished'));
        @endphp
        <tfoot class="offline-summary-grand-total">
            <tr>
                <td class="text-center fw-bold text-dark"></td>
                <td class="fw-bold text-dark text-uppercase">
                    <strong>GRAND TOTAL</strong>
                </td>
                <td class="text-center fw-bold text-dark">{{ number_format($gtPrograms) }}</td>
                <td class="text-center fw-bold text-dark">{{ number_format($gtParticipants) }}</td>
                <td class="text-center fw-bold text-dark">{{ ($gtHours == 0 ? '0.0' : (fmod($gtHours, 1) == 0 ? (int) $gtHours : round($gtHours, 2))) }} Hours</td>
                <td class="text-center text-warning fw-bold">{{ number_format($gtOngoing) }}</td>
                <td class="text-center text-success fw-bold">{{ number_format($gtFinished) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<style>
    .offline-summary-grand-total tr {
        background-color: #f0f7fc !important;
        border-top: 2px solid #93c5fd !important;
        border-bottom: 2px solid #cbd5e1 !important;
    }
    .offline-summary-grand-total td {
        padding-top: 12px !important;
        padding-bottom: 12px !important;
        font-size: 0.95rem;
    }
    [data-bs-theme="dark"] .offline-summary-grand-total tr,
    body.dark-mode .offline-summary-grand-total tr,
    body.theme-dark .offline-summary-grand-total tr {
        background-color: rgba(30, 58, 95, 0.4) !important;
        border-top: 2px solid #1d4ed8 !important;
        border-bottom: 2px solid #334155 !important;
    }
    [data-bs-theme="dark"] .offline-summary-grand-total .text-dark,
    body.dark-mode .offline-summary-grand-total .text-dark,
    body.theme-dark .offline-summary-grand-total .text-dark {
        color: #f1f5f9 !important;
    }
</style>
