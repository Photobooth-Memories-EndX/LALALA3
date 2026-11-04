<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineAndAllRecordGrandTotalTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /**
     * Test 1: Online Record Classification Summary returns GRAND TOTAL in HTML and JSON.
     */
    public function test_online_classification_summary_displays_correct_grand_total(): void
    {
        // 1. JSON Request
        $jsonResponse = $this->actingAs($this->admin)->getJson(route('manage.record.online.ajax.classification', ['format' => 'json']));
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJson(['success' => true]);

        $data = collect($jsonResponse->json('data'));
        $grandTotal = $jsonResponse->json('grand_total');

        $this->assertNotEmpty($data, 'Online classification summary data should not be empty.');
        $this->assertNotNull($grandTotal, 'Online classification summary must return grand_total object.');

        // Verify column sums match grand total
        $expectedContent = (int) $data->sum('content_total');
        $expectedTotal = (int) $data->sum('total_learning');
        $expectedOngoing = (int) $data->sum('ongoing_learning');
        $expectedFinished = (int) $data->sum('finished_learning');
        $expectedHours = round($data->sum('learning_hours'), 1);

        $this->assertEquals($expectedContent, $grandTotal['content_total'], 'Content total in grand_total must match sum of content_total.');
        $this->assertEquals($expectedTotal, $grandTotal['total_learning'], 'Total learning in grand_total must match sum of total_learning.');
        $this->assertEquals($expectedOngoing, $grandTotal['ongoing_learning'], 'Ongoing learning in grand_total must match sum of ongoing_learning.');
        $this->assertEquals($expectedFinished, $grandTotal['finished_learning'], 'Finished learning in grand_total must match sum of finished_learning.');
        $this->assertEquals($expectedHours, (float) $grandTotal['learning_hours'], 'Learning hours in grand_total must match sum of learning_hours.');

        // 2. HTML Request (Standard AJAX loader response)
        $htmlResponse = $this->actingAs($this->admin)->get(route('manage.record.online.ajax.classification', ['format' => 'html']));
        $htmlResponse->assertStatus(200);
        $htmlResponse->assertSee('online-summary-grand-total');
        $htmlResponse->assertSee('GRAND TOTAL');
        $htmlResponse->assertSee(number_format($expectedContent));
        $htmlResponse->assertSee(number_format($expectedTotal));
        $htmlResponse->assertSee(number_format($expectedFinished));
    }

    /**
     * Test 2: All Record Classification Summary returns GRAND TOTAL in HTML and JSON with combined sources.
     */
    public function test_all_record_classification_summary_displays_correct_grand_total(): void
    {
        // 1. AJAX Request (returns JSON with html rendered)
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.all.ajax.classification'));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = collect($response->json('data'));
        $grandTotal = $response->json('grand_total');
        $html = $response->json('html');

        $this->assertNotEmpty($data, 'All Record classification summary data should not be empty.');
        $this->assertNotNull($grandTotal, 'All Record classification summary must return grand_total object.');
        $this->assertNotNull($html, 'All Record classification summary must return rendered html.');

        // Verify column sums match grand total
        $expectedKp = (int) $data->sum('kp_owl');
        $expectedNonKp = (int) $data->sum('non_kp_owl');
        $expectedTotal = (int) $data->sum('total_learning');
        $expectedOngoing = (int) $data->sum('ongoing');
        $expectedFinished = (int) $data->sum('finished');
        $expectedHours = round($data->sum('learning_hours'), 2);

        $this->assertEquals($expectedKp, $grandTotal['kp_owl'], 'KP OWL total in grand_total must match sum.');
        $this->assertEquals($expectedNonKp, $grandTotal['non_kp_owl'], 'Non KP OWL total in grand_total must match sum.');
        $this->assertEquals($expectedTotal, $grandTotal['total_learning'], 'Total Learning in grand_total must match sum.');
        $this->assertEquals($expectedOngoing, $grandTotal['ongoing'], 'Ongoing in grand_total must match sum.');
        $this->assertEquals($expectedFinished, $grandTotal['finished'], 'Finished in grand_total must match sum.');
        $this->assertEquals($expectedHours, (float) $grandTotal['learning_hours'], 'Learning hours in grand_total must match sum.');

        // Verify HTML markup has grand total row and correct values
        $this->assertStringContainsString('all-summary-grand-total', $html);
        $this->assertStringContainsString('GRAND TOTAL', $html);
        $this->assertStringContainsString(number_format($expectedKp), $html);
        $this->assertStringContainsString(number_format($expectedNonKp), $html);
        $this->assertStringContainsString(number_format($expectedTotal), $html);
        $this->assertStringContainsString(number_format($expectedFinished), $html);
    }

    /**
     * Test 3: Verify Offline Record Classification Summary continues to function properly.
     */
    public function test_offline_record_classification_summary_remains_intact(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.offline.ajax.classification'));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = collect($response->json('data'));
        $grandTotal = $response->json('grand_total');

        $this->assertNotEmpty($data);
        $this->assertNotNull($grandTotal);

        $this->assertEquals((int) $data->sum('programs'), $grandTotal['programs']);
        $this->assertEquals((int) $data->sum('total_participants'), $grandTotal['total_participants']);
        $this->assertEquals((int) $data->sum('ongoing'), $grandTotal['ongoing']);
        $this->assertEquals((int) $data->sum('finished'), $grandTotal['finished']);
    }

    /**
     * Test 4: Landing pages load successfully and render containers.
     */
    public function test_all_three_landing_pages_render_classification_summary_containers(): void
    {
        // 1. Online Record landing page
        $onlineRes = $this->actingAs($this->admin)->get(route('manage.record.online'));
        $onlineRes->assertStatus(200);
        $onlineRes->assertSee('classification-summary-container');

        // 2. All Record landing page
        $allRes = $this->actingAs($this->admin)->get(route('manage.record.all'));
        $allRes->assertStatus(200);
        $allRes->assertSee('all-classification-container');

        // 3. Offline Record landing page
        $offlineRes = $this->actingAs($this->admin)->get(route('manage.record.offline'));
        $offlineRes->assertStatus(200);
        $offlineRes->assertSee('offline-classification-summary-container');
    }
}
