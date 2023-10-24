@extends('layouts.master')
@section('content')
    <div class="page-body">
        <div class="container-xl">
            <x-alert type='success' />
            <x-alert type='warning' />
            <div class="card">
                <div class="row g-0">
                    <div class="col-3 d-none d-md-block border-end">
                        <div class="card-body">
                            <div class="list-group list-group-transparent">
                                <a href="{{ route('user.profile') }}" class="list-group-item list-group-item-action d-flex align-items-center">My Account</a>
                                <a href="{{ route('user.comment') }}" class="list-group-item list-group-item-action d-flex align-items-center active">My Comment</a>
                                <a href="{{ route('user.changepassword') }}" class="list-group-item list-group-item-action d-flex align-items-center">Change Password</a>
                            </div>
                        </div>
                    </div>
                    <div class="col d-flex flex-column" id="tabmain">
                        <div class="card-body">
                            <h2 class="mb-4">My Comments</h2>

                            @unless (count($comment) == 0)
                                <div class="divide-y mt-5" id="comment_list">
                                    @foreach ($comment as $list)
                                        <div>
                                            <div class="row">
                                                <div class="col-auto">
                                                    {{-- <span class="avatar" style="background-image: url({{ asset('storage/profile') }}/{{ $list->user->avatar ?? 'default.jpg' }})"></span> --}}
                                                </div>
                                                <div class="col">
                                                    <div class="text-truncate">
                                                        <strong>{{ $list->user->name }}</strong> on <strong>{{ $commenttype[$list->type] }}</strong>
                                                        <br>
                                                        @php
                                                            $url = '';
                                                            $judul = '';
                                                            if ($list->type == 'content')
                                                            {
                                                                $url = route('content.user.view', $list->content->slug);
                                                                $judul = $list->content->title;
                                                            }
                                                            elseif($list->type == 'news')
                                                            {
                                                                $url = route('news.user.view', $list->news->slug) ;
                                                                $judul = $list->news->title;
                                                            }
                                                            elseif($list->type == 'eventphoto')
                                                            {
                                                                $url =  route('gallery.user.view', $list->eventphoto->slug);
                                                                $judul = $list->eventphoto->title;
                                                            }
                                                        @endphp
                                                        <a class="text-reset" href="{{ $url }}#comment-{{ $list->id }}">{{ $judul }}</a>
                                                    </div>
                                                    <div class="text-muted">{!! nl2br($list->description) !!}</div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="text-muted">{{ \Carbon\Carbon::parse($list->created_at)->diffForHumans() }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endunless

                        </div>
                        <div class="card-footer bg-transparent mt-auto">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

