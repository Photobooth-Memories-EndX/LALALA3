<script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

<script>
    console.log('jquery:', typeof $);
    console.log('modalCreatePtkp:', $('#modalCreatePtkp').length);
    console.log('bootstrap modal:', typeof $.fn.modal);

    $('#show-entries').on('change', function() {
        $('#form-filter-data-user').submit();
    });

  $('.toggle-class').change(function() {

    var active = $(this).prop('checked') == true ? '0' : '1';
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

            console.log(response.message);

            // Optional notification
            // alert(response.message);

        },
        error: function(xhr) {

            console.error(xhr.responseText);

            alert('Failed to update user status');

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

    
</script>