<table class="tb">
    <thead>
        <tr>
            <th>No</th>
            <th>Question</th>
            <th>Options</th>
            <th>Answer</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($assesment_question as $list)
            <tr>
                <td>{{ $loop->index + 1 }}</td>
                <td>{{ $list->question }}</td>
                <td>{!! implode(' | ', $list->option) !!}</td>
                <td>{{ $list->answer }}</td>
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
