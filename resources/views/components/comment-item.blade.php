@php
    /*
    |--------------------------------------------------------------------------
    | Single comment / reply row (Instagram-style)
    |--------------------------------------------------------------------------
    | Params:
    |   $item    -> Comment model (root comment OR reply)
    |   $isReply -> bool, true when rendered inside the reply list
    |   $rootId  -> id of the top-level parent comment (reply form target)
    */
    $isReply = $isReply ?? false;
    $rootId  = $rootId ?? $item->id;

    $likeIcon   = $item->is_liked ? 'ti-heart-filled' : 'ti-heart';
    $likeClass  = $item->is_liked ? 'is-liked' : '';
    $avatarSize = $isReply ? 'avatar-xs' : 'avatar-sm';
@endphp

<div class="d-flex gap-2 align-items-start comment-item {{ $isReply ? 'comment-item-reply' : 'comment-item-root' }}" id="comment-row-{{ $item->id }}">

    <div class="flex-shrink-0">
        @if(!empty($item->user->avatar))
            <span class="avatar {{ $avatarSize }} rounded-circle"
                  style="background-image:url('{{ asset('storage/profile/' . $item->user->avatar) }}')"></span>
        @else
            <span class="avatar {{ $avatarSize }} rounded-circle bg-primary-lt text-primary fw-bold">
                {{ strtoupper(substr($item->user->name ?? 'U', 0, 1)) }}
            </span>
        @endif
    </div>

    <div class="flex-fill min-w-0 w-100">
        <div class="comment-bubble">
            <div class="fw-semibold comment-name">{{ $item->user->name ?? 'User' }}</div>
            <div class="comment-text">{!! nl2br(e($item->description)) !!}</div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-3 mt-1 comment-meta">
            <span class="text-muted">{{ $item->created_at->diffForHumans() }}</span>

            <button type="button"
                    class="btn btn-link p-0 comment-action comment-like-toggle {{ $likeClass }}"
                    data-comment-id="{{ $item->id }}"
                    data-url="{{ route('comment.like', $item->id) }}"
                    aria-pressed="{{ $item->is_liked ? 'true' : 'false' }}">
                <i class="ti {{ $likeIcon }}"></i>
                <span class="comment-like-count">{{ $item->likes_count }}</span>
            </button>

            <button type="button"
                    class="btn btn-link p-0 comment-action text-muted comment-reply-toggle"
                    data-comment-id="{{ $item->id }}"
                    data-parent-id="{{ $rootId }}"
                    data-target="reply-form-{{ $rootId }}">
                Reply
            </button>

            @if(auth()->check() && (auth()->id() === $item->user_id || (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()) || auth()->user()->role === 'admin' || (int) auth()->user()->role === \App\Models\User::ROLE_ADMIN))
                <button type="button"
                        class="btn btn-link p-0 comment-action text-danger text-decoration-none border-0 bg-transparent comment-delete-btn"
                        data-comment-id="{{ $item->id }}"
                        data-url="{{ route('comments.destroy', $item->id) }}">
                    Delete
                </button>
            @endif
        </div>
    </div>

</div>
