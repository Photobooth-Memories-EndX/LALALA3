<div class="table-responsive">
    <table class="table table-vcenter table-hover align-middle mb-0">
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
                        <span class="badge bg-amber-lt px-2.5 py-1 fw-semibold" style="color: #b45309 !important;">{{ number_format((int) ($list->non_kp_owl ?? 0)) }}</span>
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
    </table>
</div>
