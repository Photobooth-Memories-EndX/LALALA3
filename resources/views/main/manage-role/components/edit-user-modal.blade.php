<div class="modal modal-blur fade"
     id="editUserModal{{ $user->id }}"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <form method="POST"
              action="{{ route('manage.role.update', $user->id) }}"
              class="modal-content">

            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-user-cog me-1"></i>
                    Edit User Organization
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="card bg-body border mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-md me-3">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>

                            <div>
                                <div class="fw-bold">{{ $user->name }}</div>
                                <div class="text-muted small">{{ $user->email }}</div>
                                <div class="text-muted small">
                                    NIP: {{ $user->id_emp ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            @foreach ($assignableRoles as $value => $label)
                                <option value="{{ $value }}"
                                    {{ (string) $user->role === (string) $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Division</label>
                        <select name="division" class="form-select">
                            <option value="">- Pilih Division -</option>
                            @foreach ($divisions as $item)
                                <option value="{{ $item }}" {{ $user->division == $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="">- Pilih Department -</option>
                            @foreach ($departments as $item)
                                <option value="{{ $item }}" {{ $user->department == $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Team</label>
                        <select name="team" class="form-select">
                            <option value="">- Pilih Team -</option>
                            @foreach ($teams as $item)
                                <option value="{{ $item }}" {{ $user->team == $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Organization Unit</label>
                        <input type="text"
                               name="id_org_unit"
                               value="{{ $user->id_org_unit }}"
                               class="form-control"
                               placeholder="Contoh: ORG001">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-link link-secondary"
                        data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit"
                        class="btn btn-primary"
                        onclick="return confirm('Simpan perubahan {{ $user->name }}?')">
                    <i class="ti ti-device-floppy me-1"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
