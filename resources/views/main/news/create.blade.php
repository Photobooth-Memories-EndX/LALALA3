@extends('layouts.sidebar-master')
@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            Manage News
          </h2>
        </div>
        <!-- Page title actions -->
        <div class="col-auto ms-auto d-print-none">
          <div class="d-flex">
            <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
              <li class="breadcrumb-item"><a href="#">Manage</a></li>
              <li class="breadcrumb-item"><a href="{{ route('news.index') }}">News</a></li>
              <li class="breadcrumb-item active" aria-current="page"><a href="#">Create</a></li>
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
    <div class="container py-4">

        <x-form class="card card-md" action="{{ route('news.store') }}" method="post" enctype="multipart/form-data">
        <div class="card-header">
            <h3 class="card-title">Create News</h3>
          </div>
          <div class="card-body">
            <div class="mb-3 row">
              <x-label class="col-3 col-form-label required">Title</x-label>
              <div class="col">
                <x-input type="text" name="title" placeholder="Enter title" value="{{ old('title') }}" />
              </div>
            </div>
            <div class="mb-3 row">
              <x-label class="col-3 col-form-label required">Description</x-label>
              <div class="col">
                <x-textarea name="description" rows="6" placeholder="Content description.." data-bs-toggle="autosize">{{ old('description') }}</x-textarea>
              </div>
            </div>
            <div class="mb-3 row">
                <x-label class="col-3 col-form-label required">Image</x-label>
                <div class="col">
                  <img id="image-preview" alt="image preview" style="display:none;" class="img-fluid"/>
                  <x-input type="file" id="image" name="image" placeholder="Enter image" onchange="previewImage();" />
                </div>
              </div>

          </div>
          <div class="card-footer text-end">
            <x-button>Submit</x-button>
          </div>
        </x-form>
    </div>

    </div>
    <!-- Container Page -->
  </div>
  <!-- Page body -->

@endsection

@section('script')
<script>
    function previewImage() {
        document.getElementById("image-preview").style.display = "block";
        var oFReader = new FileReader();
        oFReader.readAsDataURL(document.getElementById("image").files[0]);
        oFReader.onload = function(oFREvent) {
            document.getElementById("image-preview").src = oFREvent.target.result;
        };
    };
</script>

@endsection

