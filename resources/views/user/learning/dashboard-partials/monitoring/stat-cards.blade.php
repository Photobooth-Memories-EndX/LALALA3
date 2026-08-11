{{-- Monitoring Stat Cards --}}
<div class="row g-3 mb-4">
    @foreach ($cards as $card)
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md bg-{{ $card['color'] }}-lt text-{{ $card['color'] }} me-3">
                            <i class="ti {{ $card['icon'] }} fs-2"></i>
                        </div>

                        <div>
                            <div class="text-muted small">
                                {{ $card['label'] }}
                            </div>

                            <div class="h2 fw-bold mb-0">
                                {{ $card['value'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>