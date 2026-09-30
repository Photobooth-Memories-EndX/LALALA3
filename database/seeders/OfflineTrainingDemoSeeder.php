<?php

namespace Database\Seeders;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class OfflineTrainingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Wipe existing dummy data safely
        Schema::disableForeignKeyConstraints();
        OfflineTrainingUser::truncate();
        OfflineTraining::truncate();
        Schema::enableForeignKeyConstraints();

        $adminUser = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
            ->first() ?? User::first();
        $createdByUserId = $adminUser?->id;

        // 2. Define the 4 clean, realistic PTKP training records matching UI structure
        $trainings = [
            // 1. Leadership Development Program
            [
                'title'               => 'Leadership Development Program',
                'category'            => 'Soft Skill',
                'training_code'       => 'PTKP-LDP-01',
                'instructor'          => 'Dr. Hendra Wijaya, M.M.',
                'location'            => 'Training Room Building A, 2nd Fl.',
                'target_employee'     => 'Staff to Supervisor, All Departments',
                'prerequisites'       => 'Minimum 1 year tenure at PT Krakatau Posco',
                'quota'               => 30,
                'status'              => 'Open Registration',
                'start_date'          => '2026-09-18',
                'end_date'            => '2026-09-18',
                'start_time'          => '08:00:00',
                'end_time'            => '16:00:00',
                'description'         => 'Comprehensive executive leadership development empowering managers and supervisors to drive high-performance cultures, visionary strategic alignment, and positive workplace synergy.',
                'learning_objectives' => "• Enhance leadership capabilities and visionary direction\n• Improve team decision making and operational execution\n• Cultivate ethical governance standards across departments",
                'syllabus'            => [
                    [
                        'topic'       => 'Executive Leadership Core & Visionary Direction',
                        'description' => 'Understanding modern visionary leadership, leading organizational transformation, aligning departmental goals with company strategies, and cultivating ethical governance standards.',
                    ],
                    [
                        'topic'       => 'Strategic Team Communication & Cross-Functional Influence',
                        'description' => 'Effective executive communication, cross-functional team alignment, managing high-stakes negotiations, empathy in workplace leadership, and collaborative conflict resolution.',
                    ],
                    [
                        'topic'       => 'Decision Making Frameworks & Risk Mitigation',
                        'description' => 'Data-informed strategic decision making, risk assessment models in manufacturing plant operations, problem root cause analysis, and agility under volatile operating conditions.',
                    ],
                    [
                        'topic'       => 'Action Plan & Real-world Project Implementation',
                        'description' => 'Developing a 90-day actionable department roadmap, setting milestone KPIs, fostering peer mentorship frameworks, and presenting capstone management initiatives.',
                    ],
                ],
                'enrolled_count'      => 25,
            ],

            // 2. Excel Advanced for Data Analysis
            [
                'title'               => 'Excel Advanced for Data Analysis',
                'category'            => 'Technical Skill',
                'training_code'       => 'PTKP-EXC-02',
                'instructor'          => 'Bambang Sujarwo, S.Kom.',
                'location'            => 'Computer Lab 2, 3rd Fl.',
                'target_employee'     => 'Specialist, Engineer & Data Analyst',
                'prerequisites'       => 'Basic Microsoft Excel proficiency',
                'quota'               => 40,
                'status'              => 'Running',
                'start_date'          => '2026-09-18',
                'end_date'            => '2026-09-18',
                'start_time'          => '09:00:00',
                'end_time'            => '15:00:00',
                'description'         => 'In-depth masterclass focusing on high-level formulas, advanced XLOOKUP, dynamic PivotTables, Power Query data transformation, and automated reporting dashboards for operational analysts.',
                'learning_objectives' => "• Master complex nested array formulas and lookup functions\n• Automate ETL data pipelines with Power Query\n• Design interactive executive reporting dashboards",
                'syllabus'            => [
                    [
                        'topic'       => 'Advanced Formulas & Dynamic Array Functions',
                        'description' => 'Mastering XLOOKUP, INDEX-MATCH-MATCH, FILTER, UNIQUE, SORT, and complex nested conditional mathematical statements.',
                    ],
                    [
                        'topic'       => 'Power Query & Automated Data ETL',
                        'description' => 'Connecting disparate data sources, unpivoting tables, cleansing dirty data, merging relational datasets, and automating recurring monthly extracts.',
                    ],
                    [
                        'topic'       => 'Data Modeling & Power Pivot Analytics',
                        'description' => 'Building robust star schema data models, defining relationships between fact and dimension tables, and writing fundamental DAX measures.',
                    ],
                    [
                        'topic'       => 'Interactive Executive Reporting Dashboards',
                        'description' => 'Designing KPI cards, advanced multi-slicer charts, conditional formatting heatmaps, and automated drill-down reports for departmental presentations.',
                    ],
                ],
                'enrolled_count'      => 18,
            ],

            // 3. K3 & Safety Awareness in Factory
            [
                'title'               => 'K3 & Safety Awareness in Factory',
                'category'            => 'Compliance & Safety',
                'training_code'       => 'PTKP-SAF-03',
                'instructor'          => 'Ir. Agus Priyono, CSP',
                'location'            => 'Auditorium Krakatau Posco',
                'target_employee'     => 'All Plant Operations & Maintenance Personnel',
                'prerequisites'       => 'Valid PTKP ID badge and completed basic safety induction',
                'quota'               => 50,
                'status'              => 'Open Registration',
                'start_date'          => '2026-09-25',
                'end_date'            => '2026-09-25',
                'start_time'          => '08:00:00',
                'end_time'            => '11:30:00',
                'description'         => 'Vital occupational safety protocols, hazard identification risk assessment (HIRA), compliance with Indonesian safety laws, personal protective equipment (PPE) standards, and zero incident culture in steel plant environments.',
                'learning_objectives' => "• Identify plant floor safety hazards proactively\n• Understand HIRADC principles and incident escalation\n• Comply with Indonesian labor safety legislation and corporate guidelines",
                'syllabus'            => [
                    [
                        'topic'       => 'Industrial Safety Standards & Legal Compliance',
                        'description' => 'Overview of Occupational Health and Safety (K3) regulations, Krakatau Posco plant safety guidelines, and employee legal obligations.',
                    ],
                    [
                        'topic'       => 'Hazard Identification, Risk Assessment & Determining Control (HIRADC)',
                        'description' => 'Methodologies for proactive hazard spotting on the plant floor, risk severity matrices, and implementing hierarchy of controls.',
                    ],
                    [
                        'topic'       => 'PPE Proper Usage & Machine Guarding Compliance',
                        'description' => 'Standard inspection of safety helmets, protective eye gear, flame-retardant uniforms, and safety interlocking systems on heavy machinery.',
                    ],
                    [
                        'topic'       => 'Emergency Response, Near-Miss Reporting & Evacuation Drills',
                        'description' => 'Near-miss reporting procedures, immediate emergency escalation protocols, fire extinguisher readiness, and assembly point muster drills.',
                    ],
                ],
                'enrolled_count'      => 40,
            ],

            // 4. Effective Business Communication
            [
                'title'               => 'Effective Business Communication',
                'category'            => 'Soft Skill',
                'training_code'       => 'PTKP-COMM-04',
                'instructor'          => 'Citra Lestari, M.Psi.',
                'location'            => 'Meeting Room Bintang 1',
                'target_employee'     => 'Staff, Officers, and Section Heads',
                'prerequisites'       => 'None',
                'quota'               => 25,
                'status'              => 'Closed',
                'start_date'          => '2026-09-29',
                'end_date'            => '2026-09-29',
                'start_time'          => '13:00:00',
                'end_time'            => '17:00:00',
                'description'         => 'Master persuasive business presentations, interpersonal verbal communication, formal corporate email writing, cross-cultural workplace coordination, and constructive feedback delivery.',
                'learning_objectives' => "• Structure clear, concise, and persuasive business communications\n• Deliver high-impact executive presentations with confidence\n• Apply constructive feedback frameworks using the SBI model",
                'syllabus'            => [
                    [
                        'topic'       => 'Foundations of High-Impact Business Communication',
                        'description' => 'Communication models, active listening skills, decoding body language, and tailoring messages according to stakeholder seniority.',
                    ],
                    [
                        'topic'       => 'Professional Written Communication & Memo Formulation',
                        'description' => 'Drafting concise executive summaries, formal emails, operational incident briefs, and avoiding ambiguity in cross-departmental correspondence.',
                    ],
                    [
                        'topic'       => 'Persuasive Presentation & Public Speaking Techniques',
                        'description' => 'Structuring high-stakes pitch decks, vocal projection, managing audience Q&A smoothly, and handling challenging objections with poise.',
                    ],
                    [
                        'topic'       => 'Conflict Resolution & Constructive Feedback Mechanisms',
                        'description' => 'The SBI (Situation-Behavior-Impact) feedback model, de-escalating tense disagreements, and building mutual trust across teams.',
                    ],
                ],
                'enrolled_count'      => 25,
            ],
        ];

        $users = User::all();
        $userCount = $users->count();

        foreach ($trainings as $tIdx => $tData) {
            $enrolledCount = $tData['enrolled_count'];
            unset($tData['enrolled_count']);

            $tData['created_by'] = $createdByUserId;
            $tData['course_name'] = $tData['title'];
            $tData['venue'] = $tData['location'];
            $tData['finish_date'] = $tData['end_date'];
            $tData['batch'] = $tData['training_code'];
            $tData['duration_seconds'] = 28800;
            $tData['learning_hours'] = 8.0;

            $record = OfflineTraining::create($tData);

            // Seed realistic participant records
            for ($p = 1; $p <= $enrolledCount; $p++) {
                $user = $userCount > 0 ? $users[($tIdx * 10 + $p) % $userCount] : null;
                $empId = $user?->id_emp ?: sprintf('KP%05d', 1000 + ($tIdx * 50) + $p);
                $empName = $user?->name ?: "PTKP Participant {$tIdx}-{$p}";

                OfflineTrainingUser::create([
                    'offline_training_id' => $record->id,
                    'user_id'             => $user?->id,
                    'employee_id'         => $empId,
                    'name'                => $empName,
                    'grade'               => 'Staff',
                    'job_position'        => 'Operations Specialist',
                    'department'          => 'Plant Operations',
                    'company'             => 'PT Krakatau Posco',
                    'attendance'          => true,
                    'progress'            => 100,
                ]);
            }
        }
    }
}
