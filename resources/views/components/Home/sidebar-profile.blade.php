<div class="home-modern-card">

    {{-- User Info --}}
    <div class="home-modern-card-header">
        {{-- <div class="d-flex align-items-center gap-3 w-100">
            <span class="avatar avatar-md rounded bg-primary-lt text-primary fw-bold flex-shrink-0">
                {{ strtoupper(substr(auth()->user()->initials ?? auth()->user()->name ?? 'U', 0, 2)) }}
            </span>
            <div style="min-width:0">
                <div class="fw-semibold text-truncate" style="max-width:170px; font-size:14px">
                    {{ ucwords(strtolower(auth()->user()->name ?? '')) }}
                </div>
                <div class="text-muted" style="font-size:12px">
                    {{ auth()->user()->team ?? 'No Team' }}
                </div>
            </div>
        </div> --}}
    </div>

    {{-- ApexCharts Donut --}}
    <div class="home-modern-card-body pt-0">

        <div id="chart-demo-pie" style="min-height:200px"></div>

        <div class="d-flex justify-content-center gap-4 mt-1">
            <div class="text-center">
                <div class="fw-bold fs-4 text-primary">{{ $ongoingCourse }}</div>
                <div class="text-muted" style="font-size:11px">On Going</div>
            </div>
            <div class="vr opacity-25"></div>
            <div class="text-center">
                <div class="fw-bold fs-4 text-success">{{ $completedCourse }}</div>
                <div class="text-muted" style="font-size:11px">Completed</div>
            </div>
        </div>

    </div>

</div>
