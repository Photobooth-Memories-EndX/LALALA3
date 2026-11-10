<?php

namespace App\Services\EmployeeSync;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeApiClient
{
    protected string $fullUrl;
    protected string $baseUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleepMs;
    protected int $maxPages = 500;

    /**
     * Constructor supports both:
     * - New style: __construct(?string $fullUrl, ?string $baseUrl, ?int $timeout, ?int $retryTimes, ?int $retrySleepMs)
     * - Legacy style: __construct(?string $baseUrl, ?int $timeout, ?int $retryTimes, ?int $retrySleepMs)
     */
    public function __construct(
        ?string $fullUrl = null,
        mixed $baseUrlOrTimeout = null,
        ?int $timeoutOrRetryTimes = null,
        ?int $retryTimesOrSleep = null,
        ?int $retrySleepMs = null
    ) {
        $defaultFull = (string) config('api.employee.full_url', 'http://172.21.23.73/employee-api/public/api/employees-full');
        $defaultBase = (string) config('api.employee.base_url', 'http://172.21.23.73/employee-api/public/api/employees/');
        $defaultTimeout = (int) config('api.employee.timeout', 15);
        $defaultRetryTimes = (int) config('api.employee.retry_times', 2);
        $defaultRetrySleep = (int) config('api.employee.retry_sleep', 500);

        if (is_numeric($baseUrlOrTimeout)) {
            // Legacy signature: ($baseUrl, $timeout, $retryTimes, $retrySleep)
            $this->baseUrl = $fullUrl ?? $defaultBase;
            $this->fullUrl = $fullUrl ?? $defaultFull;
            $this->timeout = (int) $baseUrlOrTimeout;
            $this->retryTimes = $timeoutOrRetryTimes ?? $defaultRetryTimes;
            $this->retrySleepMs = $retryTimesOrSleep ?? $defaultRetrySleep;
        } else {
            // Standard / new signature
            $this->fullUrl = $fullUrl ?? $defaultFull;
            $this->baseUrl = is_string($baseUrlOrTimeout) ? $baseUrlOrTimeout : $defaultBase;
            $this->timeout = $timeoutOrRetryTimes ?? $defaultTimeout;
            $this->retryTimes = $retryTimesOrSleep ?? $defaultRetryTimes;
            $this->retrySleepMs = $retrySleepMs ?? $defaultRetrySleep;
        }
    }

    public function getFullUrl(): string
    {
        return $this->fullUrl;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getRetryTimes(): int
    {
        return $this->retryTimes;
    }

    public function getRetrySleepMs(): int
    {
        return $this->retrySleepMs;
    }

    /**
     * Fetch all employees from the primary employee API endpoint (employees-full).
     * Handles both unpaginated and paginated API responses automatically.
     *
     * @return array<int, array<string, mixed>>
     * @throws EmployeeApiException
     */
    public function fetchAllEmployees(): array
    {
        $allEmployees = [];
        $nextUrl = $this->fullUrl;
        $pageCount = 0;

        while ($nextUrl && $pageCount < $this->maxPages) {
            $pageCount++;

            try {
                $response = Http::timeout($this->timeout)
                    ->retry($this->retryTimes, $this->retrySleepMs, function ($exception) {
                        return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                    })
                    ->get($nextUrl);
            } catch (Throwable $e) {
                Log::error('EmployeeApiClient: Network or connection error while fetching employees', [
                    'endpoint' => $nextUrl,
                    'page' => $pageCount,
                    'error' => $e->getMessage(),
                ]);
                throw new EmployeeApiException('Failed to connect to Employee API Gateway: ' . $e->getMessage(), 0, $e);
            }

            if ($response->failed()) {
                $status = $response->status();
                Log::error('EmployeeApiClient: API returned non-success HTTP status', [
                    'endpoint' => $nextUrl,
                    'page' => $pageCount,
                    'status' => $status,
                ]);
                throw new EmployeeApiException("Employee API returned HTTP status {$status}", $status);
            }

            $body = $response->body();
            if (trim($body) === '') {
                if ($pageCount === 1) {
                    throw new EmployeeApiException('Employee API returned an empty response body');
                }
                break;
            }

            $decoded = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('EmployeeApiClient: Malformed JSON received from API', [
                    'endpoint' => $nextUrl,
                    'page' => $pageCount,
                    'json_error' => json_last_error_msg(),
                ]);
                throw new EmployeeApiException('Employee API response is not valid JSON: ' . json_last_error_msg());
            }

            if (!is_array($decoded)) {
                throw new EmployeeApiException('Employee API response structure is unexpected (expected a list or object of employee records)');
            }

            // Case A: Sequential list (unpaginated flat array of employees)
            if (array_is_list($decoded)) {
                return $decoded;
            }

            // Case B: Empty associative array on first page
            if (empty($decoded)) {
                if ($pageCount === 1) {
                    return [];
                }
                break;
            }

            // Case C: Object containing 'data' or 'employees' array
            $pageRecords = [];
            if (isset($decoded['data']) && is_array($decoded['data'])) {
                $pageRecords = $decoded['data'];
            } elseif (isset($decoded['employees']) && is_array($decoded['employees'])) {
                $pageRecords = $decoded['employees'];
            } else {
                throw new EmployeeApiException('Employee API response structure is unexpected (expected a list of employee records)');
            }

            $allEmployees = array_merge($allEmployees, $pageRecords);

            // Pagination detection
            $nextPageUrl = null;
            if (!empty($decoded['next_page_url'])) {
                $nextPageUrl = (string) $decoded['next_page_url'];
            } elseif (!empty($decoded['links']['next'])) {
                $nextPageUrl = (string) $decoded['links']['next'];
            } elseif (isset($decoded['current_page'], $decoded['last_page']) && (int) $decoded['current_page'] < (int) $decoded['last_page']) {
                $parsedUrl = parse_url($nextUrl);
                parse_str($parsedUrl['query'] ?? '', $queryParams);
                $queryParams['page'] = ((int) $decoded['current_page']) + 1;
                $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
                $host = $parsedUrl['host'] ?? '';
                $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
                $path = $parsedUrl['path'] ?? '';
                $nextPageUrl = $scheme . $host . $port . $path . '?' . http_build_query($queryParams);
            }

            if (!$nextPageUrl || $nextPageUrl === $nextUrl) {
                break;
            }

            $nextUrl = $nextPageUrl;
        }

        return $allEmployees;
    }

    /**
     * Fetch a single employee by employee ID using the legacy detail endpoint.
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

        if (isset($data['data']) && is_array($data['data']) && !array_is_list($data['data'])) {
            return $data['data'];
        }

        return $data;
    }
}
