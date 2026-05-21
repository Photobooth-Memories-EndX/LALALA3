@extends('layouts.sidebar-master')
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
              <li class="breadcrumb-item active" aria-current="page"><a href="#">Pages</a></li>
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
            <h3 class="card-title">List Pages</h3>
          </div>
        <div class="card-body">
          <div id="table-default" class="table-responsive">
            @unless(count($pages) == 0)
            <table class="table table-bordered table-striped" id="MyTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th width="65px"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($pages as $list)
                    <tr>
                        <td>{{ $list->title }}</td>
                        <td>
                            <div class="d-inline">
                                <a href="{{ route('pages.edit', $list->slug) }}" class="btn btn-info btn-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                        <path d="M16 5l3 3"></path>
                                     </svg>
                                  </a>
                            </div>

                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <p>No pages data found</p>
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

