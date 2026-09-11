<script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

<script>
    console.log('jquery:', typeof $);
    console.log('modalCreatePtkp:', $('#modalCreatePtkp').length);
    console.log('bootstrap modal:', typeof $.fn.modal);

    $('#show-entries').on('change', function() {
        $('#form-filter-data-user').submit();
    });

$('.toggle-class').change(function() {

    var active = $(this).prop('checked') ? 1 : 0;
    var user_id = $(this).data('id');

    $.ajax({
        type: "POST",
        dataType: "JSON",
        url: '{{ route('user.changestatus') }}',
        data: {
            active: active,
            id: user_id,
            _token: "{{ csrf_token() }}"
        },

        success: function(response) {

            $('#status-alert').html(`
                <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3">
                    <i class="ti ti-circle-check mr-2"></i>
                    ${response.message}

                    <button type="button"
                        class="close"
                        data-dismiss="alert">

                        <span>&times;</span>

                    </button>
                </div>
            `);

            setTimeout(function() {
                $('#status-alert .alert').fadeOut();
            }, 3000);

        },

        error: function() {

            $('#status-alert').html(`
                <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3">
                    <i class="ti ti-alert-circle mr-2"></i>
                    Failed to update user status

                    <button type="button"
                        class="close"
                        data-dismiss="alert">

                        <span>&times;</span>

                    </button>
                </div>
            `);

        }
    });

});



    $('#modalAction').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);

        $('#formAction').attr('action', button.data('url'));
        $('#action_method').val(button.data('method'));
        $('#action_title').text(button.data('title'));
        $('#action_message').text(button.data('message'));
        $('#action_button')
            .removeClass('btn-danger btn-warning btn-primary')
            .addClass('btn-' + button.data('color'))
            .text(button.data('button'));
    });

    $('.btn-edit-user').on('click', function() {
        var id = $(this).data('id');

        $('#formEditPtkp').attr('action', '/manage/user/' + id);

        $('#edit_id_emp').val($(this).data('id-emp'));
        $('#edit_name').val($(this).data('name'));
        $('#edit_email').val($(this).data('email'));
        $('#edit_team').val($(this).data('team'));
        $('#edit_department').val($(this).data('department'));
        $('#edit_division').val($(this).data('division'));
    });

    $('.btn-edit-outsourcing').on('click', function() {
        var id = $(this).data('id');

        $('#formEditOutsourcing').attr('action', '/manage/user-outsourcing/' + id + '/update');

        $('#edit_out_id_employee').val($(this).data('id-employee'));
        $('#edit_out_name').val($(this).data('name'));
        $('#edit_out_email').val($(this).data('email'));
        $('#edit_out_company').val($(this).data('company-name'));
    });

    $('#btn-api-create').on('click', function(e) {
        e.preventDefault();

        var emp_id = $('#create_id_emp').val();

        $.ajax({
            type: "GET",
            url: '{{ config('api.employee.base_url') }}' + emp_id,
            dataType: "json",
            success: function(response) {
                if (response.status == 404) {
                    alert(response.message);
                } else {
                    $('#create_sap_code').val(response.sap_code);
                    $('#create_ssohash').val(response.newhash);
                    $('#create_name').val(response.name);
                    $('#create_department').val(response.department);
                    $('#create_team').val(response.team);
                    $('#create_division').val(response.division);
                    $('#create_email').val(response.email);
                    $('#create_id_org_unit').val(response.id_ou);
                    $('#create_id_user').val(response.id_user);
                }
            }
        });
    });

    $('#btn-api-edit').on('click', function(e) {
        e.preventDefault();

        var emp_id = $('#edit_id_emp').val();

        $.ajax({
            type: "GET",
            url: '{{ config('api.employee.base_url') }}' + emp_id,
            dataType: "json",
            success: function(response) {
                if (response.status == 404) {
                    alert(response.message);
                } else {
                    $('#edit_sap_code').val(response.sap_code);
                    $('#edit_ssohash').val(response.newhash);
                    $('#edit_name').val(response.name);
                    $('#edit_department').val(response.department);
                    $('#edit_team').val(response.team);
                    $('#edit_division').val(response.division);
                    $('#edit_email').val(response.email);
                    $('#edit_id_org_unit').val(response.id_ou);
                    $('#edit_id_user').val(response.id_user);
                }
            }
        });
    });


        $('.btn-edit-user').on('click', function() {
        var id = $(this).data('id');

        $.get('/manage/user/' + id + '/detail', function(user) {
            $('#formEditPtkp').attr('action', '/manage/user/' + user.id);

            $('#edit_id_emp').val(user.id_emp);
            $('#edit_name').val(user.name);
            $('#edit_email').val(user.email);
            $('#edit_team').val(user.team);
            $('#edit_department').val(user.department);
            $('#edit_division').val(user.division);
            $('#edit_id_org_unit').val(user.id_org_unit);
            $('#edit_id_user').val(user.id_user);
            $('#edit_ssohash').val(user.ssohash);
            $('#edit_sap_code').val(user.sap_code);
        });
    });
    
        $(document).on('click', '#btn-api-create', function(e) {
            e.preventDefault();

            var emp_id = $('#create_id_emp').val();

            if (!emp_id) {
                alert('Employee ID wajib diisi.');
                return;
            }

            $.ajax({
                type: "GET",
                url: "/manage/user/get-employee/" + emp_id,
                dataType: "json",
                beforeSend: function() {
                    $('#btn-api-create').prop('disabled', true).html('Getting Data...');
                },
                success: function(response) {
                    $('#create_sap_code').val(response.sap_code);
                    $('#create_ssohash').val(response.ssohash);
                    $('#create_name').val(response.name);
                    $('#create_email').val(response.email);
                    $('#create_team').val(response.team);
                    $('#create_department').val(response.department);
                    $('#create_division').val(response.division);
                    $('#create_id_org_unit').val(response.id_org_unit);
                    $('#create_id_user').val(response.id_user);
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message ?? 'Employee tidak ditemukan.');
                },
                complete: function() {
                    $('#btn-api-create').prop('disabled', false).html('Get Data');
                }
            });
        });

        // copas id emp span

        $(document).on('click', '.copy-id', function() {

            let id = $(this).data('id');

            navigator.clipboard.writeText(id);

            $('#status-alert').html(`
                <div class="alert alert-success alert-dismissible fade show mb-3">
                    Employee ID ${id} copied successfully
                </div>
            `);

            setTimeout(function() {
                $('#status-alert .alert').fadeOut();
            }, 2000);
        });

        // JS Handler to immediately hide modal backdrop on form submit
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
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('overflow', '');
                }
            });
        });
    
</script>