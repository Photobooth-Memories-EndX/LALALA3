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
              <li class="breadcrumb-item"><a href="{{ route('user.index') }}">User</a></li>
              <li class="breadcrumb-item active" aria-current="page"><a href="#">Edit</a></li>
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

        <div class="container container-slim py-4 d-none" id="overlay">
            <div class="text-center">

              <div class="text-muted mb-3">Getting employee data</div>
              <div class="progress progress-sm">
                <div class="progress-bar progress-bar-indeterminate"></div>
              </div>
            </div>
          </div>

        <form class="card card-md" action="{{ route('user.update', $user->id) }}" method="post">
        @csrf
        @method('PUT')
          <div class="card-body">
            <h2 class="card-title text-center mb-4">Edit user</h2>
            <div class="input-group mb-3">
                <input type="text" class="form-control @error('id_emp')is-invalid @enderror" placeholder="Enter ID Employee" name="id_emp" id="id_emp" required value="{{ $user->id_emp }}">
                <button class="btn btn-success" type="button" id="btn-api">Check For Update</button>
                @error('id_emp')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" placeholder="Enter name" name="name" id="name" required value="{{ $user->name }}">
                <input type="hidden" id="id_org_unit" name="id_org_unit" value="{{ $user->id_org_unit }}">
                <input type="hidden" id="id_user" name="id_user" value="{{ $user->id_user }}">
              </div>
            <div class="mb-3">
              <label class="form-label">Email address</label>
              <input type="email" class="form-control @error('email')is-invalid @enderror" placeholder="Enter email" name="email" id="email" required value="{{ $user->email }}">
              @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Team</label>
                <input type="text" class="form-control" placeholder="Enter team" name="team" id="team" value="{{ $user->team }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Department</label>
                <input type="text" class="form-control" placeholder="Enter department" name="department" id="department" value="{{ $user->department }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Division</label>
                <input type="text" class="form-control" placeholder="Enter division" name="division" id="division" value="{{ $user->division }}">
            </div>
            <div class="form-footer">
              <button type="submit" class="btn btn-primary w-100">Update Data</button>
            </div>
          </div>
        </form>
    </div>

    </div>
    <!-- Container Page -->
  </div>
  <!-- Page body -->

@endsection

@section('style')
<style>
    #overlay{
        position: absolute;
        top: 0rem;
        right: 0rem;
        left: 0rem;
        bottom: 0rem;
        display: -webkit-box;
        display: -webkit-flex;
        display: -ms-flexbox;
        display: flex;
        -webkit-align-items: center;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        -webkit-justify-content: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 0.25rem;
        z-index: 1000;
    }
</style>
@endsection

@section('script')
<script>
    $(document).on('click', '#btn-api', function(e) {
        e.preventDefault();
        $("#overlay").removeClass('d-none');
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
                $("#overlay").addClass('d-none');
            }
        });
    });

</script>
@endsection
