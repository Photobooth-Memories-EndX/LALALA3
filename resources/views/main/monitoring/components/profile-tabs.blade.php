{{-- Props: $profile --}}
<div class="card owl-main-card">
    <div class="card-header bg-white border-0">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a href="#tab-learning" class="nav-link active" data-toggle="tab" aria-selected="true" role="tab">
                    <i class="fas fa-book mr-1"></i> Learning Progress
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-assessment" class="nav-link" data-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    <i class="fas fa-clipboard-check mr-1"></i> Assessment Progress
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-playlist" class="nav-link" data-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    <i class="fas fa-list-ul mr-1"></i> Playlist Progress
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">

            {{-- Learning --}}
            <div class="tab-pane active show" id="tab-learning" role="tabpanel">
                <div class="table-responsive">
                    <table id="learning-table" class="table table-hover table-vcenter owl-table mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Content</th>
                                <th>Category</th>
                                <th>Progress</th>
                                <th class="text-center">Status</th>
                                <th>Last Access</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {{-- Assessment --}}
            <div class="tab-pane" id="tab-assessment" role="tabpanel">
                <div class="table-responsive">
                    <table id="assessment-table" class="table table-hover table-vcenter owl-table mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Assessment</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Attempt</th>
                                <th>Completed At</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {{-- Playlist --}}
            <div class="tab-pane" id="tab-playlist" role="tabpanel">
                <div class="table-responsive">
                    <table id="playlist-table" class="table table-hover table-vcenter owl-table mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Playlist</th>
                                <th>Progress</th>
                                <th class="text-center">Completed Content</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    $(function () {
        const emptyText = 'Tidak ada data.';

        const learningTable = $('#learning-table').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            pageLength: 10,
            language: { emptyTable: emptyText },
            ajax: { url: @json(route('monitoring.profile.learning.datatable', $profile->ssohash)) },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                {data: 'title', name: 'title', orderable: false},
                {data: 'classification', name: 'classification', orderable: false},
                {data: 'progress_badge', name: 'progress', orderable: false, searchable: false},
                {data: 'finish_badge', name: 'finish', orderable: false, searchable: false, className: 'text-center'},
                {data: 'updated_at', name: 'updated_at', searchable: false},
            ]
        });

        let assessmentTable = null;
        let playlistTable = null;

        function initAssessment() {
            if (assessmentTable !== null) return;
            assessmentTable = $('#assessment-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                pageLength: 10,
                language: { emptyTable: emptyText },
                ajax: { url: @json(route('monitoring.profile.assessment.datatable', $profile->ssohash)) },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'title', name: 'title', orderable: false},
                    {data: 'score_badge', name: 'score', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'status_badge', name: 'pass', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'attempt', name: 'attempt', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'created_at', name: 'created_at', searchable: false},
                ]
            });
        }

        function initPlaylist() {
            if (playlistTable !== null) return;
            playlistTable = $('#playlist-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                pageLength: 10,
                language: { emptyTable: 'Playlist progress belum tersedia.' },
                ajax: { url: @json(route('monitoring.profile.playlist.datatable', $profile->ssohash)) },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'title', name: 'title', orderable: false},
                    {data: 'progress_badge', name: 'progress', orderable: false, searchable: false},
                    {data: 'items', name: 'items', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center'},
                ]
            });
        }

        // Tab switching mandiri (tidak bergantung pada JS bootstrap / data-bs-*).
        $('.nav-tabs a[data-toggle="tab"]').on('click', function (e) {
            e.preventDefault();
            const target = $(this).attr('href');

            $(this).closest('.nav-tabs').find('.nav-link')
                .removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');

            $('.tab-content > .tab-pane').removeClass('active show');
            $(target).addClass('active show');

            if (target === '#tab-assessment') initAssessment();
            if (target === '#tab-playlist') initPlaylist();

            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
</script>
@endpush
