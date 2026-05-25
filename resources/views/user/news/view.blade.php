@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">

    <div class="container-xl">

        <div class="card border-0">

            <img src="{{ asset('storage/news/'.$news->image) }}"
                 class="card-img-top"
                 style="height:500px;object-fit:cover;">

            <div class="card-body p-5">

                <div class="mb-3">

                    <span class="badge bg-blue-lt text-blue">
                        Innovation
                    </span>

                    <span class="ms-2 text-secondary">
                        {{ $news->created_at->format('M d, Y') }}
                    </span>

                </div>

                <h1 class="display-4 fw-bold mb-4">
                    {{ $news->title }}
                </h1>

                <div class="d-flex align-items-center mb-5">

                    <span class="avatar avatar-md me-3 bg-blue-lt">

                        {{ strtoupper(substr($news->user->name ?? 'U',0,1)) }}

                    </span>

                    <div>

                        <div class="fw-bold">

                            {{ $news->user->name ?? 'Unknown' }}

                        </div>

                        <div class="text-secondary">

                            Author

                        </div>

                    </div>

                </div>

                <div class="article-content fs-3 lh-lg text-secondary">

                    {!! nl2br($news->description) !!}

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
