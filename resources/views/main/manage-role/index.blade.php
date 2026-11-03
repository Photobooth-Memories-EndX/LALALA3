@extends('layouts.sidebar-master')

@section('content')

    {{-- Reuse design system (owl-* cards/table + dark mode) --}}
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-0 pb-0">
        <div class="container-xl">
            <!-- Back Navigation Link -->
            <a href="{{ route('manage.dashboard') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
                <i class="ti ti-arrow-left fs-2"></i> Back
            </a>

            <!-- Manage Role Hero Header Card -->
            <div class="card border border-light-subtle shadow-sm p-4 mb-3 rounded-3 bg-white hero-header-card">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box"
                             style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                            <i class="ti ti-user-check" style="font-size: 2rem !important;"></i>
                        </div>
                        <div>
                            <h2 class="h3 fw-bold mb-1 text-dark header-title">Manage Role</h2>
                            <p class="text-muted small mb-0 header-subtitle">Manage user roles, divisions, departments, teams, and organizational units.</p>
                        </div>
                    </div>

                    <!-- Synchronize Button (Dry-Run Simulation) -->
                    <div class="flex-shrink-0">
                        <button type="button"
                                class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm rounded-3"
                                style="background-color: #003b6d; border-color: #003b6d;"
                                data-bs-toggle="modal"
                                data-bs-target="#syncSimulationModal">
                            <i class="ti ti-refresh fs-2"></i>
                            <span class="fw-semibold">Synchronize</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body mt-0 pt-0">
        <div class="container-xl">

            <x-alert type="success" />
            <x-alert type="warning" />

            @if ($errors->any())
                <div class="alert alert-danger shadow-sm mb-3" role="alert">
                    {!! implode('', $errors->all('<div>:message</div>')) !!}
                </div>
            @endif

            @include('main.manage-role.components.role-nav-tabs')

            @include('main.manage-role.components.stat-cards')

            @include('main.manage-role.components.filter-card')

            @include('main.manage-role.components.user-table')

            {{-- Modal wajib di luar table/tbody agar markup tabel tidak pecah --}}
            @foreach ($users as $user)
                @if (!$user->isSuperAdmin() && $user->id !== auth()->id())
                    @include('main.manage-role.components.edit-user-modal', ['user' => $user])
                @endif
            @endforeach

            {{-- 1. Synchronize (Real & Dry-Run) Modal --}}
            <div class="modal fade" id="syncSimulationModal" tabindex="-1" aria-labelledby="syncSimulationModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="syncSimulationModalLabel">
                                <i class="ti ti-refresh text-primary"></i> Sinkronisasi Karyawan POSCO ke OWL
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCloseSyncModal"></button>
                        </div>

                        <div class="modal-body py-4" id="syncModalBody">
                            <!-- State 1: Confirmation -->
                            <div id="syncConfirmationState">
                                <div class="alert alert-primary d-flex align-items-start gap-3 rounded-3 mb-3">
                                    <i class="ti ti-refresh fs-1 text-primary flex-shrink-0 mt-1"></i>
                                    <div>
                                        <h5 class="fw-bold mb-1">Sinkronisasi Data Karyawan POSCO ke Database OWL</h5>
                                        <p class="mb-1 small">
                                            Proses ini akan membaca data terbaru dari <strong>API Gateway POSCO</strong> (<code>http://172.21.23.73</code>), memperbarui profil organisasi dan 18 kolom data HR ke tabel <code>users</code> OWL, serta membuat akun bagi karyawan baru yang belum terdaftar.
                                        </p>
                                        <p class="mb-0 small text-success fw-semibold">
                                            <i class="ti ti-shield-check me-1"></i> <strong>Proteksi Kredensial:</strong> Password lokal, role sistem, status aktivasi LMS (<code>active</code>), dan token autentikasi SSO terlindungi dan tidak akan ditimpa.
                                        </p>
                                    </div>
                                </div>

                                <div class="card border rounded-3 p-3 bg-light">
                                    <h6 class="fw-bold text-dark mb-2"><i class="ti ti-settings-cog me-1"></i> Mode Operasi Tersedia:</h6>
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <div class="border rounded-3 p-2 bg-white h-100">
                                                <div class="fw-bold text-primary small mb-1"><i class="ti ti-database-export me-1"></i> Sinkronisasi Nyata (Live)</div>
                                                <div class="text-muted extra-small">Menyimpan penambahan dan pembaruan data secara langsung ke tabel <code>users</code> database OWL.</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="border rounded-3 p-2 bg-white h-100">
                                                <div class="fw-bold text-secondary small mb-1"><i class="ti ti-eye me-1"></i> Uji Simulasi (Dry-Run)</div>
                                                <div class="text-muted extra-small">Hanya menganalisis perkiraan data tanpa menyimpan perubahan apa pun (100% Zero-Write).</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- State 2: Running / Loading -->
                            <div id="syncLoadingState" class="d-none text-center py-4">
                                <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem;" role="status">
                                    <span class="visually-hidden">Memproses...</span>
                                </div>
                                <h5 class="fw-bold text-dark mb-1" id="syncLoadingTitle">Menjalankan Sinkronisasi...</h5>
                                <p class="text-muted small mb-0" id="syncLoadingSubtitle">
                                    Menghubungi API Gateway POSCO dan memproses 2.700+ data karyawan. Harap tunggu beberapa detik...
                                </p>
                            </div>

                            <!-- State 3: Results -->
                            <div id="syncResultState" class="d-none">
                                <div class="alert alert-success d-flex align-items-center justify-content-between py-2 px-3 mb-3 rounded-3" id="syncAlertBox">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ti ti-check-circle fs-2 text-success"></i>
                                        <div class="fw-semibold small" id="syncResultSummary">
                                            Sinkronisasi berhasil diselesaikan!
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-success d-none" id="btnReloadTable" onclick="window.location.reload()">
                                        <i class="ti ti-refresh me-1"></i> Muat Ulang Halaman
                                    </button>
                                </div>

                                <div class="table-responsive border rounded-3 mb-3">
                                    <table class="table table-sm table-striped table-hover mb-0" style="font-size: 0.88rem;">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted fw-semibold" style="width: 50%;">Status Hasil</td>
                                                <td><span class="badge bg-success-lt text-success" id="resStatus">SUCCESS</span></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">Mode Eksekusi</td>
                                                <td><span class="badge" id="resMode">REAL (Synchronize Database)</span></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">Durasi Pemrosesan</td>
                                                <td class="fw-semibold text-dark" id="resDuration">-</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">Total Data Karyawan dari API</td>
                                                <td class="fw-bold text-dark" id="resTotalApi">-</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">Total User Terdaftar di Database OWL</td>
                                                <td class="fw-semibold text-dark" id="resTotalOwl">-</td>
                                            </tr>
                                            <tr class="table-info">
                                                <td class="text-dark fw-semibold">User Baru Berhasil Dibuat</td>
                                                <td class="fw-bold text-primary" id="resCreatedCount">-</td>
                                            </tr>
                                            <tr class="table-warning">
                                                <td class="text-dark fw-semibold">Profil User Berhasil Diperbarui</td>
                                                <td class="fw-bold text-warning-emphasis" id="resUpdatedCount">-</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted small ps-4">└ User Aktif yang Diperbarui</td>
                                                <td class="small text-dark" id="resUpdatedActiveCount">-</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted small ps-4">└ User Soft-Deleted yang Diperbarui</td>
                                                <td class="small text-muted" id="resUpdatedSoftDeletedCount">-</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">User yang Tidak Mengalami Perubahan</td>
                                                <td class="text-muted" id="resUnchangedCount">-</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted fw-semibold">Data Ditolak / Ditemukan Konflik</td>
                                                <td class="text-danger fw-semibold" id="resConflictCount">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-secondary py-2 px-3 small d-flex align-items-center gap-2 mb-0" id="syncFooterNote">
                                    <i class="ti ti-database-check text-success fs-3"></i>
                                    <div>
                                        <strong>Data Tersimpan Nyata:</strong> Seluruh 18 kolom atribut profil HR dan penugasan organisasi telah diperbarui ke tabel <code>users</code> OWL.
                                    </div>
                                </div>
                            </div>

                            <!-- State 4: Error -->
                            <div id="syncErrorState" class="d-none">
                                <div class="alert alert-danger d-flex align-items-start gap-2 rounded-3 mb-0">
                                    <i class="ti ti-alert-triangle fs-2 text-danger flex-shrink-0 mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Gagal Menjalankan Sinkronisasi</h6>
                                        <p class="mb-0 small" id="syncErrorMessage">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top pt-3 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" id="btnCancelSync">
                                Tutup
                            </button>
                            <div class="d-flex align-items-center gap-2" id="syncActionButtons">
                                <button type="button" class="btn btn-outline-info px-3" id="btnStartDryRun">
                                    <i class="ti ti-eye me-1"></i> Uji Simulasi (Dry-Run)
                                </button>
                                <button type="button" class="btn btn-primary px-4" id="btnStartRealSync" style="background-color: #003b6d; border-color: #003b6d;">
                                    <i class="ti ti-refresh me-1"></i> Sinkronkan Sekarang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Delete Confirmation Modal --}}
            <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form id="deleteUserForm" method="POST" action="" class="modal-content rounded-4 border-0 shadow">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="deleteUserModalLabel">
                                <i class="ti ti-alert-triangle"></i> Konfirmasi Hapus User
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-4 text-center">
                            <div class="mb-3">
                                <div class="avatar avatar-lg bg-danger-subtle text-danger rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                    <i class="ti ti-trash fs-1"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-1 text-dark" id="deleteUserName">Nama User</h4>
                            <div class="text-muted small mb-3">NIP / Employee ID: <span id="deleteUserEmp" class="fw-semibold text-dark">-</span></div>
                            <div class="alert alert-warning py-2 px-3 small text-start mb-0 rounded-3">
                                <i class="ti ti-info-circle me-1"></i> Akun akan diarsipkan secara aman (<strong>soft-deleted</strong>). User tidak dapat login ke sistem OWL namun riwayat data historis tetap terjaga.
                            </div>
                        </div>
                        <div class="modal-footer border-top pt-3">
                            <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger px-4">
                                <i class="ti ti-trash me-1"></i> Hapus User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('style')
