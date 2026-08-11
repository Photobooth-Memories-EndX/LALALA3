<?php

namespace Tests\Feature;

use App\Models\EventPhoto;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GalleryDesignSystemTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'gallery_testing');
        config()->set('database.connections.gallery_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('gallery_testing');
        $this->createSchema();

        $this->user = User::forceCreate([
            'name' => 'Gallery Tester',
            'email' => 'gallery@example.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_USER,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::connection('gallery_testing')->dropAllTables();
        DB::purge('gallery_testing');
        parent::tearDown();
    }

    public function test_listing_only_exposes_active_and_non_deleted_galleries(): void
    {
        $this->gallery(['title' => 'Published Event']);
        $this->gallery(['title' => 'Inactive Event', 'active' => 'Inactive']);
        $this->gallery(['title' => 'Deleted Event', 'deleted_at' => now()]);

        $this->actingAs($this->user)
            ->get(route('gallery.user.index'))
            ->assertOk()
            ->assertSee('Published Event')
            ->assertDontSee('Inactive Event')
            ->assertDontSee('Deleted Event');
    }

    public function test_combined_database_filters_are_applied(): void
    {
        $this->gallery([
            'title' => 'Technical Workshop Alpha',
            'uploader' => 'Training Team',
            'category' => 'Workshop',
            'eventdate' => '2026-03-15',
        ]);
        $this->gallery([
            'title' => 'Technical Workshop Beta',
            'uploader' => 'Another Team',
            'category' => 'Workshop',
            'eventdate' => '2026-03-15',
        ]);

        $response = $this->actingAs($this->user)->get(route('gallery.user.index', [
            'q' => 'Alpha',
            'uploader' => 'Training Team',
            'category' => ['Workshop'],
            'year' => [2026],
            'month' => [3],
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ]));

        $response->assertOk()
            ->assertSee('Technical Workshop Alpha')
            ->assertDontSee('Technical Workshop Beta');
    }

    public function test_views_are_sorted_numerically_instead_of_lexically(): void
    {
        $this->gallery(['title' => 'Ten Views', 'views' => '10']);
        $this->gallery(['title' => 'Two Views', 'views' => '2']);
        $this->gallery(['title' => 'Hundred Views', 'views' => '100']);

        $html = $this->jsonListing(['sort' => 'most_viewed'])->json('html');

        $this->assertLessThan(strpos($html, 'Ten Views'), strpos($html, 'Hundred Views'));
        $this->assertLessThan(strpos($html, 'Two Views'), strpos($html, 'Ten Views'));
    }

    public function test_likes_are_sorted_numerically_and_null_is_zero(): void
    {
        $this->gallery(['title' => 'Nine Likes', 'likes' => '9']);
        $this->gallery(['title' => 'Twelve Likes', 'likes' => '12']);
        $this->gallery(['title' => 'No Likes', 'likes' => null]);

        $mostLiked = $this->jsonListing(['sort' => 'most_liked'])->json('html');
        $leastLiked = $this->jsonListing(['sort' => 'least_liked'])->json('html');

        $this->assertLessThan(strpos($mostLiked, 'Nine Likes'), strpos($mostLiked, 'Twelve Likes'));
        $this->assertLessThan(strpos($leastLiked, 'Nine Likes'), strpos($leastLiked, 'No Likes'));
    }

    public function test_oldest_and_alphabetical_sorts_are_stable(): void
    {
        $this->gallery(['title' => 'Zulu', 'created_at' => '2026-02-01 00:00:00']);
        $this->gallery(['title' => 'Alpha', 'created_at' => '2026-01-01 00:00:00']);

        $oldest = $this->jsonListing(['sort' => 'oldest'])->json('html');
        $az = $this->jsonListing(['sort' => 'az'])->json('html');
        $za = $this->jsonListing(['sort' => 'za'])->json('html');

        $this->assertLessThan(strpos($oldest, 'Zulu'), strpos($oldest, 'Alpha'));
        $this->assertLessThan(strpos($az, 'Zulu'), strpos($az, 'Alpha'));
        $this->assertLessThan(strpos($za, 'Alpha'), strpos($za, 'Zulu'));
    }

    public function test_ajax_contract_contains_results_and_synchronized_filters(): void
    {
        $this->gallery(['title' => 'AJAX Gallery']);

        $this->jsonListing(['q' => 'AJAX'])
            ->assertOk()
            ->assertJsonStructure(['html', 'filters_html', 'count'])
            ->assertJsonPath('count', 1);
    }

    public function test_native_advanced_form_values_override_quick_controls_only_for_advanced_submit(): void
    {
        $this->gallery(['title' => 'Quick Uploader Event', 'uploader' => 'Quick Team']);
        $this->gallery(['title' => 'Advanced Uploader Event', 'uploader' => 'Advanced Team']);

        $this->actingAs($this->user)
            ->get(route('gallery.user.index', [
                'uploader' => 'Quick Team',
                'modal_uploader' => 'Advanced Team',
            ]))
            ->assertOk()
            ->assertSee('Quick Uploader Event')
            ->assertDontSee('Advanced Uploader Event');

        $this->actingAs($this->user)
            ->get(route('gallery.user.index', [
                'gallery_source' => 'advanced',
                'uploader' => 'Quick Team',
                'modal_uploader' => 'Advanced Team',
            ]))
            ->assertOk()
            ->assertSee('Advanced Uploader Event')
            ->assertDontSee('Quick Uploader Event');
    }

    public function test_invalid_range_and_inactive_status_are_rejected(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('gallery.user.index', ['date_from' => '2026-04-10', 'date_to' => '2026-04-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');

        $this->actingAs($this->user)
            ->getJson(route('gallery.user.index', ['status' => ['Inactive']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status.0');
    }

    public function test_filtered_pagination_preserves_query_string(): void
    {
        foreach (range(1, 10) as $index) {
            $this->gallery(['title' => 'Paginated Event ' . $index]);
        }

        $this->actingAs($this->user)
            ->get(route('gallery.user.index', ['q' => 'Paginated']))
            ->assertOk()
            ->assertSee('q=Paginated', false);
    }

    public function test_detail_rejects_inactive_and_atomically_increments_active_views(): void
    {
        $active = $this->gallery(['title' => 'Active Detail', 'views' => '4']);
        $nullViews = $this->gallery(['title' => 'Null Views Detail', 'views' => null]);
        $inactive = $this->gallery(['title' => 'Inactive Detail', 'active' => 'Inactive']);

        $this->actingAs($this->user)
            ->get(route('gallery.user.view', $inactive->slug))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get(route('gallery.user.view', $active->slug))
            ->assertOk()
            ->assertSee('Active Detail');

        $this->assertSame('5', (string) $active->fresh()->views);

        $this->actingAs($this->user)
            ->get(route('gallery.user.view', $nullViews->slug))
            ->assertOk();

        $this->assertSame('1', (string) $nullViews->fresh()->views);
    }

    private function jsonListing(array $query = [])
    {
        return $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->get(route('gallery.user.index', $query));
    }

    private function gallery(array $overrides = []): EventPhoto
    {
        static $sequence = 0;
        $sequence++;

        return EventPhoto::create(array_merge([
            'user_id' => $this->user->id,
            'title' => 'Gallery ' . $sequence,
            'category' => 'Event',
            'eventdate' => '2026-01-15',
            'photos' => '[]',
            'likes' => '0',
            'uploader' => 'Training Team',
            'views' => '0',
            'thumbnail' => null,
            'active' => 'Active',
            'created_at' => now()->addSeconds($sequence),
            'updated_at' => now()->addSeconds($sequence),
        ], $overrides));
    }

    private function createSchema(): void
    {
        Schema::connection('gallery_testing')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(1);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('gallery_testing')->create('eventphoto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title')->nullable();
            $table->string('category')->nullable();
            $table->date('eventdate')->nullable();
            $table->text('photos')->nullable();
            $table->string('likes')->nullable();
            $table->string('slug')->nullable();
            $table->string('uploader')->nullable();
            $table->string('views')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('active')->default('Inactive');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('gallery_testing')->create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('eventphoto');
            $table->unsignedBigInteger('eventphoto_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
}
