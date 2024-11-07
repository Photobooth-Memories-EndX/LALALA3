@unless (count($comments) == 0)
    <div class="divide-y mt-5" id="comment_list">
        @foreach ($comments as $list)
            <div class="d-flex">
                <div class="avatar" style="background-image: url({{ asset('storage/profile') }}/{{ $list->user->avatar ?? 'default.jpg' }})"></div>
                <div style="width: 100%;" class="ms-3">
                    <div class="d-flex justify-content-between">
                        <div class="text-truncate">
                            <strong>{{ $list->user->name }}{{ $list->user->role == 2 ? '(Admin)' : ''}}</strong>
                        </div>
                        <div class="text-muted">{{ \Carbon\Carbon::parse($list->created_at)->diffForHumans() }}</div>
                    </div>
                    <div class="text-muted" id="comment-{{ $list->id }}">{!! nl2br($list->description) !!}</div>

                    @php
                        $parentIdCommentExist = App\Models\Comment::with('user')->where('parent_id', $list->id)->first()->parent_id ?? '';
                    @endphp
                    @if ($parentIdCommentExist != $list->id && Auth::user()->isAdmin())
                        <div class="row mt-3">
                            <div class="col-auto">
                                <span class="avatar avatar-md" style="background-image: url({{ asset('storage/profile') }}/{{ auth()->user()->avatar ?? 'default.jpg' }}); height: 40px; width: 40px;"></span>
                            </div>
                            <div class="col">
                                <x-form action="{{ $url }}" method="post">
                                    <x-textarea class="form-control form-control-sm" name="description" data-bs-toggle="autosize" placeholder="Enter your thoughts.…" maxlength="1000" cols="1000"></x-textarea>
                                    <input type="hidden" value="{{ $hiddenplaylistslug }}" name="playlistslug">
                                    <input type="hidden" name="parent_id" value="{{ $list->id }}">
                                    <div class="d-flex justify-content-end mt-2">
                                        <button class="btn btn-primary btn-sm px-5 py-1" type="submit">Reply</button>
                                    </div>
                                </x-form>
                            </div>
                        </div>
                    @endif

                    @include('components.commentDisplay', ['comments' => $list->replies])

                </div>
            </div>
        @endforeach
    </div>
@endunless