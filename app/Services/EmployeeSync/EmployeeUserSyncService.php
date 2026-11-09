<?php

namespace App\Services\EmployeeSync;

use App\Models\EmployeeSyncLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeUserSyncService
{
    public const PROGRESS_CACHE_KEY = 'owl:sync-employee:progress';

    protected EmployeeApiClient $apiClient;

    public function __construct(?EmployeeApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? new EmployeeApiClient();
    }

    /**
     * Get the latest synchronization progress from cache.
     *
     * @return array<string, mixed>
     */
    public static function getProgress(): array
    {
        $default = [
            'status' => 'idle',
            'percentage' => 0,
            'message' => 'Tidak ada proses sinkronisasi yang berjalan.',
            'total' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'is_dry_run' => false,
            'is_running' => false,
            'duration_seconds' => 0.0,
            'started_at' => null,
            'finished_at' => null,
            'error' => null,
        ];

        $cached = Cache::get(self::PROGRESS_CACHE_KEY);
        if (!is_array($cached)) {
            return $default;
        }

        return array_merge($default, $cached);
    }

    /**
     * Reset progress cache at the beginning of a synchronization task.
     *
     * @param bool $isDryRun
     */
    public static function resetProgress(bool $isDryRun = false): void
    {
        $now = now();
        Cache::put(self::PROGRESS_CACHE_KEY, [
            'status' => 'preparing',
            'percentage' => 0,
            'message' => 'Menyiapkan proses sinkronisasi...',
            'total' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'is_dry_run' => $isDryRun,
            'is_running' => true,
            'duration_seconds' => 0.0,
            'started_at' => $now->toISOString(),
            'finished_at' => null,
            'error' => null,
        ], now()->addHour());
    }

    /**
     * Update current synchronization progress in cache.
     *
     * @param string $status
     * @param int $processed
     * @param int $total
     * @param string $message
     * @param array<string, mixed> $extra
     */
    public static function updateProgress(
        string $status,
        int $processed,
        int $total,
        string $message,
        array $extra = []
    ): void {
        $current = self::getProgress();

        $percentage = 0;
        if ($status === 'completed') {
            $percentage = 100;
        } elseif ($status === 'preparing') {
            $percentage = 0;
        } elseif ($status === 'fetching') {
            $percentage = 5;
        } elseif ($total > 0) {
            $calc = (int) floor(($processed / $total) * 100);
            $percentage = max(5, min(99, $calc));
        }

        $now = now();
        $startedAt = $extra['started_at'] ?? $current['started_at'] ?? $now->toISOString();
        $durationSeconds = $extra['duration_seconds'] ?? 0.0;
        if ($startedAt && !isset($extra['duration_seconds'])) {
            $startCarbon = Carbon::parse($startedAt);
            $durationSeconds = round($startCarbon->diffInMilliseconds($now) / 1000, 1);
        }

        $data = array_merge($current, [
            'status' => $status,
            'percentage' => $percentage,
            'message' => $message,
            'total' => $total,
            'processed' => $processed,
            'is_running' => in_array($status, ['preparing', 'fetching', 'processing', 'creating', 'updating'], true),
            'duration_seconds' => (float) $durationSeconds,
            'started_at' => $startedAt,
        ], $extra);

        if ($status === 'completed' || $status === 'failed') {
            $data['finished_at'] = $now->toISOString();
            $data['is_running'] = false;
        }

        Cache::put(self::PROGRESS_CACHE_KEY, $data, now()->addHour());
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
     * @param callable|null $onProgress
     * @return EmployeeSyncResult
     */
    public function sync(array $options = [], ?callable $onProgress = null): EmployeeSyncResult
    {
        $isDryRun = (bool) ($options['dry_run'] ?? false);
        $allowCreate = (bool) ($options['allow_create'] ?? config('api.employee.auto_provision', true));
        $limit = isset($options['limit']) ? (int) $options['limit'] : null;

        $result = new EmployeeSyncResult($isDryRun);
        $startedAt = now()->toISOString();

        self::updateProgress('preparing', 0, 0, 'Menyiapkan proses sinkronisasi...', [
            'is_dry_run' => $isDryRun,
            'started_at' => $startedAt,
        ]);
        if ($onProgress) {
            $onProgress('preparing', 0, 0, 'Menyiapkan proses sinkronisasi...');
        }

        // 1. Fetch data from API or use provided dataset (for testing)
        self::updateProgress('fetching', 0, 0, 'Mengambil data dari Employee API Gateway...', [
            'is_dry_run' => $isDryRun,
            'started_at' => $startedAt,
        ]);
        if ($onProgress) {
            $onProgress('fetching', 0, 0, 'Mengambil data dari Employee API Gateway...');
        }

        try {
            $rawEmployees = $options['api_data'] ?? $this->apiClient->fetchAllEmployees();
        } catch (Throwable $e) {
            $result->finish('failed', $e->getMessage());
            self::updateProgress('failed', 0, 0, 'Gagal terhubung ke Employee API Gateway: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'is_dry_run' => $isDryRun,
                'started_at' => $startedAt,
            ]);
            if ($onProgress) {
                $onProgress('failed', 0, 0, $e->getMessage());
            }
            if (!$isDryRun) {
                $this->persistLog($result);
            }
            return $result;
        }

        // 2. Safety check: reject empty response to prevent mass side-effects
        if (empty($rawEmployees)) {
            $errorMsg = 'API Gateway mengembalikan 0 record karyawan. Sinkronisasi dibatalkan secara aman.';
            $result->finish('failed', $errorMsg);
            self::updateProgress('failed', 0, 0, $errorMsg, [
                'error' => $errorMsg,
                'is_dry_run' => $isDryRun,
                'started_at' => $startedAt,
            ]);
            if ($onProgress) {
                $onProgress('failed', 0, 0, $errorMsg);
            }
            if (!$isDryRun) {
                $this->persistLog($result);
            }
            return $result;
        }

        if ($limit !== null && $limit > 0) {
            $rawEmployees = array_slice($rawEmployees, 0, $limit);
        }

        $totalApi = count($rawEmployees);
        $result->totalApi = $totalApi;

        self::updateProgress('processing', 0, $totalApi, "Data karyawan berhasil diunduh ({$totalApi} data). Memulai pemrosesan...", [
            'is_dry_run' => $isDryRun,
            'started_at' => $startedAt,
        ]);
        if ($onProgress) {
            $onProgress('processing', 0, $totalApi, "Data karyawan berhasil diunduh ({$totalApi} data). Memulai pemrosesan...");
        }

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
            $idEmp = isset($emp['id_emp']) ? trim((string) $emp['id_emp']) : (isset($emp['nip']) ? trim((string) $emp['nip']) : '');
            if ($idEmp !== '') {
                $apiEmpCounts[$idEmp] = ($apiEmpCounts[$idEmp] ?? 0) + 1;
            }
        }

        // 6. Process each employee record
        $processedEmpKeys = [];
        $processedCount = 0;

        foreach ($rawEmployees as $emp) {
            $idEmp = isset($emp['id_emp']) ? trim((string) $emp['id_emp']) : (isset($emp['nip']) ? trim((string) $emp['nip']) : '');
            $processedCount++;

            // Rule 8: Data API dengan id_emp kosong ditolak
            if ($idEmp === '') {
                $result->recordConflict(
                    '',
                    'Data karyawan API ditolak karena id_emp kosong/null.',
                    $emp
                );
                $this->triggerProgressUpdate($onProgress, $processedCount, $totalApi, $isDryRun, $startedAt, $result);
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
                $this->triggerProgressUpdate($onProgress, $processedCount, $totalApi, $isDryRun, $startedAt, $result);
                continue;
            }

            // Rule 10: Duplikasi id_emp pada database ditangani aman
            if (isset($dbDuplicateEmpIds[$idEmp])) {
                $result->recordConflict(
                    $idEmp,
                    "Duplikasi id_emp [{$idEmp}] ditemukan pada database internal OWL. Data dilewati untuk mencegah konflik relasi.",
                    $emp
                );
                $this->triggerProgressUpdate($onProgress, $processedCount, $totalApi, $isDryRun, $startedAt, $result);
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

            $this->triggerProgressUpdate($onProgress, $processedCount, $totalApi, $isDryRun, $startedAt, $result);
        }

        $finalStatus = $result->conflictCount > 0 ? 'partial' : 'success';
        $result->finish($finalStatus);

        $finalMsg = "Sinkronisasi selesai! {$result->createdCount} user baru dibuat dan {$result->updatedCount} profil diperbarui.";
        if ($isDryRun) {
            $finalMsg = "Simulasi selesai! {$result->createdCount} kandidat user baru terdeteksi dan {$result->updatedCount} rencana pembaruan.";
        }

        self::updateProgress('completed', $totalApi, $totalApi, $finalMsg, [
            'created' => $result->createdCount,
            'updated' => $result->updatedCount,
            'skipped' => $result->skippedCount + $result->conflictCount,
            'failed' => $result->failedCount,
            'is_dry_run' => $isDryRun,
            'duration_seconds' => $result->durationSeconds(),
            'started_at' => $startedAt,
        ]);
        if ($onProgress) {
            $onProgress('completed', $totalApi, $totalApi, $finalMsg);
        }

        // Zero-write in dry-run mode: do not write to employee_sync_logs when dry-running
        if (!$isDryRun) {
            $this->persistLog($result);
        }

        return $result;
    }

    /**
     * Trigger progress cache update periodically during synchronization.
     */
    protected function triggerProgressUpdate(
        ?callable $onProgress,
        int $processedCount,
        int $totalApi,
        bool $isDryRun,
        string $startedAt,
        EmployeeSyncResult $result
    ): void {
        if ($processedCount === 1 || $processedCount === $totalApi || $processedCount % 25 === 0) {
            $stage = 'processing';
            $stageMsg = "Memproses data karyawan ({$processedCount} dari {$totalApi})...";

            self::updateProgress($stage, $processedCount, $totalApi, $stageMsg, [
                'created' => $result->createdCount,
                'updated' => $result->updatedCount,
                'skipped' => $result->skippedCount + $result->conflictCount,
                'failed' => $result->failedCount,
                'is_dry_run' => $isDryRun,
                'started_at' => $startedAt,
            ]);

            if ($onProgress) {
                $onProgress($stage, $processedCount, $totalApi, $stageMsg);
            }
        }
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

        // 4. id_org_unit (mapped from API id_ou or id_org_unit)
        $apiOu = trim((string) ($emp['id_ou'] ?? ($emp['id_org_unit'] ?? '')));
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
                $cleanVal = $this->cleanHrField($dbCol, $emp[$apiField] ?? null);
                $dbVal = $user->{$dbCol};
                // Only update if cleaned API value is not null and differs
                if ($cleanVal !== null && $cleanVal !== $dbVal) {
                    $changes[$dbCol] = ['from' => $user->{$dbCol}, 'to' => $cleanVal];
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
            $user->id_org_unit = isset($emp['id_ou']) && $emp['id_ou'] !== '' ? (string) $emp['id_ou'] : (isset($emp['id_org_unit']) && $emp['id_org_unit'] !== '' ? (string) $emp['id_org_unit'] : '0');
            $user->team = isset($emp['team']) && $emp['team'] !== '' ? trim((string) $emp['team']) : '-';
            $user->department = isset($emp['department']) && $emp['department'] !== '' ? trim((string) $emp['department']) : '-';
            $user->division = isset($emp['division']) && $emp['division'] !== '' ? trim((string) $emp['division']) : '-';
            $user->sap_code = isset($emp['sap_code']) && $emp['sap_code'] !== '' ? trim((string) $emp['sap_code']) : '-';
            $user->ssohash = isset($emp['ssohash']) && $emp['ssohash'] !== '' ? trim((string) $emp['ssohash']) : (isset($emp['newhash']) && $emp['newhash'] !== '' ? trim((string) $emp['newhash']) : '-');
            $user->role = User::ROLE_USER; // Default Role 1
            $user->active = 0; // Safe Inactive default

            // Extended HR employee fields
            $user->id_team = $this->cleanHrField('id_team', $emp['id_team'] ?? null);
            $user->id_department = $this->cleanHrField('id_department', $emp['id_department'] ?? null);
            $user->id_division = $this->cleanHrField('id_division', $emp['id_division'] ?? null);
            $user->cell_phone = $this->cleanHrField('cell_phone', $emp['cell_phone'] ?? null);
            $user->sex = $this->cleanHrField('sex', $emp['sex'] ?? null);
            $user->orgunit = $this->cleanHrField('orgunit', $emp['orgunit'] ?? null);
            $user->id_job_position = $this->cleanHrField('id_job_position', $emp['id_job_position'] ?? null);
            $user->jobposition = $this->cleanHrField('jobposition', $emp['jobposition'] ?? null);
            $user->positionID = $this->cleanHrField('positionID', $emp['positionID'] ?? null);
            $user->appr1 = $this->cleanHrField('appr1', $emp['appr1'] ?? null);
            $user->appr2 = $this->cleanHrField('appr2', $emp['appr2'] ?? null);
            $user->resign = $this->cleanHrField('resign', $emp['resign'] ?? null) ?: 'N';
            $user->flag = isset($emp['flag']) && $emp['flag'] !== '' ? (int) $emp['flag'] : 1;
            $user->jobgrade = $this->cleanHrField('jobgrade', $emp['jobgrade'] ?? null);
            $user->id_job_clan = $this->cleanHrField('id_job_clan', $emp['id_job_clan'] ?? null);
            $user->job_clan = $this->cleanHrField('job_clan', $emp['job_clan'] ?? null);
            $user->id_job_family = $this->cleanHrField('id_job_family', $emp['id_job_family'] ?? null);
            $user->job_family = $this->cleanHrField('job_family', $emp['job_family'] ?? null);

            $user->save();

            // Cache in memory for subsequent checks
            $dbUsersByEmp[$idEmp] = $user;
            if ($user->email !== '') {
                $usersByEmail[strtolower($user->email)] = $user;
            }
        }
    }

    /**
     * Sanitize and format HR field value safely before storing.
     */
    protected function cleanHrField(string $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $val = trim((string) $value);
        if ($val === '' || $val === '?' || strtolower($val) === 'null') {
            return null;
        }

        if ($field === 'sex') {
            if (in_array(strtolower($val), ['?', '-', 'null', 'undefined', '--select sex--', '--select--'], true) || str_starts_with($val, '--')) {
                return null;
            }
            $upper = strtoupper($val);
            if ($upper === 'M' || $upper === 'F') {
                return $upper;
            }
            return mb_substr($val, 0, 10);
        }

        $maxLengths = [
            'id_team' => 20,
            'id_department' => 20,
            'id_division' => 20,
            'cell_phone' => 30,
            'sex' => 10,
            'orgunit' => 255,
            'id_job_position' => 20,
            'jobposition' => 100,
            'positionID' => 30,
            'appr1' => 30,
            'appr2' => 30,
            'resign' => 10,
            'jobgrade' => 50,
            'id_job_clan' => 20,
            'job_clan' => 100,
            'id_job_family' => 20,
            'job_family' => 100,
            'team' => 255,
            'department' => 255,
            'division' => 255,
            'sap_code' => 255,
            'id_org_unit' => 20,
            'id_user' => 20,
        ];

        if (isset($maxLengths[$field])) {
            return mb_substr($val, 0, $maxLengths[$field]);
        }

        return $val;
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
