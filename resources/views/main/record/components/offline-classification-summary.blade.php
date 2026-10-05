<div class="table-responsive">
    <table class="table table-vcenter table-hover align-middle mb-0">
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
                    <td class="text-center fw-semibold text-dark">{{ number_format((float) ($list->learning_hours ?? 0), 2) }} Hours</td>
                    <td class="text-center text-warning fw-semibold">{{ number_format((int) ($list->ongoing ?? 0)) }}</td>
                    <td class="text-center text-success fw-semibold">{{ number_format((int) ($list->finished ?? 0)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No offline training classifications found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
