<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\ImportLog\ImportFileStorageService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class ImportBatchDatabaseTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected ImportFileStorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? User::create([
                'name'         => 'Test Admin ' . Str::random(5),
                'email'        => 'admin_' . Str::random(5) . '@posco.net',
                'username'     => 'admin_' . Str::random(5),
                'password'     => bcrypt('secret'),
                'role'         => User::ROLE_ADMIN,
                'company_name' => 'PT Krakatau Posco',
            ]);

        $this->storageService = new ImportFileStorageService();
    }

    /**
     * Test tables and columns exist in schema.
     */
    public function test_import_batches_and_rows_tables_exist_and_have_columns(): void
    {
        $this->assertTrue(Schema::hasTable('import_batches'));
        $this->assertTrue(Schema::hasTable('import_batch_rows'));

        $batchColumns = [
            'id', 'batch_uuid', 'source_type', 'original_filename',
            'file_path', 'file_disk', 'file_hash', 'file_size',
            'imported_by', 'status', 'total_rows', 'valid_rows',
            'warning_rows', 'duplicate_rows', 'invalid_rows',
            'imported_rows', 'skipped_rows', 'failed_rows',
            'trainings_created', 'trainings_reused',
            'started_at', 'finished_at', 'error_summary', 'metadata',
        ];

        foreach ($batchColumns as $col) {
            $this->assertTrue(Schema::hasColumn('import_batches', $col), "Missing column: {$col} on import_batches");
        }

        $rowColumns = [
            'id', 'import_batch_id', 'row_number', 'sheet_name',
            'employee_id', 'employee_name', 'course_name',
            'validation_status', 'action',
            'offline_training_id', 'offline_training_user_id',
            'error_code', 'messages', 'row_data',
        ];

        foreach ($rowColumns as $col) {
            $this->assertTrue(Schema::hasColumn('import_batch_rows', $col), "Missing column: {$col} on import_batch_rows");
        }
    }

    /**
     * Test ImportBatch creation and casting.
     */
    public function test_import_batch_creation_and_attribute_casting(): void
    {
        $uuid = (string) Str::uuid();

        $batch = ImportBatch::create([
            'batch_uuid'        => $uuid,
            'source_type'       => ImportBatch::SOURCE_PTKP,
            'original_filename' => 'PTKP_April_2026.xlsx',
            'file_path'         => "imports/ptkp/{$uuid}/original.xlsx",
            'file_disk'         => 'local',
            'file_hash'         => str_repeat('a', 64),
            'file_size'         => 1048576,
            'imported_by'       => $this->adminUser->id,
            'status'            => ImportBatch::STATUS_COMPLETED,
            'total_rows'        => 10,
            'valid_rows'        => 8,
            'warning_rows'      => 2,
            'duplicate_rows'    => 0,
            'invalid_rows'      => 0,
            'imported_rows'     => 10,
            'skipped_rows'      => 0,
            'failed_rows'       => 0,
            'trainings_created' => 2,
            'trainings_reused'  => 1,
            'started_at'        => now()->subMinutes(2),
            'finished_at'       => now(),
            'metadata'          => [
                'ip'         => '127.0.0.1',
                'user_agent' => 'PHPUnit',
            ],
        ]);

        $this->assertDatabaseHas('import_batches', ['batch_uuid' => $uuid]);
        $this->assertSame(1048576, $batch->file_size);
        $this->assertIsArray($batch->metadata);
        $this->assertSame('PHPUnit', $batch->metadata['user_agent']);
        $this->assertTrue($batch->isSuccessful());
    }

    /**
     * Test relationship between ImportBatch and ImportBatchRow.
     */
    public function test_batch_and_row_relationships(): void
    {
        $batch = ImportBatch::create([
            'batch_uuid'        => (string) Str::uuid(),
            'source_type'       => ImportBatch::SOURCE_NON_PTKP,
            'original_filename' => 'Non_Ptkp_Batch.xlsx',
            'imported_by'       => $this->adminUser->id,
            'status'            => ImportBatch::STATUS_IMPORTING,
            'started_at'        => now(),
        ]);

        $row1 = $batch->rows()->create([
            'row_number'        => 2,
            'employee_id'       => 'EMP001',
            'employee_name'     => 'Alice',
            'course_name'       => 'Safety Induction',
            'validation_status' => ImportBatchRow::STATUS_VALID,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
            'messages'          => ['Validated OK'],
            'row_data'          => ['hours' => 8],
        ]);

        $row2 = $batch->rows()->create([
            'row_number'        => 3,
            'employee_id'       => 'EMP002',
            'employee_name'     => 'Bob',
            'course_name'       => 'Safety Induction',
            'validation_status' => ImportBatchRow::STATUS_WARNING,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
            'messages'          => ['Company mismatch'],
        ]);

        $this->assertCount(2, $batch->fresh()->rows);
        $this->assertSame($batch->id, $row1->batch->id);
        $this->assertSame($this->adminUser->id, $batch->importedBy->id);
        $this->assertTrue($row1->isImported());
    }

    /**
     * Test storage service stores file to private disk with correct hash and metadata.
     */
    public function test_storage_service_stores_file_to_private_disk_correctly(): void
    {
        Storage::fake('local');

        $uuid = (string) Str::uuid();
        $fakeContent = "test,excel,data\n1,2,3";
        $uploadedFile = UploadedFile::fake()->createWithContent('Template_PTKP.xlsx', $fakeContent);

        $result = $this->storageService->storeImportFile(
            file: $uploadedFile,
            sourceType: ImportBatch::SOURCE_PTKP,
            batchUuid: $uuid,
            originalFilename: 'Template_PTKP.xlsx'
        );

        $expectedPath = "imports/ptkp/{$uuid}/original.xlsx";
        $this->assertSame($expectedPath, $result['file_path']);
        $this->assertSame('local', $result['file_disk']);
        $this->assertSame('Template_PTKP.xlsx', $result['original_filename']);
        $this->assertSame(strlen($fakeContent), $result['file_size']);
        $this->assertSame(hash('sha256', $fakeContent), $result['file_hash']);

        // Assert file exists on private local storage
        Storage::disk('local')->assertExists($expectedPath);

        // Assert file does NOT exist on public storage
        $this->assertFalse(Storage::disk('public')->exists($expectedPath));
    }

    /**
     * Test storage service handles Non-PTKP source folder and real file path.
     */
    public function test_storage_service_handles_non_ptkp_from_real_path(): void
    {
        Storage::fake('local');

        $uuid = (string) Str::uuid();
        $tempFile = tempnam(sys_get_temp_dir(), 'owl_test_') . '.csv';
        file_put_contents($tempFile, "col1,col2\nval1,val2");

        try {
            $result = $this->storageService->storeImportFile(
                file: $tempFile,
                sourceType: ImportBatch::SOURCE_NON_PTKP,
                batchUuid: $uuid,
                originalFilename: 'custom_source.csv'
            );

            $expectedPath = "imports/non-ptkp/{$uuid}/original.csv";
            $this->assertSame($expectedPath, $result['file_path']);
            Storage::disk('local')->assertExists($expectedPath);
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Test storage service rejects disallowed file extensions.
     */
    public function test_storage_service_rejects_disallowed_extension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file extension');

        $maliciousFile = UploadedFile::fake()->create('exploit.php', 10);
        $this->storageService->storeImportFile(
            file: $maliciousFile,
            sourceType: ImportBatch::SOURCE_PTKP,
            batchUuid: (string) Str::uuid()
        );
    }

    /**
     * Test storage service rejects invalid UUID to prevent path traversal.
     */
    public function test_storage_service_rejects_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid batch UUID format');

        $uploadedFile = UploadedFile::fake()->create('valid.xlsx', 10);
        $this->storageService->storeImportFile(
            file: $uploadedFile,
            sourceType: ImportBatch::SOURCE_PTKP,
            batchUuid: '../../etc/passwd'
        );
    }

    /**
     * Test file deletion cleanup helper.
     */
    public function test_storage_service_delete_cleanup(): void
    {
        Storage::fake('local');

        $uuid = (string) Str::uuid();
        $uploadedFile = UploadedFile::fake()->create('to_delete.xlsx', 20);

        $stored = $this->storageService->storeImportFile(
            file: $uploadedFile,
            sourceType: ImportBatch::SOURCE_PTKP,
            batchUuid: $uuid
        );

        $batch = new ImportBatch([
            'batch_uuid' => $uuid,
            'file_path'  => $stored['file_path'],
            'file_disk'  => $stored['file_disk'],
        ]);

        $this->assertTrue($this->storageService->fileExists($batch));

        $deleted = $this->storageService->deleteStoredFile($batch);
        $this->assertTrue($deleted);
        $this->assertFalse($this->storageService->fileExists($batch));
        Storage::disk('local')->assertMissing($stored['file_path']);
    }

    /**
     * Test foreign key cascading on batch deletion.
     */
    public function test_cascade_delete_batch_deletes_rows(): void
    {
        $batch = ImportBatch::create([
            'batch_uuid'        => (string) Str::uuid(),
            'source_type'       => ImportBatch::SOURCE_PTKP,
            'original_filename' => 'cascade_test.xlsx',
            'imported_by'       => $this->adminUser->id,
            'status'            => ImportBatch::STATUS_COMPLETED,
            'started_at'        => now(),
        ]);

        $row = $batch->rows()->create([
            'row_number'        => 2,
            'validation_status' => ImportBatchRow::STATUS_VALID,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
        ]);

        $batchId = $batch->id;
        $rowId = $row->id;

        $batch->delete();

        $this->assertDatabaseMissing('import_batches', ['id' => $batchId]);
        $this->assertDatabaseMissing('import_batch_rows', ['id' => $rowId]);
    }

    /**
     * Test foreign key set null on offline training deletion.
     */
    public function test_deleting_offline_training_sets_null_on_batch_row(): void
    {
        $batch = ImportBatch::create([
            'batch_uuid'        => (string) Str::uuid(),
            'source_type'       => ImportBatch::SOURCE_PTKP,
            'original_filename' => 'fk_test.xlsx',
            'imported_by'       => $this->adminUser->id,
            'status'            => ImportBatch::STATUS_COMPLETED,
            'started_at'        => now(),
        ]);

        $training = OfflineTraining::create([
            'title'            => 'Temp Safety Class ' . Str::random(5),
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->toDateString(),
            'training_type'    => 'Offline',
            'training_category'=> 'Technical',
            'learning_hours'   => 4.0,
            'created_by'       => $this->adminUser->id,
        ]);

        $row = $batch->rows()->create([
            'row_number'          => 2,
            'validation_status'   => ImportBatchRow::STATUS_VALID,
            'action'              => ImportBatchRow::ACTION_IMPORTED,
            'offline_training_id' => $training->id,
        ]);

        $this->assertSame($training->id, $row->fresh()->offline_training_id);

        $training->forceDelete();

        $this->assertNull($row->fresh()->offline_training_id);
    }

    /**
     * Test foreign key set null on offline training user deletion and batch download helpers.
     */
    public function test_deleting_offline_training_user_sets_null_and_batch_download_helpers(): void
    {
        Storage::fake('local');

        $uuid = (string) Str::uuid();
        $stored = $this->storageService->storeImportFile(
            file: UploadedFile::fake()->create('Export 2026! @#$.xlsx', 50),
            sourceType: ImportBatch::SOURCE_PTKP,
            batchUuid: $uuid,
            originalFilename: 'Export 2026! @#$.xlsx'
        );

        $batch = ImportBatch::create([
            'batch_uuid'        => $uuid,
            'source_type'       => ImportBatch::SOURCE_PTKP,
            'original_filename' => 'Export 2026! @#$.xlsx',
            'file_path'         => $stored['file_path'],
            'file_disk'         => $stored['file_disk'],
            'file_hash'         => $stored['file_hash'],
            'file_size'         => $stored['file_size'],
            'imported_by'       => $this->adminUser->id,
            'status'            => ImportBatch::STATUS_COMPLETED,
            'started_at'        => now(),
        ]);

        $training = OfflineTraining::create([
            'title'            => 'Temp Safety Class ' . Str::random(5),
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->toDateString(),
            'training_type'    => 'Offline',
            'training_category'=> 'Technical',
            'learning_hours'   => 4.0,
            'created_by'       => $this->adminUser->id,
        ]);

        $participant = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->adminUser->id,
            'employee_id'         => 'TESTEMP',
            'name'                => 'Test Emp',
        ]);

        $row = $batch->rows()->create([
            'row_number'               => 2,
            'validation_status'        => ImportBatchRow::STATUS_VALID,
            'action'                   => ImportBatchRow::ACTION_IMPORTED,
            'offline_training_id'      => $training->id,
            'offline_training_user_id' => $participant->id,
        ]);

        $this->assertTrue($batch->hasStoredFile());
        $stream = $batch->getStoredFileStream();
        $this->assertIsResource($stream);
        fclose($stream);

        $downloadFilename = $batch->getSafeDownloadFilename();
        $this->assertStringStartsWith('ptkp_import_' . $uuid, $downloadFilename);
        $this->assertStringEndsWith('.xlsx', $downloadFilename);
        $this->assertStringNotContainsString('@#$', $downloadFilename);

        $this->assertSame($participant->id, $row->fresh()->offline_training_user_id);
        $participant->forceDelete();
        $this->assertNull($row->fresh()->offline_training_user_id);
    }
}
