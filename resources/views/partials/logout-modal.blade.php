{{-- Logout Confirmation Modal --}}
<div class="modal modal-blur fade" id="modal-logout-confirm" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document" style="max-width: 420px; margin: 1.75rem auto;">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-body text-center py-4 px-4">
                {{-- Logout Icon Circle --}}
                <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-red-lt" style="width: 56px; height: 56px; color: var(--tblr-danger, #ef3340);">
                    <i class="ti ti-logout fs-1"></i>
                </div>
                
                {{-- Title & Description --}}
                <h3 class="fw-bold mb-2 text-dark dark:text-white" style="font-size: 1.15rem; line-height: 1.3;">
                    Are you sure you want to log out?
                </h3>
                <p class="text-muted fs-4 mb-4" style="line-height: 1.5; font-size: 0.9rem;">
                    You will be signed out of your account and redirected to the login page.
                </p>

                {{-- Modal Action Buttons --}}
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary w-50 py-2 fw-semibold rounded-3" id="btn-cancel-logout" data-bs-dismiss="modal" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger w-50 py-2 fw-semibold rounded-3 d-flex align-items-center justify-content-center gap-2" id="btn-confirm-logout">
                        <i class="ti ti-logout"></i>
                        <span>Log Out</span>
                    </button>
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
            if (form && form.action && (form.action.includes('logout') || form.id === 'sidebar-logout-form')) {
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
                    if (activeLogoutForm) {
                        window.isExecutingLogoutSubmit = true;
                        confirmLogoutBtn.disabled = true;
                        confirmLogoutBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Logging out...';
                        activeLogoutForm.submit();
                    }
                });
            }
        });
    })();
</script>
