<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\OfflineTrainingOption;
use App\Models\User;
use Database\Seeders\ClassificationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClassificationSeederTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::factory()->create(['role' => '2']);
    }

    /**
     * Test 1: ClassificationSeeder seeds all 13 classifications accurately.
     */
    public function test_seeder_populates_all_13_classifications(): void
    {
        $this->seed(ClassificationSeeder::class);

        $this->assertEquals(13, Classification::count());

        foreach (ClassificationSeeder::CLASSIFICATIONS as $expected) {
            $record = Classification::where('slug', $expected['slug'])->first();
            $this->assertNotNull($record, "Classification with slug '{$expected['slug']}' must exist.");
            $this->assertEquals($expected['classification_name'], $record->classification_name);
            $this->assertEquals(1, $record->is_active);
        }
    }

    /**
     * Test 2: ClassificationSeeder is idempotent (second run produces zero duplicates).
     */
    public function test_seeder_is_idempotent_no_duplicates(): void
    {
        $this->seed(ClassificationSeeder::class);
        $countFirst = Classification::count();
        $idsFirst = Classification::orderBy('id')->pluck('id')->toArray();

        $this->seed(ClassificationSeeder::class);
        $countSecond = Classification::count();
        $idsSecond = Classification::orderBy('id')->pluck('id')->toArray();

        $this->assertEquals(13, $countFirst);
        $this->assertEquals(13, $countSecond);
        $this->assertEquals($idsFirst, $idsSecond, "Record IDs must not change upon re-seeding.");
    }

    /**
     * Test 3: Sub classifications remain linked to Main Classifications.
     */
    public function test_sub_classifications_remain_linked_after_seeding(): void
    {
        $this->seed(ClassificationSeeder::class);

        $assessment = Classification::where('slug', 'assessment-test')->firstOrFail();
        $childSubs = OfflineTrainingOption::where('classification_id', $assessment->id)->get();

        $this->assertTrue($childSubs->count() > 0, "Assessment/Test must maintain linked sub classifications.");
    }

    /**
     * Test 4: Manage Classification page renders 200 with all master data intact.
     */
    public function test_manage_classification_page_renders_cleanly(): void
    {
        $this->seed(ClassificationSeeder::class);

        $response = $this->actingAs($this->admin)->get('/manage/classification?type=classification');

        $response->assertStatus(200);
        $response->assertSee('List Training Classification');
        $response->assertSee('22 row(s) displayed');
        $response->assertSee('Basic & Common Duties');
        $response->assertSee('Compliance');
        $response->assertSee('Innovation');
        $response->assertSee('Job Functional');
        $response->assertSee('Language');
        $response->assertSee('Leadership');
        $response->assertSee('Assessment/Test');
    }
}
