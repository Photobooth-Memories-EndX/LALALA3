@php
    $replyCount = $comment->relationLoaded('replies')
        ? $comment->replies->count()
        : $comment->replies()->count();

    $isReply = ($depth ?? 0) > 0;

    $likeButtonClass = $comment->is_liked
        ? 'btn-primary'
        : 'btn-light border text-secondary';

    $likeIconClass = $comment->is_liked
        ? 'ti-thumb-up-filled'
        : 'ti-thumb-up';
@endphp

<div class="comment-thread comment-thread-root comment-level-{{ $depth ?? 0 }} {{ ($depth ?? 0) === 0 ? 'border-bottom' : 'mb-2' }}">

    <div class="d-flex gap-2 align-items-start">

        <div class="flex-shrink-0">
            @if(!empty($comment->user->avatar))
                <span class="avatar avatar-sm rounded-circle"
                    style="background-image:url('{{ asset('storage/profile/' . $comment->user->avatar) }}')">
                </span>
            @else
                <span class="avatar avatar-sm rounded-circle bg-primary-lt text-primary fw-bold">
                    {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                </span>
            @endif
        </div>

        <div class="flex-fill min-w-0">

            <div>

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                    <div>
                        <div class="fw-semibold small">
                            {{ $comment->user->name ?? 'User' }}
                        </div>

                        <div class="text-muted" style="font-size:12px;">
                            {{ $comment->created_at->diffForHumans() }}
                        </div>
                    </div>

                    @if($isReply)
                        <span class="badge bg-primary-lt text-primary rounded-pill small">
                            Reply
                        </span>
                    @endif

                </div>

                <div class="mt-2 text-secondary small lh-base">
                    {!! nl2br(e($comment->description)) !!}
                </div>

                <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">

                    <form action="{{ route('comment.like', $comment->id) }}" method="POST" class="d-inline">
                        @csrf

                        <button type="submit"
                            class="btn btn-sm py-1 px-2 rounded-pill {{ $likeButtonClass }}"
                            style="font-size:12px;">

                            <i class="ti {{ $likeIconClass }} me-1"></i>
                            Like

                            <span class="ms-1 badge rounded-pill {{ $comment->is_liked ? 'bg-white text-primary' : 'bg-secondary-lt text-secondary' }}"
                                style="font-size:10px;">
                                {{ $comment->likes_count }}
                            </span>
                        </button>
                    </form>

                    <button type="button"
                        class="btn btn-sm py-1 px-2 rounded-pill btn-outline-secondary"
                        style="font-size:12px;"
                        data-bs-toggle="collapse"
                        data-bs-target="#reply-form-{{ $comment->id }}"
                        aria-controls="reply-form-{{ $comment->id }}"
                        aria-expanded="false">

                        <i class="ti ti-message-circle-plus me-1"></i>
                        Reply

                        <span class="ms-1 badge rounded-pill bg-secondary-lt text-secondary"
                            style="font-size:10px;">
                            {{ $replyCount }}
                        </span>
                    </button>

                </div>

                <div class="collapse mt-2" id="reply-form-{{ $comment->id }}">
                    <x-form action="{{ $url }}" method="post">

                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <input type="hidden" value="{{ $hiddenplaylistslug }}" name="playlistslug">

                        <x-textarea
                            class="form-control modern-comment-input"
                            name="description"
                            placeholder="Tulis balasan..."
                            maxlength="1000"
                            rows="2">
                        </x-textarea>

                        <div class="d-flex justify-content-end mt-2">
                            <button class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="ti ti-send me-1"></i>
                                Reply
                            </button>
                        </div>

                    </x-form>
                </div>

                @if($replyCount > 0)
                    <div class="mt-3">
                        @foreach($comment->replies as $reply)
                            @include('components.comment-thread', [
                                'comment' => $reply,
                                'content' => $content,
                                'url' => $url,
                                'hiddenplaylistslug' => $hiddenplaylistslug,
                                'depth' => ($depth ?? 0) + 1,
                            ])
                        @endforeach
                    </div>
                @endif

            </div>

        </div>

    </div>

</div>