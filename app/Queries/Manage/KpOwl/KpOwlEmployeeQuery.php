<?php

namespace App\Queries\Manage\KpOwl;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KpOwlEmployeeQuery
{
    /**
     * Search employees by query string (ID or Name).
     */
    public function searchEmployees(string $keyword, int $limit = 20): Collection
    {
        $term = trim($keyword);
        if ($term === '') {
            return collect();
        }

        return DB::table('users')
            ->select([
                'id',
                'id_emp',
                'name',
                'department',
                'team',
                'company_name',
                'jobgrade',
                'jobposition',
            ])
            ->where(function ($q) use ($term) {
                $q->where('id_emp', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Get distinct company names for filter dropdown.
     */
    public function getCompanies(): array
    {
        return DB::table('users')
            ->whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->distinct()
            ->orderBy('company_name')
            ->pluck('company_name')
            ->toArray();
    }

    /**
     * Get distinct department names for filter dropdown.
     */
    public function getDepartments(): array
    {
        return DB::table('users')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->toArray();
    }

    /**
     * Get distinct team names for filter dropdown.
     */
    public function getTeams(): array
    {
        return DB::table('users')
            ->whereNotNull('team')
            ->where('team', '!=', '')
            ->distinct()
            ->orderBy('team')
            ->pluck('team')
            ->toArray();
    }

    /**
     * Find a user by ID.
     */
    public function findUserById(int $userId): ?object
    {
        return DB::table('users')
            ->select([
                'id',
                'id_emp',
                'name',
                'email',
                'jobgrade',
                'jobposition',
                'department',
                'division',
                'team',
                'company_name',
            ])
            ->where('id', $userId)
            ->first();
    }
}
