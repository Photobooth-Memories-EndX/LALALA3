@extends('layouts.sidebar-master')
@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            Manage Category
          </h2>
        </div>
        <!-- Page title actions -->
        <div class="col-auto ms-auto d-print-none">
          <div class="d-flex">
            <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
              <li class="breadcrumb-item"><a href="#">Manage</a></li>
              <li class="breadcrumb-item"><a href="{{ route('classification.index') }}">Category</a></li>
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

        <x-form class="card card-md" action="{{ route('classification.store') }}" method="post">
        <div class="card-header">
            <h3 class="card-title">Create Category</h3>
          </div>
          <div class="card-body">
            <div class="mb-3 row">
              <x-label class="col-3 col-form-label required">Name</x-label>
              <div class="col">
                <x-input type="text" name="classification_name" placeholder="Enter category name" value="{{ old('classification_name') }}" />
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