<style>
    /* Hero header & Card styling */
    .hero-header-card {
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .filter-label-head {
        color: #1e293b;
    }

    /* Dark Mode Overrides */
    [data-bs-theme="dark"] .hero-header-card,
    [data-theme="dark"] .hero-header-card,
    body.theme-dark .hero-header-card,
    [data-bs-theme="dark"] .stat-card-item,
    [data-theme="dark"] .stat-card-item,
    body.theme-dark .stat-card-item,
    [data-bs-theme="dark"] .role-filter-card,
    [data-theme="dark"] .role-filter-card,
    body.theme-dark .role-filter-card,
    [data-bs-theme="dark"] .role-table-card,
    [data-theme="dark"] .role-table-card,
    body.theme-dark .role-table-card,
    [data-bs-theme="dark"] .role-nav-tabs-card,
    [data-theme="dark"] .role-nav-tabs-card,
    body.theme-dark .role-nav-tabs-card {
        background-color: #1e293b !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .hero-icon-box,
    [data-theme="dark"] .hero-icon-box,
    body.theme-dark .hero-icon-box {
        background-color: rgba(0, 59, 109, 0.35) !important;
        color: #60a5fa !important;
    }

    [data-bs-theme="dark"] .header-title,
    [data-theme="dark"] .header-title,
    body.theme-dark .header-title,
    [data-bs-theme="dark"] .card-title,
    [data-theme="dark"] .card-title,
    body.theme-dark .card-title,
    [data-bs-theme="dark"] .filter-label-head,
    [data-theme="dark"] .filter-label-head,
    body.theme-dark .filter-label-head {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .header-subtitle,
    [data-theme="dark"] .header-subtitle,
    body.theme-dark .header-subtitle {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #moreRoleFiltersModal .modal-content,
    [data-theme="dark"] #moreRoleFiltersModal .modal-content,
    body.theme-dark #moreRoleFiltersModal .modal-content,
    [data-bs-theme="dark"] #syncSimulationModal .modal-content,
    [data-theme="dark"] #syncSimulationModal .modal-content,
    body.theme-dark #syncSimulationModal .modal-content,
    [data-bs-theme="dark"] #deleteUserModal .modal-content,
    [data-theme="dark"] #deleteUserModal .modal-content,
    body.theme-dark #deleteUserModal .modal-content {
        background-color: var(--tblr-bg-surface, #1e293b) !important;
        color: #f8fafc !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
    }

    [data-bs-theme="dark"] #moreRoleFiltersModal .text-dark,
    [data-theme="dark"] #moreRoleFiltersModal .text-dark,
    body.theme-dark #moreRoleFiltersModal .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #moreRoleFiltersModal .modal-header,
    [data-bs-theme="dark"] #moreRoleFiltersModal .modal-footer,
    [data-theme="dark"] #moreRoleFiltersModal .modal-header,
    [data-theme="dark"] #moreRoleFiltersModal .modal-footer,
    body.theme-dark #moreRoleFiltersModal .modal-header,
    body.theme-dark #moreRoleFiltersModal .modal-footer {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #moreRoleFiltersModal .form-control,
    [data-bs-theme="dark"] #moreRoleFiltersModal .form-select,
    [data-bs-theme="dark"] .role-filter-card .form-control,
    [data-bs-theme="dark"] .role-filter-card .form-select,
    [data-theme="dark"] #moreRoleFiltersModal .form-control,
    [data-theme="dark"] #moreRoleFiltersModal .form-select,
    [data-theme="dark"] .role-filter-card .form-control,
    [data-theme="dark"] .role-filter-card .form-select,
    body.theme-dark #moreRoleFiltersModal .form-control,
    body.theme-dark #moreRoleFiltersModal .form-select,
    body.theme-dark .role-filter-card .form-control,
    body.theme-dark .role-filter-card .form-select {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .role-filter-card .form-select option,
    [data-bs-theme="dark"] #moreRoleFiltersModal .form-select option,
    [data-theme="dark"] .role-filter-card .form-select option,
    [data-theme="dark"] #moreRoleFiltersModal .form-select option,
    body.theme-dark .role-filter-card .form-select option,
    body.theme-dark #moreRoleFiltersModal .form-select option {
        background-color: #0f172a !important;
        color: #f8fafc !important;
    }

    /* Live Search Dropdown Styling & Dark Mode */
    #role-search-results {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid #e2e8f0 !important;
    }

    #role-search-results .dropdown-item:hover,
    #role-search-results .dropdown-item:focus {
        background-color: rgba(15, 94, 156, .08);
    }

    #role-search-results .dropdown-item:not(:last-child) {
        border-bottom: 1px solid rgba(0, 0, 0, .05);
    }

    [data-bs-theme="dark"] #role-search-results,
    [data-theme="dark"] #role-search-results,
    body.theme-dark #role-search-results {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
    }

    [data-bs-theme="dark"] #role-search-items,
    [data-theme="dark"] #role-search-items,
    body.theme-dark #role-search-items {
        background-color: #1e293b !important;
    }

    [data-bs-theme="dark"] #role-see-all-results,
    [data-theme="dark"] #role-see-all-results,
    body.theme-dark #role-see-all-results {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #38bdf8 !important;
    }

    [data-bs-theme="dark"] #role-search-results .dropdown-item,
    [data-theme="dark"] #role-search-results .dropdown-item,
    body.theme-dark #role-search-results .dropdown-item {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #role-search-results .dropdown-item .text-dark,
    [data-theme="dark"] #role-search-results .dropdown-item .text-dark,
    body.theme-dark #role-search-results .dropdown-item .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #role-search-results .dropdown-item:not(:last-child),
    [data-theme="dark"] #role-search-results .dropdown-item:not(:last-child),
    body.theme-dark #role-search-results .dropdown-item:not(:last-child) {
        border-bottom-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] #role-search-results .dropdown-item:hover,
    [data-bs-theme="dark"] #role-search-results .dropdown-item:focus,
    [data-theme="dark"] #role-search-results .dropdown-item:hover,
    [data-theme="dark"] #role-search-results .dropdown-item:focus,
    body.theme-dark #role-search-results .dropdown-item:hover,
    body.theme-dark #role-search-results .dropdown-item:focus {
        background-color: rgba(106, 174, 214, 0.16) !important;
    }
