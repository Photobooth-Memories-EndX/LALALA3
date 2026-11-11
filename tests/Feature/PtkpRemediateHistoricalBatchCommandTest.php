<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PtkpRemediateHistoricalBatchCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected string $testBatchUuid;
    protected ImportBatch $batch;
    protected OfflineTraining $training27;
    protected OfflineTraining $training37;
    protected OfflineTrainingUser $otu373;
    protected OfflineTrainingUser $otu374;
    protected OfflineTrainingUser $otu470;
    protected OfflineTrainingUser $otu471;
    protected OfflineTrainingUser $validOtu37;
    protected string $backupFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBatchUuid = (string) Str::uuid();
        $this->backupFile = storage_path("app/ptkp_remediation_backup_{$this->testBatchUuid}.json");

        // Find or create test users
        $kim = User::where('id_emp', '000022')->first() ?? User::first();
        $novial = User::where('id_emp', '000033')->first() ?? User::first();

        // Create test batch
        $this->batch = ImportBatch::create([
            'batch_uuid'        => $this->testBatchUuid,
            'source_type'       => ImportBatch::SOURCE_PTKP,
            'original_filename' => 'test_ptkp.xlsx',
            'file_hash'         => hash('sha256', 'test'),
            'imported_by'       => $kim->id,
            'started_at'        => now(),
            'finished_at'       => now(),
            'status'            => ImportBatch::STATUS_COMPLETED,
            'valid_rows'        => 3409,
            'invalid_rows'      => 211,
            'warning_rows'      => 58,
            'duplicate_rows'    => 1,
            'total_rows'        => 3679,
            'imported_rows'     => 3467,
            'skipped_rows'      => 212,
            'trainings_created' => 197,
        ]);

        // Create dummy orphan training (simulate Training 27)
        $this->training27 = OfflineTraining::create([
            'title'        => 'Dummy Training 27',
            'status'       => 'published',
            'category'     => 'Internal',
            'instructor'   => 'Trainer',
            'location'     => 'Room 1',
            'start_date'   => '2026-01-01',
            'end_date'     => '2026-01-02',
        ]);

        // Create dummy mixed training (simulate Training 37)
        $this->training37 = OfflineTraining::create([
            'title'        => 'Dummy Training 37',
            'status'       => 'published',
            'category'     => 'Internal',
            'instructor'   => 'Trainer',
            'location'     => 'Room 2',
            'start_date'   => '2026-01-01',
            'end_date'     => '2026-01-02',
        ]);

        // Create OTUs
        $this->otu373 = OfflineTrainingUser::create([
            'offline_training_id' => $this->training27->id,
            'user_id'             => $kim->id,
            'employee_id'         => '000022',
            'name'                => 'KIM KYU-JE',
        ]);
        $this->otu374 = OfflineTrainingUser::create([
            'offline_training_id' => $this->training27->id,
            'user_id'             => $novial->id,
            'employee_id'         => '000033',
            'name'                => 'NOVIAL',
        ]);
        $this->otu470 = OfflineTrainingUser::create([
            'offline_training_id' => $this->training37->id,
            'user_id'             => $kim->id,
            'employee_id'         => '000022',
            'name'                => 'KIM KYU-JE',
        ]);
        $this->otu471 = OfflineTrainingUser::create([
            'offline_training_id' => $this->training37->id,
            'user_id'             => $novial->id,
            'employee_id'         => '000033',
            'name'                => 'NOVIAL',
        ]);

        // Add 1 valid participant to training 37 so it is not an orphan
        $this->validOtu37 = OfflineTrainingUser::create([
            'offline_training_id' => $this->training37->id,
            'user_id'             => $kim->id,
            'employee_id'         => '116022',
            'name'                => 'VALID PARTICIPANT',
        ]);

        // Create 4 target batch rows
        ImportBatchRow::create([
            'import_batch_id'          => $this->batch->id,
            'row_number'               => 380,
            'sheet_name'               => 'Sheet1',
            'employee_id'              => '000022',
            'employee_name'            => 'KIM KYU-JE',
            'course_name'              => 'Dummy Training 27',
            'validation_status'        => ImportBatchRow::STATUS_VALID,
            'action'                   => ImportBatchRow::ACTION_IMPORTED,
            'offline_training_id'      => $this->training27->id,
            'offline_training_user_id' => $this->otu373->id,
        ]);
        ImportBatchRow::create([
            'import_batch_id'          => $this->batch->id,
            'row_number'               => 391,
            'sheet_name'               => 'Sheet1',
            'employee_id'              => '000033',
            'employee_name'            => 'NOVIAL',
            'course_name'              => 'Dummy Training 27',
            'validation_status'        => ImportBatchRow::STATUS_VALID,
            'action'                   => ImportBatchRow::ACTION_IMPORTED,
            'offline_training_id'      => $this->training27->id,
            'offline_training_user_id' => $this->otu374->id,
        ]);
        ImportBatchRow::create([
            'import_batch_id'          => $this->batch->id,
            'row_number'               => 494,
            'sheet_name'               => 'Sheet1',
            'employee_id'              => '000022',
            'employee_name'            => 'KIM KYU-JE',
            'course_name'              => 'Dummy Training 37',
            'validation_status'        => ImportBatchRow::STATUS_VALID,
            'action'                   => ImportBatchRow::ACTION_IMPORTED,
            'offline_training_id'      => $this->training37->id,
            'offline_training_user_id' => $this->otu470->id,
        ]);
        ImportBatchRow::create([
            'import_batch_id'          => $this->batch->id,
            'row_number'               => 505,
            'sheet_name'               => 'Sheet1',
            'employee_id'              => '000033',
            'employee_name'            => 'NOVIAL',
            'course_name'              => 'Dummy Training 37',
            'validation_status'        => ImportBatchRow::STATUS_VALID,
            'action'                   => ImportBatchRow::ACTION_IMPORTED,
            'offline_training_id'      => $this->training37->id,
            'offline_training_user_id' => $this->otu471->id,
        ]);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->backupFile)) {
            File::delete($this->backupFile);
        }
        parent::tearDown();
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        $this->artisan('ptkp:remediate-historical-batch', [
            'batch_uuid' => $this->testBatchUuid,
            '--dry-run'  => true,
        ])->assertSuccessful();

        // Batch counters unchanged
        $this->batch->refresh();
        $this->assertSame(3409, $this->batch->valid_rows);
        $this->assertSame(211, $this->batch->invalid_rows);
        $this->assertSame(3467, $this->batch->imported_rows);
        $this->assertSame(212, $this->batch->skipped_rows);

        // OTUs still not deleted
        $this->assertNull(OfflineTrainingUser::find($this->otu373->id)->deleted_at);
        $this->assertNull(OfflineTrainingUser::find($this->otu374->id)->deleted_at);

        // Trainings still not deleted
        $this->assertNull(OfflineTraining::find($this->training27->id)->deleted_at);
        $this->assertNull(OfflineTraining::find($this->training37->id)->deleted_at);

        // No backup file created
        $this->assertFileDoesNotExist($this->backupFile);
    }

    public function test_execute_and_idempotency_and_rollback(): void
    {
        // 1. EXECUTE
        $this->artisan('ptkp:remediate-historical-batch', [
            'batch_uuid' => $this->testBatchUuid,
            '--execute'  => true,
        ])->assertSuccessful();

        // Assert backup file created
        $this->assertFileExists($this->backupFile);

        // Assert batch rows updated
        $row380 = ImportBatchRow::where('import_batch_id', $this->batch->id)->where('row_number', 380)->first();
        $this->assertSame('22', $row380->employee_id);
        $this->assertSame('IQBAL NUR RACHMAN', $row380->employee_name);
        $this->assertSame(ImportBatchRow::STATUS_INVALID, $row380->validation_status);
        $this->assertSame(ImportBatchRow::ACTION_SKIPPED, $row380->action);
        $this->assertNull($row380->offline_training_user_id);
        $this->assertNull($row380->offline_training_id);

        // Assert erroneous OTUs are soft-deleted
        $this->assertNotNull(OfflineTrainingUser::withTrashed()->find($this->otu373->id)->deleted_at);
        $this->assertNotNull(OfflineTrainingUser::withTrashed()->find($this->otu374->id)->deleted_at);
        $this->assertNotNull(OfflineTrainingUser::withTrashed()->find($this->otu470->id)->deleted_at);
        $this->assertNotNull(OfflineTrainingUser::withTrashed()->find($this->otu471->id)->deleted_at);

        // Assert valid OTU in training 37 is NOT deleted
        $this->assertNull(OfflineTrainingUser::find($this->validOtu37->id)->deleted_at);

        // Assert training 27 is soft-deleted (orphan)
        $this->assertNotNull(OfflineTraining::withTrashed()->find($this->training27->id)->deleted_at);

        // Assert training 37 is NOT deleted (has valid users)
        $this->assertNull(OfflineTraining::find($this->training37->id)->deleted_at);

        // Assert batch counters adjusted
        $this->batch->refresh();
        $this->assertSame(3405, $this->batch->valid_rows);
        $this->assertSame(215, $this->batch->invalid_rows);
        $this->assertSame(3463, $this->batch->imported_rows);
        $this->assertSame(216, $this->batch->skipped_rows);
        $this->assertSame(196, $this->batch->trainings_created);

        // 2. IDEMPOTENCY CHECK (Run --execute a second time)
        $this->artisan('ptkp:remediate-historical-batch', [
            'batch_uuid' => $this->testBatchUuid,
            '--execute'  => true,
        ])->assertSuccessful();

        // Counters should NOT decrement again!
        $this->batch->refresh();
        $this->assertSame(3405, $this->batch->valid_rows);
        $this->assertSame(215, $this->batch->invalid_rows);
        $this->assertSame(3463, $this->batch->imported_rows);
        $this->assertSame(216, $this->batch->skipped_rows);
        $this->assertSame(196, $this->batch->trainings_created);

        // 3. ROLLBACK CHECK
        $this->artisan('ptkp:remediate-historical-batch', [
            'batch_uuid' => $this->testBatchUuid,
            '--rollback' => true,
        ])->assertSuccessful();

        // Assert restored
        $row380->refresh();
        $this->assertSame('000022', $row380->employee_id);
        $this->assertSame('KIM KYU-JE', $row380->employee_name);
        $this->assertSame(ImportBatchRow::STATUS_VALID, $row380->validation_status);
        $this->assertSame(ImportBatchRow::ACTION_IMPORTED, $row380->action);
        $this->assertSame($this->otu373->id, $row380->offline_training_user_id);

        $this->assertNull(OfflineTrainingUser::find($this->otu373->id)->deleted_at);
        $this->assertNull(OfflineTrainingUser::find($this->otu374->id)->deleted_at);
        $this->assertNull(OfflineTrainingUser::find($this->otu470->id)->deleted_at);
        $this->assertNull(OfflineTrainingUser::find($this->otu471->id)->deleted_at);

        $this->assertNull(OfflineTraining::find($this->training27->id)->deleted_at);
        $this->assertNull(OfflineTraining::find($this->training37->id)->deleted_at);

        $this->batch->refresh();
        $this->assertSame(3409, $this->batch->valid_rows);
        $this->assertSame(211, $this->batch->invalid_rows);
        $this->assertSame(3467, $this->batch->imported_rows);
        $this->assertSame(212, $this->batch->skipped_rows);
        $this->assertSame(197, $this->batch->trainings_created);
    }
}
