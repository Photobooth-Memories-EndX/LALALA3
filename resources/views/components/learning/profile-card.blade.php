@props([
    'user' => null,
])

@php
    $name = $user->name ?? 'User';
    $initial = strtoupper(substr($name, 0, 1));
@endphp

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
                <div class="fw-semibold">
                    {{ $user->position ?? $user->job_title ?? '-' }}
                </div>

                <a href="mailto:{{ $user->email ?? '' }}" class="small text-primary">
                    {{ $user->email ?? '-' }}
                </a>
            </div>
        </div>
    </div>
</div>