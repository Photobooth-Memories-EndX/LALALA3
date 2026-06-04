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
                    checkbox.prop('checked', !checkbox.prop('checked')).change();
                    showToast('Gagal memperbarui status assessment.', 'danger');
                }
            });
        });

        $('#assessment-search').on('keyup', function () {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#MyTable')) {
                $('#MyTable').DataTable().search(this.value).draw();
            }
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