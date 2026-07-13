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
            <th>Classification</th>
            <th>Duration</th>
            <th>Date</th>
            <th>Progress</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($content as $list)
            <tr>
                <td>{{ $loop->index + 1 }}</td>
                <td>{{ $list->status }}</td>
                <td>{{ $list->user->id_emp }}</td>
                <td>{{ $list->user->name }}</td>
                <td>{{ $list->user->department }}</td>
                <td>{{ $list->user->team }}</td>
                <td>{{ $list->content->title }}</td>
                <td>{{ $list->content->classification->classification_name ?? '' }}</td>
                <td>{{ \App\Support\DurationFormatter::format($list->content->duration ?? null) }}</td>
                <td>{{ $list->created_at }}</td>
                <td>{{ $list->progress }}%</td>
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
