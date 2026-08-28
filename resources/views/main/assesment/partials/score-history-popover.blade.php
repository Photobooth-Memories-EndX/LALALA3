@props(['histories' => collect()])

<div class="score-history-popover-card">
    @if ($histories && $histories->count() > 0)
        <div class="score-history-table-responsive" style="max-height: 260px; overflow-y: auto;">
            <table class="table table-sm table-borderless table-striped mb-0 score-history-table align-middle">
                <thead>
                    <tr class="border-bottom text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th class="text-center py-1 px-2" style="width: 35px;">No</th>
                        <th class="text-start py-1 px-2">Score</th>
                        <th class="text-start py-1 px-2">Changed By</th>
                        <th class="text-start py-1 px-2">Date</th>
                    </tr>
                </thead>
                <tbody style="font-size: 11.5px;">
                    @foreach ($histories as $index => $history)
                        <tr>
                            <td class="text-center text-muted fw-semibold py-1 px-2">{{ $loop->iteration }}</td>
                            <td class="text-start py-1 px-2 text-nowrap">
                                <span class="text-secondary">{{ $history->previous_score ?? '-' }}</span>
                                <i class="fas fa-arrow-right mx-1 text-muted small" style="font-size: 9px;"></i>
                                <span class="fw-bold text-primary">{{ $history->new_score }}</span>
                            </td>
                            <td class="text-start py-1 px-2 text-dark fw-medium" style="max-width: 140px; word-break: break-word;">
                                {{ $history->changedBy->name ?? 'System' }}
                            </td>
                            <td class="text-start text-muted py-1 px-2 text-nowrap">
                                {{ $history->created_at ? $history->created_at->format('d M Y H:i') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-muted small text-center py-2">No score changes yet.</div>
    @endif
</div>
