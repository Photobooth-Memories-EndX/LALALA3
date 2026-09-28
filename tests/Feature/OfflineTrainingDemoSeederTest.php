<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineTrainingDemoSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure at least one admin user exists
        if (User::count() === 0) {
            User::create([
                'id_user'     => 'USR99999',
                'id_emp'      => '99999999',
                'name'        => 'Test Seeder Admin',
                'email'       => 'test.admin@krakatauposco.co.id',
                'password'    => bcrypt('secret'),
                'team'        => 'Test Team',
                'department'  => 'Test Dept',
                'division'    => 'Test Div',
                'ssohash'     => 'sso_test',
                'sap_code'    => 'SAP999',
                'role'        => User::ROLE_ADMIN,
            ]);
        }
    }

    /**
     * Test full lifecycle, idempotency, and data contract of OfflineTrainingDemoSeeder.
     */
    public function test_offline_training_demo_seeder_executes_safely_and_satisfies_all_contracts(): void
    {
        // Setup: Create an existing non-demo offline training to verify non-destruction
        $preExisting = OfflineTraining::create([
            'course_name'         => 'Pre-existing Independent Training',
            'batch'               => 'EXISTING-PROD-BATCH-001',
            'main_classification' => 'Maintenance',
            'duration_seconds'    => 7200,
            'learning_hours'      => 2.0,
            'start_date'          => '2026-01-01',
            'finish_date'         => '2026-01-01',
        ]);

        $preExistingParticipant = OfflineTrainingUser::create([
            'offline_training_id' => $preExisting->id,
            'employee_id'         => 'EXISTING-EMP-999',
            'name'                => 'Pre-existing Employee',
            'progress'            => 100,
            'learning_hours'      => 2.0,
            'attendance'          => true,
        ]);

        try {
            // First execution
            $exitCode1 = Artisan::call('db:seed', ['--class' => 'OfflineTrainingDemoSeeder']);
            $this->assertSame(0, $exitCode1, 'Seeder should exit with status 0 on initial execution.');

            $demoTrainings = OfflineTraining::where('batch', 'like', 'DEMO-OFFLINE-%')->get();

            // 1. Seeder creates expected training count (exactly 20)
            $this->assertCount(20, $demoTrainings, 'Seeder must create exactly 20 demo offline trainings.');

            // 2. Every training has 2-5 participants & 3. No duplicate participant within same training
            $totalDemoParticipants = 0;
            $allParticipants = collect();

            foreach ($demoTrainings as $training) {
                $participants = OfflineTrainingUser::where('offline_training_id', $training->id)->get();
                $count = $participants->count();

                $this->assertGreaterThanOrEqual(2, $count, "Training {$training->batch} must have at least 2 participants.");
                $this->assertLessThanOrEqual(5, $count, "Training {$training->batch} must have at most 5 participants.");

                $uniqueEmpIds = $participants->pluck('employee_id')->unique();
                $this->assertCount($count, $uniqueEmpIds, "Training {$training->batch} contains duplicate participant employee IDs.");

                // 4. finish_date >= start_date
                $this->assertNotNull($training->start_date);
                $this->assertNotNull($training->finish_date);
                $this->assertTrue(
                    $training->finish_date >= $training->start_date,
                    "Training {$training->batch} finish_date must be on or after start_date."
                );

                $totalDemoParticipants += $count;
                $allParticipants = $allParticipants->merge($participants);
            }

            // Total demo participants target range (50 - 80)
            $this->assertGreaterThanOrEqual(50, $totalDemoParticipants);
            $this->assertLessThanOrEqual(80, $totalDemoParticipants);

            // 5. progress is between 0 and 100
            foreach ($allParticipants as $p) {
                $this->assertGreaterThanOrEqual(0.0, (float) $p->progress);
                $this->assertLessThanOrEqual(100.0, (float) $p->progress);
            }

            // 6. Finished samples exist (progress >= 100)
            $finishedCount = $allParticipants->filter(fn ($p) => (float) $p->progress >= 100.0)->count();
            $this->assertGreaterThan(0, $finishedCount, 'Seeded data must contain Finished participants.');

            // 7. Ongoing samples exist (0 < progress < 100)
            $ongoingCount = $allParticipants->filter(fn ($p) => (float) $p->progress > 0.0 && (float) $p->progress < 100.0)->count();
            $this->assertGreaterThan(0, $ongoingCount, 'Seeded data must contain Ongoing participants.');

            // 8. Not Started samples exist (progress == 0)
            $notStartedCount = $allParticipants->filter(fn ($p) => (float) $p->progress == 0.0)->count();
            $this->assertGreaterThan(0, $notStartedCount, 'Seeded data must contain Not Started participants.');

            // 9. Multiple classifications exist (including Unclassified)
            $classifications = $demoTrainings->pluck('main_classification')->unique()->values();
            $this->assertGreaterThan(5, $classifications->count(), 'Seeded trainings must span multiple classifications.');
            $this->assertTrue(
                $demoTrainings->contains(fn ($t) => empty($t->main_classification)),
                'Seeded data must include at least one Unclassified (null/empty main_classification) training.'
            );

            // 10. Corporate KPI Yes/No variation exists
            $kpiYes = $demoTrainings->filter(fn ($t) => (bool) $t->counted_corporate_kpi)->count();
            $kpiNo = $demoTrainings->filter(fn ($t) => ! (bool) $t->counted_corporate_kpi)->count();
            $this->assertGreaterThan(0, $kpiYes, 'Seeded data must have counted_corporate_kpi = true.');
            $this->assertGreaterThan(0, $kpiNo, 'Seeded data must have counted_corporate_kpi = false.');

            // 11. Attendance Yes/No variation exists
            $attendYes = $allParticipants->filter(fn ($p) => (bool) $p->attendance)->count();
            $attendNo = $allParticipants->filter(fn ($p) => ! (bool) $p->attendance)->count();
            $this->assertGreaterThan(0, $attendYes, 'Seeded data must contain attendees.');
            $this->assertGreaterThan(0, $attendNo, 'Seeded data must contain absent participants with reasons.');
            $this->assertTrue(
                $allParticipants->filter(fn ($p) => ! (bool) $p->attendance)->every(fn ($p) => ! empty($p->attendance_reason)),
                'Absent participants must have a populated attendance_reason.'
            );

            // 12. Bond Yes/No variation exists
            $bonded = $allParticipants->filter(fn ($p) => strtolower((string) $p->bond_status) === 'yes');
            $nonBonded = $allParticipants->filter(fn ($p) => strtolower((string) $p->bond_status) === 'no');
            $this->assertGreaterThan(0, $bonded->count(), 'Seeded data must contain bonded participants.');
            $this->assertGreaterThan(0, $nonBonded->count(), 'Seeded data must contain non-bonded participants.');
            foreach ($bonded as $b) {
                $this->assertNotNull($b->bond_end_date);
                $this->assertGreaterThan(0, (float) $b->bond_amount);
            }

            // 13. Existing unrelated Offline records are NOT deleted
            $this->assertDatabaseHas('offline_trainings', [
                'id'    => $preExisting->id,
                'batch' => 'EXISTING-PROD-BATCH-001',
            ]);
            $this->assertDatabaseHas('offline_training_users', [
                'id'          => $preExistingParticipant->id,
                'employee_id' => 'EXISTING-EMP-999',
            ]);

            // 14. Running seeder twice does not blindly duplicate the demo training set (Idempotency)
            $exitCode2 = Artisan::call('db:seed', ['--class' => 'OfflineTrainingDemoSeeder']);
            $this->assertSame(0, $exitCode2);

            $demoTrainingsAfter = OfflineTraining::where('batch', 'like', 'DEMO-OFFLINE-%')->get();
            $this->assertCount(20, $demoTrainingsAfter, 'Second run must not duplicate demo trainings.');

            $demoParticipantsAfter = OfflineTrainingUser::whereIn('offline_training_id', $demoTrainingsAfter->pluck('id'))->get();
            $this->assertSame($totalDemoParticipants, $demoParticipantsAfter->count(), 'Second run must not duplicate participants.');

            // 15. Seeded Offline records can be returned by RecordAllCombinedService
            /** @var RecordAllCombinedService $service */
            $service = app(RecordAllCombinedService::class);
            $summary = $service->getSummary();

            $this->assertGreaterThanOrEqual(
                $totalDemoParticipants,
                $summary['offline']['total_learning'],
                'RecordAllCombinedService must reflect seeded offline participant records.'
            );
            $this->assertGreaterThan(
                0,
                $summary['offline']['total_learning_hours'],
                'RecordAllCombinedService must reflect offline learning hours.'
            );

            // Classification summary in combined service
            $classificationSummary = $service->getClassificationSummary();
            $this->assertTrue($classificationSummary->isNotEmpty());

            // Non-KP OWL count in combined classification summary
            $nonKpTotal = $classificationSummary->sum('non_kp_owl');
            $this->assertGreaterThanOrEqual($totalDemoParticipants, $nonKpTotal);

        } finally {
            // Cleanup pre-existing test dummy
            $preExistingParticipant->forceDelete();
            $preExisting->forceDelete();
        }
    }
}
