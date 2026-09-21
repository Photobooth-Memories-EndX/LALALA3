<div class="table-responsive placeholder-glow" aria-busy="true" aria-label="Loading classification summary">
    <table class="table table-vcenter align-middle mb-0">
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Classification</th>
                <th class="text-center">KP OWL</th>
                <th class="text-center">Non KP OWL</th>
                <th class="text-center">Total Learning</th>
                <th class="text-center">Learning Hours</th>
                <th class="text-center">Ongoing</th>
                <th class="text-center">Finished</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < 5; $i++)
                <tr>
                    <td><span class="placeholder col-6" style="height: 14px;"></span></td>
                    <td><span class="placeholder col-8" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-5" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-5" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-5" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-6" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-5" style="height: 14px;"></span></td>
                    <td class="text-center"><span class="placeholder col-5" style="height: 14px;"></span></td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>
