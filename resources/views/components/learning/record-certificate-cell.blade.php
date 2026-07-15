@props(['row'])

{{-- Kolom Certificate — Hanya menampilkan icon jika ready, dan tanda minus (-) jika belum ready --}}
@if ($row['cert_state'] === 'ready')
    <div class="d-flex justify-content-center">
        @if ($row['cert_is_form'])
            <form method="POST" action="{{ $row['cert_action_url'] }}" class="w-100">
                @csrf
                <button class="btn btn-primary btn-sm px-2 learning-record-cert-btn" title="Generate Certificate">
                    <i class="ti ti-certificate fs-3"></i>
                </button>
            </form>
        @else
            <a href="{{ $row['cert_action_url'] }}"
               class="btn btn-primary btn-sm px-2 learning-record-cert-btn" 
               title="Generate Certificate">
                <i class="ti ti-certificate fs-3"></i>
            </a>
        @endif
    </div>
@else
    <div class="text-center text-muted">-</div>
@endif