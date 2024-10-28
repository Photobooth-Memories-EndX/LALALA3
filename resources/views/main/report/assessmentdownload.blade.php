<table class="tb">
    <thead>
        <tr>
            <th style="text-align: center">No</th>
            <th style="text-align: center">ID</th>
            <th style="text-align: center">Name</th>
            <th style="text-align: center">Team</th>
            <th style="text-align: center">Department</th>
            <th style="text-align: center">Score</th>
            <th style="text-align: center">Batch</th>
            <th style="text-align: center">Result</th>
            <th style="text-align: center">Start Date Time</th>
            <th style="text-align: center">Finish Date Time</th>
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
                <td>{{ date("d/m/y H:i:s", strtotime($list->created_at)) }}</td>
                <td>{{ date("d/m/y H:i:s", strtotime($list->updated_at)) }}</td>
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
