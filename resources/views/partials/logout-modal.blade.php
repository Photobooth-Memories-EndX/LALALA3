{{-- Logout Confirmation Modal --}}
<div class="modal modal-blur fade" id="modal-logout-confirm" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 380px !important;">
        <div class="modal-content rounded-4 border-0 shadow text-center p-3">
            <div class="modal-body">
                {{-- Logout Icon Circle --}}
                <div class="logout-icon-box mx-auto mb-3 bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="ti ti-logout fs-2"></i>
                </div>
                
                {{-- Title & Description --}}
                <h5 class="fw-bold text-dark mb-2">Are you sure you want to log out?</h5>
                <p class="text-muted small mb-4">You will be signed out of your account and redirected to the login page.</p>

                {{-- Modal Action Buttons --}}
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill flex-fill" id="btn-cancel-logout" data-bs-dismiss="modal" data-dismiss="modal">
                        Cancel
                    </button>
                    <form action="{{ Route::has('logoutpage') ? route('logoutpage') : (Route::has('logout') ? route('logout') : url('/logout')) }}" method="POST" class="flex-fill m-0 p-0" id="modal-logout-form">
                        @csrf
                        <button type="submit" class="btn btn-danger rounded-pill w-100 fw-semibold d-flex align-items-center justify-content-center gap-1" id="btn-confirm-logout">
                            <i class="ti ti-logout me-1"></i> Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        let activeLogoutForm = null;

        function openLogoutModal() {
            const modalElement = document.getElementById('modal-logout-confirm');
            if (!modalElement) return;

            if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                $(modalElement).modal('show');
            } else if (window.bootstrap && bootstrap.Modal) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                modal.show();
            } else {
                modalElement.style.display = 'block';
                modalElement.classList.add('show');
                document.body.classList.add('modal-open');
                let backdrop = document.querySelector('.modal-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }
            }
        }

        function closeLogoutModal() {
            const modalElement = document.getElementById('modal-logout-confirm');
            if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                $(modalElement).modal('hide');
            } else if (modalElement) {
                modalElement.style.display = 'none';
                modalElement.classList.remove('show');
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        }

        // 1. Intercept ALL logout clicks during Capture Phase (before any navigation/submit can execute)
        document.addEventListener('click', function (e) {
            const logoutBtn = e.target.closest('[data-logout-trigger="true"], .owl-sidebar-logout-btn, form[action*="logout"] button, a[href*="logout"]');
            
            // If click was on Cancel button or close modal button
            if (e.target.closest('#btn-cancel-logout, [data-bs-dismiss="modal"], [data-dismiss="modal"]')) {
                const isInsideLogoutModal = e.target.closest('#modal-logout-confirm');
                if (isInsideLogoutModal) {
                    closeLogoutModal();
                    activeLogoutForm = null;
                    return;
                }
            }

            if (logoutBtn) {
                // STOP ORIGINAL ACTION IMMEDIATELY BEFORE ANY OTHER LISTENER RUNS
                e.preventDefault();
                e.stopPropagation();
                if (e.stopImmediatePropagation) {
                    e.stopImmediatePropagation();
                }

                // Save reference to the parent logout form
                activeLogoutForm = logoutBtn.closest('form') || document.getElementById('sidebar-logout-form');

                // Show confirmation modal
                openLogoutModal();
            }
        }, true);

        // 2. Intercept submit events as fallback
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (form && form.action && (form.action.includes('logout') || form.id === 'sidebar-logout-form' || form.id === 'modal-logout-form')) {
                if (!window.isExecutingLogoutSubmit) {
                    e.preventDefault();
                    e.stopPropagation();
                    activeLogoutForm = form;
                    openLogoutModal();
                }
            }
        }, true);

        // 3. Confirm Log Out button handler
        document.addEventListener('DOMContentLoaded', function () {
            const confirmLogoutBtn = document.getElementById('btn-confirm-logout');
            const cancelLogoutBtn = document.getElementById('btn-cancel-logout');

            if (cancelLogoutBtn) {
                cancelLogoutBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeLogoutModal();
                    activeLogoutForm = null;
                });
            }

            if (confirmLogoutBtn) {
                confirmLogoutBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    window.isExecutingLogoutSubmit = true;
                    confirmLogoutBtn.disabled = true;
                    confirmLogoutBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Logging out...';
                    if (activeLogoutForm) {
                        activeLogoutForm.submit();
                    } else {
                        const fallbackForm = document.getElementById('modal-logout-form') || document.getElementById('sidebar-logout-form');
                        if (fallbackForm) {
                            fallbackForm.submit();
                        }
                    }
                });
            }
        });
    })();
</script>
