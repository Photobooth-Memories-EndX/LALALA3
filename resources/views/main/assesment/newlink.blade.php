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
              <li class="breadcrumb-item active" aria-current="page"><a href="#">New Link</a></li>
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

        <x-form class="card card-md" action="{{ route('assesment.updatelink', $assesment->slug) }}" method="put">
        <div class="card-header">
            <h3 class="card-title">New Link Assesment</h3>
          </div>
          <div class="card-body">
            <div class="mb-3 row">
                <x-label class="col-2 col-form-label required">Link</x-label>
                <div class="col">
                  <x-input type="text" name="slug" placeholder="Enter new link" value="{{ $assesment->slug }}" />
                    <small class="form-hint">No space and symbol allowed</small>
                </div>
            </div>
            <div class="mb-3 row">
                <x-label class="col-2 col-form-label required">Batch No</x-label>
                <div class="col">
                  <x-input type="number" name="batch" placeholder="Enter batch no" required value="" />
                </div>
                <x-label class="col-2 col-form-label required">Time Limit?</x-label>
                <div class="col">
                    <x-select name="timer" onchange="setRequiredDate(this);">
                        <option value="">-choose-</option>
                        <option value="y" {{ ($assesment->timer == 'y') ? 'selected' : ''  }}>Yes</option>
                        <option value="n" {{ ($assesment->timer == 'n') ? 'selected' : ''  }}>No</option>
                    </x-select>
                </div>
            </div>
            <div class="mb-3 row" id="date_wrapper">
              <x-label class="col-2 col-form-label required">From Date</x-label>
              <div class="col">
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
                    <x-input placeholder="Select a date" id="datepicker-fromdate" name="fromdate" value="{{ $assesment->fromdate }}" required class="input-date" />
                </div>
              </div>

              <x-label class="col-2 col-form-label required">To Date</x-label>
              <div class="col">
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
                    <x-input placeholder="Select a date" id="datepicker-todate" name="todate" value="{{ $assesment->todate }}" required class="input-date" />
                </div>
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

@section('style')
<link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('css/dark.css') }}">
@endsection

@section('script')
<script src="{{ asset('js/flatpickr.js') }}"></script>
<script>
    $(".input-date").flatpickr({
        enableTime: true,
        dateFormat: "Y-m-d H:i",
    });

    function setRequiredDate(that) {
		if (that.value == "y") {
            document.getElementById("datepicker-fromdate").setAttribute('required','required');
            document.getElementById("datepicker-todate").setAttribute('required','required');
            $("#date_wrapper").removeClass('d-none');
		} else{
			document.getElementById("datepicker-fromdate").removeAttribute('required');
            document.getElementById("datepicker-todate").removeAttribute('required');
            $("#date_wrapper").addClass('d-none');
            $("#datepicker-fromdate").val('').change();
            $("#datepicker-todate").val('').change();
		}
	}
  </script>
@endsection


