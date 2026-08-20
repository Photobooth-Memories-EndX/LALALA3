<script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

<script>
    $(document).ready(function () {

        $('[data-toggle="tooltip"]').tooltip();

        $(document).on('click', '.btn-copy-assessment', function () {
            let button = $(this);
            let text = button.data('copy-text');

            copyAssessmentLink(text, button);
        });

        $(document).on('change', '.toggle-class', function () {
            let checkbox = $(this);
            let status = checkbox.prop('checked') ? 'OK' : 'NOT';
            let id = checkbox.data('id');

            $.ajax({
                type: "POST",
                dataType: "json",
                url: "{{ route('assesment.changestatus') }}",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "status": status,
                    "id": id
                },
                success: function () {
                    showToast('Status assessment berhasil diperbarui.', 'success');
                },
                error: function () {
                    if (typeof checkbox.bootstrapToggle === 'function') {
                        checkbox.bootstrapToggle(status === 'OK' ? 'off' : 'on', true);
                    } else {
                        checkbox.prop('checked', status !== 'OK');
                    }
                    showToast('Gagal memperbarui status assessment.', 'danger');
                }
            });
        });

        $('#assessment-search').on('keyup', function () {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#MyTable')) {
                $('#MyTable').DataTable().search(this.value).draw();
            }
        });

        function positionAssessmentDropdown($dropdown) {
            let $menu = $dropdown.find('.dropdown-menu');
            let $button = $dropdown.find('.owl-dropdown-btn, [data-toggle="dropdown"], [data-bs-toggle="dropdown"]');

            if (!$button.length || !$menu.length) return;

            $menu.css({
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'right': 'auto',
                'bottom': 'auto',
                'margin': '0',
                'z-index': '1065',
                'visibility': 'hidden',
                'display': 'block'
            });

            let btnRect = $button[0].getBoundingClientRect();
            let menuWidth = $menu.outerWidth();
            let menuHeight = $menu.outerHeight();

            let windowWidth = $(window).width();
            let windowHeight = $(window).height();

            let leftPos = btnRect.right - menuWidth;

            if (leftPos < 10) {
                leftPos = Math.max(10, btnRect.left);
            }
            if (leftPos + menuWidth > windowWidth - 10) {
                leftPos = windowWidth - menuWidth - 10;
            }

            let topPos = btnRect.bottom + 4;

            if (topPos + menuHeight > windowHeight - 10) {
                if (btnRect.top - menuHeight - 4 > 10) {
                    topPos = btnRect.top - menuHeight - 4;
                } else {
                    topPos = Math.max(10, windowHeight - menuHeight - 10);
                }
            }

            $menu.css({
                'top': topPos + 'px',
                'left': leftPos + 'px',
                'visibility': 'visible'
            });
        }

        function resetAssessmentDropdown($dropdown) {
            let $menu = $dropdown.find('.dropdown-menu');
            $menu.css({
                'position': '',
                'top': '',
                'left': '',
                'right': '',
                'bottom': '',
                'margin': '',
                'z-index': '',
                'visibility': '',
                'display': ''
            });
        }

        $(document).on('show.bs.dropdown shown.bs.dropdown', '.table-responsive .dropdown', function () {
            positionAssessmentDropdown($(this));
        });

        $(document).on('hidden.bs.dropdown', '.table-responsive .dropdown', function () {
            resetAssessmentDropdown($(this));
        });

        $(window).add('.table-responsive').on('scroll resize', function () {
            $('.table-responsive .dropdown.show, .table-responsive .dropdown .show').each(function () {
                let $dropdown = $(this).hasClass('dropdown') ? $(this) : $(this).closest('.dropdown');
                let $btn = $dropdown.find('[data-toggle="dropdown"], [data-bs-toggle="dropdown"]');
                if ($btn.length && typeof $btn.dropdown === 'function') {
                    $btn.dropdown('hide');
                } else {
                    $dropdown.removeClass('show');
                    $dropdown.find('.dropdown-menu').removeClass('show');
                    resetAssessmentDropdown($dropdown);
                }
            });
        });

    });

    function copyAssessmentLink(text, button) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () {
                showCopySuccess(button);
            }).catch(function () {
                fallbackCopyText(text, button);
            });
        } else {
            fallbackCopyText(text, button);
        }
    }

    function fallbackCopyText(text, button) {
        let tempInput = $('<input>');

        $('body').append(tempInput);
        tempInput.val(text).select();

        try {
            document.execCommand('copy');
            showCopySuccess(button);
        } catch (err) {
            showToast('Copy link gagal. Silakan copy manual.', 'danger');
        }

        tempInput.remove();
    }

    function showCopySuccess(button) {
        let originalHtml = button.html();

        button
            .addClass('copy-success')
            .html('<i class="fas fa-check"></i>');

        showToast('Link assessment berhasil disalin.', 'success');

        setTimeout(function () {
            button
                .removeClass('copy-success')
                .html(originalHtml);
        }, 1200);
    }

    function showToast(message, type) {
        let bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        let icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';

        let toast = `
            <div class="toast owl-toast ${bgClass} text-white" role="alert" data-delay="2200">
                <div class="toast-body d-flex align-items-center">
                    <i class="fas ${icon} mr-2"></i>
                    <span>${message}</span>
                </div>
            </div>
        `;

        if ($('#owl-toast-wrapper').length === 0) {
            $('body').append('<div id="owl-toast-wrapper"></div>');
        }

        $('#owl-toast-wrapper').append(toast);

        let currentToast = $('#owl-toast-wrapper .toast').last();
        currentToast.toast('show');

        currentToast.on('hidden.bs.toast', function () {
            $(this).remove();
        });
    }
</script>