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

                          <div class="tab-content">
                            @foreach ($assesment_question as $step)
                              <div id="step-{{ $step->id }}" class="tab-pane" role="tabpanel" aria-labelledby="step-{{ $step->id }}">
                                <div class="mb-3">
                                    <label class="form-label">{{ $step->question }}</label>
                                    @if ($step->image != '')
                                    <img src="{{ asset('storage/assesment').'/'.$step->image }}" class="img-fluid">
                                    @endif
                                </div>
                                <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                    @php
                                        $options = collect($step->option);
                                    @endphp
                                    @foreach ($options->shuffle()->all() as $option)
                                    <label class="form-selectgroup-item flex-fill">
                                      <input type="radio" name="soal-{{ $step->id }}" value="{{ $option }}" class="form-selectgroup-input">
                                      <div class="form-selectgroup-label d-flex align-items-center p-3">
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

                          <!-- Include optional progressbar HTML -->
                          {{-- <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                          </div> --}}
                      </div>
                      <!-- SmartWizard html -->

                    </div>
                    @else
                    <p>No questions data yet</p>
                    @endunless
              </div>
            </div>
        </div>
    </div>
  </div>

@endsection

@section('style')
<!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/smartwizard@6/dist/css/smart_wizard_all.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('script')
<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/smartwizard@6/dist/js/jquery.smartWizard.min.js" type="text/javascript"></script>
<script>
 $(function() {
    $('#smartwizard').smartWizard({
        selected: 0,
        theme: 'dots',
        enableUrlHash: false,
    });
});
</script>
@endsection
