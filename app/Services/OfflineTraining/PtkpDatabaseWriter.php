<?php

namespace App\Services\OfflineTraining;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Services\Record\OfflineOptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PtkpDatabaseWriter
{
    /**
     * Persist grouped training events and participant records inside a transaction.
     *
     * @param array<string, array{training_data: array, participants: array}> $groupedByTraining
     * @param array<string, OfflineTraining> $existingTrainings
     * @param int $userId
     * @return array{imported: int, trainings_created: int, trainings_reused: int, participant_map: array}
     */
    public function persistTrainingsAndParticipants(
        array $groupedByTraining,
        array &$existingTrainings,
        int $userId
    ): array {
        $trainingsCreated = 0;
        $trainingsReused = 0;
        $importedCount = 0;
        $participantMap = [];

        DB::transaction(function () use (
            $groupedByTraining,
            &$existingTrainings,
            $userId,
            &$trainingsCreated,
            &$trainingsReused,
            &$importedCount,
            &$participantMap
        ) {
            $now = now();

            foreach ($groupedByTraining as $fp => $group) {
                $tData = $group['training_data'];
                $participants = $group['participants'];

                if (isset($existingTrainings[$fp])) {
                    $training = $existingTrainings[$fp];
                    $trainingsReused++;
                } else {
                    $training = OfflineTraining::create([
                        'course_name'         => $tData['course_name'],
                        'sub_course_name'     => $tData['sub_course_name'],
                        'type'                => $tData['type'],
                        'category'            => $tData['category'],
                        'level'               => $tData['level'],
                        'batch'               => $tData['batch'],
                        'main_classification' => $tData['main_classification'],
                        'sub_classification'  => $tData['sub_classification'],
                        'delivery_method'     => $tData['delivery_method'],
                        'sub_delivery_method' => $tData['sub_delivery_method'],
                        'provider'            => $tData['provider'],
                        'sub_provider'        => $tData['sub_provider'],
                        'provider_name'       => $tData['provider_name'],
                        'venue'               => $tData['venue'],
                        'instructor'          => $tData['instructor'],
                        'duration_seconds'    => $tData['duration_seconds'],
                        'learning_hours'      => $tData['learning_hours'],
                        'start_date'          => $tData['start_date'],
                        'finish_date'         => $tData['finish_date'],
                        'start_time'          => $tData['start_time'],
                        'end_time'            => $tData['end_time'],
                        'created_by'          => $userId,
                    ]);
                    $existingTrainings[$fp] = $training;
                    $trainingsCreated++;

                    try {
                        app(OfflineOptionService::class)->ensureOptionsFromTraining($tData, 'import_ptkp', $userId);
                    } catch (Throwable $e) {
                        Log::warning('PtkpImport option sync notice: ' . $e->getMessage());
                    }
                }

                foreach ($participants as $p) {
                    $insertData = [
                        'offline_training_id' => $training->id,
                        'user_id'             => $p['user_id'],
                        'employee_id'         => $p['employee_id'],
                        'name'                => $p['name'],
                        'grade'               => $p['grade'],
                        'job_position'        => $p['job_position'],
                        'team'                => $p['team'],
                        'department'          => $p['department'],
                        'company'             => $p['company'],
                        'learning_hours'      => $p['learning_hours'],
                        'progress'            => $p['progress'],
                        'assessment_score'    => $p['assessment_score'],
                        'certificate'         => $p['certificate'],
                        'attendance'          => $p['attendance'],
                        'attendance_reason'   => $p['attendance_reason'],
                        'bond_status'         => $p['bond_status'],
                        'bond_duration'       => $p['bond_duration'],
                        'bond_end_date'       => $p['bond_end_date'],
                        'bond_amount_idr'     => $p['bond_amount_idr'],
                        'l1_program'          => $p['l1_program'],
                        'l2_numerical'        => $p['l2_numerical'],
                        'l2_verbal'           => $p['l2_verbal'],
                        'l3_numerical'        => $p['l3_numerical'],
                        'l3_verbal'           => $p['l3_verbal'],
                        'pic'                 => $p['pic'],
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];

                    $participantId = DB::table('offline_training_users')->insertGetId($insertData);
                    $importedCount++;

                    $rowNum = $p['excel_row'] ?? null;
                    if ($rowNum !== null) {
                        $participantMap[$rowNum] = [
                            'offline_training_id'      => $training->id,
                            'offline_training_user_id' => $participantId,
                        ];
                    }
                }
            }
        });

        return [
            'imported'          => $importedCount,
            'trainings_created' => $trainingsCreated,
            'trainings_reused'  => $trainingsReused,
            'participant_map'   => $participantMap,
        ];
    }
}
