<div class="table-responsive" aria-busy="true" aria-label="Loading offline classification summary">
    <table class="table table-vcenter align-middle mb-0">
        <thead class="bg-light text-muted small text-uppercase">
            <tr>
                <th style="width: 50px;">No</th>
                <th>Classification</th>
                <th class="text-center">Programs</th>
                <th class="text-center">Total Participants</th>
                <th class="text-center">Learning Hours</th>
                <th class="text-center text-warning">Ongoing</th>
                <th class="text-center text-success">Finished</th>
            </tr>
        </thead>
        <tbody class="placeholder-glow">
            @for ($i = 1; $i <= 5; $i++)
                <tr>
                    <td class="text-muted"><span class="placeholder col-6" style="height: 14px;"></span></td>
                    <td>
                        <span class="placeholder placeholder-sm {{ $i % 2 === 0 ? 'col-8' : 'col-6' }}" style="height: 14px; border-radius: 4px;"></span>
                    </td>
                    <td class="text-center"><span class="placeholder col-4" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-5" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-6" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-4" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-4" style="height: 14px;"></span></td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>
