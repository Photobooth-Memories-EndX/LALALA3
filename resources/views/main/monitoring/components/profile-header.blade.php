{{-- Props: $profile --}}
@php
    $roleName = \App\Models\User::roles()[$profile->role] ?? '-';
@endphp
<div class="card owl-main-card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="avatar avatar-xl bg-primary-lt">
                    {{ $profile->name ? $profile->initials : 'U' }}
                </span>
            </div>
            <div class="col">
                <h2 class="mb-1">{{ $profile->name ?? '-' }}</h2>
                <div class="text-muted mb-2">
                    <i class="fas fa-envelope mr-1"></i> {{ $profile->email ?? '-' }}
                </div>
                <div class="d-flex flex-wrap" style="gap:.5rem;">
                    <span class="badge bg-blue-lt text-blue">NIP: {{ $profile->id_emp ?: '-' }}</span>
                    <span class="badge bg-azure-lt text-azure">Division: {{ $profile->division ?: '-' }}</span>
                    <span class="badge bg-cyan-lt text-cyan">Department: {{ $profile->department ?: '-' }}</span>
                    <span class="badge bg-purple-lt text-purple">Team: {{ $profile->team ?: '-' }}</span>
                    <span class="badge bg-green-lt text-green">Role: {{ $roleName }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
