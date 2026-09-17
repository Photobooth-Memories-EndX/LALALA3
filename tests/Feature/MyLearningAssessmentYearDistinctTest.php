<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MyLearningAssessmentYearDistinctTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Assesment $a2026_1;
    protected Assesment $a2026_2;
    protected Assesment $a2026_3;
    protected Assesment $a2025_1;
    protected Assesment $a2025_2;
    protected Assesment $a2027_1;
    protected Assesment $a2027_2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Assessment Year Learner',
            'team'       => 'QA Year Team',
            'department' => 'Operations',
            'division'   => 'Plant',
            'ssohash'    => 'ay-' . Str::uuid(),
            'sap_code'   => 'SAP-AY-' . rand(1000, 9999),
            'id_emp'     => 'EMP-AY-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $createAssessment = function ($title, $year, $month = '05') {
            $a = Assesment::create([
                'title'                 => $title . '_' . Str::random(5),
                'type'                  => 'global',
                'assasment_content_for' => 'content',
                'status'                => 'OK',
                'fromdate'              => "{$year}-{$month}-01 09:00:00",
                'todate'                => "{$year}-{$month}-28 17:00:00",
                'passing_grade'         => 75,
            ]);

            AssesmentAssigned::create([
                'assesment_id'         => $a->id,
                'assigned_id'          => $this->user->id_emp,
                'assigned_type'        => 'user',
                'assigned_description' => 'Test',
            ]);

            \App\Models\AssesmentResult::create([
                'user_id'      => $this->user->id,
                'assesment_id' => $a->id,
                'score'        => 80,
                'pass'         => 1,
                'final'        => 1,
                'batch'        => 1,
                'user_answer'  => [],
            ]);

            return $a;
        };

        // 3 assessments in 2026
        $this->a2026_1 = $createAssessment('Assessment 2026 A', 2026, '01');
        $this->a2026_2 = $createAssessment('Assessment 2026 B', 2026, '06');
        $this->a2026_3 = $createAssessment('Assessment 2026 C', 2026, '12');

        // 2 assessments in 2025
        $this->a2025_1 = $createAssessment('Assessment 2025 A', 2025, '03');
        $this->a2025_2 = $createAssessment('Assessment 2025 B', 2025, '08');

        // 2 assessments in 2027
        $this->a2027_1 = $createAssessment('Assessment 2027 A', 2027, '02');
        $this->a2027_2 = $createAssessment('Assessment 2027 B', 2027, '11');
    }

    /**
     * Test that year options are strictly unique, sorted descending, and without duplicates in HTML.
     */
    public function test_quick_filter_year_options_are_strictly_unique_and_descending(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));

        $response->assertOk();
        $html = $response->getContent();

        // 1. Check controller view data
        $filterOptions = $response->viewData('filterOptions');
        $this->assertNotNull($filterOptions);
        $this->assertArrayHasKey('years', $filterOptions);

        $years = collect($filterOptions['years']);
        // Verify unique
        $this->assertEquals($years->unique()->values()->all(), $years->all(), 'Years collection in filterOptions must be unique');

        // Verify descending order
        $this->assertEquals(
            $years->sortDesc()->values()->all(),
            $years->all(),
            'Years collection must be sorted descending'
        );

        // 2. Extract Year dropdown items from HTML
        preg_match('/<div[^>]*assessment-filter-dropdown-year[^>]*>.*?<\/div>\s*<\/div>/s', $html, $dropdownMatch);
        $this->assertNotEmpty($dropdownMatch, 'Year dropdown container must be found in HTML');

        $dropdownHtml = $dropdownMatch[0];

        // Parse all dropdown-item data-value in the year dropdown
        preg_match_all('/class="[^"]*assessment-quick-item[^"]*"[^>]*data-value="([^"]+)"/i', $dropdownHtml, $itemMatches);
        $values = $itemMatches[1];

        // First item must be 'all'
        $this->assertEquals('all', $values[0]);

        $yearValues = array_slice($values, 1);

        // Verify there are no duplicate years in the dropdown HTML
        $this->assertEquals(
            array_unique($yearValues),
            $yearValues,
            'Dropdown items for Year must not contain duplicates: ' . json_encode($yearValues)
        );

        // Verify exact presence of 2027, 2026, 2025
        $this->assertContains('2027', $yearValues);
        $this->assertContains('2026', $yearValues);
        $this->assertContains('2025', $yearValues);

        // Verify count of 2027, 2026, 2025 is exactly 1 each
        $counts = array_count_values($yearValues);
        $this->assertEquals(1, $counts['2027']);
        $this->assertEquals(1, $counts['2026']);
        $this->assertEquals(1, $counts['2025']);

        // Verify descending order in dropdown HTML
        $this->assertTrue(array_search('2027', $yearValues) < array_search('2026', $yearValues));
        $this->assertTrue(array_search('2026', $yearValues) < array_search('2025', $yearValues));
    }

    /**
     * Test filtering by Year works as expected.
     */
    public function test_filtering_by_unique_year_returns_correct_assessments(): void
    {
        // 1. Filter Year = 2026
        $resp2026 = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'year' => '2026']));

        $resp2026->assertOk();
        $resp2026->assertSee($this->a2026_1->title);
        $resp2026->assertSee($this->a2026_2->title);
        $resp2026->assertSee($this->a2026_3->title);
        $resp2026->assertDontSee($this->a2025_1->title);
        $resp2026->assertDontSee($this->a2025_2->title);
        $resp2026->assertDontSee($this->a2027_1->title);
        $resp2026->assertDontSee($this->a2027_2->title);

        // 2. Filter Year = 2025
        $resp2025 = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'year' => '2025']));

        $resp2025->assertOk();
        $resp2025->assertSee($this->a2025_1->title);
        $resp2025->assertSee($this->a2025_2->title);
        $resp2025->assertDontSee($this->a2026_1->title);
        $resp2025->assertDontSee($this->a2027_1->title);

        // 3. Filter Year = 2027
        $resp2027 = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'year' => '2027']));

        $resp2027->assertOk();
        $resp2027->assertSee($this->a2027_1->title);
        $resp2027->assertSee($this->a2027_2->title);
        $resp2027->assertDontSee($this->a2026_1->title);
        $resp2027->assertDontSee($this->a2025_1->title);

        // 4. Filter Year = all
        $respAll = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'year' => 'all']));

        $respAll->assertOk();
        $respAll->assertSee($this->a2026_1->title);
        $respAll->assertSee($this->a2025_1->title);
        $respAll->assertSee($this->a2027_1->title);
    }
}
