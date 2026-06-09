<div class="card border-0 shadow-sm">

    <div class="table-responsive">

        <table class="table table-vcenter table-hover card-table mb-0">

            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Assessment</th>
                    <th>Min Progress</th>
                    <th>Min Score</th>
                    <th>Issuer</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>

            <tbody>

                @forelse($settings as $setting)

                    @include('main.certificate.components.table-row', [
                        'setting' => $setting
                    ])

                    @include('main.certificate.components.edit-modal', [
                        'setting' => $setting
                    ])

                @empty

                    @include('main.certificate.components.empty-state')

                @endforelse

            </tbody>

        </table>

    </div>

</div>

<div class="mt-3">
    {{ $settings->links() }}
</div>