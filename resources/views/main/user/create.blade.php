@extends('layouts.master')
@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            Manage User
          </h2>
        </div>
        <!-- Page title actions -->
        <div class="col-auto ms-auto d-print-none">
          <div class="d-flex">
            <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
              <li class="breadcrumb-item"><a href="#">Manage</a></li>
              <li class="breadcrumb-item"><a href="#">User</a></li>
              <li class="breadcrumb-item active" aria-current="page"><a href="#">Create New</a></li>
            </ol>
          </div>
        </div>
      </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <!-- Container Page -->
    <div class="page page-center">
    <div class="container container-tight py-4">

        <form class="card card-md" action="{{ route('user.store') }}" method="post">
        @csrf
        @method('POST')
          <div class="card-body">
            <h2 class="card-title text-center mb-4">Create new user</h2>
            <div class="input-group mb-3">
                <input type="text" class="form-control @error('id_emp')is-invalid @enderror" placeholder="Enter ID Employee" name="id_emp" id="id_emp" required value="{{ old('id_emp') }}">
                <button class="btn btn-success" type="button" id="btn-api">Get Data</button>
                @error('id_emp')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" placeholder="Enter name" name="name" id="name" required>
                <input type="hidden" id="id_org_unit" name="id_org_unit">
                <input type="hidden" id="id_user" name="id_user">
              </div>
            <div class="mb-3">
              <label class="form-label">Email address</label>
              <input type="email" class="form-control @error('email')is-invalid @enderror" placeholder="Enter email" name="email" id="email" required>
              @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Team</label>
                <input type="text" class="form-control" placeholder="Enter team" name="team" id="team">
            </div>
            <div class="mb-3">
                <label class="form-label">Department</label>
                <input type="text" class="form-control" placeholder="Enter department" name="department" id="department">
            </div>
            <div class="mb-3">
                <label class="form-label">Division</label>
                <input type="text" class="form-control" placeholder="Enter division" name="division" id="division">
            </div>
            <div class="form-footer">
              <button type="submit" class="btn btn-primary w-100">Create new account</button>
            </div>
          </div>
        </form>
    </div>

    </div>
    <!-- Container Page -->
  </div>
  <!-- Page body -->

@endsection

@section('script')
<script>
    $(document).on('click', '#btn-api', function(e) {
        e.preventDefault();
        var emp_id = $('#id_emp').val();
        $.ajax({
            type: "GET",
            url : '{{ config('api.employee.base_url') }}'+emp_id,
            dataType: "json",
            success: function (response) {
                if (response.status== 404)
                {
                    alert(response.message);
                }
                else
                {
                    $("#name").val(response.name);
                    $("#department").val(response.department);
                    $("#team").val(response.team);
                    $("#division").val(response.division);
                    $("#email").val(response.email);
                    $("#id_org_unit").val(response.id_ou);
                    $("#id_user").val(response.id_user);
                }
            }
        });
    });

</script>
@endsection
