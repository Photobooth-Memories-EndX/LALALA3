@props(['row'])

{{-- Kolom Certificate — satu aksi tunggal: Generate Certificate tampil selama
     seluruh syarat terpenuhi (walau sertifikat sudah pernah dibuat — backend
     memakai firstOrCreate lalu redirect ke /certificate/{credential_id},
     jadi tidak pernah menduplikasi record). Selain itu kolom dibiarkan kosong;
     detail kekurangan syarat sudah terlihat di kolom Progress dan Score. --}}

@if ($row['cert_state'] === 'ready')
    @if ($row['cert_is_form'])
        <form method="POST" action="{{ $row['cert_action_url'] }}">
            @csrf
            <button class="btn btn-primary btn-sm w-100 learning-record-cert-btn">
                <i class="ti ti-certificate me-1"></i>
                Generate Certificate
            </button>
        </form>
    @else
        <a href="{{ $row['cert_action_url'] }}"
           class="btn btn-primary btn-sm w-100 learning-record-cert-btn">
            <i class="ti ti-certificate me-1"></i>
            Generate Certificate
        </a>
    @endif
@endif
