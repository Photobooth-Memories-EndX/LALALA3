@extends('layouts.sidebar-master')

@php
    $trainingplan       = $trainingplan ?? null;
    $trainingplandetail = $trainingplandetail ?? collect();
    $isLocked           = $isLocked ?? ($trainingplan && $trainingplan->active === 'Active');
    $title              = $title ?? 'Order Playlist Content';
@endphp

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div style="max-width: 1100px; margin: 0 auto;">
                <div class="mb-3">
                    <a href="{{ route('playlist.edit', $trainingplan->slug) }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none">
                        <i class="ti ti-arrow-left"></i>
                        <span>Back to Edit</span>
                    </a>
                </div>
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Manage / Playlist</div>
                        <h2 class="owl-page-title mb-1">{{ $title }}</h2>
                        <div class="owl-page-subtitle d-flex align-items-center gap-2">
                            <span>{{ $trainingplan->title }}</span>
                            @if ($trainingplan->active === 'Active')
                                <span class="badge bg-success-lt text-success">Active</span>
                            @else
                                <span class="badge bg-warning-lt text-warning">Inactive</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="javascript:;">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('playlist.index') }}">Playlist</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('playlist.edit', $trainingplan->slug) }}">Edit Playlist</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Order Content</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div style="max-width: 1100px; margin: 0 auto;">

                <x-alert type="success" />
                <x-alert type="warning" />
                <x-alert type="danger" />

                @if ($isLocked)
                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3 py-2 px-3">
                        <i class="ti ti-lock fs-2 text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <strong class="d-block text-dark small mb-1">Ordering is locked</strong>
                            <span class="small text-secondary">
                                This playlist is currently active. Unpublish the playlist before changing the content order.
                            </span>
                        </div>
                    </div>
                @endif

                <form action="{{ route('playlist.update', $trainingplan->slug) }}" method="post" autocomplete="off" id="formOrderPlaylist">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="savetype" value="reorder">

                    <div class="row g-3">
                        {{-- Left Column: Sortable List --}}
                        <div class="col-lg-8">
                            @include('main.trainingplan.components.playlist-order-list', [
                                'trainingplan'       => $trainingplan,
                                'trainingplandetail' => $trainingplandetail,
                                'isLocked'           => $isLocked,
                            ])
                        </div>

                        {{-- Right Column: Guidance Panel --}}
                        <div class="col-lg-4">
                            @include('main.trainingplan.components.playlist-order-guide', [
                                'trainingplan' => $trainingplan,
                                'isLocked'     => $isLocked,
                            ])
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        .trainingplan-sortlist {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .trainingplan-sortlist-item {
            transition: background .15s ease, box-shadow .15s ease, transform .15s ease;
            user-select: none;
        }

        .trainingplan-sortlist-item:hover {
            background-color: #f8fafc !important;
            box-shadow: 0 4px 12px rgba(0,0,0,.05) !important;
        }

        .trainingplan-drag-handle {
            cursor: grab;
            user-select: none;
            touch-action: none;
        }

        .trainingplan-drag-handle:active {
            cursor: grabbing;
        }

        .sortable-ghost {
            opacity: 0.35;
            background-color: #e2e8f0 !important;
            border: 2px dashed #94a3b8 !important;
        }

        .sortable-chosen {
            background-color: #f1f5f9 !important;
            box-shadow: 0 6px 16px rgba(0,0,0,.1) !important;
        }

        /* Dark mode */
        [data-theme="dark"] .trainingplan-sortlist-item,
        body.theme-dark .trainingplan-sortlist-item {
            background: #182433 !important;
            border-color: #25384d !important;
            color: #dce6f1 !important;
        }

        [data-theme="dark"] .trainingplan-sortlist-item:hover,
        body.theme-dark .trainingplan-sortlist-item:hover {
            background: #223247 !important;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sortableEl = document.getElementById('sortlist');
            var formEl = document.getElementById('formOrderPlaylist');
            var isLocked = {{ $isLocked ? 'true' : 'false' }};
            var saveBtn = document.getElementById('btnSaveOrder');
            var spinner = document.getElementById('saveSpinner');
            var saveIcon = document.getElementById('saveIcon');
            var saveBtnText = document.getElementById('saveBtnText');

            function updateSequenceBadges() {
                if (!sortableEl) return;
                var items = sortableEl.querySelectorAll('.trainingplan-sortlist-item');
                items.forEach(function (item, index) {
                    var badge = item.querySelector('.trainingplan-item-badge');
                    if (badge) {
                        badge.textContent = index + 1;
                    }
                });
            }

            // Initialize SortableJS if not locked
            if (sortableEl && !isLocked && typeof Sortable !== 'undefined') {
                new Sortable(sortableEl, {
                    handle: '.trainingplan-drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function () {
                        updateSequenceBadges();
                    }
                });
            }

            // Form Submit: Rebuild hidden inputs from live DOM order
            if (formEl) {
                formEl.addEventListener('submit', function (e) {
                    if (isLocked) {
                        e.preventDefault();
                        return;
                    }

                    if (saveBtn && saveBtn.getAttribute('data-submitting') === 'true') {
                        e.preventDefault();
                        return;
                    }

                    if (!sortableEl) return;

                    var items = sortableEl.querySelectorAll('.trainingplan-sortlist-item');
                    if (items.length === 0) return;

                    // Remove any existing/stale order inputs
                    formEl.querySelectorAll('input[name^="order["]').forEach(function (el) {
                        el.remove();
                    });

                    // Build fresh order inputs strictly according to DOM order
                    items.forEach(function (item, index) {
                        var detailId = item.getAttribute('data-detail-id');
                        if (detailId) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'order[' + detailId + ']';
                            input.value = index;
                            formEl.appendChild(input);
                        }
                    });

                    // Set loading state on button
                    if (saveBtn) {
                        saveBtn.disabled = true;
                        saveBtn.setAttribute('data-submitting', 'true');
                        if (spinner) spinner.classList.remove('d-none');
                        if (saveIcon) saveIcon.classList.add('d-none');
                        if (saveBtnText) saveBtnText.textContent = 'Saving...';
                    }
                });
            }
        });
    </script>
@endsection