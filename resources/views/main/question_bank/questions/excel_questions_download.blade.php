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
        @foreach ($questions as $question)
            <tr>
                <td>{{ $loop->index + 1 }}</td>
                <td>{{ $question->question }}</td>
                <td>{!! htmlspecialchars(implode(' | ', $question->option)) !!}</td>
                <td>{{ $question->answer }}</td>
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
