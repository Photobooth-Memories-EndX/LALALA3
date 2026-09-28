<?php

namespace Tests\Feature;

use App\Exports\ExportMyLearningRecord;
use App\Http\Controllers\LearningRecordController;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordClassificationFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classificationBasic;
    protected Classification $classificationLeadership;
    protected Content $contentBasic;
    protected Content $contentLeadership;
    protected ContentUser $cuBasic;
    protected ContentUser $cuLeadership;
    protected LearningRecordController $controller;
    protected CertificateEligibilityService $eligibilityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = app(LearningRecordController::class);
        $this->eligibilityService = app(CertificateEligibilityService::class);

        $this->user = User::factory()->create([
            'name'       => 'Test Learner Classification ' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-class-' . Str::uuid(),
            'sap_code'   => 'SAP-CLS-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classificationBasic = Classification::firstOrCreate(
            ['classification_name' => 'Basic & Common Duties'],
            ['slug' => 'basic-common-duties']
        );

        $this->classificationLeadership = Classification::firstOrCreate(
            ['classification_name' => 'Leadership'],
            ['slug' => 'leadership']
        );

        $this->contentBasic = Content::create([
            'title'             => 'Corporate Culture 101',
            'slug'              => 'corp-culture-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classificationBasic->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $this->cuBasic = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $this->contentBasic->id,
            'current_time'     => 600,
            'duration_seconds' => 600,
            'progress'         => 100,
            'finish'           => '1',
            'status'           => 'optional',
        ]);
        DB::table('content_users')->where('id', $this->cuBasic->id)->update([
            'created_at'   => '2025-02-10 10:00:00',
            'updated_at'   => '2025-02-15 11:00:00',
            'completed_at' => '2025-02-15 11:00:00',
        ]);

        $this->contentLeadership = Content::create([
            'title'             => 'Executive Leadership',
            'slug'              => 'exec-lead-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classificationLeadership->id,
            'duration'          => '00:15:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $this->cuLeadership = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $this->contentLeadership->id,
            'current_time'     => 900,
            'duration_seconds' => 900,
            'progress'         => 100,
            'finish'           => '1',
            'status'           => 'mandatory',
        ]);
        DB::table('content_users')->where('id', $this->cuLeadership->id)->update([
            'created_at'   => '2025-03-01 09:00:00',
            'updated_at'   => '2025-03-05 14:00:00',
            'completed_at' => '2025-03-05 14:00:00',
        ]);
    }

    public function test_quick_filter_classification_basic_and_common_duties_returns_matching_records(): void
    {
        $this->actingAs($this->user);
        request()->replace(['record_filter_classification' => 'Basic & Common Duties']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];

        $this->assertCount(1, $rows);
        $first = $rows->first();
        $this->assertEquals('Corporate Culture 101', $first['title']);
        $this->assertEquals('Basic & Common Duties', $first['classification']);
    }

    public function test_quick_filter_classification_excludes_non_matching_records(): void
    {
        $this->actingAs($this->user);
        request()->replace(['record_filter_classification' => 'Leadership']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];

        $this->assertCount(1, $rows);
        $first = $rows->first();
        $this->assertEquals('Executive Leadership', $first['title']);
        $this->assertEquals('Leadership', $first['classification']);
    }

    public function test_classification_all_returns_all_completed_records(): void
    {
        $this->actingAs($this->user);
        request()->replace(['record_filter_classification' => 'all']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];

        $this->assertCount(2, $rows);
    }

    public function test_html_entity_encoded_parameter_resolves_correctly_due_to_normalization(): void
    {
        $this->actingAs($this->user);
        request()->replace(['record_filter_classification' => 'Basic &amp; Common Duties']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];

        $this->assertCount(1, $rows);
        $this->assertEquals('Basic & Common Duties', $rows->first()['classification']);
    }

    public function test_blade_record_filter_renders_data_value_without_double_escaping(): void
    {
        $filterOptions = [
            'classifications' => collect(['Basic & Common Duties', 'Leadership']),
            'types'           => collect(['Video', 'Document']),
            'years'           => collect(['2025']),
            'learningHours'   => collect(),
        ];

        $html = view('components.learning.record-filter', ['filterOptions' => $filterOptions])->render();

        // Must render single escaped attribute in HTML, NOT double escaped &amp;amp;
        $this->assertStringContainsString('data-value="Basic &amp; Common Duties"', $html);
        $this->assertStringNotContainsString('data-value="Basic &amp;amp; Common Duties"', $html);
    }

    public function test_export_with_basic_and_common_duties_classification(): void
    {
        $this->actingAs($this->user);

        $export = new ExportMyLearningRecord(['record_filter_classification' => 'Basic & Common Duties']);
        $data = $export->array();
        $exportItems = array_filter(array_slice($data, 1, -2), fn ($r) => !empty($r[0]));

        $this->assertCount(1, $exportItems);
        $firstItem = reset($exportItems);
        $this->assertEquals('Corporate Culture 101', $firstItem[1]);
        $this->assertEquals('Basic & Common Duties', $firstItem[4]);
    }

    public function test_combined_filter_classification_with_year_and_type(): void
    {
        $this->actingAs($this->user);

        // Matching: Basic & Common Duties + 2025 + Video
        request()->replace([
            'record_filter_classification' => 'Basic & Common Duties',
            'record_filter_year'           => '2025',
            'record_filter_type'           => 'Video',
        ]);
        $view = $this->controller->partial($this->eligibilityService);
        $this->assertCount(1, $view->getData()['rows']);

        // Non-matching type: Basic & Common Duties + 2025 + Document -> 0 rows
        request()->replace([
            'record_filter_classification' => 'Basic & Common Duties',
            'record_filter_year'           => '2025',
            'record_filter_type'           => 'Document',
        ]);
        $viewNonMatch = $this->controller->partial($this->eligibilityService);
        $this->assertCount(0, $viewNonMatch->getData()['rows']);
    }
}
