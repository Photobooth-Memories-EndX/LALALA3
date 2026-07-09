@props([
    'user' => null,
    'learningHours' => 0,
])

@php
    $name = $user->name ?? 'User';
    $initial = strtoupper(substr($name, 0, 1));
    $position = $user->position ?? $user->job_title ?? null;
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

                    @if (!empty($position))
                        · {{ $position }}
                    @endif
                </div>

                <div class="mt-2">
                    <div class="text-primary small fw-semibold mb-2">
                        {{ $user->email ?? auth()->user()->email ?? '-' }}
                    </div>

                    <span class="badge bg-primary-lt text-primary">
                        <i class="ti ti-clock me-1"></i>
                        {{ $learningHours ?? 0 }} Learning Hours
                    </span>
                </div>
            </div>

        </div>
    </div>
</div>
