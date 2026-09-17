<table class="tb">
    <thead>
        <tr>
            <th>#</th>
            <th>Status</th>
            <th>Employee ID</th>
            <th>Name</th>
            <th>Department</th>
            <th>Team</th>
            <th>Title</th>
            <th>Type</th>
            <th>Classification</th>
            <th>Category</th>
            <th>Duration</th>
            <th>Date</th>
            <th>Progress</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($content as $list)
            <tr>
                <td>{{ $loop->index + 1 }}</td>
                <td>{{ $list->status ?? '-' }}</td>
                <td>{{ $list->user_id_emp ?? ($list->user->id_emp ?? '-') }}</td>
                <td>{{ $list->user_name ?? ($list->user->name ?? '-') }}</td>
                <td>{{ $list->user_department ?? ($list->user->department ?? '-') }}</td>
                <td>{{ $list->user_team ?? ($list->user->team ?? '-') }}</td>
                <td>
                    {{ $list->content_title ?? ($list->content->title ?? '-') }}
                    @if (!empty($list->playlist_title))
                        (Playlist: {{ $list->playlist_title }})
                    @endif
                </td>
                @php
                    $rawType = (string) ($list->content_type ?? ($list->content->type ?? ''));
                    $mappedType = in_array(strtolower($rawType), ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';
                @endphp
                <td>{{ $mappedType }}</td>
                <td>{{ $list->classification_name ?? ($list->content->classification->classification_name ?? '-') }}</td>
                <td>{{ $list->category_source ?? 'All Content' }}</td>
                <td>{{ \App\Support\DurationFormatter::format($list->content_duration ?? ($list->content->duration ?? null)) }}</td>
                <td>{{ $list->created_at }}</td>
                <td>{{ $list->progress ?? 0 }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>
<style>
    .tb {
        border-collapse: collapse;
    }

    .tb tr td {
        border: 1px solid black;
    }

    .tb tr th {
        border: 1px solid black;
    }
</style>
