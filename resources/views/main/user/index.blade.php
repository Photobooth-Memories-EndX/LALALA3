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
              <li class="breadcrumb-item active" aria-current="page"><a href="#">User</a></li>
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

      {{-- alert component   --}}
      <x-alert type='success' />
      <x-alert type='warning' />

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
                        <th width="65px"></th>
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
                        <td>
                            <div class="d-inline">
                                <a href="{{ route('user.edit', $list->id) }}" class="btn btn-info btn-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                        <path d="M16 5l3 3"></path>
                                     </svg>
                                  </a>
                            </div>

                            <form action="{{ route('user.destroy',$list->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-icon" onclick="return confirm('Are you sure want to delete this data?')"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M4 7l16 0"></path>
                                    <path d="M10 11l0 6"></path>
                                    <path d="M14 11l0 6"></path>
                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                 </svg></button>
                            </form>
                        </td>
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
