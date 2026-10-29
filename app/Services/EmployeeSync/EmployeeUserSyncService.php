<?php

namespace App\Services\EmployeeSync;

use App\Models\EmployeeSyncLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeUserSyncService
{
    protected EmployeeApiClient $apiClient;

    public function __construct(?EmployeeApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? new EmployeeApiClient();
    }

    /**
     * Run employee user synchronization.
     *
     * @param array{
     *     dry_run?: bool,
     *     allow_create?: bool,
     *     limit?: int|null,
     *     api_data?: array|null
     * } $options
     * @return EmployeeSyncResult
     */
    public function sync(array $options = []): EmployeeSyncResult
    {
        $isDryRun = (bool) ($options['dry_run'] ?? false);
        $allowCreate = (bool) ($options['allow_create'] ?? config('api.employee.auto_provision', true));
        $limit = isset($options['limit']) ? (int) $options['limit'] : null;

        $result = new EmployeeSyncResult($isDryRun);

        // 1. Fetch data from API or use provided dataset (for testing)
        try {
            $rawEmployees = $options['api_data'] ?? $this->apiClient->fetchAllEmployees();
        } catch (Throwable $e) {
            $result->finish('failed', $e->getMessage());
            if (!$isDryRun) {
                $this->persistLog($result);
            }
            return $result;
        }

        // 2. Safety check: reject empty response to prevent mass side-effects
        if (empty($rawEmployees)) {
            $result->finish('failed', 'API Gateway returned 0 employee records. Synchronization aborted safely.');
            if (!$isDryRun) {
                $this->persistLog($result);
            }
            return $result;
        }

        if ($limit !== null && $limit > 0) {
            $rawEmployees = array_slice($rawEmployees, 0, $limit);
        }

        $result->totalApi = count($rawEmployees);

        // 3. Preload all OWL users (including soft-deleted) into memory with all HR columns
        $owlUsers = User::withTrashed()->get([
            'id',
            'company_name',
            'id_user',
            'id_emp',
            'id_org_unit',
            'id_team',
            'id_department',
            'id_division',
            'name',
            'email',
            'cell_phone',
            'sex',
            'team',
            'department',
            'division',
            'orgunit',
            'id_job_position',
            'jobposition',
            'positionID',
            'appr1',
            'appr2',
            'sap_code',
            'ssohash',
            'resign',
            'flag',
            'jobgrade',
            'id_job_clan',
            'job_clan',
            'id_job_family',
            'job_family',
            'role',
            'active',
            'deleted_at',
        ]);

        $result->totalOwl = $owlUsers->count();

        // 4. Identify internal database duplicate id_emp conflicts
        $dbUsersByEmp = [];
        $dbDuplicateEmpIds = [];
        $usersByEmail = [];

        foreach ($owlUsers as $u) {
            $empKey = trim((string) $u->id_emp);
            if ($empKey !== '') {
                if (isset($dbUsersByEmp[$empKey])) {
                    $dbDuplicateEmpIds[$empKey] = true;
                } else {
                    $dbUsersByEmp[$empKey] = $u;
                }
            }

            $emailKey = strtolower(trim((string) $u->email));
            if ($emailKey !== '') {
                $usersByEmail[$emailKey] = $u;
            }
        }

        // 5. Pre-analyze API payload for duplicates and empty id_emp
        $apiEmpCounts = [];
        foreach ($rawEmployees as $emp) {
            $idEmp = isset($emp['id_emp']) ? trim((string) $emp['id_emp']) : '';
            if ($idEmp !== '') {
                $apiEmpCounts[$idEmp] = ($apiEmpCounts[$idEmp] ?? 0) + 1;
            }
        }

        // 6. Process each employee record
        $processedEmpKeys = [];

        foreach ($rawEmployees as $emp) {
            $idEmp = isset($emp['id_emp']) ? trim((string) $emp['id_emp']) : '';

            // Rule 8: Data API dengan id_emp kosong ditolak
            if ($idEmp === '') {
                $result->recordConflict(
                    '',
                    'Data karyawan API ditolak karena id_emp kosong/null.',
                    $emp
                );
                continue;
            }

            // Rule 9: Duplikasi id_emp pada respons API terdeteksi
            if (($apiEmpCounts[$idEmp] ?? 0) > 1) {
                // If already handled in this loop, avoid duplicating conflict record
                if (!isset($processedEmpKeys[$idEmp])) {
                    $result->recordConflict(
                        $idEmp,
                        "Duplikasi id_emp [{$idEmp}] ditemukan sebanyak {$apiEmpCounts[$idEmp]} kali dalam payload API.",
                        $emp
                    );
                    $processedEmpKeys[$idEmp] = true;
                }
                continue;
            }

            // Rule 10: Duplikasi id_emp pada database ditangani aman
            if (isset($dbDuplicateEmpIds[$idEmp])) {
                $result->recordConflict(
                    $idEmp,
                    "Duplikasi id_emp [{$idEmp}] ditemukan pada database internal OWL. Data dilewati untuk mencegah konflik relasi.",
                    $emp
                );
                continue;
            }

            $processedEmpKeys[$idEmp] = true;

            // Matching
            if (isset($dbUsersByEmp[$idEmp])) {
                // Kasus B: Karyawan Sudah Ada di OWL (Aktif atau Soft-Deleted)
                $existingUser = $dbUsersByEmp[$idEmp];
                $this->syncExistingUser($existingUser, $emp, $usersByEmail, $isDryRun, $result);
            } else {
                // Kasus A: Karyawan Belum Ada di OWL
                $this->provisionNewUser($idEmp, $emp, $usersByEmail, $dbUsersByEmp, $allowCreate, $isDryRun, $result);
            }
        }

        $finalStatus = $result->conflictCount > 0 ? 'partial' : 'success';
        $result->finish($finalStatus);

        // Zero-write in dry-run mode: do not write to employee_sync_logs when dry-running
        if (!$isDryRun) {
            $this->persistLog($result);
        }

        return $result;
    }

    /**
     * Synchronize differences for an existing user.
     *
     * @param User $user
     * @param array<string, mixed> $emp
     * @param array<string, User> $usersByEmail
     * @param bool $isDryRun
     * @param EmployeeSyncResult $result
     */
    protected function syncExistingUser(
        User $user,
        array $emp,
        array &$usersByEmail,
        bool $isDryRun,
        EmployeeSyncResult $result
    ): void {
        $changes = [];
        $isSoftDeleted = !is_null($user->deleted_at);

        // 1. Name
        $apiName = trim((string) ($emp['name'] ?? ''));
        if ($apiName !== '' && $apiName !== trim((string) $user->name)) {
            $changes['name'] = ['from' => $user->name, 'to' => $apiName];
        }

        // 2. Email (with uniqueness validation against other users)
        $apiEmail = strtolower(trim((string) ($emp['email'] ?? '')));
        $currentEmail = strtolower(trim((string) $user->email));

        if ($apiEmail !== '' && $apiEmail !== $currentEmail) {
            // Check collision with another user in OWL
            if (isset($usersByEmail[$apiEmail]) && $usersByEmail[$apiEmail]->id !== $user->id) {
                $conflictUser = $usersByEmail[$apiEmail];
                $result->recordConflict(
                    $user->id_emp,
                    "Email baru [{$apiEmail}] sudah digunakan oleh user lain (ID: {$conflictUser->id}, id_emp: {$conflictUser->id_emp}). Perubahan email dilewati.",
                    ['api_email' => $apiEmail, 'current_email' => $currentEmail]
                );
            } else {
                $changes['email'] = ['from' => $user->email, 'to' => $apiEmail];
            }
        }

        // 3. Organization fields
        $orgFields = [
            'team' => 'team',
            'department' => 'department',
            'division' => 'division',
            'sap_code' => 'sap_code',
        ];

        foreach ($orgFields as $apiField => $dbCol) {
            $apiVal = trim((string) ($emp[$apiField] ?? ''));
            if ($apiVal !== '' && $apiVal !== trim((string) ($user->{$dbCol} ?? ''))) {
                $changes[$dbCol] = ['from' => $user->{$dbCol}, 'to' => $apiVal];
            }
        }

        // 4. id_org_unit (mapped from API id_ou)
        $apiOu = trim((string) ($emp['id_ou'] ?? ''));
        if ($apiOu !== '' && $apiOu !== trim((string) $user->id_org_unit)) {
            $changes['id_org_unit'] = ['from' => $user->id_org_unit, 'to' => $apiOu];
        }

        // 5. ssohash (if provided, non-empty, and different)
        $apiSsoHash = trim((string) ($emp['ssohash'] ?? ($emp['newhash'] ?? '')));
        if ($apiSsoHash !== '' && $apiSsoHash !== '-' && $apiSsoHash !== trim((string) $user->ssohash)) {
            $changes['ssohash'] = ['from' => $user->ssohash, 'to' => $apiSsoHash];
        }

        // 6. id_user (only fill if OWL user had empty/0 id_user)
        $apiIdUser = trim((string) ($emp['id_user'] ?? ''));
        $currentIdUser = trim((string) $user->id_user);
        if ($apiIdUser !== '' && ($currentIdUser === '' || $currentIdUser === '0') && $apiIdUser !== $currentIdUser) {
            $changes['id_user'] = ['from' => $user->id_user, 'to' => $apiIdUser];
        }

        // 7. Extended Employee Profile HR fields
        $extendedFields = [
            'id_team' => 'id_team',
            'id_department' => 'id_department',
            'id_division' => 'id_division',
            'cell_phone' => 'cell_phone',
            'sex' => 'sex',
            'orgunit' => 'orgunit',
            'id_job_position' => 'id_job_position',
            'jobposition' => 'jobposition',
            'positionID' => 'positionID',
            'appr1' => 'appr1',
            'appr2' => 'appr2',
            'resign' => 'resign',
            'jobgrade' => 'jobgrade',
            'id_job_clan' => 'id_job_clan',
            'job_clan' => 'job_clan',
            'id_job_family' => 'id_job_family',
            'job_family' => 'job_family',
        ];

        foreach ($extendedFields as $apiField => $dbCol) {
            if (array_key_exists($apiField, $emp)) {
                $apiVal = trim((string) ($emp[$apiField] ?? ''));
                $dbVal = trim((string) ($user->{$dbCol} ?? ''));
                // Only update if API has non-empty value and differs
                if ($apiVal !== '' && $apiVal !== $dbVal) {
                    $changes[$dbCol] = ['from' => $user->{$dbCol}, 'to' => $apiVal];
                }
            }
        }

        // 8. flag field (numeric)
        if (array_key_exists('flag', $emp) && $emp['flag'] !== null && $emp['flag'] !== '') {
            $apiFlag = (int) $emp['flag'];
            $dbFlag = $user->flag !== null ? (int) $user->flag : null;
            if ($dbFlag === null || $apiFlag !== $dbFlag) {
                $changes['flag'] = ['from' => $user->flag, 'to' => $apiFlag];
            }
        }

        // If no changes
        if (empty($changes)) {
            $result->unchangedCount++;
            return;
        }

        // Record update with soft delete distinction
        $result->recordUpdated($user->id_emp, $user->name, $changes, $isSoftDeleted);

        // Apply changes if not dry-run
        if (!$isDryRun) {
            foreach ($changes as $field => $change) {
                $user->{$field} = $change['to'];
            }
            $user->save();

            // Explicitly sync offline training records if any of the 5 identity fields changed
            if (array_intersect_key($changes, array_flip(['name', 'jobgrade', 'jobposition', 'team', 'department', 'id_emp']))) {
                app(\App\Services\OfflineTraining\EmployeeIdentitySyncService::class)->syncUserToOfflineRecords($user);
            }

            // Update local memory map for email if changed
            if (isset($changes['email'])) {
                unset($usersByEmail[$currentEmail]);
                $usersByEmail[$apiEmail] = $user;
            }
        }
    }

    /**
     * Provision a new user safely according to OWL business rules.
     *
     * @param string $idEmp
     * @param array<string, mixed> $emp
     * @param array<string, User> $usersByEmail
     * @param array<string, User> $dbUsersByEmp
     * @param bool $allowCreate
     * @param bool $isDryRun
     * @param EmployeeSyncResult $result
     */
    protected function provisionNewUser(
        string $idEmp,
        array $emp,
        array &$usersByEmail,
        array &$dbUsersByEmp,
        bool $allowCreate,
        bool $isDryRun,
        EmployeeSyncResult $result
    ): void {
        $email = strtolower(trim((string) ($emp['email'] ?? '')));
        $name = trim((string) ($emp['name'] ?? ''));

        // Check if email conflicts with an existing OWL account
        if ($email !== '' && isset($usersByEmail[$email])) {
            $conflictUser = $usersByEmail[$email];
            $result->recordConflict(
                $idEmp,
                "Kandidat user baru ditolak karena email [{$email}] sudah dipakai akun OWL ID {$conflictUser->id} (id_emp: {$conflictUser->id_emp}).",
                $emp
            );
            return;
        }

        if (!$allowCreate) {
            $result->recordSkip(
                $idEmp,
                "Kandidat user baru [{$name}] terdeteksi, namun pembuatan akun dinonaktifkan (safe mode)."
            );
            return;
        }

        $result->recordCreated($idEmp, $name, $email);

        if (!$isDryRun) {
            $user = new User();
            $user->id_emp = $idEmp;
            $user->name = $name ?: 'Employee ' . $idEmp;
            $user->email = $email ?: ($idEmp . '@krakatauposco.co.id');
            $user->password = Hash::make($idEmp);
            $user->company_name = 'PT Krakatau Posco';
            $user->id_user = isset($emp['id_user']) && $emp['id_user'] !== '' ? (string) $emp['id_user'] : '0';
            $user->id_org_unit = isset($emp['id_ou']) && $emp['id_ou'] !== '' ? (string) $emp['id_ou'] : '0';
            $user->team = isset($emp['team']) && $emp['team'] !== '' ? trim((string) $emp['team']) : '-';
            $user->department = isset($emp['department']) && $emp['department'] !== '' ? trim((string) $emp['department']) : '-';
            $user->division = isset($emp['division']) && $emp['division'] !== '' ? trim((string) $emp['division']) : '-';
            $user->sap_code = isset($emp['sap_code']) && $emp['sap_code'] !== '' ? trim((string) $emp['sap_code']) : '-';
            $user->ssohash = isset($emp['ssohash']) && $emp['ssohash'] !== '' ? trim((string) $emp['ssohash']) : (isset($emp['newhash']) && $emp['newhash'] !== '' ? trim((string) $emp['newhash']) : '-');
            $user->role = User::ROLE_USER; // Default Role 1
            $user->active = 0; // Safe Inactive default

            // Extended HR employee fields
            $user->id_team = isset($emp['id_team']) && $emp['id_team'] !== '' ? trim((string) $emp['id_team']) : null;
            $user->id_department = isset($emp['id_department']) && $emp['id_department'] !== '' ? trim((string) $emp['id_department']) : null;
            $user->id_division = isset($emp['id_division']) && $emp['id_division'] !== '' ? trim((string) $emp['id_division']) : null;
            $user->cell_phone = isset($emp['cell_phone']) && $emp['cell_phone'] !== '' ? trim((string) $emp['cell_phone']) : null;
            $user->sex = isset($emp['sex']) && $emp['sex'] !== '' ? trim((string) $emp['sex']) : null;
            $user->orgunit = isset($emp['orgunit']) && $emp['orgunit'] !== '' ? trim((string) $emp['orgunit']) : null;
            $user->id_job_position = isset($emp['id_job_position']) && $emp['id_job_position'] !== '' ? trim((string) $emp['id_job_position']) : null;
            $user->jobposition = isset($emp['jobposition']) && $emp['jobposition'] !== '' ? trim((string) $emp['jobposition']) : null;
            $user->positionID = isset($emp['positionID']) && $emp['positionID'] !== '' ? trim((string) $emp['positionID']) : null;
            $user->appr1 = isset($emp['appr1']) && $emp['appr1'] !== '' ? trim((string) $emp['appr1']) : null;
            $user->appr2 = isset($emp['appr2']) && $emp['appr2'] !== '' ? trim((string) $emp['appr2']) : null;
            $user->resign = isset($emp['resign']) && $emp['resign'] !== '' ? trim((string) $emp['resign']) : 'N';
            $user->flag = isset($emp['flag']) && $emp['flag'] !== '' ? (int) $emp['flag'] : 1;
            $user->jobgrade = isset($emp['jobgrade']) && $emp['jobgrade'] !== '' ? trim((string) $emp['jobgrade']) : null;
            $user->id_job_clan = isset($emp['id_job_clan']) && $emp['id_job_clan'] !== '' ? trim((string) $emp['id_job_clan']) : null;
            $user->job_clan = isset($emp['job_clan']) && $emp['job_clan'] !== '' ? trim((string) $emp['job_clan']) : null;
            $user->id_job_family = isset($emp['id_job_family']) && $emp['id_job_family'] !== '' ? trim((string) $emp['id_job_family']) : null;
            $user->job_family = isset($emp['job_family']) && $emp['job_family'] !== '' ? trim((string) $emp['job_family']) : null;

            $user->save();

            // Cache in memory for subsequent checks
            $dbUsersByEmp[$idEmp] = $user;
            if ($user->email !== '') {
                $usersByEmail[strtolower($user->email)] = $user;
            }
        }
    }

    /**
     * Persist synchronization execution log into database.
     *
     * @param EmployeeSyncResult $result
     */
    protected function persistLog(EmployeeSyncResult $result): void
    {
        try {
            EmployeeSyncLog::create([
                'started_at' => $result->startedAt ?: now(),
                'finished_at' => $result->finishedAt ?: now(),
                'status' => $result->status,
                'is_dry_run' => $result->isDryRun,
                'total_api' => $result->totalApi,
                'total_owl' => $result->totalOwl,
                'created_count' => $result->createdCount,
                'updated_count' => $result->updatedCount,
                'unchanged_count' => $result->unchangedCount,
                'skipped_count' => $result->skippedCount,
                'conflict_count' => $result->conflictCount,
                'failed_count' => $result->failedCount,
                'details' => [
                    'duration_seconds' => $result->durationSeconds(),
                    'error_message' => $result->errorMessage,
                    'updated_active_count' => $result->updatedActiveCount,
                    'updated_soft_deleted_count' => $result->updatedSoftDeletedCount,
                    'conflicts' => $result->conflicts,
                    'updated_sample' => array_slice($result->updatedDetails, 0, 50),
                    'created_sample' => array_slice($result->createdDetails, 0, 50),
                    'skipped_sample' => array_slice($result->skippedDetails, 0, 50),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('EmployeeUserSyncService: Failed to persist sync log', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
