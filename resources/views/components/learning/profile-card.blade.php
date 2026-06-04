@props([
    'user' => null,
    'learningHours' => 0,
])

@php
    $name = $user->name ?? 'User';
    $initial = strtoupper(substr($name, 0, 1));
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
        <div class="row align-items-center g-3">

            {{-- Avatar --}}
            <div class="col-auto">
                <span class="avatar avatar-lg rounded-circle bg-primary text-white">
                    {{ $initial }}
                </span>
            </div>

            {{-- Profile Info --}}
            <div class="col">
                <div class="fw-bold fs-3">
                    {{ $name }}
                </div>

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

            {{-- Right Section --}}
            <div class="col-md-auto text-md-end">

                <div class="fw-semibold">
                    {{ $user->position ?? $user->job_title ?? '-' }}
                </div>

                <a href="mailto:{{ $user->email ?? '' }}" class="small text-primary d-block">
                    {{ $user->email ?? '-' }}
                </a>

                <div class="mt-3">
                    <span class="badge bg-primary-lt text-primary px-3 py-2">
                        <i class="ti ti-clock me-1"></i>
                        {{ number_format($learningHours ?? 0, 1) }} Learning Hours
                    </span>
                </div>

            </div>

        </div>
    </div>
</div>