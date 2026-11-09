<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordAssessmentScoreDisplayTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Test User Assessment Score',
            'id_emp'     => 'SCORE_' . rand(1000, 9999),
            'email'      => 'test_score_' . uniqid() . '@example.com',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-score-' . Str::uuid(),
            'sap_code'   => 'SAPSCORE_' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);
    }

    /**
     * Test direct blade rendering of learning-record-all table cell across all score conditions.
     */
    public function test_learning_record_all_assessment_score_display_rules(): void
    {
        // 1. Score = 90
        $view90 = Blade::render(
            '<td class="text-center">
                <div class="d-flex flex-column align-items-center justify-content-center text-center">
                    @php
                        $allScoreVal = $row[\'score\'] ?? $row[\'assessment_score\'] ?? null;
                        $hasScore = $allScoreVal !== null && $allScoreVal !== \'\' && is_numeric($allScoreVal);
                    @endphp
                    @if ($hasScore)
                        @php
                            $allMinScore = (float) ($row[\'min_score\'] ?? 70);
                            $allPassed = (float) $allScoreVal >= $allMinScore;
                        @endphp
                        @if ($allPassed)
                            <span class="badge rounded-pill learning-record-badge learning-score-pill" style="background-color: #dcfce7; color: #15803d; font-weight: 600;">Passed</span>
                            <div class="small fw-semibold mt-1" style="color: #15803d;">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                        @else
                            <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
                            <div class="small text-danger mt-1">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                            <div class="small text-danger">Passing Grade : {{ (int) $allMinScore }}</div>
                        @endif
                    @else
                        <span class="learning-score-empty-pill">-</span>
                    @endif
                </div>
            </td>',
            ['row' => ['score' => 90, 'min_score' => 70]]
        );
        $this->assertStringContainsString('Score : 90', $view90);
        $this->assertStringContainsString('Passed', $view90);
        $this->assertStringNotContainsString('â€“', $view90);
        $this->assertStringNotContainsString('â€”', $view90);
        $this->assertStringNotContainsString('learning-score-empty-pill', $view90);

        // 2. Score = 85.5
        $view85_5 = Blade::render(
            '<td class="text-center">
                <div class="d-flex flex-column align-items-center justify-content-center text-center">
                    @php
                        $allScoreVal = $row[\'score\'] ?? $row[\'assessment_score\'] ?? null;
                        $hasScore = $allScoreVal !== null && $allScoreVal !== \'\' && is_numeric($allScoreVal);
                    @endphp
                    @if ($hasScore)
                        @php
                            $allMinScore = (float) ($row[\'min_score\'] ?? 70);
                            $allPassed = (float) $allScoreVal >= $allMinScore;
                        @endphp
                        @if ($allPassed)
                            <span class="badge rounded-pill learning-record-badge learning-score-pill" style="background-color: #dcfce7; color: #15803d; font-weight: 600;">Passed</span>
                            <div class="small fw-semibold mt-1" style="color: #15803d;">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                        @else
                            <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
                            <div class="small text-danger mt-1">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                            <div class="small text-danger">Passing Grade : {{ (int) $allMinScore }}</div>
                        @endif
                    @else
                        <span class="learning-score-empty-pill">-</span>
                    @endif
                </div>
            </td>',
            ['row' => ['score' => 85.5, 'min_score' => 70]]
        );
        $this->assertStringContainsString('Score : 85.5', $view85_5);
        $this->assertStringContainsString('Passed', $view85_5);
        $this->assertStringNotContainsString('â€“', $view85_5);
        $this->assertStringNotContainsString('â€”', $view85_5);
        $this->assertStringNotContainsString('learning-score-empty-pill', $view85_5);

        // 3. Score = 0 (MUST NOT BECOME '-')
        $view0 = Blade::render(
            '<td class="text-center">
                <div class="d-flex flex-column align-items-center justify-content-center text-center">
                    @php
                        $allScoreVal = $row[\'score\'] ?? $row[\'assessment_score\'] ?? null;
                        $hasScore = $allScoreVal !== null && $allScoreVal !== \'\' && is_numeric($allScoreVal);
                    @endphp
                    @if ($hasScore)
                        @php
                            $allMinScore = (float) ($row[\'min_score\'] ?? 70);
                            $allPassed = (float) $allScoreVal >= $allMinScore;
                        @endphp
                        @if ($allPassed)
                            <span class="badge rounded-pill learning-record-badge learning-score-pill" style="background-color: #dcfce7; color: #15803d; font-weight: 600;">Passed</span>
                            <div class="small fw-semibold mt-1" style="color: #15803d;">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                        @else
                            <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
                            <div class="small text-danger mt-1">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                            <div class="small text-danger">Passing Grade : {{ (int) $allMinScore }}</div>
                        @endif
                    @else
                        <span class="learning-score-empty-pill">-</span>
                    @endif
                </div>
            </td>',
            ['row' => ['score' => 0, 'min_score' => 70]]
        );
        $this->assertStringContainsString('Score : 0', $view0);
        $this->assertStringNotContainsString('Score : -', $view0);
        $this->assertStringNotContainsString('learning-score-empty-pill', $view0);
        $this->assertStringNotContainsString('â€“', $view0);
        $this->assertStringNotContainsString('â€”', $view0);

        // 4. Score = NULL (MUST BE '-')
        $viewNull = Blade::render(
            '<td class="text-center">
                <div class="d-flex flex-column align-items-center justify-content-center text-center">
                    @php
                        $allScoreVal = $row[\'score\'] ?? $row[\'assessment_score\'] ?? null;
                        $hasScore = $allScoreVal !== null && $allScoreVal !== \'\' && is_numeric($allScoreVal);
                    @endphp
                    @if ($hasScore)
                        @php
                            $allMinScore = (float) ($row[\'min_score\'] ?? 70);
                            $allPassed = (float) $allScoreVal >= $allMinScore;
                        @endphp
                        @if ($allPassed)
                            <span class="badge rounded-pill learning-record-badge learning-score-pill" style="background-color: #dcfce7; color: #15803d; font-weight: 600;">Passed</span>
                            <div class="small fw-semibold mt-1" style="color: #15803d;">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                        @else
                            <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
                            <div class="small text-danger mt-1">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                            <div class="small text-danger">Passing Grade : {{ (int) $allMinScore }}</div>
                        @endif
                    @else
                        <span class="learning-score-empty-pill">-</span>
                    @endif
                </div>
            </td>',
            ['row' => ['score' => null, 'min_score' => 70]]
        );
        $this->assertStringContainsString('<span class="learning-score-empty-pill">-</span>', $viewNull);
        $this->assertStringNotContainsString('â€“', $viewNull);
        $this->assertStringNotContainsString('â€”', $viewNull);
        $this->assertStringNotContainsString('Score :', $viewNull);

        // 5. Score = empty string '' (MUST BE '-')
        $viewEmpty = Blade::render(
            '<td class="text-center">
                <div class="d-flex flex-column align-items-center justify-content-center text-center">
                    @php
                        $allScoreVal = $row[\'score\'] ?? $row[\'assessment_score\'] ?? null;
                        $hasScore = $allScoreVal !== null && $allScoreVal !== \'\' && is_numeric($allScoreVal);
                    @endphp
                    @if ($hasScore)
                        @php
                            $allMinScore = (float) ($row[\'min_score\'] ?? 70);
                            $allPassed = (float) $allScoreVal >= $allMinScore;
                        @endphp
                        @if ($allPassed)
                            <span class="badge rounded-pill learning-record-badge learning-score-pill" style="background-color: #dcfce7; color: #15803d; font-weight: 600;">Passed</span>
                            <div class="small fw-semibold mt-1" style="color: #15803d;">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                        @else
                            <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
                            <div class="small text-danger mt-1">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                            <div class="small text-danger">Passing Grade : {{ (int) $allMinScore }}</div>
                        @endif
                    @else
                        <span class="learning-score-empty-pill">-</span>
                    @endif
                </div>
            </td>',
            ['row' => ['score' => '', 'min_score' => 70]]
        );
        $this->assertStringContainsString('<span class="learning-score-empty-pill">-</span>', $viewEmpty);
        $this->assertStringNotContainsString('â€“', $viewEmpty);
        $this->assertStringNotContainsString('â€”', $viewEmpty);
        $this->assertStringNotContainsString('FAILED', $viewEmpty);

        // 6. Record without assessment (score not provided)
        $viewNoAssessment = Blade::render(
            '<td class="text-center">
                <div class="d-flex flex-column align-items-center justify-content-center text-center">
                    @php
                        $allScoreVal = $row[\'score\'] ?? $row[\'assessment_score\'] ?? null;
                        $hasScore = $allScoreVal !== null && $allScoreVal !== \'\' && is_numeric($allScoreVal);
                    @endphp
                    @if ($hasScore)
                        @php
                            $allMinScore = (float) ($row[\'min_score\'] ?? 70);
                            $allPassed = (float) $allScoreVal >= $allMinScore;
                        @endphp
                        @if ($allPassed)
                            <span class="badge rounded-pill learning-record-badge learning-score-pill" style="background-color: #dcfce7; color: #15803d; font-weight: 600;">Passed</span>
                            <div class="small fw-semibold mt-1" style="color: #15803d;">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                        @else
                            <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
                            <div class="small text-danger mt-1">Score : {{ $allScoreVal !== null && $allScoreVal !== \'\' ? $allScoreVal : \'-\' }}</div>
                            <div class="small text-danger">Passing Grade : {{ (int) $allMinScore }}</div>
                        @endif
                    @else
                        <span class="learning-score-empty-pill">-</span>
                    @endif
                </div>
            </td>',
            ['row' => []]
        );
        $this->assertStringContainsString('<span class="learning-score-empty-pill">-</span>', $viewNoAssessment);
        $this->assertStringNotContainsString('â€“', $viewNoAssessment);
        $this->assertStringNotContainsString('â€”', $viewNoAssessment);
    }

    /**
     * Test full HTTP request to /my-learning/partial/record?record_mode=all
     * verifies rendered HTML has clean '-' and NO corrupted encoding 'â€“' or 'â€”'.
     */
    public function test_my_learning_record_all_http_renders_clean_empty_score(): void
    {
        // Create an offline record without assessment score (score = NULL)
        $training = OfflineTraining::create([
            'title'               => 'Training Without Assessment Score',
            'type'                => 'Offline',
            'category'            => 'General',
            'classification'      => 'Soft Skills',
            'sub_classification'  => 'Communication',
            'provider'            => 'Internal',
            'provider_name'       => 'KP-OWL',
            'venue'               => 'Hall A',
            'learning_hours'      => 4.0,
            'passing_grade'       => 70,
            'start_date'          => '2026-03-01',
            'end_date'            => '2026-03-02',
            'status'              => 'published',
            'certificate_enabled' => false,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->user->id,
            'id_emp'              => $this->user->id_emp,
            'employee_name'       => $this->user->name,
            'attendance_status'   => 'Yes',
            'progress'            => 100,
            'assessment_score'    => null, // Empty score!
        ]);

        // Request /my-learning/partial/record?record_mode=all
        $response = $this->actingAs($this->user)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $html = $response->getContent();

        // Must contain the training
        $this->assertStringContainsString('Training Without Assessment Score', $html);

        // Assessment score cell must render clean '-' inside learning-score-empty-pill
        $this->assertStringContainsString('<span class="learning-score-empty-pill">-</span>', $html);

        // Absolutely NO corrupted characters â€“ or â€” in the response!
        $this->assertStringNotContainsString('â€“', $html);
        $this->assertStringNotContainsString('â€”', $html);
    }

    /**
     * Test HTTP request with score = 0 remains displayed as 0 and NOT '-'.
     */
    public function test_my_learning_record_all_http_renders_zero_score_as_zero(): void
    {
        $training = OfflineTraining::create([
            'title'               => 'Training With Zero Assessment Score',
            'type'                => 'Offline',
            'category'            => 'General',
            'classification'      => 'Compliance',
            'sub_classification'  => 'Ethics',
            'provider'            => 'Internal',
            'provider_name'       => 'KP-OWL',
            'venue'               => 'Room 101',
            'learning_hours'      => 2.0,
            'passing_grade'       => 70,
            'start_date'          => '2026-03-10',
            'end_date'            => '2026-03-10',
            'status'              => 'published',
            'certificate_enabled' => false,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->user->id,
            'id_emp'              => $this->user->id_emp,
            'employee_name'       => $this->user->name,
            'attendance_status'   => 'Yes',
            'progress'            => 100,
            'assessment_score'    => 0, // Zero score!
        ]);

        $response = $this->actingAs($this->user)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('Training With Zero Assessment Score', $html);
        // Zero score must be shown as Score : 0, NOT Score : -
        $this->assertStringContainsString('Score : 0', $html);
        $this->assertStringNotContainsString('Score : -', $html);
        $this->assertStringNotContainsString('â€“', $html);
        $this->assertStringNotContainsString('â€”', $html);
    }

    /**
     * Test HTTP request with score = 85.5 is rendered accurately.
     */
    public function test_my_learning_record_all_http_renders_decimal_score(): void
    {
        $training = OfflineTraining::create([
            'title'               => 'Training With Decimal Assessment Score',
            'type'                => 'Offline',
            'category'            => 'Technical',
            'classification'      => 'IT',
            'sub_classification'  => 'Development',
            'provider'            => 'Internal',
            'provider_name'       => 'KP-OWL',
            'venue'               => 'Lab 1',
            'learning_hours'      => 3.0,
            'passing_grade'       => 70,
            'start_date'          => '2026-03-15',
            'end_date'            => '2026-03-15',
            'status'              => 'published',
            'certificate_enabled' => false,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->user->id,
            'id_emp'              => $this->user->id_emp,
            'employee_name'       => $this->user->name,
            'attendance_status'   => 'Yes',
            'progress'            => 100,
            'assessment_score'    => 85.5,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('Training With Decimal Assessment Score', $html);
        $this->assertStringContainsString('Score : 85.5', $html);
        $this->assertStringContainsString('Passed', $html);
        $this->assertStringNotContainsString('â€“', $html);
        $this->assertStringNotContainsString('â€”', $html);
    }
}
