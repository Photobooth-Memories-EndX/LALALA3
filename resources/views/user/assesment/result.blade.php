@extends('layouts.master')
@section('content')

<div class="page-body">
    <div class="container-xl">
        <div class="card card-lg">
            <div class="card-body ">
              <div class="row g-4">
                <div class="col-12 markdown">
                  <h1 class="mb-2">{{ $assesment->title }}</h1>

                  <div class="d-flex align-items-center">
                    <div>
                      <div class="text-muted">{{ date('d F Y', strtotime($assesment->created_at)) }}</div>
                    </div>
                  </div>

                </div>

              </div>

              <div class="p-10 text-center">
                <!-- text -->
                <div class="mb-4 ">
                    @if ($assesment_result->score > 80)
                    <h2>🎉 Congratulations. You passed!</h2>
                    @else
                    <h2>Sorry, you have not passed this test</h2>
                    @endif

                </div>
                <!-- chart -->
                <div class="d-flex justify-content-center">

                </div>
                <!-- text -->
                <div class="mt-3">
                  <span>Your Score: <span class="text-dark">{{ $assesment_result->score }}</span></span><br>
                  <span class="mt-2 d-block">Passing Score: <span class="text-dark">80%</span></span>
                </div>
                <!-- btn -->
                <div class="mt-5">
                  <a href="#" class="btn btn-primary">Finish</a>
                  <a href="#" class="btn btn-outline-secondary ms-2">Share <i class="fe fe-external-link"></i></a>
                </div>
            </div>

            </div>



        </div>
    </div>
  </div>

@endsection

@section('style')
@endsection

@section('script')
<script>

</script>
@endsection
