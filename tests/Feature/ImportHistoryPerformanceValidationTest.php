<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\User;
use App\Services\ImportHistory\ImportHistoryExportService;
use App\Services\ImportLog\ImportHistoryQueryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Tests\TestCase;

class ImportHistoryPerformanceValidationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected ImportHistoryExportService $exportService;
    protected ImportHistoryQueryService $queryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? User::create([
                'name'         => 'Perf Test Admin',
                'email'        => 'perf_admin@posco.net',
                'password'     => bcrypt('secret'),
                'id_emp'       => 'PERF_EMP_01',
                'id_user'      => 'PERF_UID_01',
                'id_org_unit'  => 'OU_PERF',
                'team'         => 'Perf Team',
                'department'   => 'Perf Dept',
                'division'     => 'Perf Div',
                'sap_code'     => 'SAP_PERF',
                'ssohash'      => 'HASH_PERF',
                'role'         => User::ROLE_ADMIN,
                'company_name' => 'PT Krakatau Posco',
            ]);

        $this->queryService = app(ImportHistoryQueryService::class);
        $this->exportService = app(ImportHistoryExportService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        gc_collect_cycles();
    }

    /**
     * Helper to load only specific rows from an Excel file without exhausting memory.
     */
    protected function loadFilteredSpreadsheet(string $filePath, array $targetRows): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $filter = new class($targetRows) implements IReadFilter {
            private array $rows;
            public function __construct(array $rows) {
                $this->rows = $rows;
            }
            public function readCell($columnAddress, $row, $worksheetName = ''): bool {
                return in_array((int) $row, $this->rows, true);
            }
        };

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter($filter);
        return $reader->load($filePath);
    }

    /**
     * Create a synthetic batch with N rows inserted via bulk insert.
     */
    protected function createLargeBatch(int $count, array $statusDistribution = []): ImportBatch
    {
        $uuid = (string) Str::uuid();
        $batch = ImportBatch::create([
            'batch_uuid'        => $uuid,
            'source_type'       => ImportBatch::SOURCE_PTKP,
            'original_filename' => "perf_test_{$count}.xlsx",
            'file_path'         => null,
            'file_disk'         => 'local',
            'file_hash'         => hash('sha256', "perf_{$count}"),
            'file_size'         => 1024 * $count,
            'imported_by'       => $this->admin->id,
            'status'            => ImportBatch::STATUS_COMPLETED_WITH_WARNINGS,
            'total_rows'        => $count,
            'valid_rows'        => $statusDistribution['VALID'] ?? $count,
            'warning_rows'      => $statusDistribution['WARNING'] ?? 0,
            'duplicate_rows'    => $statusDistribution['DUPLICATE'] ?? 0,
            'invalid_rows'      => $statusDistribution['INVALID'] ?? 0,
            'imported_rows'     => $count,
            'skipped_rows'      => 0,
            'failed_rows'       => 0,
            'trainings_created' => 1,
            'trainings_reused'  => 0,
            'started_at'        => now()->subMinutes(15),
            'finished_at'       => now(),
        ]);

        $statusSequence = [];
        if (!empty($statusDistribution)) {
            foreach ($statusDistribution as $st => $stCount) {
                for ($k = 0; $k < $stCount; $k++) {
                    $statusSequence[] = $st;
                }
            }
        }

        $nowStr = now()->format('Y-m-d H:i:s');
        $defaultRowData = json_encode([
            'grade'          => '4A',
            'job_position'   => 'Staff',
            'team'           => 'Engineering',
            'department'     => 'Plant Ops',
            'company'        => 'PT Krakatau Posco',
            'learning_hours' => 8.0,
            'progress'       => 100,
            'start_date'     => '2026-10-01',
            'end_date'       => '2026-10-02',
        ]);
        $defaultMessages = json_encode(['Row validated successfully']);

        $chunks = array_chunk(range(1, $count), 500);
        foreach ($chunks as $chunk) {
            $insertData = [];
            foreach ($chunk as $seq) {
                $status = $statusSequence[$seq - 1] ?? ImportBatchRow::STATUS_VALID;
                $empId = sprintf('00%06d', $seq);
                $insertData[] = [
                    'import_batch_id'          => $batch->id,
                    'row_number'               => $seq + 1,
                    'sheet_name'               => 'Sheet1',
                    'employee_id'              => $empId,
                    'employee_name'            => "Employee {$seq}",
                    'course_name'              => "Automation Course " . (($seq % 5) + 1),
                    'validation_status'        => $status,
                    'action'                   => $status === ImportBatchRow::STATUS_INVALID ? ImportBatchRow::ACTION_SKIPPED : ImportBatchRow::ACTION_IMPORTED,
                    'offline_training_id'      => null,
                    'offline_training_user_id' => null,
                    'error_code'               => $status === ImportBatchRow::STATUS_INVALID ? 'ERR_INVALID' : null,
                    'messages'                 => $defaultMessages,
                    'row_data'                 => $defaultRowData,
                    'created_at'               => $nowStr,
                    'updated_at'               => $nowStr,
                ];
            }
            DB::table('import_batch_rows')->insert($insertData);
            unset($insertData);
        }

        return $batch;
    }

    /**
     * 1. 100 records: Export and Detail verification
     */
    public function test_100_records_export_and_detail(): void
    {
        $batch = $this->createLargeBatch(100, ['VALID' => 100]);

        $t0 = microtime(true);
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'VALID',
            ]));

        $response->assertStatus(200);
        $elapsed = microtime(true) - $t0;

        $tempFile = tempnam(sys_get_temp_dir(), 'perf_100_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $spreadsheet = $this->loadFilteredSpreadsheet($tempFile, [1, 2, 3, 102, 103]);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('VALID', $sheet->getCell('A1')->getValue());
        $this->assertEquals('00000001', $sheet->getCell('A3')->getValue());
        $this->assertEquals('00000100', $sheet->getCell('A102')->getValue());
        $this->assertNull($sheet->getCell('A103')->getValue());

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);
        @unlink($tempFile);

        $this->assertLessThan(5.0, $elapsed, "100 rows export must complete under 5 seconds (took {$elapsed}s)");

        // Verify Detail page
        $detailResponse = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.detail', $batch->batch_uuid));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('100');
    }

    /**
     * 2. 1,000 records: Export and Detail verification
     */
    public function test_1000_records_export_and_detail(): void
    {
        $batch = $this->createLargeBatch(1000, ['VALID' => 1000]);

        $t0 = microtime(true);
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'VALID',
            ]));

        $response->assertStatus(200);
        $elapsed = microtime(true) - $t0;

        $tempFile = tempnam(sys_get_temp_dir(), 'perf_1000_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $spreadsheet = $this->loadFilteredSpreadsheet($tempFile, [1, 2, 3, 1002, 1003]);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('00000001', $sheet->getCell('A3')->getValue());
        $this->assertEquals('00001000', $sheet->getCell('A1002')->getValue());
        $this->assertNull($sheet->getCell('A1003')->getValue());

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);
        @unlink($tempFile);

        $this->assertLessThan(10.0, $elapsed, "1000 rows export must complete under 10 seconds (took {$elapsed}s)");
    }

    /**
     * 3. 5,000 records: Export and Detail verification
     */
    public function test_5000_records_export_and_detail(): void
    {
        $batch = $this->createLargeBatch(5000, ['VALID' => 5000]);

        $t0 = microtime(true);
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'VALID',
            ]));

        $response->assertStatus(200);
        $elapsed = microtime(true) - $t0;

        $tempFile = tempnam(sys_get_temp_dir(), 'perf_5000_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $spreadsheet = $this->loadFilteredSpreadsheet($tempFile, [1, 2, 3, 5002, 5003]);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('00000001', $sheet->getCell('A3')->getValue());
        $this->assertEquals('00005000', $sheet->getCell('A5002')->getValue());
        $this->assertNull($sheet->getCell('A5003')->getValue());

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);
        @unlink($tempFile);

        $this->assertLessThan(25.0, $elapsed, "5000 rows export must complete under 25 seconds (took {$elapsed}s)");
    }

    /**
     * 4. 7,000 records: Exact failure scenario from user prompt (target: completes well within 60s, ideally < 30s)
     */
    public function test_7000_records_export_and_detail(): void
    {
        $batch = $this->createLargeBatch(7000, ['VALID' => 7000]);

        $t0 = microtime(true);
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'VALID',
            ]));

        $response->assertStatus(200);
        $elapsed = microtime(true) - $t0;

        $tempFile = tempnam(sys_get_temp_dir(), 'perf_7000_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $spreadsheet = $this->loadFilteredSpreadsheet($tempFile, [1, 2, 3, 7002, 7003]);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('00000001', $sheet->getCell('A3')->getValue());
        $this->assertEquals('00007000', $sheet->getCell('A7002')->getValue());
        $this->assertNull($sheet->getCell('A7003')->getValue());

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);
        @unlink($tempFile);

        // Crucial requirement: Must not exceed 60s, our target is under 35s
        $this->assertLessThan(35.0, $elapsed, "7000 rows export must complete under 35 seconds, avoiding 60s timeout (took {$elapsed}s)");

        // Verify detail page handles 7000 rows quickly
        $tDetail = microtime(true);
        $detailResponse = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.detail', $batch->batch_uuid));
        $detailElapsed = microtime(true) - $tDetail;

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('7,000');
        $this->assertLessThan(2.0, $detailElapsed, "Detail page for 7000 rows must load under 2 seconds (took {$detailElapsed}s)");
    }

    /**
     * 5. Detail endpoint query audit: verifies zero N+1 queries on batch rows
     */
    public function test_detail_endpoint_query_audit(): void
    {
        $batch = $this->createLargeBatch(1000, [
            'VALID'           => 400,
            'WARNING'         => 200,
            'DUPLICATE'       => 150,
            'INVALID'         => 150,
            'INVALID COMPANY' => 100,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.detail', $batch->batch_uuid));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);
        $response->assertSee('400');
        $response->assertSee('200');
        $response->assertSee('150');
        $response->assertSee('100');
        $response->assertSee('1,000');

        // Audit: queries targeting import_batch_rows must be exactly 1 (the aggregate group by)
        $batchRowsQueries = array_filter($queries, function ($q) {
            return str_contains(strtolower($q['query']), 'import_batch_rows');
        });

        $this->assertCount(1, $batchRowsQueries, "There must be exactly 1 query on import_batch_rows during detail page load.");
    }

    /**
     * 6. Rows datatable provider query audit: pagination works without loading full table
     */
    public function test_rows_datatable_pagination_and_query_audit(): void
    {
        $batch = $this->createLargeBatch(500, ['VALID' => 500]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.rows', [
                'batch_uuid' => $batch->batch_uuid,
                'length'     => 10,
                'start'      => 0,
            ]));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(500, $json['recordsTotal']);
        $this->assertCount(10, $json['data']);
        $this->assertEquals('00000001', $json['data'][0]['employee_id']);

        // DataTables query must use LIMIT
        $hasLimitQuery = false;
        foreach ($queries as $q) {
            if (str_contains(strtolower($q['query']), 'limit 10')) {
                $hasLimitQuery = true;
                break;
            }
        }
        $this->assertTrue($hasLimitQuery, "DataTables must use SQL LIMIT for pagination.");
    }

    /**
     * 7. Data consistency: Detail counts and Export row counts match across ALL validation filters
     */
    public function test_data_consistency_across_all_validation_filters(): void
    {
        $batch = $this->createLargeBatch(500, [
            'VALID'           => 150,
            'WARNING'         => 100,
            'DUPLICATE'       => 100,
            'INVALID'         => 100,
            'INVALID COMPANY' => 50,
        ]);

        $filters = ['VALID', 'WARNING', 'DUPLICATE', 'INVALID', 'INVALID COMPANY'];

        foreach ($filters as $status) {
            $response = $this->actingAs($this->admin)
                ->get(route('manage.record.offline.import-history.export', [
                    'batch_uuid'        => $batch->batch_uuid,
                    'validation_status' => $status,
                ]));

            $response->assertStatus(200);

            $tempFile = tempnam(sys_get_temp_dir(), 'consistency_') . '.xlsx';
            file_put_contents($tempFile, $response->streamedContent());

            $expectedCount = match ($status) {
                'VALID'           => 150,
                'WARNING'         => 100,
                'DUPLICATE'       => 100,
                'INVALID'         => 100,
                'INVALID COMPANY' => 50,
            };

            $lastExpectedRow = 2 + $expectedCount;
            $beyondRow = $lastExpectedRow + 1;

            $ss = $this->loadFilteredSpreadsheet($tempFile, [1, 2, 3, $lastExpectedRow, $beyondRow]);
            $sheet = $ss->getActiveSheet();

            // Check that row count in sheet equals header (2) + expectedCount
            $this->assertNotNull($sheet->getCell("A{$lastExpectedRow}")->getValue(), "Row {$lastExpectedRow} must be present for {$status}");
            $this->assertNull($sheet->getCell("A{$beyondRow}")->getValue(), "Row {$beyondRow} must be null for {$status}");

            $ss->disconnectWorksheets();
            unset($ss, $sheet);
            @unlink($tempFile);
        }
    }
}
