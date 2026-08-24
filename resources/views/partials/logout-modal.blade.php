{{-- Logout Confirmation Modal --}}
<div class="modal modal-blur fade" id="modal-logout-confirm" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-backdrop="static">
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
        function openLogoutModal() {
            const modalElement = document.getElementById('modal-logout-confirm');
            if (!modalElement) return;

            if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $(modalElement).modal('show');
            } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                try {
                    if (typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
                        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                    } else {
                        const instance = window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance(modalElement) : null;
                        (instance || new window.bootstrap.Modal(modalElement)).show();
                    }
                } catch (err) {
                    modalElement.style.display = 'block';
                    modalElement.classList.add('show');
                    document.body.classList.add('modal-open');
                }
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
            if (!modalElement) return;

            if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $(modalElement).modal('hide');
            } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                try {
                    if (typeof window.bootstrap.Modal.getInstance === 'function') {
                        const instance = window.bootstrap.Modal.getInstance(modalElement);
                        if (instance) instance.hide();
                    }
                } catch (err) {
                    modalElement.style.display = 'none';
                    modalElement.classList.remove('show');
                    document.body.classList.remove('modal-open');
                }
            } else {
                modalElement.style.display = 'none';
                modalElement.classList.remove('show');
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        }

        // 1. Global Click Interceptor: ONLY intercepts external logout trigger buttons
        document.addEventListener('click', function (e) {
            // PENTING: Jangan intercept event apa pun yang berasal dari dalam modal logout.
            if (e.target.closest('#modal-logout-confirm')) {
                return;
            }

            // Hanya proses trigger pembuka modal di luar modal.
            const logoutBtn = e.target.closest(
                '[data-logout-trigger="true"], .owl-sidebar-logout-btn, #sidebar-logout-form button, form[action*="logout"] button, a[href*="logout"]'
            );
            if (!logoutBtn) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            openLogoutModal();
        }, true);

        // 2. Modal-internal Event Handling
        const logoutForm = document.getElementById('modal-logout-form');
        const confirmLogoutBtn = document.getElementById('btn-confirm-logout');
        const cancelLogoutBtn = document.getElementById('btn-cancel-logout');

        // Form submit: native submission + prevent double click & show loading state
        if (logoutForm) {
            logoutForm.addEventListener('submit', function () {
                if (confirmLogoutBtn) {
                    if (confirmLogoutBtn.dataset.submitting === 'true') {
                        return;
                    }
                    confirmLogoutBtn.dataset.submitting = 'true';
                    confirmLogoutBtn.style.pointerEvents = 'none';
                    confirmLogoutBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Logging out...';
                }
            });
        }

        // Cancel button fallback close handling
        if (cancelLogoutBtn) {
            cancelLogoutBtn.addEventListener('click', function () {
                closeLogoutModal();
            });
        }
    })();
</script>
