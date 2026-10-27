<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\Classification;
use App\Models\User;
use Tests\TestCase;

class LearningCategoryTableUiTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /** @test */
    public function it_loads_the_basic_common_duties_category_page_successfully()
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning/category/basic-common-duties');
        $response->assertStatus(200);
        $response->assertSee('learning-table');
        $response->assertSee('EMPLOYEE ID');
        $response->assertSee('COURSE TITLE');
        $response->assertSee('PROGRESS STATUS');
        $response->assertSee('DATE TIME');
        $response->assertSee('RECORD MONTH');
        $response->assertSee('TOTAL ENGAGEMENT TIME');
        $response->assertSee('learningDetailModal');
    }

    /** @test */
    public function it_returns_canonical_29_column_keys_in_datatable_json()
    {
        $response = $this->actingAs($this->admin)->getJson('/manage/learning/datatable?classification_slug=basic-common-duties&start=0&length=2&draw=1');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            $row = $json['data'][0];
            $this->assertArrayHasKey('employee_id', $row);
            $this->assertArrayHasKey('employee_name', $row);
            $this->assertArrayHasKey('team', $row);
            $this->assertArrayHasKey('department', $row);
            $this->assertArrayHasKey('company', $row);
            $this->assertArrayHasKey('course_title', $row);
            $this->assertArrayHasKey('type', $row);
            $this->assertArrayHasKey('category', $row);
            $this->assertArrayHasKey('main_classification', $row);
            $this->assertArrayHasKey('sub_classification', $row);
            $this->assertArrayHasKey('delivery_method', $row);
            $this->assertArrayHasKey('sub_delivery_method', $row);
            $this->assertArrayHasKey('provider_name', $row);
            $this->assertArrayHasKey('venue', $row);
            $this->assertArrayHasKey('course_duration', $row);
            $this->assertArrayHasKey('learning_hours', $row);
            $this->assertArrayHasKey('progress_bar', $row);
            $this->assertArrayHasKey('progress_status', $row);
            $this->assertArrayHasKey('date_time_merged', $row);
            $this->assertArrayHasKey('record_month', $row);
            $this->assertArrayHasKey('requirement', $row);
            $this->assertArrayHasKey('attendance', $row);
            $this->assertArrayHasKey('score_l1', $row);
            $this->assertArrayHasKey('score_l2', $row);
            $this->assertArrayHasKey('score_l3', $row);
            $this->assertArrayHasKey('pic', $row);
            $this->assertArrayHasKey('total_engagement_time', $row);
            $this->assertArrayHasKey('action', $row);

            $this->assertTrue(
                str_contains($row['progress_status'], 'Finished') ||
                str_contains($row['progress_status'], 'Ongoing') ||
                str_contains($row['progress_status'], 'Not Started')
            );
        }
    }

    /** @test */
    public function it_returns_complete_detail_modal_attributes_via_record_detail_endpoint()
    {
        $cu = ContentUser::first();
        if (!$cu) {
            $this->markTestSkipped('No ContentUser records available.');
        }

        $response = $this->actingAs($this->admin)->getJson("/manage/learning/record-detail/content/{$cu->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'source_type',
                'source_id',
                'employee' => [
                    'id_emp',
                    'name',
                    'department',
                    'team',
                    'company',
                    'grade',
                    'job_position',
                ],
                'learning' => [
                    'title',
                    'sub_course_title',
                    'type',
                    'classification',
                    'main_classification',
                    'sub_classification',
                    'category',
                    'source',
                    'level',
                    'batch',
                    'delivery_method',
                    'sub_delivery_method',
                    'provider_name',
                    'venue',
                    'instructor',
                ],
                'progress' => [
                    'progress',
                    'finish',
                    'progress_status',
                    'course_duration',
                    'total_recognized_duration',
                    'learning_hours',
                    'total_engagement_time',
                ],
                'record_info' => [
                    'record_month',
                    'requirement',
                    'attendance',
                    'attendance_reason',
                    'start_date',
                    'end_date',
                ],
                'obligation' => [
                    'bond_status',
                    'bond_duration',
                    'bond_end_date',
                    'bond_amount',
                ],
                'assessment' => [
                    'score_l1',
                    'score_l2',
                    'l2_status',
                    'score_l3',
                    'l3_status',
                    'pic',
                    'certificate',
                ],
                'timestamps' => [
                    'created_at',
                    'updated_at',
                    'completed_at',
                ],
            ],
        ]);
    }
}
