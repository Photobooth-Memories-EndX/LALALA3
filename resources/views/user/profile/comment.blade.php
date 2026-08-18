@extends('layouts.sidebar-master')

@section('content')
@include('user.profile.partials.styles')

<div class="page-body">
    <div class="container-xl">
        <x-alert type='success' />
        <x-alert type='warning' />

        <div class="card my-profile-card">
            <div class="row g-0">
                @include('user.profile.partials.nav')

                <div class="col-12 col-md-9 d-flex flex-column" id="tabmain">
                    <div class="card-body p-3 p-md-4">
                        <div class="border-bottom pb-3 mb-4">
                            <h2 class="h2 mb-1 text-body">My Comments</h2>
                            <p class="text-muted mb-0 small">View your posted comments and discussion history across OWL Learning Hub.</p>
                        </div>

                        @if (count($comment) > 0)
                            <div class="list-group list-group-flush border rounded-3 overflow-hidden" id="comment_list">
                                @foreach ($comment as $list)
                                    @php
                                        $url = '#';
                                        $judul = '-';
                                        if ($list->type == 'content' && isset($list->content)) {
                                            $url = route('content.user.view', $list->content->slug ?? '');
                                            $judul = $list->content->title ?? '-';
                                        } elseif ($list->type == 'news' && isset($list->news)) {
                                            $url = route('news.user.view', $list->news->slug ?? '');
                                            $judul = $list->news->title ?? '-';
                                        } elseif ($list->type == 'eventphoto' && isset($list->eventphoto)) {
                                            $url = route('gallery.user.view', $list->eventphoto->slug ?? '');
                                            $judul = $list->eventphoto->title ?? '-';
                                        }
                                        $typeName = $commenttype[$list->type] ?? ucfirst($list->type);
                                    @endphp
                                    <div class="list-group-item p-3">
                                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <span class="badge bg-primary-lt text-primary me-2">{{ $typeName }}</span>
                                                <a class="fw-bold text-body text-decoration-none" href="{{ $url }}#comment-{{ $list->id }}">
                                                    {{ $judul }} <i class="ti ti-external-link small ms-1 text-muted"></i>
                                                </a>
                                            </div>
                                            <span class="text-muted small ms-sm-auto text-nowrap">
                                                <i class="ti ti-clock me-1"></i>{{ \Carbon\Carbon::parse($list->created_at)->diffForHumans() }}
                                            </span>
                                        </div>
                                        <div class="text-muted small bg-body-tertiary p-2.5 rounded-2 border-start border-3 border-primary">
                                            {!! nl2br(e($list->description)) !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="avatar avatar-xl rounded-circle bg-primary-lt mb-3">
                                    <i class="ti ti-message-dots fs-1 text-primary"></i>
                                </div>
                                <h4 class="text-body mb-1">No comments found</h4>
                                <p class="text-muted small">You haven't posted any comments yet.</p>
                            </div>
                        @endif
                    </div>

                    <div class="card-footer bg-transparent border-top mt-auto p-3 p-md-4">
                        <div class="d-flex justify-content-end align-items-center my-profile-actions">
                            <button type="button" class="btn btn-outline-secondary px-4" onclick="handleCancel(event)">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleCancel(e) {
        if (e) e.preventDefault();
        if (document.referrer && document.referrer !== window.location.href && document.referrer.includes(window.location.host)) {
            window.history.back();
        } else {
            window.location.href = "{{ route('user.profile') }}";
        }
    }
</script>
@endpush
