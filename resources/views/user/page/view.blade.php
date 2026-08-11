@extends('layouts.sidebar-master')
@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            {{ $page->title }}
          </h2>
        </div>
      </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card card-lg">
            <div class="card-body ">
              <div class="space-y-4">
                  {!! $page->description !!}
              </div>
            </div>
          </div>
    </div>
  </div>

@endsection
