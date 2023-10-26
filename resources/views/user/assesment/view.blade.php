@extends('layouts.master')
@section('content')

<div class="page-body">
    <div class="container-xl">
        @if ($assesment->timer == 'y')
        <div class="row g-2 align-items-center">
            <div class="col"></div>
            <div class="col-auto ms-auto d-print-none h1" id="timer"></div>
        </div>
        @endif

        <div class="card card-lg">
            <div class="card-body ">
              <div class="row g-4">
                <div class="col-12 markdown mb-3">
                  <h1 class="mb-2">{{ $assesment->title }}</h1>

                  <div class="d-flex align-items-center">
                    <div>
                      <div class="text-muted">{{ date('d F Y', strtotime(now())) }}</div>
                    </div>
                  </div>

                </div>

                <x-form action="{{ route('assesment.user.store', $assesment->slug) }}" method="post" id="formAssesment">
                @unless(count($assesment_question) == 0)

                    <div class="mb-3 col-12">
                        <!-- SmartWizard html -->
                        <div id="smartwizard">
                            <ul class="nav">
                                @foreach ($assesment_question as $step)
                                <li class="nav-item">
                                    <a class="nav-link" href="#step-{{ $step->id }}">
                                        <div class="num">{{ $loop->index + 1 }}</div>
                                    </a>
                                </li>
                                @endforeach
                            </ul>

                            <!-- Include optional progressbar HTML -->
                            <div class="progress" style="display: none;">
                                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                            <div class="tab-content">
                                @foreach ($assesment_question as $step)
                                <div id="step-{{ $step->id }}" class="tab-pane" role="tabpanel" aria-labelledby="step-{{ $step->id }}">
                                    <div class="mb-3">
                                        {{-- <label class="form-label h2">{{ $step->question }}</label> --}}
                                        {{-- <p class="form-label h3">{!! nl2br($step->question) !!}</p> --}}
                                        <div class="display-6" style="font-size: 1.2rem !important">
                                            {!! $step->question !!}
                                        </div>
                                        @if ($step->image != '')
                                        <img src="{{ asset('storage/assesment').'/'.$step->image }}" class="img-fluid">
                                        @endif
                                    </div>
                                    <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                    @php
                                        $options = collect($step->option);
                                        $jawaban_temp = $assesment_result_draft->user_answer[$step->id] ?? null;
                                    @endphp

                                        @foreach ($options->shuffle()->all() as $option)
                                        <label class="form-selectgroup-item flex-fill">
                                        <input type="radio" name="{{ $step->id }}" value="{{ $option }}" class="form-selectgroup-input" {{ ($jawaban_temp == $option) ? 'checked' : ''  }}>
                                        <div class="form-selectgroup-label d-flex align-items-center p-2">
                                            <div class="me-3">
                                                <span class="form-selectgroup-check"></span>
                                            </div>
                                            <div>
                                                {{ $option }}
                                            </div>
                                        </div>
                                        </label>
                                        @endforeach

                                    </div>
                                </div>
                                @endforeach
                            </div>

                        </div>
                        <!-- SmartWizard html -->

                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure want to finish the assesment and submit all the answer?')">Finish & Submit Answers</button>
                    </div>
                @else
                    <p>No questions data yet</p>
                @endunless

                </x-form>
              </div>
            </div>
        </div>
    </div>
  </div>

@endsection

@section('style')
<link href="{{ asset('css/smart_wizard.min.css') }}" rel="stylesheet" type="text/css" />
<style>
@media all and (min-width:0px) and (max-width: 768px) {
  #smartwizard .nav{display: none;}
  #smartwizard .progress{display: block !important;}
}
</style>
<link href="{{ asset('css/smart_wizard_dots.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('script')
<script src="{{ asset('js/jquery.smartWizard.min.js') }}" type="text/javascript"></script>
<script>
 $(function() {
    $('#smartwizard').smartWizard({
        selected: 0,
        theme: 'dots',
        enableUrlHash: false,
        // toolbar: {
        //     extraHtml: '<button type="submit" class="btn btn-success btn-finish text-end" onclick="return confirm(\'Are you sure want to finish the assesment and submit all the answer?\')">Submit Answers</button>'
        // }
    });
});
</script>

@if (!auth()->user()->isAdmin())
<script>
    $(function() {
       $(document).on('click', '.sw-btn', function(e) {
           let formData = new FormData($('#formAssesment')[0]);
           $.ajax({
               type: "POST",
               url: "{{ route('assesment.user.autosave', $assesment->slug) }}",
               data: formData,
               contentType: false,
               processData: false,
               success: function (response) {
                   if (response.status== 200)
                   {
                       console.log(response.message);
                   }
                   else{
                       console.log('Gagal Autosave');
                   }
               }
           });
       });
   });
</script>
@endif

    @if ($assesment->timer == 'y')
    <script>
        // Set the date we're counting down to
        var countDownDate = new Date("{{ $assesment->todate }}").getTime();
        // Update the count down every 1 second
        var x = setInterval(function() {
            // Get today's date and time
            var now = new Date().getTime();
            // Find the distance between now and the count down date
            var distance = countDownDate - now;
            // Time calculations for hours, minutes and seconds
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            // Display the result in the element with id="timer"
            document.getElementById("timer").innerHTML = hours + "h "
            + minutes + "m " + seconds + "s ";
            // If the count down is finished, write some text
            if (distance < 0) {
                clearInterval(x);
                document.getElementById("timer").innerHTML = "EXPIRED";

                @if (!auth()->user()->isAdmin())
                document.getElementById("formAssesment").submit();
                @endif

            }
        }, 1000);
    </script>
    @endif
@endsection