</style>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Live Search Dropdown
    const searchInput = document.getElementById('role-search-input');
    const searchResultsDropdown = document.getElementById('role-search-results');
    const searchItemsContainer = document.getElementById('role-search-items');
    const seeAllResultsBtn = document.getElementById('role-see-all-results');
    const searchKeywordText = document.getElementById('search-keyword-text');
    let debounceTimer = null;

    if (searchInput && searchResultsDropdown) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length === 0) {
                searchResultsDropdown.classList.add('d-none');
                searchResultsDropdown.style.display = 'none';
                return;
            }

            if (searchKeywordText) {
                searchKeywordText.textContent = query;
            }

            debounceTimer = setTimeout(() => {
                fetch(`/manage/role/live-search?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchItemsContainer.innerHTML = '';

                        if (data && data.length > 0) {
                            data.forEach(user => {
                                const item = document.createElement('a');
                                item.className = 'dropdown-item d-flex align-items-center justify-content-between px-3 py-2 border-bottom';
                                item.href = `/manage/role?q=${encodeURIComponent(user.name)}`;
                                item.innerHTML = `
                                    <div class="text-truncate me-2" style="max-width: 280px;">
                                        <strong class="d-block text-dark small text-truncate">${user.name}</strong>
                                        <span class="text-muted extra-small d-block text-truncate" style="font-size: 0.75rem;">${user.id_emp || ''} - ${user.email || ''}</span>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill extra-small px-2 py-1 flex-shrink-0">USER</span>
                                `;
                                searchItemsContainer.appendChild(item);
                            });
                        } else {
                            searchItemsContainer.innerHTML = '<div class="px-3 py-2 text-muted small">No users found</div>';
                        }

                        searchResultsDropdown.classList.remove('d-none');
                        searchResultsDropdown.style.display = 'block';
                    })
                    .catch(err => {
                        console.error('Role Live Search Error:', err);
                        searchResultsDropdown.classList.add('d-none');
                        searchResultsDropdown.style.display = 'none';
                    });
            }, 250);
        });

        if (seeAllResultsBtn) {
            seeAllResultsBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (searchInput.form) {
                    searchInput.form.submit();
                }
            });
        }

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !searchResultsDropdown.contains(e.target)) {
                searchResultsDropdown.classList.add('d-none');
                searchResultsDropdown.style.display = 'none';
            }
        });
    }

    // 2. Delete User Modal Handler
    const deleteModal = document.getElementById('deleteUserModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            const action = button.getAttribute('data-action');
            const name = button.getAttribute('data-name');
            const emp = button.getAttribute('data-emp');

            const form = document.getElementById('deleteUserForm');
            if (form && action) form.action = action;

            const nameEl = document.getElementById('deleteUserName');
            if (nameEl) nameEl.textContent = name || '-';

            const empEl = document.getElementById('deleteUserEmp');
            if (empEl) empEl.textContent = emp || '-';
        });
    }

    // 3. Synchronize (Real & Dry-Run) Handler
    const btnStartRealSync = document.getElementById('btnStartRealSync');
    const btnStartDryRun = document.getElementById('btnStartDryRun');
    const syncConfState = document.getElementById('syncConfirmationState');
    const syncLoadingState = document.getElementById('syncLoadingState');
    const syncResultState = document.getElementById('syncResultState');
    const syncErrorState = document.getElementById('syncErrorState');
    const btnCancelSync = document.getElementById('btnCancelSync');
    const btnCloseSyncModal = document.getElementById('btnCloseSyncModal');
    const btnReloadTable = document.getElementById('btnReloadTable');
    const syncActionButtons = document.getElementById('syncActionButtons');

    function executeSyncProcess(endpoint, isDryRun) {
        // Switch to loading state
        syncConfState.classList.add('d-none');
        syncResultState.classList.add('d-none');
        syncErrorState.classList.add('d-none');
        syncLoadingState.classList.remove('d-none');

        const titleEl = document.getElementById('syncLoadingTitle');
        const subtitleEl = document.getElementById('syncLoadingSubtitle');
        if (titleEl) {
            titleEl.textContent = isDryRun ? 'Menjalankan Simulasi Sinkronisasi (Dry-Run)...' : 'Menjalankan Sinkronisasi Nyata ke Database OWL...';
        }
        if (subtitleEl) {
            subtitleEl.textContent = isDryRun
                ? 'Menganalisis perbandingan data API Gateway POSCO tanpa menyimpan (Zero-Write)...'
                : 'Mengambil data API Gateway POSCO dan memperbarui tabel users OWL. Harap tunggu...';
        }

        if (btnStartRealSync) btnStartRealSync.disabled = true;
        if (btnStartDryRun) btnStartDryRun.disabled = true;
        if (btnCancelSync) btnCancelSync.disabled = true;
        if (btnCloseSyncModal) btnCloseSyncModal.disabled = true;

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, body: data })))
        .then(({ status, ok, body }) => {
            syncLoadingState.classList.add('d-none');
            if (btnCancelSync) btnCancelSync.disabled = false;
            if (btnCloseSyncModal) btnCloseSyncModal.disabled = false;
            if (btnStartRealSync) btnStartRealSync.disabled = false;
            if (btnStartDryRun) btnStartDryRun.disabled = false;

            if (ok && body.success) {
                syncResultState.classList.remove('d-none');
                document.getElementById('resStatus').textContent = body.status || 'SUCCESS';

                const modeBadge = document.getElementById('resMode');
                const footerNote = document.getElementById('syncFooterNote');
                if (body.is_dry_run) {
                    modeBadge.className = 'badge bg-secondary-lt text-secondary';
                    modeBadge.textContent = 'DRY-RUN (Simulasi Zero-Write)';
                    if (btnReloadTable) btnReloadTable.classList.add('d-none');
                    if (footerNote) {
                        footerNote.innerHTML = '<i class="ti ti-lock text-muted fs-3"></i><div><strong>100% Zero-Write Terbukti:</strong> Tidak ada perubahan yang disimpan ke tabel <code>users</code> atau log eksekusi.</div>';
                    }
                } else {
                    modeBadge.className = 'badge bg-success text-white';
                    modeBadge.textContent = 'REAL (Tersimpan ke Database OWL)';
                    if (btnReloadTable) btnReloadTable.classList.remove('d-none');
                    if (footerNote) {
                        footerNote.innerHTML = '<i class="ti ti-database-check text-success fs-3"></i><div><strong>Data Tersimpan Nyata:</strong> Seluruh data kepegawaian dan 18 kolom profil HR telah disimpan ke database OWL.</div>';
                    }
                }

                document.getElementById('resDuration').textContent = (body.duration || '0') + ' detik';
                document.getElementById('resTotalApi').textContent = (body.total_api || 0).toLocaleString('id-ID') + ' entri';
                document.getElementById('resTotalOwl').textContent = (body.total_owl || 0).toLocaleString('id-ID') + ' user';
                document.getElementById('resCreatedCount').textContent = (body.created_count || 0).toLocaleString('id-ID') + (body.is_dry_run ? ' kandidat baru' : ' user baru dibuat');
                document.getElementById('resUpdatedCount').textContent = (body.updated_count || 0).toLocaleString('id-ID') + (body.is_dry_run ? ' rencana pembaruan' : ' user diperbarui');
                document.getElementById('resUpdatedActiveCount').textContent = (body.updated_active_count || 0).toLocaleString('id-ID') + ' user aktif';
                document.getElementById('resUpdatedSoftDeletedCount').textContent = (body.updated_soft_deleted_count || 0).toLocaleString('id-ID') + ' user';
                document.getElementById('resUnchangedCount').textContent = (body.unchanged_count || 0).toLocaleString('id-ID') + ' user';
                document.getElementById('resConflictCount').textContent = (body.conflict_count || 0).toLocaleString('id-ID') + ' entri';

                document.getElementById('syncResultSummary').textContent = body.message || 'Sinkronisasi berhasil!';
            } else {
                syncErrorState.classList.remove('d-none');
                document.getElementById('syncErrorMessage').textContent = body.message || 'Terjadi kesalahan saat menjalankan sinkronisasi.';
            }
        })
        .catch(err => {
            syncLoadingState.classList.add('d-none');
            syncErrorState.classList.remove('d-none');
            document.getElementById('syncErrorMessage').textContent = 'Koneksi gagal atau terjadi error internal: ' + err.message;
            if (btnCancelSync) btnCancelSync.disabled = false;
            if (btnCloseSyncModal) btnCloseSyncModal.disabled = false;
            if (btnStartRealSync) btnStartRealSync.disabled = false;
            if (btnStartDryRun) btnStartDryRun.disabled = false;
        });
    }

    if (btnStartRealSync) {
        btnStartRealSync.addEventListener('click', function () {
            if (confirm('Jalankan sinkronisasi nyata? Data karyawan dari API POSCO akan disimpan dan memperbarui tabel users OWL.')) {
                executeSyncProcess('{{ route("manage.role.sync") }}', false);
            }
        });
    }

    if (btnStartDryRun) {
        btnStartDryRun.addEventListener('click', function () {
            executeSyncProcess('{{ route("manage.role.sync.dry-run") }}', true);
        });
    }
});
</script>
@endsection
