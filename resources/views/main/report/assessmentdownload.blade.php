<table class="tb">
    <thead>
        <tr>
            <th>No</th>
            <th>ID</th>
            <th>Name</th>
            <th>Team</th>
            <th>Department</th>
            <th>Score</th>
            <th>Batch</th>
            <th>Result</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($assesment_result as $list)
            <tr>
                <td>{{ $loop->index + 1 }}</td>
                <td>{{ $list->user->id_emp }}</td>
                <td>{{ $list->user->name }}</td>
                <td>{{ $list->user->team }}</td>
                <td>{{ $list->user->department }}</td>
                <td class="text-center">{{ $list->score }}</td>
                <td class="text-center">{{ $list->batch }}</td>
                <td class="text-center">
                    @if ($list->pass)
                        <span class="badge bg-green">Pass</span>
                    @else
                        <span class="badge bg-red">Failed</span>
                    @endif
                </td>
                <td>
                    {{ date("d/m/y", strtotime($list->created_at)) }}
                </td>
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
