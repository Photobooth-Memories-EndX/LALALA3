@php
    $url_tabler = asset('Tabler/dist/js/tabler.min.js');
    $url_demo = asset('Tabler/dist/js/demo.min.js');
@endphp

<!-- Tabler -->
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src={{ $url_tabler }}></script>
<script src={{ $url_demo }}></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Global handler to immediately dismiss modal and backdrop when a modal form is submitted
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal form').forEach(function (form) {
            form.addEventListener('submit', function () {
                const modalEl = this.closest('.modal');
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                    }
                    if (typeof $ !== 'undefined' && $.fn.modal) {
                        $(modalEl).modal('hide');
                    }
                    document.querySelectorAll('.modal-backdrop').forEach(function (bd) {
                        bd.remove();
                    });
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                }
            });
        });
    });
</script>