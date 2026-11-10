<div class="modal modal-blur fade"
     id="editUserModal{{ $user->id }}"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form method="POST"
              action="{{ route('manage.role.update', $user->id) }}"
              class="modal-content rounded-4 border-0 shadow">

            @csrf
            @method('PUT')

            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="ti ti-user-cog text-primary"></i>
                    Edit User Organization
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                {{-- User Read-Only Identity Card --}}
                <div class="card bg-body-tertiary border mb-3 rounded-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-md rounded-circle me-3 bg-primary text-white fw-bold">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>

                                <div>
                                    <div class="fw-bold text-dark">{{ $user->name }}</div>
                                    <div class="text-muted small">{{ $user->email }}</div>
                                    <div class="text-muted extra-small" style="font-size: 0.78rem;">
                                        NIP / Employee ID: <span class="fw-semibold text-dark">{{ $user->id_emp ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <span class="badge bg-secondary-subtle text-secondary border">
                                <i class="ti ti-database me-1"></i> Data HR POSCO
                            </span>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                    <i class="ti ti-info-circle fs-3 flex-shrink-0 text-info"></i>
                    <div>
                        Field identitas dasar disinkronkan dari HR Gateway POSCO. Form ini khusus mengelola kewenangan <strong>Role</strong>, status <strong>Akses LMS</strong>, dan penugasan organisasi internal OWL.
                    </div>
                </div>

                <div class="row g-3">
                    {{-- 1. Role (OWL Internal) --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">
                            Role OWL <span class="text-danger">*</span>
                        </label>
                        <select name="role" class="form-select rounded-3" required>
                            @foreach ($assignableRoles as $value => $label)
                                <option value="{{ $value }}"
                                    {{ (string) $user->role === (string) $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 2. Status Aktif LMS (OWL Internal) --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">
                            Status Akses LMS OWL
                        </label>
                        <select name="active" class="form-select rounded-3">
                            <option value="1" {{ (string) $user->active === '1' ? 'selected' : '' }}>1 - Aktif (Dapat Login & Akses LMS)</option>
                            <option value="0" {{ (string) $user->active === '0' ? 'selected' : '' }}>0 - Nonaktif</option>
                        </select>
                    </div>

                    {{-- 3. Division --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">Division</label>
                        <select name="division" class="form-select rounded-3">
                            <option value="">- Pilih Division -</option>
                            @foreach ($divisions as $item)
                                <option value="{{ $item }}" {{ $user->division == $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 4. Department --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">Department</label>
                        <select name="department" class="form-select rounded-3">
                            <option value="">- Pilih Department -</option>
                            @foreach ($departments as $item)
                                <option value="{{ $item }}" {{ $user->department == $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 5. Team --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">Team</label>
                        <select name="team" class="form-select rounded-3">
                            <option value="">- Pilih Team -</option>
                            @foreach ($teams as $item)
                                <option value="{{ $item }}" {{ $user->team == $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 6. Organization Unit --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-dark">Organization Unit (ID)</label>
                        <input type="text"
                               name="id_org_unit"
                               value="{{ $user->id_org_unit }}"
                               class="form-control rounded-3"
                               placeholder="Contoh: ORG001"
                               maxlength="20">
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top pt-3">
                <button type="button"
                        class="btn btn-outline-secondary px-3"
                        data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit"
                        class="btn btn-primary px-4"
                        style="background-color: #003b6d; border-color: #003b6d;"
                        onclick="return confirm('Simpan perubahan data {{ $user->name }}?')">
                    <i class="ti ti-device-floppy me-1"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>