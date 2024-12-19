@props(['content', 'comment'])

@php
    $url = '';
    if ($attributes->get('type') == 'content') {
        $hiddenplaylistslug = '';
        $url = route('content.comment.store', $content->slug);
    } elseif ($attributes->get('type') == 'news') {
        $hiddenplaylistslug = '';
        $url = route('news.comment.store', $content->slug);
    } elseif ($attributes->get('type') == 'contentplaylist') {
        $hiddenplaylistslug = $attributes->get('playlistslug');
        $url = route('contentplaylist.comment.store', $content->slug);
    } else {
        $hiddenplaylistslug = '';
        $url = route('gallery.comment.store', $content->slug);
    }
@endphp

<div class="container-xl mt-4">
    <div class="card card-lg" id="comment">
        <div class="card-body py-5">

            <div class="row">
                <div class="col-auto">
                    <span class="avatar avatar-md" style="background-image: url({{ asset('storage/profile') }}/{{ auth()->user()->avatar ?? 'default.jpg' }})"></span>
                </div>
                <div class="col">
                    <x-form action="{{ $url }}" method="post">
                        <x-textarea class="form-control" name="description" data-bs-toggle="autosize" placeholder="Enter your thoughts.…" onkeyup="countWord(this)" maxlength="1000" cols="1000"></x-textarea>
                        {{-- {{ $hiddenplaylistslug }} --}}
                        <input type="hidden" value="{{ $hiddenplaylistslug }}" name="playlistslug">
                        <div class="row mt-3">
                            <div class="col">
                                <span id="note_remaining" class="text-muted small">1000 characters remaining</span>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-success" type="submit">Submit comment</button>
                            </div>
                        </div>
                    </x-form>
                </div>
            </div>

            @include('components.commentDisplay', ['comments' => $comment])

        </div>
    </div>
</div>

@push('scripts')
    <script>
        function countWord(val) {
            var len = val.value.length;
            if (len >= 1000) {
                $('#note_remaining').text("");
            } else {
                $('#note_remaining').text(1000 - len + " characters remaining");
            }
        };
    </script>
@endpush
