<div class="table-responsive">
    <table class="table table-vcenter align-middle online-classification-table">
        <thead>
            <tr>
                <th style="width: 60px;">No</th>
                <th>Classification</th>
                <th class="text-center">Content</th>
                <th class="text-center">Total Learning</th>
                <th class="text-center">Learning Hours</th>
                <th class="text-center">Ongoing</th>
                <th class="text-center">Finished</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($classification as $list)
                <tr>
                    <td>{{ $list->no ?? $loop->iteration }}</td>
                    <td>
                        @if($list->slug)
                            <a href="{{ route('manage.record.online.classification', $list->slug) }}" class="fw-bold text-primary text-decoration-underline online-classification-link" data-slug="{{ $list->slug }}">
                                {{ $list->classification_name }}
                            </a>
                        @else
                            <span class="fw-semibold">{{ $list->classification_name }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format((int) ($list->content_total ?? 0)) }}</td>
                    <td class="text-center fw-semibold">{{ number_format((int) ($list->total_learning ?? 0)) }}</td>
                    <td class="text-center fw-semibold">{{ ((float) ($list->learning_hours ?? 0) == 0 ? '0.0' : (fmod((float) ($list->learning_hours ?? 0), 1) == 0 ? (int) ($list->learning_hours ?? 0) : round((float) ($list->learning_hours ?? 0), 1))) }} Hours</td>
                    <td class="text-center text-warning fw-semibold">{{ number_format((int) ($list->ongoing_learning ?? 0)) }}</td>
                    <td class="text-center text-success fw-semibold">{{ number_format((int) ($list->finished_learning ?? 0)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No classification data found.</td>
                </tr>
            @endforelse
        </tbody>
        @if(isset($classification) && $classification->isNotEmpty())
        @php
            $gtContent = (int) ($grandTotal['content_total'] ?? $grandTotal['content'] ?? $classification->sum('content_total'));
            $gtTotal = (int) ($grandTotal['total_learning'] ?? $classification->sum('total_learning'));
            $gtHours = (float) ($grandTotal['learning_hours'] ?? round($classification->sum('learning_hours'), 1));
            $gtOngoing = (int) ($grandTotal['ongoing_learning'] ?? $grandTotal['ongoing'] ?? $classification->sum('ongoing_learning'));
            $gtFinished = (int) ($grandTotal['finished_learning'] ?? $grandTotal['finished'] ?? $classification->sum('finished_learning'));
        @endphp
        <tfoot class="online-summary-grand-total">
            <tr>
                <td class="text-center fw-bold text-dark"></td>
                <td class="fw-bold text-dark text-uppercase">
                    <strong>GRAND TOTAL</strong>
                </td>
                <td class="text-center fw-bold text-dark">{{ number_format($gtContent) }}</td>
                <td class="text-center fw-bold text-dark">{{ number_format($gtTotal) }}</td>
                <td class="text-center fw-bold text-dark">{{ ($gtHours == 0 ? '0.0' : (fmod($gtHours, 1) == 0 ? (int) $gtHours : round($gtHours, 1))) }} Hours</td>
                <td class="text-center text-warning fw-bold">{{ number_format($gtOngoing) }}</td>
                <td class="text-center text-success fw-bold">{{ number_format($gtFinished) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<style>
    .online-summary-grand-total tr {
        background-color: #f0f7fc !important;
        border-top: 2px solid #93c5fd !important;
        border-bottom: 2px solid #cbd5e1 !important;
    }
    .online-summary-grand-total td {
        padding-top: 12px !important;
        padding-bottom: 12px !important;
        font-size: 0.95rem;
    }
    [data-bs-theme="dark"] .online-summary-grand-total tr,
    body.dark-mode .online-summary-grand-total tr,
    body.theme-dark .online-summary-grand-total tr {
        background-color: rgba(30, 58, 95, 0.4) !important;
        border-top: 2px solid #1d4ed8 !important;
        border-bottom: 2px solid #334155 !important;
    }
    [data-bs-theme="dark"] .online-summary-grand-total .text-dark,
    body.dark-mode .online-summary-grand-total .text-dark,
    body.theme-dark .online-summary-grand-total .text-dark {
        color: #f1f5f9 !important;
    }
</style>
