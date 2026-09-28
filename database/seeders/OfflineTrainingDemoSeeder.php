<?php

namespace Database\Seeders;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OfflineTrainingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds for Non KP OWL (Offline Training) demo records.
     *
     * IMPORTANT ARCHITECTURAL PRINCIPLE:
     * Non KP OWL records are stored in `offline_trainings` and `offline_training_users`.
     * All KP OWL automatically combines Online (content_users / playlist_content_users)
     * and Offline (offline_trainings / offline_training_users) via RecordAllCombinedService.
     * Therefore, NO separate All KP OWL table or direct All KP OWL seeder is required.
     */
    public function run(): void
    {
        // 1. Environment Safety Guard
        if (! app()->environment(['local', 'development', 'testing'])) {
            throw new RuntimeException('OfflineTrainingDemoSeeder can only be executed in local, development, or testing environments.');
        }

        $this->command?->info('Starting OfflineTrainingDemoSeeder...');

        // 2. Fetch existing users to preserve authentic employee relationships
        $existingUsers = User::query()
            ->whereNotNull('id_emp')
            ->where('id_emp', '!=', '')
            ->orderBy('id', 'asc')
            ->take(50)
            ->get();

        if ($existingUsers->isEmpty()) {
            $existingUsers = User::query()
                ->orderBy('id', 'asc')
                ->take(50)
                ->get();
        }

        $totalUsers = $existingUsers->count();
        if ($totalUsers === 0) {
            $this->command?->warn('No users found in database. Please ensure users are present before seeding participants.');
            return;
        }

        $adminUser = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
            ->first() ?? $existingUsers->first();
        $createdByUserId = $adminUser?->id;

        // 3. Define the 20 Demo Offline Trainings
        $trainingsDefinitions = $this->getDemoTrainingsData();

        $trainingCount = 0;
        $participantCount = 0;

        // Realistic employee job position and grade fallback banks
        $demoGrades = ['3A', '3B', '4A', '4B', 'Junior Staff', 'Staff', 'Senior Staff', 'Junior Engineer', 'Senior Engineer'];
        $demoPositions = [
            'Electrical Maintenance Engineer',
            'Mechanical Maintenance Technician',
            'Operations Process Engineer',
            'Safety & Environmental Officer',
            'Quality Control Inspector',
            'Automation & Instrumentation Engineer',
            'Blast Furnace Shift Leader',
            'Rolling Mill Technician',
            'Continuous Casting Specialist',
            'HR Development Specialist',
            'Logistics & Warehouse Planner',
        ];

        // 4. Iterate over definitions using an idempotent updateOrCreate strategy by unique demo batch
        foreach ($trainingsDefinitions as $tIndex => $def) {
            $batchCode = $def['batch'];

            $training = OfflineTraining::updateOrCreate(
                ['batch' => $batchCode],
                [
                    'course_name'           => $def['course_name'],
                    'sub_course_name'       => $def['sub_course_name'],
                    'type'                  => $def['type'],
                    'category'              => $def['category'],
                    'level'                 => $def['level'],
                    'main_classification'   => $def['main_classification'],
                    'sub_classification'    => $def['sub_classification'],
                    'counted_corporate_kpi' => $def['counted_corporate_kpi'],
                    'sub_corporate_kpi'     => $def['sub_corporate_kpi'],
                    'delivery_method'       => $def['delivery_method'],
                    'sub_delivery_method'   => $def['sub_delivery_method'],
                    'provider'              => $def['provider'],
                    'sub_provider'          => $def['sub_provider'],
                    'provider_name'         => $def['provider_name'],
                    'venue'                 => $def['venue'],
                    'instructor'            => $def['instructor'],
                    'duration_seconds'      => $def['duration_seconds'],
                    'learning_hours'        => $def['learning_hours'],
                    'start_date'            => $def['start_date'],
                    'finish_date'           => $def['finish_date'],
                    'created_by'            => $createdByUserId,
                ]
            );

            $trainingCount++;

            // Synchronize participants idempotently
            $participantsSpecs = $def['participants'];
            $numParticipants = count($participantsSpecs);

            // Select distinct users for this specific training to prevent duplicates within the same training
            $assignedUsers = [];
            for ($pIdx = 0; $pIdx < $numParticipants; $pIdx++) {
                $userIndex = (($tIndex * 3) + $pIdx) % $totalUsers;
                $assignedUsers[] = $existingUsers->get($userIndex);
            }

            foreach ($participantsSpecs as $pIdx => $spec) {
                /** @var User $assignedUser */
                $assignedUser = $assignedUsers[$pIdx];
                $employeeId = !empty($assignedUser->id_emp)
                    ? (string) $assignedUser->id_emp
                    : sprintf('EMP%05d', $assignedUser->id);

                $participantHours = match (true) {
                    $spec['progress'] >= 100 => (float) $def['learning_hours'],
                    $spec['progress'] > 0    => round((float) $def['learning_hours'] * ($spec['progress'] / 100), 2),
                    default                  => 0.0,
                };

                OfflineTrainingUser::updateOrCreate(
                    [
                        'offline_training_id' => $training->id,
                        'employee_id'         => $employeeId,
                    ],
                    [
                        'user_id'           => $assignedUser->id,
                        'name'              => $assignedUser->name,
                        'grade'             => $demoGrades[($tIndex + $pIdx) % count($demoGrades)],
                        'job_position'      => $demoPositions[($tIndex + $pIdx) % count($demoPositions)],
                        'team'              => $assignedUser->team ?? 'General Operations',
                        'department'        => $assignedUser->department ?? 'Operations Department',
                        'company'           => $assignedUser->company_name ?? 'PT Krakatau Posco',
                        'learning_hours'    => $participantHours,
                        'progress'          => $spec['progress'],
                        'assessment_score'  => $spec['assessment_score'],
                        'certificate'       => $spec['certificate'],
                        'attendance'        => $spec['attendance'],
                        'attendance_reason' => $spec['attendance_reason'],
                        'bond_status'       => $spec['bond_status'],
                        'bond_end_date'     => $spec['bond_end_date'],
                        'bond_amount'       => $spec['bond_amount'],
                        'l1_program'        => $spec['l1_program'],
                        'l2_numerical'      => $spec['l2_numerical'],
                        'l2_verbal'         => $spec['l2_verbal'],
                        'l3_numerical'      => $spec['l3_numerical'],
                        'l3_verbal'         => $spec['l3_verbal'],
                        'pic'               => 'HRDC Training Team',
                    ]
                );

                $participantCount++;
            }
        }

        $this->command?->info("OfflineTrainingDemoSeeder completed successfully!");
        $this->command?->info("- Seeded/Synchronized Trainings: {$trainingCount}");
        $this->command?->info("- Seeded/Synchronized Participants: {$participantCount}");
    }

    /**
     * Definition dataset of 20 realistic offline training programs and participants.
     */
    protected function getDemoTrainingsData(): array
    {
        return [
            // 1. Leadership
            [
                'batch'                 => 'DEMO-OFFLINE-001',
                'course_name'           => 'Team Leadership & Operational Excellence',
                'sub_course_name'       => 'Supervisory Leadership Module',
                'type'                  => 'In-House',
                'category'              => 'Leadership Competency',
                'level'                 => 'Intermediate',
                'main_classification'   => 'Leadership',
                'sub_classification'    => 'People Management',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Operational Excellence 2026',
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Interactive Workshop',
                'provider'              => 'Internal',
                'sub_provider'          => 'HRDC',
                'provider_name'         => 'PT Krakatau Posco HRDC',
                'venue'                 => 'Main Training Center Room 201',
                'instructor'            => 'Dr. Hendra Gunawan, S.T., M.M.',
                'duration_seconds'      => 28800, // 8 hours
                'learning_hours'        => 8.00,
                'start_date'            => '2026-02-10',
                'finish_date'           => '2026-02-11',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 88.50, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Very Good',  'l2_numerical' => 88.50, 'l2_verbal' => 'Very Good',  'l3_numerical' => null,  'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 92.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Excellent',  'l2_numerical' => 92.00, 'l2_verbal' => 'Excellent',  'l3_numerical' => null,  'l3_verbal' => null],
                    ['progress' => 75.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true,  'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,         'l2_numerical' => null,  'l2_verbal' => null,         'l3_numerical' => null,  'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => true,  'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,         'l2_numerical' => null,  'l2_verbal' => null,         'l3_numerical' => null,  'l3_verbal' => null],
                ],
            ],

            // 2. Leadership
            [
                'batch'                 => 'DEMO-OFFLINE-002',
                'course_name'           => 'Executive Leadership Development Program',
                'sub_course_name'       => 'Strategic Vision & Change Management',
                'type'                  => 'Public',
                'category'              => 'Leadership Competency',
                'level'                 => 'Advanced',
                'main_classification'   => 'Leadership',
                'sub_classification'    => 'Strategic Leadership',
                'counted_corporate_kpi' => false,
                'sub_corporate_kpi'     => null,
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Executive Seminar',
                'provider'              => 'External',
                'sub_provider'          => 'Vendor',
                'provider_name'         => 'PPM Manajemen',
                'venue'                 => 'Hotel Aryaduta Jakarta',
                'instructor'            => 'Prof. Roy Sembel, Ph.D.',
                'duration_seconds'      => 57600, // 16 hours
                'learning_hours'        => 16.00,
                'start_date'            => '2026-05-18',
                'finish_date'           => '2026-05-20',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 94.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-05-20', 'bond_amount' => 15000000.00, 'l1_program' => 'Excellent', 'l2_numerical' => 94.00, 'l2_verbal' => 'Excellent', 'l3_numerical' => 88.00, 'l3_verbal' => 'Project completed on schedule'],
                    ['progress' => 100.0, 'assessment_score' => 87.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-05-20', 'bond_amount' => 15000000.00, 'l1_program' => 'Very Good',  'l2_numerical' => 87.00, 'l2_verbal' => 'Very Good',  'l3_numerical' => null,  'l3_verbal' => null],
                    ['progress' => 50.0,  'assessment_score' => 65.00, 'certificate' => 'No',  'attendance' => true,  'attendance_reason' => null, 'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,        'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null,  'l3_verbal' => null],
                ],
            ],

            // 3. Leadership
            [
                'batch'                 => 'DEMO-OFFLINE-003',
                'course_name'           => 'First Line Supervisory Skill',
                'sub_course_name'       => 'Coaching & Mentoring in Steel Mill',
                'type'                  => 'In-House',
                'category'              => 'Leadership Competency',
                'level'                 => 'Basic',
                'main_classification'   => 'Leadership',
                'sub_classification'    => 'Supervisory',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Frontline Empowerment',
                'delivery_method'       => 'Hybrid',
                'sub_delivery_method'   => 'Blended Learning',
                'provider'              => 'Internal',
                'sub_provider'          => 'Internal Trainer',
                'provider_name'         => 'PT Krakatau Posco HRDC',
                'venue'                 => 'Auditorium Hall A',
                'instructor'            => 'Suryadi, S.Psi., M.M.',
                'duration_seconds'      => 14400, // 4 hours
                'learning_hours'        => 4.00,
                'start_date'            => '2025-11-12',
                'finish_date'           => '2025-11-12',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 84.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,   'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 84.00, 'l2_verbal' => 'Satisfactory', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 80.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,   'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 80.00, 'l2_verbal' => 'Satisfactory', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 80.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true,  'attendance_reason' => null,   'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,   'l2_numerical' => null,  'l2_verbal' => null,           'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => false, 'attendance_reason' => 'Sick', 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,   'l2_numerical' => null,  'l2_verbal' => null,           'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 4. Basic & Common Duties
            [
                'batch'                 => 'DEMO-OFFLINE-004',
                'course_name'           => 'Basic Safety & 5S Mindset',
                'sub_course_name'       => 'Plant Housekeeping & Safety Habit',
                'type'                  => 'In-House',
                'category'              => 'Core Competency',
                'level'                 => 'Basic',
                'main_classification'   => 'Basic & Common Duties',
                'sub_classification'    => '5S & Workplace Safety',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Zero Accident Culture',
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Practical Workshop',
                'provider'              => 'Internal',
                'sub_provider'          => 'SHE Team',
                'provider_name'         => 'SHE Department PTKP',
                'venue'                 => 'Safety Training Center Room B',
                'instructor'            => 'Ir. Agus Wijaya',
                'duration_seconds'      => 14400, // 4 hours
                'learning_hours'        => 4.00,
                'start_date'            => '2026-01-20',
                'finish_date'           => '2026-01-20',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 95.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Excellent', 'l2_numerical' => 95.00, 'l2_verbal' => 'Excellent', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 90.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Very Good',  'l2_numerical' => 90.00, 'l2_verbal' => 'Very Good',  'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 50.0,  'assessment_score' => 70.00, 'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,         'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 5. Basic & Common Duties
            [
                'batch'                 => 'DEMO-OFFLINE-005',
                'course_name'           => 'Korean Business Etiquette & Cross-Culture',
                'sub_course_name'       => 'Global Communication in Joint Venture',
                'type'                  => 'In-House',
                'category'              => 'Core Competency',
                'level'                 => 'Intermediate',
                'main_classification'   => 'Basic & Common Duties',
                'sub_classification'    => 'Cross-Cultural Competency',
                'counted_corporate_kpi' => false,
                'sub_corporate_kpi'     => null,
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Lecture & Roleplay',
                'provider'              => 'External',
                'sub_provider'          => 'Language Consultant',
                'provider_name'         => 'Korean Cultural Center',
                'venue'                 => 'Multi Purpose Room Level 3',
                'instructor'            => 'Park Ji-Hoon, M.A.',
                'duration_seconds'      => 28800, // 8 hours
                'learning_hours'        => 8.00,
                'start_date'            => '2026-03-12',
                'finish_date'           => '2026-03-13',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 91.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,            'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Very Good', 'l2_numerical' => 91.00, 'l2_verbal' => 'Very Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 86.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,            'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good',      'l2_numerical' => 86.00, 'l2_verbal' => 'Good',      'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 89.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,            'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Very Good', 'l2_numerical' => 89.00, 'l2_verbal' => 'Very Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 25.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true,  'attendance_reason' => null,            'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => false, 'attendance_reason' => 'Business Trip', 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 6. Basic & Common Duties
            [
                'batch'                 => 'DEMO-OFFLINE-006',
                'course_name'           => 'Corporate Governance & Anti-Corruption Awareness',
                'sub_course_name'       => 'Compliance & Code of Conduct 2025',
                'type'                  => 'In-House',
                'category'              => 'Core Competency',
                'level'                 => 'Basic',
                'main_classification'   => 'Basic & Common Duties',
                'sub_classification'    => 'Compliance',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Good Corporate Governance',
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Presentation & Discussion',
                'provider'              => 'Internal',
                'sub_provider'          => 'Internal Audit & Legal',
                'provider_name'         => 'Legal & Compliance Team',
                'venue'                 => 'Main Auditorium',
                'instructor'            => 'Farhan Maulana, S.H., LL.M.',
                'duration_seconds'      => 7200, // 2 hours
                'learning_hours'        => 2.00,
                'start_date'            => '2025-09-18',
                'finish_date'           => '2025-09-18',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 88.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 88.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 82.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 82.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 7. Job Functional
            [
                'batch'                 => 'DEMO-OFFLINE-007',
                'course_name'           => 'Blast Furnace Operation & Ironmaking Fundamental',
                'sub_course_name'       => 'Thermal Equilibrium & Slag Control',
                'type'                  => 'In-House',
                'category'              => 'Technical Competency',
                'level'                 => 'Advanced',
                'main_classification'   => 'Job Functional',
                'sub_classification'    => 'Ironmaking',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Process Efficiency Improvement',
                'delivery_method'       => 'Practical',
                'sub_delivery_method'   => 'On-the-job & Simulator',
                'provider'              => 'Internal',
                'sub_provider'          => 'Ironmaking Department',
                'provider_name'         => 'Ironmaking Division Experts',
                'venue'                 => 'Blast Furnace Control Center',
                'instructor'            => 'Ir. Dwi Prasetyo, M.T.',
                'duration_seconds'      => 43200, // 12 hours
                'learning_hours'        => 12.00,
                'start_date'            => '2026-04-06',
                'finish_date'           => '2026-04-08',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 93.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Excellent', 'l2_numerical' => 93.00, 'l2_verbal' => 'Excellent', 'l3_numerical' => 88.00, 'l3_verbal' => 'Implemented successfully in plant'],
                    ['progress' => 75.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null,  'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null,  'l3_verbal' => null],
                ],
            ],

            // 8. Job Functional
            [
                'batch'                 => 'DEMO-OFFLINE-008',
                'course_name'           => 'Continuous Casting & Ladle Metallurgy Process',
                'sub_course_name'       => 'Quality Defect Prevention in Slab Casting',
                'type'                  => 'Certification',
                'category'              => 'Technical Competency',
                'level'                 => 'Intermediate',
                'main_classification'   => 'Job Functional',
                'sub_classification'    => 'Steelmaking',
                'counted_corporate_kpi' => false,
                'sub_corporate_kpi'     => null,
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Technical Workshop',
                'provider'              => 'External',
                'sub_provider'          => 'Vendor',
                'provider_name'         => 'Primetals Technologies Academy',
                'venue'                 => 'Training Room 102',
                'instructor'            => 'Dr. Michael Huber',
                'duration_seconds'      => 28800, // 8 hours
                'learning_hours'        => 8.00,
                'start_date'            => '2026-06-22',
                'finish_date'           => '2026-06-23',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 90.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-06-23', 'bond_amount' => 7500000.00, 'l1_program' => 'Very Good', 'l2_numerical' => 90.00, 'l2_verbal' => 'Very Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 85.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-06-23', 'bond_amount' => 7500000.00, 'l1_program' => 'Good',      'l2_numerical' => 85.00, 'l2_verbal' => 'Good',      'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 50.0,  'assessment_score' => 60.00, 'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,       'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 25.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,       'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 9. Job Functional
            [
                'batch'                 => 'DEMO-OFFLINE-009',
                'course_name'           => 'Supply Chain & Inventory Management in Steel Industry',
                'sub_course_name'       => 'Raw Material Yard Optimization',
                'type'                  => 'In-House',
                'category'              => 'Job Functional',
                'level'                 => 'Intermediate',
                'main_classification'   => 'Job Functional',
                'sub_classification'    => 'Procurement & Logistics',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Cost Reduction Program',
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Case Study',
                'provider'              => 'Internal',
                'sub_provider'          => 'Supply Chain Team',
                'provider_name'         => 'Logistics Division PTKP',
                'venue'                 => 'Logistics Conference Hall',
                'instructor'            => 'Bambang Irawan, S.T., M.Sc.',
                'duration_seconds'      => 14400, // 4 hours
                'learning_hours'        => 4.00,
                'start_date'            => '2025-12-04',
                'finish_date'           => '2025-12-04',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 82.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,              'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 82.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 78.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,              'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 78.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => false, 'attendance_reason' => 'Emergency Leave', 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,   'l2_numerical' => null,  'l2_verbal' => null,   'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 10. Maintenance
            [
                'batch'                 => 'DEMO-OFFLINE-010',
                'course_name'           => 'Hydraulic & Pneumatic Systems Maintenance',
                'sub_course_name'       => 'Electro-Hydraulic Servo Valve Troubleshooting',
                'type'                  => 'Certification',
                'category'              => 'Maintenance Competency',
                'level'                 => 'Advanced',
                'main_classification'   => 'Maintenance',
                'sub_classification'    => 'Mechanical Maintenance',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Equipment Reliability 2026',
                'delivery_method'       => 'Practical',
                'sub_delivery_method'   => 'Lab Hands-on',
                'provider'              => 'External',
                'sub_provider'          => 'Vendor',
                'provider_name'         => 'Bosch Rexroth Training Center',
                'venue'                 => 'Bosch Rexroth Academy Cikarang',
                'instructor'            => 'Ir. Joko Susanto, M.T.',
                'duration_seconds'      => 57600, // 16 hours
                'learning_hours'        => 16.00,
                'start_date'            => '2026-07-13',
                'finish_date'           => '2026-07-15',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 96.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-07-15', 'bond_amount' => 10000000.00, 'l1_program' => 'Excellent', 'l2_numerical' => 96.00, 'l2_verbal' => 'Excellent', 'l3_numerical' => 90.00, 'l3_verbal' => 'Project completed on schedule'],
                    ['progress' => 100.0, 'assessment_score' => 91.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-07-15', 'bond_amount' => 10000000.00, 'l1_program' => 'Very Good',  'l2_numerical' => 91.00, 'l2_verbal' => 'Very Good',  'l3_numerical' => null,  'l3_verbal' => null],
                    ['progress' => 75.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,        'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,         'l3_numerical' => null,  'l3_verbal' => null],
                    ['progress' => 50.0,  'assessment_score' => 68.00, 'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,        'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,         'l3_numerical' => null,  'l3_verbal' => null],
                ],
            ],

            // 11. Maintenance
            [
                'batch'                 => 'DEMO-OFFLINE-011',
                'course_name'           => 'Vibration Analysis & Condition Based Maintenance',
                'sub_course_name'       => 'Bearing Diagnostics & Rotating Equipment',
                'type'                  => 'In-House',
                'category'              => 'Maintenance Competency',
                'level'                 => 'Intermediate',
                'main_classification'   => 'Maintenance',
                'sub_classification'    => 'Predictive Maintenance',
                'counted_corporate_kpi' => false,
                'sub_corporate_kpi'     => null,
                'delivery_method'       => 'Hybrid',
                'sub_delivery_method'   => 'Field Demonstration',
                'provider'              => 'Internal',
                'sub_provider'          => 'CBM Specialist',
                'provider_name'         => 'Central Maintenance Department',
                'venue'                 => 'Maintenance Workshop Room 2',
                'instructor'            => 'Wawan Hermawan, S.T.',
                'duration_seconds'      => 28800, // 8 hours
                'learning_hours'        => 8.00,
                'start_date'            => '2026-08-18',
                'finish_date'           => '2026-08-19',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 89.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Very Good', 'l2_numerical' => 89.00, 'l2_verbal' => 'Very Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 84.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good',      'l2_numerical' => 84.00, 'l2_verbal' => 'Good',      'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 12. Maintenance
            [
                'batch'                 => 'DEMO-OFFLINE-012',
                'course_name'           => 'High Voltage Switchgear Maintenance & Safety',
                'sub_course_name'       => 'Circuit Breaker Testing & SF6 Gas Handling',
                'type'                  => 'In-House',
                'category'              => 'Maintenance Competency',
                'level'                 => 'Advanced',
                'main_classification'   => 'Maintenance',
                'sub_classification'    => 'Electrical Maintenance',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Electrical Safety Excellence',
                'delivery_method'       => 'Practical',
                'sub_delivery_method'   => 'Substation Hands-on',
                'provider'              => 'External',
                'sub_provider'          => 'Vendor',
                'provider_name'         => 'PT Schneider Electric Indonesia',
                'venue'                 => 'Primary Substation Training Area',
                'instructor'            => 'Ir. Ahmad Zulkifli',
                'duration_seconds'      => 28800, // 8 hours
                'learning_hours'        => 8.00,
                'start_date'            => '2026-09-08',
                'finish_date'           => '2026-09-09',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 92.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,           'bond_status' => 'Yes', 'bond_end_date' => '2027-09-09', 'bond_amount' => 5000000.00, 'l1_program' => 'Excellent', 'l2_numerical' => 92.00, 'l2_verbal' => 'Excellent', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 87.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,           'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,       'l1_program' => 'Very Good',  'l2_numerical' => 87.00, 'l2_verbal' => 'Very Good',  'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 81.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,           'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,       'l1_program' => 'Good',       'l2_numerical' => 81.00, 'l2_verbal' => 'Good',       'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 90.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true,  'attendance_reason' => null,           'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,       'l1_program' => null,         'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => false, 'attendance_reason' => 'Annual Leave', 'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,       'l1_program' => null,         'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 13. Innovation
            [
                'batch'                 => 'DEMO-OFFLINE-013',
                'course_name'           => 'Six Sigma Green Belt Certification Workshop',
                'sub_course_name'       => 'DMAIC Methodology & Statistical Process Control',
                'type'                  => 'Certification',
                'category'              => 'Innovation Competency',
                'level'                 => 'Intermediate',
                'main_classification'   => 'Innovation',
                'sub_classification'    => 'Quality & Six Sigma',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Continuous Improvement 2026',
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Workshop & Project Mentoring',
                'provider'              => 'External',
                'sub_provider'          => 'Consultant',
                'provider_name'         => 'SSCX International',
                'venue'                 => 'Innovation Hub Room 301',
                'instructor'            => 'Master Black Belt Rudi Hartono',
                'duration_seconds'      => 57600, // 16 hours
                'learning_hours'        => 16.00,
                'start_date'            => '2026-08-25',
                'finish_date'           => '2026-08-27',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 95.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-08-27', 'bond_amount' => 12000000.00, 'l1_program' => 'Excellent', 'l2_numerical' => 95.00, 'l2_verbal' => 'Excellent', 'l3_numerical' => 92.00, 'l3_verbal' => 'Implemented successfully in plant'],
                    ['progress' => 100.0, 'assessment_score' => 89.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-08-27', 'bond_amount' => 12000000.00, 'l1_program' => 'Very Good',  'l2_numerical' => 89.00, 'l2_verbal' => 'Very Good',  'l3_numerical' => null,  'l3_verbal' => null],
                ],
            ],

            // 14. Innovation
            [
                'batch'                 => 'DEMO-OFFLINE-014',
                'course_name'           => 'Design Thinking & Agile Problem Solving',
                'sub_course_name'       => 'Rapid Prototyping for Operational Pain Points',
                'type'                  => 'Workshop',
                'category'              => 'Innovation Competency',
                'level'                 => 'Basic',
                'main_classification'   => 'Innovation',
                'sub_classification'    => 'Design Thinking',
                'counted_corporate_kpi' => false,
                'sub_corporate_kpi'     => null,
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Interactive Simulation',
                'provider'              => 'Internal',
                'sub_provider'          => 'Innovation Office',
                'provider_name'         => 'Innovation & Improvement Division',
                'venue'                 => 'Creative Lab Level 2',
                'instructor'            => 'Anisa Wardani, M.Ds.',
                'duration_seconds'      => 14400, // 4 hours
                'learning_hours'        => 4.00,
                'start_date'            => '2026-10-14',
                'finish_date'           => '2026-10-14',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 88.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 88.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 75.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,   'l2_numerical' => null,  'l2_verbal' => null,   'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,   'l2_numerical' => null,  'l2_verbal' => null,   'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 15. SHE
            [
                'batch'                 => 'DEMO-OFFLINE-015',
                'course_name'           => 'Confined Space Entry & Gas Monitoring Safety',
                'sub_course_name'       => 'Permit to Work & Atmospheric Testing',
                'type'                  => 'In-House',
                'category'              => 'Safety & Health',
                'level'                 => 'Intermediate',
                'main_classification'   => 'SHE',
                'sub_classification'    => 'Industrial Safety',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Zero Accident Culture',
                'delivery_method'       => 'Practical',
                'sub_delivery_method'   => 'Mock Simulator & Rescue Drill',
                'provider'              => 'Internal',
                'sub_provider'          => 'Safety Training Unit',
                'provider_name'         => 'SHE Department PTKP',
                'venue'                 => 'Fire & Rescue Simulation Ground',
                'instructor'            => 'Kapten (Purn) Sudirman & SHE Team',
                'duration_seconds'      => 28800, // 8 hours
                'learning_hours'        => 8.00,
                'start_date'            => '2026-03-24',
                'finish_date'           => '2026-03-25',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 93.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Very Good', 'l2_numerical' => 93.00, 'l2_verbal' => 'Very Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 87.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good',      'l2_numerical' => 87.00, 'l2_verbal' => 'Good',      'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 82.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good',      'l2_numerical' => 82.00, 'l2_verbal' => 'Good',      'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 50.0,  'assessment_score' => 65.00, 'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,        'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 16. SHE
            [
                'batch'                 => 'DEMO-OFFLINE-016',
                'course_name'           => 'Industrial Toxicology & Hazardous Waste (B3) Management',
                'sub_course_name'       => 'Spill Containment & Manifest Compliance',
                'type'                  => 'In-House',
                'category'              => 'Environmental Safety',
                'level'                 => 'Intermediate',
                'main_classification'   => 'SHE',
                'sub_classification'    => 'Environmental Management',
                'counted_corporate_kpi' => false,
                'sub_corporate_kpi'     => null,
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Technical Lecture',
                'provider'              => 'External',
                'sub_provider'          => 'Certified Center',
                'provider_name'         => 'Balai Hiperkes & Keselamatan Kerja',
                'venue'                 => 'Training Center Room 105',
                'instructor'            => 'Dr. dr. Triyono, Sp.Ok.',
                'duration_seconds'      => 14400, // 4 hours
                'learning_hours'        => 4.00,
                'start_date'            => '2025-06-15',
                'finish_date'           => '2025-06-15',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 85.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,            'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 85.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 79.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,            'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 79.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => false, 'attendance_reason' => 'Business Trip', 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,   'l2_numerical' => null,  'l2_verbal' => null,   'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 17. ISO Standard
            [
                'batch'                 => 'DEMO-OFFLINE-017',
                'course_name'           => 'ISO 9001:2015 Quality Management Lead Auditor',
                'sub_course_name'       => 'Clause Auditing & Corrective Action Analysis',
                'type'                  => 'Certification',
                'category'              => 'Quality Standard',
                'level'                 => 'Advanced',
                'main_classification'   => 'ISO Standard',
                'sub_classification'    => 'Quality Assurance',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Global Quality Compliance',
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Intensive Workshop',
                'provider'              => 'External',
                'sub_provider'          => 'Vendor',
                'provider_name'         => 'TUV Rheinland Indonesia',
                'venue'                 => 'Executive Boardroom & Audit Rooms',
                'instructor'            => 'Ir. Hendri Kusnadi, Lead Auditor',
                'duration_seconds'      => 86400, // 24 hours
                'learning_hours'        => 24.00,
                'start_date'            => '2026-10-19',
                'finish_date'           => '2026-10-23',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 91.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2028-10-23', 'bond_amount' => 15000000.00, 'l1_program' => 'Excellent', 'l2_numerical' => 91.00, 'l2_verbal' => 'Excellent', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 86.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2028-10-23', 'bond_amount' => 15000000.00, 'l1_program' => 'Very Good',  'l2_numerical' => 86.00, 'l2_verbal' => 'Very Good',  'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 75.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,        'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,         'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 25.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,        'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,         'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 18. ISO Standard
            [
                'batch'                 => 'DEMO-OFFLINE-018',
                'course_name'           => 'ISO 45001:2018 Occupational Health & Safety Awareness',
                'sub_course_name'       => 'Risk Assessment & Hazard Identification (IBPR)',
                'type'                  => 'In-House',
                'category'              => 'Safety Standard',
                'level'                 => 'Basic',
                'main_classification'   => 'ISO Standard',
                'sub_classification'    => 'Safety Management',
                'counted_corporate_kpi' => false,
                'sub_corporate_kpi'     => null,
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Seminar',
                'provider'              => 'Internal',
                'sub_provider'          => 'Internal Auditor',
                'provider_name'         => 'Quality & Safety Division',
                'venue'                 => 'Hall B Multipurpose',
                'instructor'            => 'Sri Wahyuni, S.T., M.T.',
                'duration_seconds'      => 14400, // 4 hours
                'learning_hours'        => 4.00,
                'start_date'            => '2026-11-10',
                'finish_date'           => '2026-11-10',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 88.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 88.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 100.0, 'assessment_score' => 83.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 83.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,   'l2_numerical' => null,  'l2_verbal' => null,   'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],

            // 19. Technology
            [
                'batch'                 => 'DEMO-OFFLINE-019',
                'course_name'           => 'PLC & SCADA Automation for Hot Strip Mill',
                'sub_course_name'       => 'Siemens TIA Portal & Industrial Ethernet',
                'type'                  => 'Public',
                'category'              => 'Technology Competency',
                'level'                 => 'Advanced',
                'main_classification'   => 'Technology',
                'sub_classification'    => 'Plant Automation',
                'counted_corporate_kpi' => true,
                'sub_corporate_kpi'     => 'Smart Mill 4.0',
                'delivery_method'       => 'Practical',
                'sub_delivery_method'   => 'Automation Lab',
                'provider'              => 'External',
                'sub_provider'          => 'Vendor',
                'provider_name'         => 'Siemens Indonesia Training Institute',
                'venue'                 => 'Siemens Technical Center Pulo Gadung',
                'instructor'            => 'Eng. Christian Pratama, M.Eng.',
                'duration_seconds'      => 43200, // 12 hours
                'learning_hours'        => 12.00,
                'start_date'            => '2026-11-24',
                'finish_date'           => '2026-11-26',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 94.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-11-26', 'bond_amount' => 10000000.00, 'l1_program' => 'Excellent', 'l2_numerical' => 94.00, 'l2_verbal' => 'Excellent', 'l3_numerical' => 88.00, 'l3_verbal' => 'Project completed on schedule'],
                    ['progress' => 100.0, 'assessment_score' => 88.00, 'certificate' => 'Yes', 'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'Yes', 'bond_end_date' => '2027-11-26', 'bond_amount' => 10000000.00, 'l1_program' => 'Very Good',  'l2_numerical' => 88.00, 'l2_verbal' => 'Very Good',  'l3_numerical' => null,  'l3_verbal' => null],
                    ['progress' => 80.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true, 'attendance_reason' => null, 'bond_status' => 'No',  'bond_end_date' => null,         'bond_amount' => null,        'l1_program' => null,        'l2_numerical' => null,  'l2_verbal' => null,         'l3_numerical' => null,  'l3_verbal' => null],
                ],
            ],

            // 20. Unclassified (null main_classification)
            [
                'batch'                 => 'DEMO-OFFLINE-020',
                'course_name'           => 'Cross-Functional Project Coordination & Reporting',
                'sub_course_name'       => 'Inter-Department Communication Protocol',
                'type'                  => 'Workshop',
                'category'              => 'General Competency',
                'level'                 => 'Basic',
                'main_classification'   => null, // Intentionally null for testing Unclassified grouping
                'sub_classification'    => 'General Skills',
                'counted_corporate_kpi' => false,
                'sub_corporate_kpi'     => null,
                'delivery_method'       => 'Classroom',
                'sub_delivery_method'   => 'Workshop',
                'provider'              => 'Internal',
                'sub_provider'          => 'Internal HR',
                'provider_name'         => 'General Affairs Division',
                'venue'                 => 'Meeting Room Krakatau',
                'instructor'            => 'Dewi Lestari, S.Sos.',
                'duration_seconds'      => 7200, // 2 hours
                'learning_hours'        => 2.00,
                'start_date'            => '2026-12-08',
                'finish_date'           => '2026-12-08',
                'participants'          => [
                    ['progress' => 100.0, 'assessment_score' => 86.00, 'certificate' => 'Yes', 'attendance' => true,  'attendance_reason' => null,   'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => 'Good', 'l2_numerical' => 86.00, 'l2_verbal' => 'Good', 'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 50.0,  'assessment_score' => null,  'certificate' => 'No',  'attendance' => true,  'attendance_reason' => null,   'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,   'l2_numerical' => null,  'l2_verbal' => null,   'l3_numerical' => null, 'l3_verbal' => null],
                    ['progress' => 0.0,   'assessment_score' => null,  'certificate' => 'No',  'attendance' => false, 'attendance_reason' => 'Sick', 'bond_status' => 'No', 'bond_end_date' => null, 'bond_amount' => null, 'l1_program' => null,   'l2_numerical' => null,  'l2_verbal' => null,   'l3_numerical' => null, 'l3_verbal' => null],
                ],
            ],
        ];
    }
}
