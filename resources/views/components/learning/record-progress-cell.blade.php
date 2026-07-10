@props(['row'])

<div class="learning-record-progress">
    <div class="percent">{{ (int) $row['progress'] }}%</div>

    @if ($row['min_progress'] > 0 && $row['row_kind'] === 'content')
        <div class="minimum{{ $row['progress'] < $row['min_progress'] ? ' is-failed' : '' }}">
            Min {{ $row['min_progress'] }}%
        </div>
    @endif
</div>
