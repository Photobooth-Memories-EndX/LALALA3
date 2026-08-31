<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContentRepositoryDataQualityTest extends TestCase
{
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'content_repo_testing');
        config()->set('database.connections.content_repo_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('content_repo_testing');
        $this->createSchema();

        $this->adminUser = User::forceCreate([
            'name' => 'Admin Test User',
            'email' => 'admin@example.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::connection('content_repo_testing')->dropAllTables();
        DB::purge('content_repo_testing');
        parent::tearDown();
    }

    private function createSchema(): void
    {
        Schema::connection('content_repo_testing')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(1);
            $table->timestamps();
        });

        Schema::connection('content_repo_testing')->create('user_access_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('module_key');
            $table->timestamps();
        });

        Schema::connection('content_repo_testing')->create('role_access_permissions', function (Blueprint $table) {
            $table->id();
            $table->integer('role');
            $table->string('module_key');
            $table->timestamps();
        });

        Schema::connection('content_repo_testing')->create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->string('type')->default('content');
            $table->timestamps();
        });

        Schema::connection('content_repo_testing')->create('classification', function (Blueprint $table) {
            $table->id();
            $table->string('classification_name');
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::connection('content_repo_testing')->create('content', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('type')->nullable();
            $table->string('classification_id')->nullable();
            $table->string('duration')->nullable();
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('slug')->nullable();
            $table->string('file')->nullable();
            $table->text('link')->nullable();
            $table->enum('exam', ['OK', 'NOT'])->default('NOT');
            $table->enum('active', ['Active', 'Inactive'])->default('Inactive');
            $table->string('playlistonly')->default('N');
            $table->boolean('has_deadline')->default(false);
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->integer('views')->default(0);
            $table->integer('page_duration')->nullable();
            $table->integer('total_page')->nullable();
            $table->float('learning_hours')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * TEST 1: Complete content appears.
     */
    public function test_1_complete_content_appears(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'Maintenance',
            'slug' => 'maintenance',
        ]);

        Content::forceCreate([
            'title' => 'Valid PLC Siemens S7-300',
            'type' => 'Video',
            'classification_id' => $classification->id,
            'duration' => '01:30:00',
            'date' => '2024-02-05',
            'description' => 'Complete PLC course description',
            'author' => 'Author Name',
            'slug' => 'valid-plc-siemens-s7-300',
            'file' => '["plc.mp4"]',
            'active' => 'Active',
            'learning_hours' => 1.5,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(1, $contents->total());
        $this->assertEquals('Valid PLC Siemens S7-300', $contents->first()->title);
    }

    /**
     * TEST 2: Content without slug still appears.
     */
    public function test_2_content_without_slug_still_appears(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'Maintenance',
            'slug' => 'maintenance',
        ]);

        $content = Content::forceCreate([
            'title' => 'Content Without Slug',
            'classification_id' => $classification->id,
            'date' => '2024-02-05',
            'active' => 'Active',
            'learning_hours' => 1.0,
        ]);
        DB::table('content')->where('id', $content->id)->update(['slug' => null]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(1, $contents->total());
        $this->assertEquals('Content Without Slug', $contents->first()->title);
    }

    /**
     * TEST 3: Content without learning_hours still appears.
     */
    public function test_3_content_without_learning_hours_still_appears(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'Maintenance',
            'slug' => 'maintenance',
        ]);

        Content::forceCreate([
            'title' => 'Content Without Learning Hours',
            'classification_id' => $classification->id,
            'date' => '2024-02-05',
            'slug' => 'content-without-lh',
            'active' => 'Active',
            'learning_hours' => null,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(1, $contents->total());
        $this->assertEquals('Content Without Learning Hours', $contents->first()->title);
    }

    /**
     * TEST 4: Content without slug is placed after content with valid slug.
     */
    public function test_4_content_without_slug_placed_after_valid_slug(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'Maintenance',
            'slug' => 'maintenance',
        ]);

        $noSlug = Content::forceCreate([
            'title' => 'Content No Slug',
            'classification_id' => $classification->id,
            'date' => '2024-02-05',
            'active' => 'Active',
            'learning_hours' => 1.5,
        ]);
        DB::table('content')->where('id', $noSlug->id)->update(['slug' => null]);

        $withSlug = Content::forceCreate([
            'title' => 'Content With Valid Slug',
            'classification_id' => $classification->id,
            'date' => '2024-02-05',
            'slug' => 'content-with-valid-slug',
            'active' => 'Active',
            'learning_hours' => 1.5,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(2, $contents->total());
        $this->assertEquals('Content With Valid Slug', $contents->first()->title);
        $this->assertEquals('Content No Slug', $contents->last()->title);
    }

    /**
     * TEST 5: Content without learning_hours is placed after content with valid learning_hours.
     */
    public function test_5_content_without_learning_hours_placed_after_valid_learning_hours(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'Maintenance',
            'slug' => 'maintenance',
        ]);

        $noLh = Content::forceCreate([
            'title' => 'Content No Learning Hours',
            'classification_id' => $classification->id,
            'date' => '2024-02-05',
            'slug' => 'content-no-lh',
            'active' => 'Active',
            'learning_hours' => null,
        ]);

        $withLh = Content::forceCreate([
            'title' => 'Content With Valid Learning Hours',
            'classification_id' => $classification->id,
            'date' => '2024-02-05',
            'slug' => 'content-with-lh',
            'active' => 'Active',
            'learning_hours' => 2.0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(2, $contents->total());
        $this->assertEquals('Content With Valid Learning Hours', $contents->first()->title);
        $this->assertEquals('Content No Learning Hours', $contents->last()->title);
    }

    /**
     * TEST 6: Content with title/classification/date complete but missing slug + LH still appears.
     */
    public function test_6_content_with_title_classification_date_complete_but_missing_slug_and_lh_still_appears(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'Maintenance',
            'slug' => 'maintenance',
        ]);

        $content = Content::forceCreate([
            'title' => 'Title Class Date Complete Missing Slug LH',
            'classification_id' => $classification->id,
            'date' => '2024-02-05',
            'active' => 'Active',
            'learning_hours' => null,
        ]);
        DB::table('content')->where('id', $content->id)->update(['slug' => null]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(1, $contents->total());
        $this->assertEquals('Title Class Date Complete Missing Slug LH', $contents->first()->title);
    }

    /**
     * TEST 7: Content with many empty fields still appears but placed at very back.
     */
    public function test_7_content_with_many_empty_fields_still_appears_placed_at_very_back(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'Maintenance',
            'slug' => 'maintenance',
        ]);

        $incomplete = Content::forceCreate([
            'title' => null,
            'classification_id' => null,
            'date' => null,
            'slug' => null,
            'active' => 'Inactive',
            'learning_hours' => null,
        ]);

        $complete = Content::forceCreate([
            'title' => 'Complete Content',
            'classification_id' => $classification->id,
            'date' => '2024-02-05',
            'slug' => 'complete-content',
            'active' => 'Active',
            'learning_hours' => 1.5,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(2, $contents->total());
        $this->assertEquals('Complete Content', $contents->first()->title);
        $this->assertNull($contents->last()->title);
    }

    /**
     * TEST 8: Active complete content has high priority.
     */
    public function test_8_active_complete_content_has_high_priority(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'General',
            'slug' => 'general',
        ]);

        $inactiveComplete = Content::forceCreate([
            'title' => 'Inactive Complete Content',
            'classification_id' => $classification->id,
            'date' => '2024-01-01',
            'slug' => 'inactive-complete',
            'active' => 'Inactive',
            'learning_hours' => 1.0,
        ]);

        $activeComplete = Content::forceCreate([
            'title' => 'Active Complete Content',
            'type' => 'Video',
            'duration' => '01:00:00',
            'description' => 'Complete description',
            'author' => 'Author Name',
            'classification_id' => $classification->id,
            'date' => '2024-01-01',
            'slug' => 'active-complete',
            'active' => 'Active',
            'learning_hours' => 1.0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(2, $contents->total());
        $this->assertEquals('Active Complete Content', $contents->first()->title);
        $this->assertEquals('Inactive Complete Content', $contents->last()->title);
    }

    /**
     * TEST 9: Inactive incomplete content placed further back.
     */
    public function test_9_inactive_incomplete_content_placed_further_back(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'General',
            'slug' => 'general',
        ]);

        $activeComplete = Content::forceCreate([
            'title' => 'Active Complete',
            'classification_id' => $classification->id,
            'date' => '2024-01-01',
            'slug' => 'active-complete',
            'active' => 'Active',
            'learning_hours' => 1.5,
        ]);

        $activeIncomplete = Content::forceCreate([
            'title' => 'Active Incomplete',
            'classification_id' => null,
            'date' => null,
            'slug' => null,
            'active' => 'Active',
            'learning_hours' => null,
        ]);

        $inactiveIncomplete = Content::forceCreate([
            'title' => 'Inactive Incomplete',
            'classification_id' => null,
            'date' => null,
            'slug' => null,
            'active' => 'Inactive',
            'learning_hours' => null,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(3, $contents->total());
        $this->assertEquals('Active Complete', $contents->first()->title);
        $this->assertEquals('Inactive Incomplete', $contents->last()->title);
    }

    /**
     * TEST 10: Pagination is correct and includes all records (no records excluded).
     */
    public function test_10_pagination_is_correct_and_includes_all_records(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'General',
            'slug' => 'general',
        ]);

        // Create 15 complete records
        for ($i = 1; $i <= 15; $i++) {
            Content::forceCreate([
                'title' => "Valid Content Item {$i}",
                'classification_id' => $classification->id,
                'date' => '2024-01-01',
                'slug' => "valid-content-item-{$i}",
                'active' => 'Active',
                'learning_hours' => 1.0,
            ]);
        }

        // Create 20 incomplete records
        for ($i = 1; $i <= 20; $i++) {
            Content::forceCreate([
                'title' => null,
                'classification_id' => null,
                'date' => null,
                'slug' => null,
                'active' => 'Inactive',
                'learning_hours' => null,
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');

        // Total MUST be 35 (15 + 20), not filtered out
        $this->assertEquals(35, $contents->total());
        $this->assertEquals(10, $contents->count());
        $this->assertEquals(4, $contents->lastPage());
        // Page 1 must contain valid/complete items
        $this->assertNotNull($contents->first()->title);
    }

    /**
     * TEST 11: Existing search and filter work properly.
     */
    public function test_11_existing_search_and_filter_work_properly(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'Safety',
            'slug' => 'safety',
        ]);

        Content::forceCreate([
            'title' => 'Safety Protocol 101',
            'classification_id' => $classification->id,
            'date' => '2024-01-10',
            'slug' => 'safety-protocol-101',
            'active' => 'Active',
            'learning_hours' => 1.0,
        ]);

        Content::forceCreate([
            'title' => 'Unrelated Topic',
            'classification_id' => $classification->id,
            'date' => '2024-01-10',
            'slug' => 'unrelated-topic',
            'active' => 'Active',
            'learning_hours' => 1.0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index', ['s' => 'Protocol']))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(1, $contents->total());
        $this->assertEquals('Safety Protocol 101', $contents->first()->title);
    }

    /**
     * TEST 12: Default ordering places most complete data at the front.
     */
    public function test_12_default_ordering_places_most_complete_data_at_front(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'Engineering',
            'slug' => 'engineering',
        ]);

        // Least complete
        Content::forceCreate([
            'title' => 'Incomplete Title Only',
            'classification_id' => null,
            'date' => null,
            'active' => 'Inactive',
        ]);

        // Most complete
        Content::forceCreate([
            'title' => 'Most Complete Engineering',
            'type' => 'Video',
            'classification_id' => $classification->id,
            'duration' => '02:00:00',
            'date' => '2024-03-01',
            'description' => 'Detailed description',
            'author' => 'John Doe',
            'slug' => 'most-complete-engineering',
            'file' => '["eng.mp4"]',
            'active' => 'Active',
            'learning_hours' => 2.0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals(2, $contents->total());
        $this->assertEquals('Most Complete Engineering', $contents->first()->title);
    }

    /**
     * REGRESSION TEST: TOTAL BEFORE = TOTAL AFTER (No records excluded).
     */
    public function test_regression_total_data_before_and_after_ordering_is_equal(): void
    {
        $classification = Classification::forceCreate([
            'classification_name' => 'General',
            'slug' => 'general',
        ]);

        // Create 10 diverse records
        for ($i = 1; $i <= 10; $i++) {
            Content::forceCreate([
                'title' => ($i % 2 == 0) ? "Record {$i}" : null,
                'classification_id' => ($i % 3 == 0) ? $classification->id : null,
                'date' => ($i % 2 == 0) ? '2024-01-01' : null,
                'slug' => ($i % 4 == 0) ? "slug-{$i}" : null,
                'active' => ($i % 2 == 0) ? 'Active' : 'Inactive',
                'learning_hours' => ($i % 3 == 0) ? 1.5 : null,
            ]);
        }

        $totalInDb = Content::count();
        $this->assertEquals(10, $totalInDb);

        $response = $this->actingAs($this->adminUser)
            ->get(route('content.index'))
            ->assertOk();

        $contents = $response->viewData('contents');
        $this->assertEquals($totalInDb, $contents->total());
    }
}