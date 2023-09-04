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
    <div class="container">

        <x-form class="card card-md" action="{{ route('assesment.update', $assesment->slug) }}" method="post">
        @csrf
        @method('PUT')
        <div class="card-header">
            <h3 class="card-title">Edit Assesment</h3>
          </div>
          <div class="card-body">
            <div class="mb-3 row">
              <x-label class="col-3 col-form-label required">Learning Content</x-label>
              <div class="col">
                <x-select name="content_id">
                    <option value="">-choose-</option>
                    @foreach ($contents as $item)
                     <option value="{{ $item->id }}" {{ ($assesment->content_id == $item->id) ? 'selected' : ''  }}>{{ $item->title }}</option>
                    @endforeach
                </x-select>
              </div>
            </div>
            <div class="mb-3 row">
                <x-label class="col-3 col-form-label required">Title</x-label>
                <div class="col">
                  <x-input type="text" class="form-control" name="title" placeholder="Enter title" value="{{ $assesment->title }}" />
                </div>
              </div>
            <div class="mb-3 row">
              <x-label class="col-3 col-form-label required">Duration</x-label>
              <div class="col">
                <x-input type="text" class="form-control" name="duration" placeholder="Enter duration" value="{{ $assesment->duration }}" />
              </div>

              <x-label class="col-2 col-form-label required">Date</x-label>
              <div class="col">
                {{-- <x-input type="text" class="form-control" name="date" placeholder="Enter date" value="{{ old('date') }}" /> --}}
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                            <path d="M16 3v4" />
                            <path d="M8 3v4" />
                            <path d="M4 11h16" />
                            <path d="M11 15h1" />
                            <path d="M12 15v3" />
                        </svg>
                    </span>
                    {{-- <x-input class="form-control" placeholder="Select a date" id="datepicker-icon-prepend" name="date" value="{{ date('Y-m-d') }}" required /> --}}
                    <x-input class="form-control" placeholder="Select a date" id="datepicker-icon-prepend" name="date" value="{{ $assesment->date }}" required />
                </div>
              </div>
            </div>

          </div>
          <div class="card-footer text-end">
            <x-button type="submit" class="btn btn-primary">Submit</x-button>
          </div>
        </x-form>
    </div>

    </div>
    <!-- Container Page -->
  </div>
  <!-- Page body -->

@endsection

@section('style')
<link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
@endsection

@section('script')
<script src="{{ asset('Tabler/dist/libs/litepicker/dist/litepicker.js?1684106062') }}" defer></script>
<script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function () {
    	window.Litepicker && (new Litepicker({
    		element: document.getElementById('datepicker-icon-prepend'),
    		buttonText: {
    			previousMonth: `<!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg>`,
    			nextMonth: `<!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>`,
    		},

    	}));
    });
    // @formatter:on
  </script>
@endsection


