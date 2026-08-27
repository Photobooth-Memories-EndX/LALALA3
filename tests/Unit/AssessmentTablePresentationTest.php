<?php

namespace Tests\Unit;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AssessmentTablePresentationTest extends TestCase
{
    public function test_table_uses_final_user_score_and_shows_passing_grade_only_below_failed_status(): void
    {
        $user = (new User())->forceFill(['id' => 7]);
        $this->actingAs($user);

        $passed = $this->assessment('Passed Assessment', 80, [
            $this->result(90, true),
        ]);
        $failed = $this->assessment('Failed Assessment', 80, [
            $this->result(70, false),
        ]);
        $pending = $this->assessment('Pending Assessment', 75, [
            $this->result(65, false, false),
        ]);
        $withoutResult = $this->assessment('No Result Assessment', 85);

        $html = Blade::render(
            '<x-learning.assessment.assessment-table :assessments="$assessments" />',
            ['assessments' => collect([$passed, $failed, $pending, $withoutResult])]
        );

        $this->assertSame(1, substr_count($html, 'SCORE'));
        $this->assertStringNotContainsString('>Passing Grade</', $html);
        $this->assertStringContainsString('data-assessment-score="90"', $html);
        $this->assertStringContainsString('data-assessment-score="70"', $html);
        $this->assertSame(1, substr_count($html, 'data-assessment-score=""'));
        $this->assertSame(1, substr_count($html, '>Pending</span>'));
        $this->assertStringContainsString('Passing Grade : 80', $html);
        $this->assertStringContainsString('Passing Grade : 75', $html);
        $this->assertStringContainsString('Passing Grade : 85', $html);
    }

    private function assessment(string $title, int $passingGrade, array $results = []): Assesment
    {
        $assessment = (new Assesment())->forceFill([
            'title' => $title,
            'type' => 'content',
            'timer' => 'n',
            'passing_grade' => $passingGrade,
        ]);
        $assessment->setRelation('scores', new Collection($results));
        $assessment->setRelation('contents', new Collection());
        $assessment->setRelation('trainingplan', null);

        return $assessment;
    }

    private function result(int $score, bool $passed, bool $final = true): AssesmentResult
    {
        return (new AssesmentResult())->forceFill([
            'user_id' => 7,
            'final' => $final ? 1 : 0,
            'pass' => $passed ? 1 : 0,
            'score' => $score,
            'batch' => 1,
        ]);
    }
}
