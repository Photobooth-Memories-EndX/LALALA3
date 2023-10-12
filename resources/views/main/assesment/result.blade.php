@extends('layouts.master')
@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            Manage Assesment
          </h2>
        </div>
        <!-- Page title actions -->
        <div class="col-auto ms-auto d-print-none">
          <div class="d-flex">
            <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
              <li class="breadcrumb-item"><a href="#">Manage</a></li>
              <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assesment</a></li>
              <li class="breadcrumb-item active" aria-current="page"><a href="#">Result</a></li>
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
            <h3 class="card-title">Assesment Result: {{ $assesment->title }}</h3>
          </div>
        <div class="card-body">
          <div id="table-default" class="table-responsive">
            @unless(count($assesment_result) == 0)
            <table class="table table-bordered table-striped" id="MyTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Team</th>
                        <th>Department</th>
                        <th>Division</th>
                        <th>Score</th>
                        <th>Batch</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($assesment_result as $list)
                    <tr>
                        <td>{{ $loop->index + 1 }}</td>
                        <td>{{ $list->user->id_emp }}</td>
                        <td>{{ $list->user->name }}</td>
                        <td>{{ $list->user->team }}</td>
                        <td>{{ $list->user->department }}</td>
                        <td>{{ $list->user->division }}</td>
                        <td class="text-center">{{ $list->score }}</td>
                        <td class="text-center">{{ $list->batch }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <p>No assesment score data found</p>
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
