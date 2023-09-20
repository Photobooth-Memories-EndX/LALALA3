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
              <li class="breadcrumb-item active" aria-current="page"><a href="#">Questions</a></li>
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
    <div class="container">

        <x-alert type='success' />

        <x-form class="card card-md" action="{{ route('assesment.question.update', [$assesment->slug, $data->id]) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('put')
        <div class="card-header">
            <h3 class="card-title">Assesment Question and Answer</h3>
          </div>
          <div class="card-body">

            <div class="mb-3">
                <x-label class="form-label">Question</x-label>
                <x-input type="text" name="question" id="question" placeholder="Input question" value="{{ $data->question }}" />
            </div>
            <div class="mb-3">
                <x-label class="form-label">Image</x-label>
                @if (($data->image != '') or ($data->image != NULL))
                  <div class="row align-items-center mb-2" id="container-img">
                    <div class="col-auto"><span class="avatar avatar-2xl" style="background-image: url({{ asset('storage/assesment/') .'/'. $data->image }})"></span>
                    </div>
                    <div class="col-auto"><a href="#" data-url="{{ route('assesment.deleteImage', $data->id) }}" class="btn btn-danger btn-remove-img">
                        Delete Image
                      </a></div>
                  </div>
                  @endif
                <img id="image-preview" alt="image preview" style="display:none;height:200px;" class="img-fluid mb-2" />
                <x-input type="file" id="image" name="image" placeholder="Enter image" onchange="previewImage();" />
            </div>
            <div class="mb-3 row">
                <div class="col-md-6">
                    <div class="form-label">Options</div>
                    <x-select class="small" name="option[]" id="options" multiple="multiple">
                        @foreach ( $data->option as $answer )
                         <option value="{{ $answer }}" selected>{{ $answer }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="col-md-6">
                    <div class="form-label">Answer</div>
                    <x-select name="answer" id="answer">
                        <option value="">-choose-</option>
                        @foreach ( $data->option as $answer )
                         <option value="{{ $answer }}" {{ ($data->answer == $answer) ? 'selected' : ''  }}>{{ $answer }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>

          </div>
          <div class="card-footer text-end">
            <x-button>Update</x-button>
          </div>
        </x-form>

        <!-- Card -->
      <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title">{{ $assesment->title }}</h3>
            <div class="card-actions">
                <a href="{{ route('assesment.question', $assesment->slug) }}" class="btn btn-primary">
                  <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg>
                  Add question
                </a>
            </div>
        </div>
        <div class="card-body">
          <div id="table-default" class="table-responsive">
            @unless(count($assesment_question) == 0)
            <table class="table table-bordered table-striped" id="MyTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Question</th>
                        <th>Option</th>
                        <th>Answer</th>
                        <th>Status</th>
                        <th width="65px"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($assesment_question as $list)
                    <tr>
                        <td>{{ $loop->index + 1 }}</td>
                        <td>
                            {{ $list->question }} <br>
                            @if ($list->image != '')
                            <img src="{{ asset('storage/assesment').'/'.$list->image }}" class="img-fluid" style="height:90px;">
                            @endif
                        </td>
                        <td>{!! implode('<hr class="my-1">', $list->option) !!}</td>
                        {{-- <td></td> --}}
                        <td>{{ $list->answer }}</td>
                        <td>{{ ($list->status) ? 'Aktif' : 'Non Aktif' }}</td>
                        <td>
                            <div class="d-inline">
                                <a href="{{ route('assesment.question.edit', [$assesment->slug, $list->id]) }}" class="btn btn-info btn-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                        <path d="M16 5l3 3"></path>
                                     </svg>
                                </a>
                            </div>

                            <form action="{{ route('assesment.question.destroy', [$assesment->slug, $list->id]) }}" method="POST" class="d-inline">
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
            <p>No question-answer found for this assesment</p>
            @endunless
          </div>
        </div>

      </div>
      <!-- Card -->
    </div>

    </div>
    <!-- Container Page -->
  </div>
  <!-- Page body -->

@endsection

@section('style')
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
@endsection

@section('script')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="{{ asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062') }}"></script>
<script>
    // let table = new DataTable('#MyTable');
    $(function() {
        $("#MyTable").DataTable({
            "lengthMenu": [
                            [10, 25, 50, -1],
                            [10, 25, 50, 'All'],
                        ],
            "pageLength" : 10,
            "autoWidth": false,
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var el;
        window.TomSelect && (new TomSelect(el = document.getElementById('options'), {
            create: true,
            copyClassesToDropdown: false,
            dropdownParent: 'body',
            controlInput: '<input>',
            plugins: ['remove_button'],
            render: {
                item: function(data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                    }
                    return '<div>' + escape(data.text) + '</div>';
                },
                option: function(data, escape) {
                    if (data.customProperties) {
                        return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                    }
                    return '<div>' + escape(data.text) + '</div>';
                },
            },
            onItemAdd : function(value) {
                            $('#answer').append($('<option>', {
                                value: value,
                                text: value
                            }));
                        },
            onItemRemove : function(value) {
                            $("#answer option[value='"+value+"']").remove();
                        }
        }));
    });

    function previewImage() {
        document.getElementById("image-preview").style.display = "block";
        var oFReader = new FileReader();
        oFReader.readAsDataURL(document.getElementById("image").files[0]);
        oFReader.onload = function(oFREvent) {
            document.getElementById("image-preview").src = oFREvent.target.result;
        };
    };

    $(document).on('click', '.btn-remove-img', function(e) {
        e.preventDefault();
        if (confirm("Are you sure want to delete this image?")) {
            var column = $(this).data('column');
                $.ajax({
                    type: "PUT",
                    url: $(this).data('url'),
                    data: {'_token': '{{ csrf_token() }}'},
                    success: function(data){
                        if (data.status == 200) {
                            $("#container-img").load(" #container-img > *");
                        }
                    }
                });
        }
    });


</script>
@endsection


