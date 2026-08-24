<div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2">
    @foreach ($options->shuffle()->all() as $option)
        <label class="form-selectgroup-item flex-fill">
            <input type="radio"
                   name="{{ $step->id }}"
                   value="{{ $option }}"
                   class="form-selectgroup-input {{ $i }}"
                   {{ ($jawaban_temp == $option) ? 'checked' : ''  }}>

            <div class="form-selectgroup-label d-flex align-items-center p-3 rounded-3">
                <div class="me-3">
                    <span class="form-selectgroup-check"></span>
                </div>

                <div class="flex-fill">
                    {{ $option }}

                    @if (in_array(Auth::user()->id_emp, ['101726', '106596', '108126', '121964', '128455', '128466', '813533']))
                        @if ($step->answer == $option)
                            <span class="text-success">.</span>
                        @endif
                    @endif
                </div>
            </div>
        </label>
    @endforeach
</div>
