<?php

namespace App\Services\EmployeeSync;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeApiClient
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleepMs;

    public function __construct(
        ?string $baseUrl = null,
        ?int $timeout = null,
        ?int $retryTimes = null,
        ?int $retrySleepMs = null
    ) {
        $this->baseUrl = $baseUrl ?? (string) config('api.employee.base_url', 'http://172.21.23.73/employee-api/public/api/employees/');
        $this->timeout = $timeout ?? (int) config('api.employee.timeout', 15);
        $this->retryTimes = $retryTimes ?? (int) config('api.employee.retry_times', 2);
        $this->retrySleepMs = $retrySleepMs ?? (int) config('api.employee.retry_sleep', 500);
    }

    /**
     * Fetch all employees from the POSCO employee API gateway.
     *
     * @return array<int, array<string, mixed>>
     * @throws EmployeeApiException
     */
    public function fetchAllEmployees(): array
    {
        $url = rtrim($this->baseUrl, '/') . '/';

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleepMs, function ($exception) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->get($url);
        } catch (Throwable $e) {
            Log::error('EmployeeApiClient: Network or connection error while fetching employees', [
                'endpoint' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new EmployeeApiException('Failed to connect to Employee API Gateway: ' . $e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            $status = $response->status();
            Log::error('EmployeeApiClient: API returned non-success HTTP status', [
                'endpoint' => $url,
                'status' => $status,
            ]);
            throw new EmployeeApiException("Employee API returned HTTP status {$status}", $status);
        }

        $body = $response->body();
        if (trim($body) === '') {
            throw new EmployeeApiException('Employee API returned an empty response body');
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('EmployeeApiClient: Malformed JSON received from API', [
                'endpoint' => $url,
                'json_error' => json_last_error_msg(),
            ]);
            throw new EmployeeApiException('Employee API response is not valid JSON: ' . json_last_error_msg());
        }

        // If the API returns a wrapper e.g. ['data' => [...]] or directly [...]
        if (is_array($decoded)) {
            if (isset($decoded['data']) && is_array($decoded['data'])) {
                return $decoded['data'];
            }
            if (isset($decoded['employees']) && is_array($decoded['employees'])) {
                return $decoded['employees'];
            }
            // Sequential array of employee objects
            if (array_is_list($decoded)) {
                return $decoded;
            }
            // In case of an empty array []
            if (empty($decoded)) {
                return [];
            }
        }

        throw new EmployeeApiException('Employee API response structure is unexpected (expected a list of employee records)');
    }

    /**
     * Fetch a single employee by employee ID.
     *
     * @param string $idEmp
     * @return array<string, mixed>|null
     * @throws EmployeeApiException
     */
    public function fetchEmployeeById(string $idEmp): ?array
    {
        $cleanId = trim($idEmp);
        if ($cleanId === '') {
            return null;
        }

        $url = rtrim($this->baseUrl, '/') . '/' . urlencode($cleanId);

        try {
            $response = Http::timeout($this->timeout)->get($url);
        } catch (Throwable $e) {
            throw new EmployeeApiException("Failed to fetch employee {$cleanId}: " . $e->getMessage(), 0, $e);
        }

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new EmployeeApiException("Employee API returned status {$response->status()} for ID {$cleanId}", $response->status());
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new EmployeeApiException("Invalid response format for employee {$cleanId}");
        }

        return $data;
    }
}
