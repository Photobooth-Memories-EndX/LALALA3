<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CombinedClassification19ColumnsAuditTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::factory()->create();
        Auth::login($this->user);
    }

    /**
     * Verify that the blade view has exactly 19 headers and 19 JS columns matching the canonical schema.
     */
    public function test_19_canonical_columns_structure_in_blade(): void
    {
        $blade = file_get_contents(resource_path('views/main/record/all/classification.blade.php'));

        // 1. Verify 19 <th> in thead
        preg_match('/<table[^>]*id="all-classification-learning-table"[^>]*>.*?<thead[^>]*>(.*?)<\/thead>/s', $blade, $mThead);
        $this->assertNotEmpty($mThead, 'Table thead must exist');
        preg_match_all('/<th[^>]*>(.*?)<\/th>/s', $mThead[1], $mTh);
        $this->assertCount(19, $mTh[0], 'Table thead must have exactly 19 <th> columns');

        $expectedHeaders = [
            'NO',
            'SOURCE',
            'EMPLOYEE ID',
            'EMPLOYEE NAME',
            'COURSE TITLE',
            'PLAYLIST',
            'TYPE',
            'CATEGORY',
            'MAIN CLASSIFICATION',
            'SUB CLASSIFICATION',
            'DELIVERY METHOD',
            'SUB DELIVERY METHOD',
            'PROVIDER',
            'SUB PROVIDER',
            'SCORE (L2)',
            'SCORE (L3)',
            'CERTIFICATE AVAILABILITY',
            'TOTAL ENGAGEMENT TIME (LH)',
            'ACTION',
        ];

        foreach ($expectedHeaders as $idx => $headerText) {
            $this->assertStringContainsString($headerText, strip_tags($mTh[0][$idx]), "Header index $idx must match $headerText");
        }

        // 2. Verify 19 column objects in JS
        preg_match('/columns:\s*\[(.*?)\]\s*,\s*language:/s', $blade, $mCols);
        $this->assertNotEmpty($mCols, 'DataTable columns definition must exist');
        preg_match_all('/data:\s*[\'"]([^\'"]+)[\'"]/', $mCols[1], $mColsList);
        $this->assertCount(19, $mColsList[1], 'DataTable columns in JS must have exactly 19 column objects');

        $expectedDataKeys = [
            'DT_RowIndex',
            'source_badge',
            'employee_id',
            'employee_name',
            'course_title',
            'playlist',
            'type',
            'category',
            'main_classification',
            'sub_classification',
            'delivery_method',
            'sub_delivery_method',
            'provider',
            'sub_provider',
            'score_l2',
            'score_l3',
            'certificate_availability',
            'total_engagement_time',
            'action',
        ];

        $this->assertEquals($expectedDataKeys, $mColsList[1], 'JS column keys must exactly match canonical 19 columns in order');
    }

    /**
     * Verify that DataTables AJAX returns all 19 columns and no double entity escaping on classification.
     */
    public function test_datatable_ajax_returns_19_columns_and_clean_classification(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/manage/record/all/classification/basic-common-duties/datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertNotEmpty($json['data']);

        $firstRow = $json['data'][0];

        // Ensure no double escaping of &amp;
        $this->assertStringNotContainsString('&amp;amp;', $firstRow['main_classification']);
        $this->assertStringNotContainsString('&amp;amp;', $firstRow['course_title']);

        // Verify required canonical keys exist
        $requiredKeys = [
            'DT_RowIndex',
            'source_badge',
            'employee_id',
            'employee_name',
            'course_title',
            'playlist',
            'type',
            'category',
            'main_classification',
            'sub_classification',
            'delivery_method',
            'sub_delivery_method',
            'provider',
            'sub_provider',
            'score_l2',
            'score_l3',
            'certificate_availability',
            'total_engagement_time',
            'action',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $firstRow, "Row must contain canonical column key: $key");
        }
    }

    /**
     * Verify All Content rows have playlist = '-' and Content Playlist / Playlist rows have proper playlist link.
     */
    public function test_playlist_mapping_logic_for_categories(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/manage/record/all/classification/basic-common-duties/datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 100,
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $row) {
            $rawCategory = strip_tags($row['category']);
            $rawPlaylist = strip_tags($row['playlist']);

            if ($rawCategory === 'All Content') {
                $this->assertEquals('-', $rawPlaylist, "All Content row must have playlist as '-'");
            } elseif ($rawCategory === 'Non KP-OWL') {
                $this->assertEquals('-', $rawPlaylist, "Non KP-OWL row must have playlist as '-'");
                $this->assertEquals('-', strip_tags($row['type']), "Non KP-OWL row must have type as '-'");
            } elseif (in_array($rawCategory, ['Playlist', 'Content Playlist'], true)) {
                $this->assertNotEmpty($rawPlaylist, "Playlist/Content Playlist row must have playlist title");
                $this->assertStringContainsString('bi-collection-play', $row['playlist']);
            }
        }
    }

    /**
     * Verify Excel Export remains untouched and functions with 200 BinaryFileResponse.
     */
    public function test_excel_export_remains_functional_get(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/manage/record/all/classification/basic-common-duties/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
    }
}
