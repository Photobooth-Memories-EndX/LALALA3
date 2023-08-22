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
              <li class="breadcrumb-item active" aria-current="page"><a href="#">List User</a></li>
            </ol>
          </div>
        </div>
      </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <!-- Container Page -->
    <div class="container">

        @if (session()->has('success'))
        <div class="alert alert-important alert-success alert-dismissible" role="alert">
          <div class="d-flex">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                  <path d="M5 12l5 5l10 -10"></path>
               </svg>
            </div>
            <div>
              {{ session('success') }}
            </div>
          </div>
          <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        @endif

      <!-- Card -->
      <div class="card">
        <div class="card-header">
            <h3 class="card-title">List Active user</h3>
            <div class="card-actions">
                <a href="{{ route('user.create') }}" class="btn btn-primary">
                  <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg>
                  Add new user
                </a>
              </div>
          </div>
        <div class="card-body">
          <div id="table-default" class="table-responsive">
            @unless(count($user) == 0)
            <table class="table table-bordered table-striped" id="MyTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Team</th>
                        <th>Department</th>
                        <th>Division</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($user as $list)
                    <tr>
                        <td>{{ $loop->index + 1 }}</td>
                        <td>{{ $list->id_emp }}</td>
                        <td>{{ $list->name }}</td>
                        <td>{{ $list->team }}</td>
                        <td>{{ $list->department }}</td>
                        <td>{{ $list->division }}</td>
                        <td>{{ $list->email }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <p>No user data found</p>
            @endunless
          </div>
        </div>
      </div>
      <!-- Card -->

    </div>
    <!-- Container Page -->
  </div>
  <!-- Page body -->

@endsection

@section('style')
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('script')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    // let table = new DataTable('#MyTable');
    $(function() {
        $("#MyTable").DataTable({
            "lengthMenu": [
                            [10, 25, 50, -1],
                            [10, 25, 50, 'All'],
                        ],
            "pageLength" : 25,
            "autoWidth": false,
        });
    });
</script>
@endsection
