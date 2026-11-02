<div class="table-responsive">
    <table class="table table-vcenter align-middle">
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
                    <td class="text-center fw-semibold">{{ (fmod((float) ($list->learning_hours ?? 0), 1) == 0 ? (int) ($list->learning_hours ?? 0) : round((float) ($list->learning_hours ?? 0), 1)) }} Hours</td>
                    <td class="text-center text-warning fw-semibold">{{ number_format((int) ($list->ongoing_learning ?? 0)) }}</td>
                    <td class="text-center text-success fw-semibold">{{ number_format((int) ($list->finished_learning ?? 0)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No classification data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
