<?php

namespace App\Exports;

use App\Exports\Manage\AllRecordExport;
use App\Models\User;
use App\Services\User\UserProfileLearningHistoryService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserProfileLearningExport implements FromCollection, WithHeadings, WithColumnWidths, Responsable
{
    use Exportable;

    protected User $user;
    protected array $filters;

    public function __construct(User $user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        return UserProfileLearningHistoryService::buildCombinedQuery($this->user, $this->filters)
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return AllRecordExport::headings();
    }

    public function columnWidths(): array
    {
        return AllRecordExport::columnWidths();
    }

    public function toResponse($request)
    {
        $filename = sprintf(
            '%s_%s_learning_history.xlsx',
            now()->format('Ymd_His'),
            Str::slug($this->user->name ?: 'user')
        );

        $cleanEmpId = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $this->user->id_emp));
        $filters = array_merge($this->filters, [
            'exact_participant' => [
                'user_id'     => $this->user->id,
                'employee_id' => $cleanEmpId,
                'name'        => $this->user->name,
            ],
        ]);

        return (new AllRecordExport($filters, $filename))->download($filename);
    }
}
