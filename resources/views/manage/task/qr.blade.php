{{-- resources/views/manage/task/qr.blade.php --}}
{{-- Share a standalone task via QR code / link / raw code. --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="mb-2">
                <a href="{{ route('manage.task.parent', $task->parentRouteParams()) }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Back
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index') }}">Task Management</a>
                    </div>
                    <h2 class="page-title">
                        <i class="ti ti-qrcode me-1"></i>Share Task
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="card-title mb-1">{{ $task->title }}</h3>
                            <div class="text-muted small mb-3">Others — share with specific users</div>

                            @if ($qrSvg)
                                <div class="mb-3 d-inline-block p-3 bg-white border rounded">
                                    {!! $qrSvg !!}
                                </div>
                                <div class="text-muted small mb-3">Scan to unlock this task</div>
                            @else
                                <div class="alert alert-warning text-start">
                                    QR generator is not installed yet. Run this once in
                                    <code>C:\laragon\www\owl</code>:
                                    <code class="d-block mt-1">composer require simplesoftwareio/simple-qrcode</code>
                                    then reload this page. The link and code below already work.
                                </div>
                            @endif

                            <div class="text-start">
                                <label class="form-label">Redeem link</label>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="qrRedeemUrl" readonly value="{{ $redeemUrl }}">
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="navigator.clipboard.writeText(document.getElementById('qrRedeemUrl').value)">
                                        <i class="ti ti-copy me-1"></i>Copy
                                    </button>
                                </div>

                                @if ($task->access_code)
                                    <label class="form-label">Access code</label>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="qrAccessCode" readonly value="{{ $task->access_code }}">
                                        <button type="button" class="btn btn-outline-secondary"
                                                onclick="navigator.clipboard.writeText(document.getElementById('qrAccessCode').value)">
                                            <i class="ti ti-copy me-1"></i>Copy
                                        </button>
                                    </div>
                                @endif

                                @if (!empty($shareNote))
                                    <div class="alert alert-info py-2 small mb-0">{{ $shareNote }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
