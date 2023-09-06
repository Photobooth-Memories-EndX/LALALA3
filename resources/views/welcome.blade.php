@extends('layouts.master')
@section('content')

{{-- <div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            Home
          </h2>
        </div>

      </div>
    </div>
</div> --}}

<div class="page-body">
    <div class="container-xl">
      <div class="card">
        <div class="row g-0">

          <div class="col d-flex flex-column border-end">
            <div class="card-body">
              <h2 class="mb-4">TOP VIDEO LEARNING</h2>

              <div class="row row-cards">

                <div class="col-12">
                  <div class="card card-md">
                    <div class="card-body p-3">
                      <div class="row align-items-center">
                        <div class="col">
                          <img src="{{ asset('courses.JPG') }}" class="img-fluid">
                        </div>
                        <div class="col-auto">
                          {{-- <a href="#" class="btn">
                            Book a demo
                          </a> --}}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <h2 class="mb-4 mt-5">Training Module</h2>

                <div class="row row-cards">

                    <div class="col-12">
                        <div class="card card-md">
                        <div class="card-body">
                            <div class="row align-items-center">
                            <div class="col">
                                <img src="{{ asset('modules.JPG') }}" class="img-fluid">
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>

                </div>

          </div>


        </div>

        <div class="col-3 d-none d-md-block">
            <div class="card-body">

                <div class="row">
                    <div class="col-auto">
                        <span class="avatar rounded">EK</span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Edi Kusumaedi</div>
                        <div class="text-muted small" style="font-size: 8pt">Information System Management Team</div>
                    </div>
                </div>

                <div class="row mt-5">
                    <div id="chart-demo-pie"></div>
                </div>

                <div class="row row-cards mt-3">

                    <div class="col-12">
                        <div class="card card-md">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                            <div class="col">
                                <h2 class="h3">Training</h2>
                                <img src="https://via.placeholder.com/700x500.png/7F7F7F/333333?text=Training Img" class="img-fluid">
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>

                </div>

                <div class="row row-cards mt-3">

                    <div class="col-12">
                        <div class="card card-md">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                            <div class="col">
                                <h2 class="h3">Notice</h2>
                                <ul>
                                    <li>Training Schedule.</li>
                                    <li>Knowledge</li>
                                </ul>
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

      </div>
    </div>
  </div>

@endsection

@section('style')
<link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
@endsection

@section('script')
<script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js?1684106062') }}" defer></script>
<script>
    // @formatter:off
    document.addEventListener("DOMContentLoaded", function () {
        window.ApexCharts && (new ApexCharts(document.getElementById('chart-demo-pie'), {
            chart: {
                type: "donut",
                fontFamily: 'inherit',
                height: 240,
                sparkline: {
                    enabled: true
                },
                animations: {
                    enabled: false
                },
            },
            fill: {
                opacity: 1,
            },
            series: [45, 55],
            labels: ["On Going Course", "Completed Course"],
            tooltip: {
                theme: 'dark'
            },
            grid: {
                strokeDashArray: 4,
            },
            colors: [tabler.getColor("primary"), tabler.getColor("primary", 0.8), tabler.getColor("primary", 0.6), tabler.getColor("gray-300")],
            legend: {
                show: true,
                position: 'left',
                offsetY: 12,
                markers: {
                    width: 10,
                    height: 10,
                    radius: 100,
                },
                itemMargin: {
                    horizontal: 8,
                    vertical: 8
                },
            },
            tooltip: {
                fillSeriesColor: false
            },
        })).render();
    });
    // @formatter:on
  </script>
@endsection
