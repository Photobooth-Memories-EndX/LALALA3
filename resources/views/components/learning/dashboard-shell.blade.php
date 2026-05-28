@props([
    'active' => 'ongoing',
    'title' => 'Learning Dashboard',
    'subtitle' => null,
])

@php
    $user = Auth::user();
    $name = $user->name ?? 'User';
    $initial = strtoupper(substr($name, 0, 1));

    $tabs = [
        [
            'key' => 'ongoing',
            'label' => 'Learning On Going',
            'url' => route('user.learning.dashboard'),
        ],
        [
            'key' => 'record',
            'label' => 'Learning Record',
            'url' => route('user.learning.dashboard'),
        ],
        [
            'key' => 'assessment',
            'label' => 'Assessment',
            'url' => route('user.learning.dashboard'),
        ],
        [
            'key' => 'claim',
            'label' => 'Klaim Learning Hour',
            'url' => Route::has('learning.claim') ? route('learning.claim') : '#',
        ],
        [
            'key' => 'certificate',
            'label' => 'Sertifikat',
            'url' => Route::has('learning.certificate') ? route('learning.certificate') : '#',
        ],
        [
            'key' => 'monitoring',
            'label' => 'Monitoring',
            'url' => Route::has('learning.monitoring') ? route('learning.monitoring') : '#',
        ],
    ];
@endphp

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title mb-1">{{ $title }}</h2>
                @if ($subtitle)
                    <div class="text-muted">{{ $subtitle }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="page-body mt-0">
    <div class="container-xl">

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <span class="avatar avatar-lg rounded-circle bg-primary text-white">
                            {{ $initial }}
                        </span>
                    </div>

                    <div class="col">
                        <div class="fw-bold fs-3">{{ $name }}</div>
                        <div class="text-muted small">
                            {{ $user->id_emp ?? '-' }}
                            @if (!empty($user->team))
                                · {{ $user->team }}
                            @endif
                        </div>
                        <div class="text-muted small">
                            {{ $user->department ?? '-' }}
                        </div>
                    </div>

                    <div class="col-md-auto text-md-end">
                        <div class="fw-semibold">{{ $user->position ?? $user->job_title ?? '-' }}</div>
                        <a href="mailto:{{ $user->email ?? '' }}" class="small text-primary">
                            {{ $user->email ?? '-' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($tabs as $tab)
                        <a href="{{ $tab['url'] }}"
                           class="btn {{ $active === $tab['key'] ? 'btn-primary' : 'btn-white' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{ $slot }}

    </div>
</div>