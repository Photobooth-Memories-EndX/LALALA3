<div class="table-responsive" aria-busy="true" aria-label="Loading classification summary">
    <table class="table table-vcenter align-middle">
        <thead>
            <tr>
                <th style="width: 60px;">No</th>
                <th>Classification</th>
                <th class="text-center">Content</th>
                <th class="text-center">Total Learning</th>
                <th class="text-center">Learning Hours</th>
                <th class="text-center">Ongoing</th>
                <th class="text-center">Finished</th>
            </tr>
        </thead>
        <tbody class="placeholder-glow">
            @for ($i = 1; $i <= 6; $i++)
                <tr>
                    <td class="text-muted"><span class="placeholder col-6"></span></td>
                    <td>
                        <span class="placeholder placeholder-sm {{ $i % 2 === 0 ? 'col-8' : 'col-6' }}" style="border-radius: 4px;"></span>
                    </td>
                    <td class="text-center"><span class="placeholder col-4"></span></td>
                    <td class="text-center"><span class="placeholder col-5"></span></td>
                    <td class="text-center"><span class="placeholder col-6"></span></td>
                    <td class="text-center"><span class="placeholder col-4"></span></td>
                    <td class="text-center"><span class="placeholder col-4"></span></td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>
