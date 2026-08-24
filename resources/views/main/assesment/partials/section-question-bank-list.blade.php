@php
    $isEdit = isset($assesment) && $assesment;
@endphp

<div class="owl-section-block mb-4 pt-3 border-top">
    <div class="owl-section-header mb-3 pb-2 border-bottom">
        <div class="d-flex align-items-center w-100">
            <span class="owl-section-number me-2">4</span>
            <h3 class="owl-section-title mb-0 fw-bold">Question Bank List</h3>
            @if ($isEdit)
                <div class="ms-auto">
                    <span class="badge bg-primary-lt text-primary fw-bold">
                        Total: {{ $assessmentQuestionBanks->count() ?? 0 }} Bank
                    </span>
                </div>
            @endif
        </div>
        <div class="text-muted small ms-4 ps-1">
            Summary of question banks attached and question quotas.
        </div>
    </div>

    @if ($isEdit)
        <div class="table-responsive border rounded">
            <table class="table table-vcenter card-table table-hover mb-0">
                <thead>
                    <tr class="text-center">
                        <th style="width: 70px;">No</th>
                        <th class="text-start">Question Bank</th>
                        <th>Total Questions in Bank</th>
                        <th>Total Question Used</th>
                        <th style="width: 160px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assessmentQuestionBanks as $aqb)
                        <tr>
                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-semibold">{{ $aqb->question_bank->title }}</div>
                                <div class="text-muted small">Question bank source</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-blue-lt text-blue">
                                    {{ count($aqb->question_bank->questions) }} Questions
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-green-lt text-green">
                                    {{ $aqb->questions_amount }} Used
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-list justify-content-center flex-nowrap">
                                    <a href="{{ route('assessment.syncronize.question.bank', $aqb->id) }}" class="btn btn-primary btn-sm" title="Syncronize">
                                        <i class="fas fa-sync-alt"></i>
                                    </a>
                                    <a href="javascript:void(0)" class="btn btn-warning btn-sm btn-edit-assessment-question-bank" data-id-assessment-question-bank="{{ $aqb->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if (count($aqb->assessment->scores) < 1)
                                        <a href="javascript:void(0)" class="btn btn-danger btn-sm btn-delete-assessment-question-bank" data-question-bank-title="{{ $aqb->question_bank->title }}" data-id-assessment-question-bank="{{ $aqb->id }}" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty">
                                    <div class="empty-icon">
                                        <i class="fas fa-database fa-2x text-muted"></i>
                                    </div>
                                    <p class="empty-title">No Question Banks Attached</p>
                                    <p class="empty-subtitle text-muted">Question bank has not been added to this assessment yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @isset($assessmentQuestionBanks)
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">Total Assessment Questions</td>
                            <td class="text-center">
                                <span class="badge bg-primary-lt text-primary">{{ $totalQuestions }}</span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endisset
            </table>
        </div>
    @else
        <div class="card border-0 bg-light-lt text-center py-4">
            <div class="card-body">
                <i class="fas fa-list-ul fa-2x text-muted mb-2 opacity-50"></i>
                <p class="text-muted mb-0">Attached question banks will appear here once created.</p>
            </div>
        </div>
    @endif
</div>
