@php
    $isEdit = isset($assesment) && $assesment;
@endphp

<div class="owl-section-block mb-4 pt-3 border-top">
    <div class="owl-section-header mb-3 pb-2 border-bottom">
        <div class="d-flex align-items-center">
            <span class="owl-section-number me-2">3</span>
            <h3 class="owl-section-title mb-0 fw-bold">Add Question Bank</h3>
        </div>
        <div class="text-muted small ms-4 ps-1">
            Attach question bank resources to this assessment.
        </div>
    </div>

    @if ($isEdit)
        <div class="card bg-dark-lt border-0 shadow-sm">
            <div class="card-body">
                <div class="row align-items-end g-3">
                    <div class="col-lg-7">
                        <label class="form-label required">Question Bank</label>
                        <x-select name="id_question_bank" id="id-question-bank" class="select2">
                            <option value="" data-total-questions="0">- Choose Question Bank -</option>
                            @foreach ($questionBanks as $questionBank)
                                @php
                                    $bankQuestionCount = $questionBank->questions_count ?? count($questionBank->questions);
                                @endphp
                                <option value="{{ $questionBank->id }}" data-total-questions="{{ $bankQuestionCount }}">
                                    {{ $questionBank->title }} ({{ $bankQuestionCount }})
                                </option>
                            @endforeach
                        </x-select>
                        <div class="invalid-feedback d-none" id="invalid-id-question-bank"></div>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label required">Question Used</label>
                        <input type="number" min="0" name="amount_question" id="amount-question" value="" class="form-control">
                        <div class="invalid-feedback d-none" id="invalid-amount-question"></div>
                    </div>

                    <div class="col-lg-2">
                        <button type="button" id="btn-store-qeustion-bank" class="btn btn-success w-100">
                            <i class="fas fa-plus me-2"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-0" role="alert">
            <i class="fas fa-info-circle me-3 fa-lg"></i>
            <div>
                <strong>Note:</strong> Question banks can be added after saving the initial assessment details.
            </div>
        </div>
    @endif
</div>
