<div class="card border-0 shadow-sm rounded-4">

    <div class="card-header border-0">

        <div>
            <h3 class="card-title mb-1">
                Learning History
            </h3>

            <div class="text-muted small">
                Daftar learning yang pernah diikuti oleh user ini.
            </div>
        </div>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            @if($totalLearning > 0)

                <table class="table table-vcenter card-table table-hover" id="MyTable">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Status</th>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Classification</th>
                            <th>Duration</th>
                            <th>Date</th>
                            <th width="15%">Progress</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($content as $list)

                            @php
                                $contentItem = $list->content;
                                $classification = $contentItem->classification ?? null;

                                $isVideo =
                                    $contentItem &&
                                    in_array(
                                        $contentItem->type,
                                        ['Video', 'YouTube']
                                    );

                                $progress = (int) ($list->progress ?? 0);
                            @endphp

                            <tr>

                                <td class="text-muted">
                                    {{ $loop->iteration }}
                                </td>

                                <td>

                                    @if((int)($list->finish ?? 0) === 1)

                                        <span class="badge bg-green-lt text-green">
                                            Finished
                                        </span>

                                    @else

                                        <span class="badge bg-yellow-lt text-yellow">
                                            Ongoing
                                        </span>

                                    @endif

                                </td>

                                <td>
                                    {{ $list->user->name ?? $profile->name ?? '-' }}
                                </td>

                                <td>

                                    <div class="d-flex align-items-center gap-2">

                                        @if($isVideo)

                                            <span class="avatar avatar-sm bg-red-lt text-red">
                                                <i class="ti ti-video"></i>
                                            </span>

                                        @else

                                            <span class="avatar avatar-sm bg-yellow-lt text-yellow">
                                                <i class="ti ti-file-type-pdf"></i>
                                            </span>

                                        @endif

                                        <div>

                                            @if($contentItem && !empty($contentItem->slug))

                                                <a href="{{ route('learning.manage.content', $contentItem->slug) }}"
                                                   class="text-reset fw-semibold">

                                                    {{ $contentItem->title ?? '-' }}

                                                </a>

                                            @else

                                                <span class="text-danger fw-semibold">
                                                    Content Deleted
                                                </span>

                                            @endif

                                            <div class="text-muted small">
                                                {{ $contentItem->type ?? '-' }}
                                            </div>

                                        </div>

                                    </div>

                                </td>

                                <td>

                                    @if($classification && !empty($classification->slug))

                                        <a href="{{ route('learning.manage.category', $classification->slug) }}"
                                           class="text-reset">

                                            {{ $classification->classification_name }}

                                        </a>

                                    @else

                                        <span class="text-muted">
                                            -
                                        </span>

                                    @endif

                                </td>

                                <td>
                                    {{ $contentItem->duration ?? '-' }}
                                </td>

                                <td>
                                    {{ optional($list->created_at)->format('d M Y') }}
                                </td>

                                <td>

                                    <div class="d-flex align-items-center gap-2">

                                        <div class="progress progress-sm flex-fill">

                                            <div class="progress-bar bg-primary"
                                                 style="width: {{ $progress }}%">
                                            </div>

                                        </div>

                                        <span class="small text-muted">
                                            {{ $progress }}%
                                        </span>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            @else

                <div class="text-center p-5">

                    <i class="ti ti-notebook-off text-muted"
                       style="font-size:56px;">
                    </i>

                    <h3 class="mt-3 mb-1">
                        No Learning Data
                    </h3>

                    <div class="text-muted">
                        User ini belum memiliki data learning.
                    </div>

                </div>

            @endif

        </div>

    </div>

</div>