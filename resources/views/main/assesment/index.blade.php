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
              <li class="breadcrumb-item active" aria-current="page"><a href="#">Assesment</a></li>
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
            <h3 class="card-title">List Assesment</h3>
            <div class="card-actions">
                <a href="{{ route('assesment.create') }}" class="btn btn-primary">
                  <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg>
                  Add assesment
                </a>
              </div>
          </div>
        <div class="card-body">
          <div id="table-default" class="table-responsive">
            @unless(count($assesment) == 0)
            <table class="table table-bordered table-striped" id="MyTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Title</th>
                        <th>Duration</th>
                        <th>Date</th>
                        {{-- <th>Created At</th> --}}
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($assesment as $list)
                    <tr>
                        <td>{{ $loop->index + 1 }}</td>
                        <td>{{ $list->title }}</td>
                        <td>{{ $list->duration }}</td>
                        <td>{{ date("d/m/y", strtotime($list->date)) }}</td>
                        {{-- <td>{{ date("d/m/y", strtotime($list->created_at)) }}</td> --}}
                        <td>{{ $list->status }}</td>
                        <td>
                            <div class="d-inline">
                                <a href="{{ route('assesment.edit', $list->slug) }}" class="btn btn-info btn-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                        <path d="M16 5l3 3"></path>
                                     </svg>
                                </a>
                            </div>

                            <form action="{{ route('assesment.destroy', $list->slug) }}" method="POST" class="d-inline">
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

                            <div class="d-inline">
                                <a href="{{ route('assesment.question', $list->slug) }}" class="btn btn-success btn-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-list-letters" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M11 6h9"></path>
                                        <path d="M11 12h9"></path>
                                        <path d="M11 18h9"></path>
                                        <path d="M4 10v-4.5a1.5 1.5 0 0 1 3 0v4.5"></path>
                                        <path d="M4 8h3"></path>
                                        <path d="M4 20h1.5a1.5 1.5 0 0 0 0 -3h-1.5h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6z"></path>
                                     </svg>
                                </a>
                            </div>

                            {{-- <div class="d-inline">
                                <button class="btn btn-success btn-icon btn-question" data-url="{{ route('assesment.question', $list->slug) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-list-letters" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M11 6h9"></path>
                                        <path d="M11 12h9"></path>
                                        <path d="M11 18h9"></path>
                                        <path d="M4 10v-4.5a1.5 1.5 0 0 1 3 0v4.5"></path>
                                        <path d="M4 8h3"></path>
                                        <path d="M4 20h1.5a1.5 1.5 0 0 0 0 -3h-1.5h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6z"></path>
                                     </svg>
                                </button>
                            </div> --}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <p>No assesment data found</p>
            @endunless
          </div>
        </div>

      </div>
      <!-- Card -->

    </div>
    <!-- Container Page -->
  </div>
  <!-- Page body -->

  <!-- Modal Question -->
  <div class="modal modal-blur fade" id="modal-questions" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="d-none modal-blur" id="overlay">
            <div class="text-center">
              <div class="text-muted mb-3">Getting questions data</div>
              <div class="progress">
                <div class="progress-bar progress-bar-indeterminate"></div>
              </div>
            </div>
        </div>
        <div class="modal-header">
          <h5 class="modal-title">Assesment Question and Answer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <strong id="title"></strong>
          </div>
          <form action="" id="formQuestion">
            <div class="mb-3">
                <x-label class="form-label">Question</x-label>
                <x-input type="text" class="form-control" name="question" id="question" placeholder="Input question" />
            </div>
            <div class="mb-3">
                <div class="form-label">Option</div>
                <x-select class="form-select small" name="option[]" id="options" multiple="multiple">
                  <option value="1">One</option>
                  <option value="2">Two</option>
                  <option value="3">Three</option>
                </x-select>
            </div>
            <div class="mb-3">
                <div class="form-label">Answer</div>
                <x-select class="form-select" name="answer" id="answer">
                  <option value="1">One</option>
                  <option value="2">Two</option>
                  <option value="3">Three</option>
                </x-select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
          {{-- <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save changes</button> --}}
        </div>
      </div>
    </div>
  </div>

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
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('script')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

<script>

    //question form
    $(document).on('click', '.btn-question', function(e) {
        e.preventDefault();
        $('#options').select2({
            dropdownParent: $('#modal-questions'),
            theme: 'bootstrap-5',
            selectionCssClass: 'select2--small',
            dropdownCssClass: 'select2--small',
        })
        $("#overlay").removeClass('d-none');
        $('#modal-questions').modal('show');
        $.ajax({
            type: "GET",
            url: $(this).data('url'),
            dataType: "json",
            success: function(response) {
                if (response.status == 404) {
                    alert(response.message);
                    $('#modal-questions').modal('hide');
                } else {

                    $('#title').text(response.assesment.title);
                    $("#overlay").addClass('d-none');

                }
            }
        });

        $("#overlay").addClass('d-none');

    });

</script>
@endsection
