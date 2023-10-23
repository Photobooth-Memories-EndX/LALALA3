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
                    @if ($assesment_result->score >= $assesment->passing_grade)
                    <h2>🎉 Congratulations. You passed!</h2>
                    @else
                    <h2>Sorry, you have not passed this test</h2>
                    @endif
                </div>
                <!-- chart -->
                <div class="d-flex justify-content-center">
                    <div id="chart"></div>
                </div>
                <!-- text -->
                <div class="mt-3">
                  <span>Your Score: <span>{{ $assesment_result->score }}</span></span><br>
                  <span class="mt-2 d-block">Passing Score: <span>{{ $assesment->passing_grade }}</span></span>
                </div>

                <!-- btn -->
                {{-- @if ($assesment_result->score < $assesment->passing_grade)
                <div class="mt-5">
                  <a href="{{ route('assesment.user.view', $assesment->slug) }}" class="btn btn-danger">Retry</a>
                </div>
                @endif --}}
             </div>

           </div>

        </div>
    </div>
</div>
@endsection

@php
    $color = '';
    if($assesment_result->pass){
        $color = 'tabler.getColor("primary"), tabler.getColor("primary", 0.8), tabler.getColor("primary", 0.6), tabler.getColor("gray-300")';
    } else {
        $color = 'tabler.getColor("danger"), tabler.getColor("danger", 0.8), tabler.getColor("danger", 0.6), tabler.getColor("gray-300")';
    }
@endphp

@section('style')
<style>
[data-bs-theme=dark] .apexcharts-text {
    fill : #f6f8fb !important;
}
</style>
@endsection

@section('script')
<script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js?1684106062') }}" defer></script>
<script>
document.addEventListener("DOMContentLoaded", function() {

    var options = {
        series: [{{ (int)$assesment_result->score }}],
        chart: {
        height: 200,
        type: 'radialBar',
    },
    plotOptions: {
        radialBar: {
          hollow: {
              size: '70%',
          },
          dataLabels: {
              name: {
                show: false
              },
              value: {
                fontSize: '22px', fontFamily:void 0,fontWeight:600,color:void 0,offsetY:7
              }
            }
        },
    },
    colors: [{!! $color !!}],
    labels: ['Score'],
    };

    var chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();
});

</script>
@endsection
