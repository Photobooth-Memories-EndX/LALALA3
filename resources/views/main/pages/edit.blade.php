@extends('layouts.master')
@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            Manage Pages
          </h2>
        </div>
        <!-- Page title actions -->
        <div class="col-auto ms-auto d-print-none">
          <div class="d-flex">
            <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
              <li class="breadcrumb-item"><a href="#">Manage</a></li>
              <li class="breadcrumb-item"><a href="{{ route('pages.index') }}">Pages</a></li>
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
    <div class="container py-4">

        <x-form class="card card-md" action="{{ route('pages.update', $page->slug) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('put')
        <div class="card-header">
            <h3 class="card-title">Update Page</h3>
          </div>
          <div class="card-body">
            <div class="mb-3 row">
              <x-label class="col-3 col-form-label required">Title</x-label>
              <div class="col">
                <x-input type="text" class="form-control" name="title" placeholder="Enter title" value="{{ $page->title }}" />
              </div>
            </div>
            <div class="mb-3 row">
              <x-label class="col-3 col-form-label required">Description</x-label>
              <div class="col">
                <x-textarea class="form-control" name="description" rows="6" placeholder="Content description.."  id="tinymce-mytextarea">{{ $page->description }}</x-textarea>
              </div>
            </div>

          </div>
          <div class="card-footer text-end">
            <x-button type="submit" class="btn btn-primary">Update</x-button>
          </div>
        </x-form>
    </div>

    </div>
    <!-- Container Page -->
  </div>
  <!-- Page body -->

@endsection

@section('script')
<script src="{{ asset('Tabler/dist/libs/tinymce/tinymce.min.js') }}" defer></script>
<script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function () {
      let options = {
        selector: '#tinymce-mytextarea',
        height: 300,
        menubar: false,
        statusbar: false,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | formatselect | ' +
          'bold italic backcolor | alignleft aligncenter ' +
          'alignright alignjustify | bullist numlist outdent indent | ' +
          'removeformat | table code',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }'
      }
      if (localStorage.getItem("tablerTheme") === 'dark') {
        options.skin = 'oxide-dark';
        options.content_css = 'dark';
      }
      tinyMCE.init(options);
    })
    // @formatter:on
  </script>
@endsection
