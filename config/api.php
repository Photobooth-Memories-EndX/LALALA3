<?php

return [
    'employee' => [
        'full_url' => env('EMPLOYEE_API_FULL_URL', 'http://172.21.23.73/employee-api/public/api/employees-full'),
        'base_url' => env('EMPLOYEE_API_BASE_URL', 'http://172.21.23.73/employee-api/public/api/employees/'),
        'base_url_hash' => env('EMPLOYEE_API_BASE_URL_HASH', 'http://172.21.23.73/employee-api/public/api/employeesbyhash/'),
        'id_url' => env('EMPLOYEE_API_ID_URL', 'http://172.21.23.73/employee-api/public/api/employeesById/'),
        'compact' => env('EMPLOYEE_API_COMPACT_URL', 'http://172.21.23.73/employee-api/public/api/employeesByIdCompact/'),
        'timeout' => (int) env('EMPLOYEE_API_TIMEOUT', 15),
        'retry_times' => (int) env('EMPLOYEE_API_RETRY_TIMES', 2),
        'retry_sleep' => (int) env('EMPLOYEE_API_RETRY_SLEEP_MS', 500),
        'auto_provision' => (bool) env('EMPLOYEE_SYNC_AUTO_PROVISION', true),
    ],
];
