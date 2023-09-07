@extends('layouts.master')
@section('content')

@php
    use App\Models\Content;
    $content = Content::select('id', 'title', 'type', 'slug', 'duration', 'link')->whereIn('type', ['YouTube', 'Video'])->where('active', 'Active')->get();
@endphp

<div class="page-body">
    <div class="container-xl">
      <div class="card">
        <div class="row g-0">

          <div class="col d-flex flex-column border-end">
            <div class="card-body">
              <h2 class="mb-4">TOP VIDEO LEARNING</h2>


            <div class="card card-md">
                <div class="card-body p-3">

                    <div class="col">

                        <div id="carousel-captions" class="carousel slide c-home" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            @foreach ($content as $item)
                            <div class="carousel-item {{ ($loop->first) ? 'active' : '' }}">

                              <div class="col-md-3">
                                <div class="card border-0">
                                    @if ($item->type == "YouTube")
                                    <div class="img-responsive img-responsive-4x3" style="background-image: url(http://img.youtube.com/vi/{{ $item->link }}/sddefault.jpg); position:relative;"><div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div></div>
                                    @else
                                    @php
                                        $src = 'storage/files/content/'.$item->id.'/thumb.jpg';
                                    @endphp
                                        @if (\File::exists(public_path($src)) && !is_dir($src))
                                        <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset($src) }});position:relative;"><div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div></div>
                                        @else
                                        <div class="img-responsive img-responsive-4x3" style="background-image: url(https://via.placeholder.com/640x480.png/7F7F7F/ffffff?text=Default-thumbnail);position:relative;"><div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div></div>
                                        @endif

                                    @endif

                                  <div class="card-body p-2">
                                    <h3 class="card-title h4"><a href="{{ route('content.user.view', $item->slug) }}">{{ Str::limit($item->title, 60) }}</a></h3>
                                  </div>
                                </div>
                              </div>

                            </div>
                            @endforeach

                        </div>
                        <a class="carousel-control-prev" href="#carousel-captions" role="button" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#carousel-captions" role="button" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </a>
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
<style>
 @media (max-width: 767px) {
		.carousel-inner .carousel-item > div {
			display: none;
		}
		.carousel-inner .carousel-item > div:first-child {
			display: block;
		}

	}

	.carousel-inner .carousel-item.active,
	.carousel-inner .carousel-item-next,
	.carousel-inner .carousel-item-prev {
		display: flex;
	}

	/* medium and up screens */
	@media (min-width: 768px) {

		.carousel-inner .carousel-item-end.active,
		.carousel-inner .carousel-item-next {
			transform: translateX(25%);
		}

		.carousel-inner .carousel-item-start.active,
		.carousel-inner .carousel-item-prev {
			transform: translateX(-25%);
		}
        .c-home .carousel-control-prev, .c-home .carousel-control-next{width: 5% !important;}
	}

	.carousel-inner .carousel-item-end,
	.carousel-inner .carousel-item-start {
		transform: translateX(0);
	}
</style>
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

  <script>
    let items = document.querySelectorAll('.carousel .carousel-item')

    items.forEach((el) => {
        const minPerSlide = 4
        let next = el.nextElementSibling
        for (var i=1; i<minPerSlide; i++) {
            if (!next) {
                // wrap carousel by using first child
                next = items[0]
            }
            let cloneChild = next.cloneNode(true)
            el.appendChild(cloneChild.children[0])
            next = next.nextElementSibling
        }
    })
  </script>
@endsection
