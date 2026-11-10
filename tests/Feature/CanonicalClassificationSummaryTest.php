<?php

namespace Tests\Feature;

use App\Exports\Manage\ClassificationRecordExport;
use App\Http\Controllers\Manage\Record\RecordAllController;
use App\Models\User;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CanonicalClassificationSummaryTest extends TestCase
{
    protected User $admin;
    protected RecordAllCombinedService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->service = app(RecordAllCombinedService::class);
    }

    /**
     * Scenario 1: Master Classification Single Source of Truth
     */
    public function test_scenario_1_master_classification_single_source_of_truth(): void
    {
        $summary = $this->service->getClassificationSummary();
        $this->assertNotEmpty($summary);

        $dbMasters = DB::table('classification')
            ->pluck('classification_name', 'slug')
            ->toArray();

        foreach ($summary as $row) {
            if ($row->slug === 'unclassification') {
                $this->assertSame('Unclassification', $row->classification_name);
                continue;
            }

            if ($row->slug === 'compliance') {
                $this->assertSame('Compliance', $row->classification_name);
                continue;
            }

            // Each master row must have a match in the classification table
            $this->assertArrayHasKey($row->slug, $dbMasters, "Slug {$row->slug} must exist in master table");
            $this->assertSame($dbMasters[$row->slug], $row->classification_name);
        }
    }

    /**
     * Scenario 2: Basic Common Duties Alias Mapping
     */
    public function test_scenario_2_basic_common_duties_alias_mapping(): void
    {
        // Test canonical resolver on variations
        $variations = [
            'Basic & Common Duties',
            'Basic Common Duties',
            'basic & common duties',
            'basic common duties',
            'basic and common duties',
            'basic-common-duties',
        ];

        foreach ($variations as $var) {
            $resolved = RecordAllController::resolveClassification($var, false);
            $this->assertSame('basic-common-duties', $resolved['slug'], "Variation '{$var}' must map to basic-common-duties slug");
            $this->assertSame('Basic & Common Duties', $resolved['name'], "Variation '{$var}' must map to Basic & Common Duties name");
            $this->assertFalse($resolved['is_unclassified']);
        }

        // Summary must contain exactly ONE row for Basic & Common Duties
        $summary = $this->service->getClassificationSummary();
        $basicRows = $summary->filter(function ($row) {
            return stripos($row->classification_name, 'Basic') !== false && stripos($row->classification_name, 'mindset') === false;
        });

        $this->assertCount(1, $basicRows, 'There must be only ONE Basic Common Duties row in summary');
        $basicRow = $basicRows->first();
        $this->assertSame('Basic & Common Duties', $basicRow->classification_name);
        $this->assertSame('basic-common-duties', $basicRow->slug);
        $this->assertSame(186642, (int) $basicRow->kp_owl);
        $this->assertSame(5772, (int) $basicRow->non_kp_owl);
        $this->assertSame(192414, (int) $basicRow->total_learning);
    }

    /**
     * Scenario 3: Unclassification Unified Bucket
     */
    public function test_scenario_3_unclassification_unified_bucket(): void
    {
        // Test canonical resolver on unclassification triggers
        $unclassTriggers = [
            '',
            'Unclassified',
            'unclassified',
            'Unclassification',
            'unclassification',
            'Audit Isolated Classification',
            'audit-isolated-classification',
        ];

        foreach ($unclassTriggers as $trigger) {
            $resolved = RecordAllController::resolveClassification($trigger, false);
            $this->assertSame('unclassification', $resolved['slug'], "Trigger '{$trigger}' must map to unclassification slug");
            $this->assertSame('Unclassification', $resolved['name'], "Trigger '{$trigger}' must map to Unclassification name");
            $this->assertTrue($resolved['is_unclassified']);
        }

        $summary = $this->service->getClassificationSummary();

        // Exactly ONE Unclassification row
        $unclassRows = $summary->filter(fn($r) => $r->slug === 'unclassification');
        $this->assertCount(1, $unclassRows);

        $unclass = $unclassRows->first();
        $this->assertSame('Unclassification', $unclass->classification_name);
        $this->assertSame(0, (int) $unclass->kp_owl);
        $this->assertSame(4, (int) $unclass->non_kp_owl);
        $this->assertSame(4, (int) $unclass->total_learning);
        $this->assertEqualsWithDelta(11.00, (float) $unclass->learning_hours, 0.01);
        $this->assertSame(1, (int) $unclass->ongoing);
        $this->assertSame(2, (int) $unclass->finished);

        // Position: must be the LAST item in summary
        $lastItem = $summary->last();
        $this->assertSame('unclassification', $lastItem->slug, 'Unclassification must strictly be at the bottom');
        $this->assertSame($summary->count(), $lastItem->no, 'Unclassification row number must be the last index');
    }

    /**
     * Scenario 4: No Duplicate IDs, Slugs, or Summary Labels
     */
    public function test_scenario_4_no_duplicate_ids_slugs_or_labels(): void
    {
        $summary = $this->service->getClassificationSummary();

        $slugs = $summary->pluck('slug')->all();
        $this->assertSame(count($slugs), count(array_unique($slugs)), 'All slugs in summary must be unique');

        $names = $summary->pluck('classification_name')->all();
        $this->assertSame(count($names), count(array_unique($names)), 'All classification names must be unique');

        $ids = $summary->pluck('id')->all();
        $this->assertSame(count($ids), count(array_unique($ids)), 'All IDs must be unique');

        // Check sequential numbering
        foreach ($summary as $idx => $row) {
            $this->assertSame($idx + 1, (int) $row->no, "Row index {$idx} must have sequential 'no' = " . ($idx + 1));
        }
    }

    /**
     * Scenario 5: Statistics Integrity
     */
    public function test_scenario_5_statistics_integrity(): void
    {
        $summary = $this->service->getClassificationSummary();

        $kpSum = 0;
        $nonKpSum = 0;
        $totalSum = 0;
        $hoursSum = 0.0;
        $ongoingSum = 0;
        $finishedSum = 0;

        foreach ($summary as $row) {
            $this->assertSame(
                (int) $row->total_learning,
                (int) $row->kp_owl + (int) $row->non_kp_owl,
                "Total learning must equal KP OWL + Non KP OWL for {$row->classification_name}"
            );

            $kpSum += (int) $row->kp_owl;
            $nonKpSum += (int) $row->non_kp_owl;
            $totalSum += (int) $row->total_learning;
            $hoursSum += (float) $row->learning_hours;
            $ongoingSum += (int) $row->ongoing;
            $finishedSum += (int) $row->finished;
        }

        $this->assertSame(410269, $kpSum, 'Sum of KP OWL must match exactly');
        $this->assertSame(6856, $nonKpSum, 'Sum of Non KP OWL must match exactly');
        $this->assertSame(417125, $totalSum, 'Sum of Total Learning must match exactly');
        $this->assertSame(40103, $ongoingSum, 'Sum of Ongoing must match exactly');
        $this->assertSame(370235, $finishedSum, 'Sum of Finished must match exactly');
        $this->assertEqualsWithDelta(108017.68, $hoursSum, 0.05, 'Sum of Learning Hours must match exactly');
    }

    /**
     * Scenario 6: Detail Navigation and Legacy URLs
     */
    public function test_scenario_6_detail_navigation_and_legacy_urls(): void
    {
        // 1. Unclassification detail page
        $resUnclass = $this->actingAs($this->admin)->get('/manage/record/all/classification/unclassification');
        $resUnclass->assertStatus(200);
        $resUnclass->assertViewHas('classificationName', 'Unclassification');
        $resUnclass->assertViewHas('slug', 'unclassification');
        $resUnclass->assertViewHas('isUnclassified', true);

        // 2. Unclassification DataTables endpoint (must return exactly 4 records)
        $resData = $this->actingAs($this->admin)->get('/manage/record/all/classification/unclassification/data?draw=1&start=0&length=10');
        $resData->assertStatus(200);
        $data = $resData->json();
        $this->assertSame(4, $data['recordsTotal']);
        $this->assertSame(4, $data['recordsFiltered']);
        $this->assertCount(4, $data['data']);

        // 3. Basic & Common Duties detail page
        $resBasic = $this->actingAs($this->admin)->get('/manage/record/all/classification/basic-common-duties');
        $resBasic->assertStatus(200);
        $resBasic->assertViewHas('classificationName', 'Basic & Common Duties');
        $resBasic->assertViewHas('slug', 'basic-common-duties');

        // 4. Legacy URLs must not 404 and resolve to Unclassification
        $resLegacy1 = $this->actingAs($this->admin)->get('/manage/record/all/classification/audit-isolated-classification');
        $resLegacy1->assertStatus(200);
        $resLegacy1->assertViewHas('classificationName', 'Unclassification');
        $resLegacy1->assertViewHas('slug', 'unclassification');

        $resLegacy2 = $this->actingAs($this->admin)->get('/manage/record/all/classification/unclassified');
        $resLegacy2->assertStatus(200);
        $resLegacy2->assertViewHas('classificationName', 'Unclassification');
        $resLegacy2->assertViewHas('slug', 'unclassification');
    }

    /**
     * Scenario 7: Export Integrity
     */
    public function test_scenario_7_export_integrity(): void
    {
        // 1. Verify 52 columns preserved
        $headings = ClassificationRecordExport::headings();
        $this->assertCount(52, $headings, 'ClassificationRecordExport must strictly preserve 52 columns');
        $this->assertSame('NO', $headings[0]);
        $this->assertSame('Source', $headings[1]);
        $this->assertSame('Total Engagement Time (LH)', $headings[51]);

        // 2. Verify export generation for Unclassification (5 rows = 1 header + 4 data)
        $export = new ClassificationRecordExport('unclassification');
        $filePath = $export->generateXlsxFile();
        $this->assertFileExists($filePath);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filePath));
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($xml);
        $rowCount = substr_count($xml, '<row r=');
        $this->assertSame(5, $rowCount, 'Unclassification XLSX must have 5 rows (1 header + 4 records)');
        $zip->close();
        @unlink($filePath);
    }
}
