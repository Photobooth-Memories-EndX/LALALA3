@props(['row'])

{{-- Kolom Certificate — Hanya menampilkan icon jika ready, dan tanda minus (-) jika belum ready --}}
@if ($row['cert_state'] === 'ready')
    <div class="d-flex justify-content-center">
        @if ($row['cert_is_form'])
            <form method="POST" action="{{ $row['cert_action_url'] }}" class="d-inline-flex m-0">
                @csrf
                <button type="submit"
                        class="btn btn-learning-certificate"
                        title="Generate Certificate"
                        aria-label="Generate Certificate">
                    <i class="ti ti-certificate" aria-hidden="true"></i>
                </button>
            </form>
        @else
            <a href="{{ $row['cert_action_url'] }}"
               class="btn btn-learning-certificate"
               title="Generate Certificate"
               aria-label="Generate Certificate">
                <i class="ti ti-certificate" aria-hidden="true"></i>
            </a>
        @endif
    </div>
@else
    <div class="text-center text-muted">-</div>
@endif
