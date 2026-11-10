<?php

namespace App\Services\Manage\KpOwl;

use App\Queries\Manage\KpOwl\KpOwlEmployeeQuery;
use Illuminate\Support\Facades\DB;

class KpOwlFilterService
{
    protected KpOwlEmployeeQuery $employeeQuery;

    public function __construct(KpOwlEmployeeQuery $employeeQuery)
    {
        $this->employeeQuery = $employeeQuery;
    }

    /**
     * Get all filter options for the KP OWL dashboard and filter bar.
     */
    public function getFilterOptions(): array
    {
        return [
            'classifications' => $this->getClassifications(),
            'types'           => ['Video', 'Document', 'Playlist'],
            'categories'      => ['All Content', 'Playlist'],
            'statuses'        => [
                'finished'    => 'Finished',
                'ongoing'     => 'Ongoing',
                'not_started' => 'Not Started',
            ],
            'months'          => [
                '1'  => 'January',
                '2'  => 'February',
                '3'  => 'March',
                '4'  => 'April',
                '5'  => 'May',
                '6'  => 'June',
                '7'  => 'July',
                '8'  => 'August',
                '9'  => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December',
            ],
            'years'           => $this->getAvailableYears(),
            'departments'     => $this->employeeQuery->getDepartments(),
            'teams'           => $this->employeeQuery->getTeams(),
            'companies'       => $this->employeeQuery->getCompanies(),
            'providers'       => ['Internal'],
        ];
    }

    /**
     * Get distinct classifications.
     */
    public function getClassifications(): array
    {
        return DB::table('classification')
            ->select(['id', 'classification_name', 'slug'])
            ->orderBy('classification_name')
            ->get()
            ->toArray();
    }

    /**
     * Get distinct years from learning records.
     */
    public function getAvailableYears(): array
    {
        $cuYears = DB::table('content_users')
            ->selectRaw('DISTINCT YEAR(created_at) as yr')
            ->pluck('yr')
            ->toArray();

        $pcuYears = DB::table('playlist_content_users')
            ->selectRaw('DISTINCT YEAR(created_at) as yr')
            ->pluck('yr')
            ->toArray();

        $years = array_unique(array_filter(array_merge($cuYears, $pcuYears)));
        rsort($years);

        if (empty($years)) {
            $years = [(int) date('Y')];
        }

        return array_values($years);
    }
}
