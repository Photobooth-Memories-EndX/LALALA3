@props(['row'])

@if (is_null($row['score']))
    <span class="badge bg-secondary-lt text-secondary learning-record-badge">-</span>

    @if ($row['min_score'] > 0 && $row['row_kind'] === 'content')
        <div class="small text-danger mt-1">Butuh score min {{ $row['min_score'] }}</div>
    @endif
@else
    @php
        $passed = $row['min_score'] <= 0 || $row['score'] >= $row['min_score'];
    @endphp

    <span class="badge {{ $passed ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }} learning-record-badge">{{ $row['score'] }}</span>

    @if ($row['min_score'] > 0)
        <div class="small text-muted mt-1">Min {{ $row['min_score'] }}</div>
    @endif
@endif
