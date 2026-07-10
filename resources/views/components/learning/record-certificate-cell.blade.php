@props(['row'])

{{-- Kolom Certificate — urutan pengecekan sama seperti sebelumnya:
     issued -> progress kurang -> score kurang -> semua syarat terpenuhi. --}}

@if ($row['cert_state'] === 'issued')
    <a href="{{ route('certificate.download', $row['cert_credential']) }}"
       class="btn btn-success btn-sm w-100">
        <i class="ti ti-download me-1"></i>
        Download
    </a>
@elseif ((int) $row['progress'] < (int) $row['min_progress'])
    <span class="badge bg-red-lt text-red w-100">
        Need Progress {{ $row['min_progress'] }}%
    </span>
@elseif ($row['min_score'] > 0 && is_null($row['score']))
    <span class="badge bg-red-lt text-red w-100">
        Need Assessment
    </span>
@elseif ($row['min_score'] > 0 && (float) $row['score'] < (float) $row['min_score'])
    <span class="badge bg-red-lt text-red w-100">
        Need Score {{ $row['min_score'] }}
    </span>
@elseif ($row['cert_is_form'])
    <form method="POST" action="{{ $row['cert_action_url'] }}">
        @csrf
        <button class="btn btn-primary btn-sm w-100">
            <i class="ti ti-certificate me-1"></i>
            Generate Certificate
        </button>
    </form>
@else
    <a href="{{ $row['cert_action_url'] }}"
       class="btn btn-primary btn-sm w-100">
        <i class="ti ti-certificate me-1"></i>
        Generate Certificate
    </a>
@endif
