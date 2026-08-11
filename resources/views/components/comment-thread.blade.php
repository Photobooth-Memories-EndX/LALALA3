@php
    /*
    |--------------------------------------------------------------------------
    | Comment thread (Instagram-style, single reply level)
    |--------------------------------------------------------------------------
    | Only ROOT comments are passed in here. All replies are flattened into a
    | single level, sorted oldest-first, and hidden behind a show/hide toggle.
    */

    // Collapse any (legacy) nested reply tree into one flat list.
    $flattenReplies = function ($items) use (&$flattenReplies) {
        $all = collect();

        foreach ($items as $item) {
            $all->push($item);

            if ($item->relationLoaded('replies') && $item->replies->isNotEmpty()) {
                $all = $all->merge($flattenReplies($item->replies));
            }
        }

        return $all;
    };

    $replies = ($comment->relationLoaded('replies')
        ? $flattenReplies($comment->replies)
        : collect())
        ->sortBy('created_at')
        ->values();

    $replyCount = $replies->count();
@endphp

<div class="comment-thread border-bottom" id="comment-{{ $comment->id }}">

    {{-- Root comment --}}
    @include('components.comment-item', [
        'item'    => $comment,
        'isReply' => false,
        'rootId'  => $comment->id,
    ])

    {{-- Reply form (single, hidden by default, always attaches to this root comment) --}}
    <div id="reply-form-{{ $comment->id }}"
         class="reply-form d-none mt-2"
         data-comment-id="{{ $comment->id }}">
        <x-form action="{{ $url }}"
                method="post"
                class="comment-form comment-reply-form w-100"
                data-comment-id="{{ $comment->id }}">
            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
            <input type="hidden" name="playlistslug" value="{{ $hiddenplaylistslug }}">

            <div class="d-flex gap-2 align-items-end w-100">
                <div class="comment-input-wrapper flex-grow-1 w-100">
                    <x-textarea
                        class="comment-textarea comment-reply-textarea"
                        id="reply-textarea-{{ $comment->id }}"
                        name="description"
                        placeholder="Write a reply..."
                        maxlength="1000"
                        rows="1"></x-textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 flex-shrink-0">
                    <i class="ti ti-send"></i>
                </button>
            </div>
        </x-form>
    </div>

    {{-- Replies: hidden by default, toggled with View/Hide replies --}}
    @if($replyCount > 0)
        <div class="replies-block">
            <button type="button"
                    class="btn btn-link btn-sm text-decoration-none comment-replies-toggle"
                    data-target="replies-{{ $comment->id }}">
                <i class="ti ti-chevron-down replies-chevron"></i>
                <span class="replies-label-show">View {{ $replyCount }} {{ $replyCount === 1 ? 'Reply' : 'Replies' }}</span>
                <span class="replies-label-hide">Hide Replies</span>
            </button>

            <div id="replies-{{ $comment->id }}" class="reply-list d-none">
                @foreach($replies as $reply)
                    @include('components.comment-item', [
                        'item'    => $reply,
                        'isReply' => true,
                        'rootId'  => $comment->id,
                    ])
                @endforeach
            </div>
        </div>
    @endif

</div>
