<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LearningHoursCardDisplayTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->make(['id' => 999]);
        $this->actingAs($user);
    }

    /**
     * Test Content Video card renders learning hours with 2 decimal places.
     */
    public function test_content_video_card_displays_learning_hours_with_two_decimal_places(): void
    {
        $cases = [
            '0.03253' => '0.03 Hours',
            '0.0417'  => '0.04 Hours',
            '0.0764'  => '0.08 Hours',
            '0.2833'  => '0.28 Hours',
            '1'       => '1.00 Hours',
            '2.5'     => '2.50 Hours',
            '4.6175'  => '4.62 Hours',
            '0'       => '0.00 Hours',
        ];

        foreach ($cases as $rawValue => $expectedText) {
            $item = (object) [
                'id' => 1,
                'type' => 'Video',
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'has_deadline' => false,
                'to_date' => null,
                'learning_hours' => $rawValue,
                'duration' => '00:10:00',
                'author' => 'Author Test',
                'playlistonly' => 'N',
                'slug' => 'test-video-' . md5($rawValue),
                'views' => 10,
                'title' => 'Test Video Title',
            ];

            $html = view('user.content.components.card-video', ['item' => $item])->render();

            $this->assertStringContainsString(
                'Learning Hours: ' . $expectedText,
                $html,
                "Failed for video card with raw value {$rawValue}"
            );
        }
    }

    /**
     * Test Content Document card renders learning hours with 2 decimal places.
     */
    public function test_content_document_card_displays_learning_hours_with_two_decimal_places(): void
    {
        $cases = [
            '0.03253' => '0.03 Hours',
            '0.0417'  => '0.04 Hours',
            '0.0764'  => '0.08 Hours',
            '0.2833'  => '0.28 Hours',
            '1'       => '1.00 Hours',
            '2.5'     => '2.50 Hours',
            '4.6175'  => '4.62 Hours',
            '0'       => '0.00 Hours',
        ];

        foreach ($cases as $rawValue => $expectedText) {
            $item = (object) [
                'id' => 2,
                'type' => 'Document',
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'has_deadline' => false,
                'to_date' => null,
                'learning_hours' => $rawValue,
                'duration' => '00:15:00',
                'page_duration' => 30,
                'author' => 'Doc Author',
                'playlistonly' => 'N',
                'slug' => 'test-doc-' . md5($rawValue),
                'views' => 5,
                'title' => 'Test Document Title',
            ];

            $html = view('user.content.components.card-document', ['item' => $item])->render();

            $this->assertStringContainsString(
                'Learning Hours: ' . $expectedText,
                $html,
                "Failed for document card with raw value {$rawValue}"
            );
        }
    }

    /**
     * Test Ongoing card renders learning hours with 2 decimal places.
     */
    public function test_ongoing_card_displays_learning_hours_with_two_decimal_places(): void
    {
        $cases = [
            '0.03253' => '0.03 Hours',
            '0.0417'  => '0.04 Hours',
            '0.0764'  => '0.08 Hours',
            '0.2833'  => '0.28 Hours',
            '1'       => '1.00 Hours',
            '2.5'     => '2.50 Hours',
            '4.6175'  => '4.62 Hours',
            '0'       => '0.00 Hours',
        ];

        foreach ($cases as $rawValue => $expectedText) {
            // Test content mode
            $htmlContent = Blade::render(
                '<x-learning.ongoing-card :card-type="\'content\'" :title="\'Test Ongoing Content\'" :learning-hours="$lh" />',
                ['lh' => $rawValue]
            );
            $this->assertStringContainsString(
                'Learning Hours: ' . $expectedText,
                $htmlContent,
                "Failed for ongoing content card with raw value {$rawValue}"
            );

            // Test playlist mode
            $htmlPlaylist = Blade::render(
                '<x-learning.ongoing-card :card-type="\'playlist\'" :title="\'Test Ongoing Playlist\'" :learning-hours="$lh" />',
                ['lh' => $rawValue]
            );
            $this->assertStringContainsString(
                'Learning Hours: ' . $expectedText,
                $htmlPlaylist,
                "Failed for ongoing playlist card with raw value {$rawValue}"
            );
        }

        // Test null/empty fallback
        $htmlNull = Blade::render(
            '<x-learning.ongoing-card :card-type="\'content\'" :title="\'Test Null\'" :learning-hours="null" />'
        );
        $this->assertStringContainsString('Learning Hours: -', $htmlNull);
    }

    /**
     * Test Playlist card renders learning hours with 2 decimal places.
     */
    public function test_playlist_card_displays_learning_hours_with_two_decimal_places(): void
    {
        $cases = [
            '0.03253' => '0.03 Hours',
            '0.0417'  => '0.04 Hours',
            '0.0764'  => '0.08 Hours',
            '0.2833'  => '0.28 Hours',
            '1'       => '1.00 Hours',
            '2.5'     => '2.50 Hours',
            '4.6175'  => '4.62 Hours',
            '0'       => '0.00 Hours',
        ];

        foreach ($cases as $rawValue => $expectedText) {
            $playlist = (object) [
                'id' => 10,
                'slug' => 'test-playlist-' . md5($rawValue),
                'title' => 'Test Playlist',
                'thumbnail_url' => null,
                'total_duration_seconds' => 1800,
                'duration' => '00:30:00',
                'learning_hours' => $rawValue,
                'created_at' => now(),
                'period' => null,
                'author' => 'Playlist Author',
                'trainingplandetail' => collect(),
            ];
            $stats = [
                'totalContent' => 3,
                'completedContent' => 1,
                'progress' => 33,
                'total_duration_seconds' => 1800,
                'duration' => '00:30:00',
                'learning_hours' => $rawValue,
                'firstContent' => (object) ['slug' => 'first-content', 'author' => 'First Author'],
                'has_deadline' => false,
                'from_date' => null,
                'to_date' => null,
            ];

            $html = view('user.playlist.components.playlist-card', [
                'playlist' => $playlist,
                'stats' => $stats,
            ])->render();

            $this->assertStringContainsString(
                'Learning Hours: ' . $expectedText,
                $html,
                "Failed for playlist card with raw value {$rawValue}"
            );
        }

        // Test null fallback
        $playlistNull = (object) [
            'id' => 11,
            'slug' => 'test-playlist-null',
            'title' => 'Test Playlist Null',
            'thumbnail_url' => null,
            'total_duration_seconds' => 0,
            'duration' => '00:00:00',
            'learning_hours' => null,
            'created_at' => now(),
            'period' => null,
            'author' => 'Playlist Author',
            'trainingplandetail' => collect(),
        ];
        $statsNull = [
            'totalContent' => 0,
            'completedContent' => 0,
            'progress' => 0,
            'total_duration_seconds' => 0,
            'duration' => '00:00:00',
            'learning_hours' => null,
            'firstContent' => null,
            'has_deadline' => false,
            'from_date' => null,
            'to_date' => null,
        ];
        $htmlNull = view('user.playlist.components.playlist-card', [
            'playlist' => $playlistNull,
            'stats' => $statsNull,
        ])->render();

        $this->assertStringContainsString('Learning Hours: -', $htmlNull);
    }

    /**
     * Test Content Learning Sidebar card renders learning hours with 2 decimal places.
     */
    public function test_content_learning_sidebar_displays_learning_hours_with_two_decimal_places(): void
    {
        $cases = [
            '0.03253' => '0.03 Hours',
            '4.6175'  => '4.62 Hours',
            '1'       => '1.00 Hours',
        ];

        foreach ($cases as $rawValue => $expectedText) {
            $content = (new Content())->forceFill([
                'id' => 50,
                'title' => 'Sidebar Test Content',
                'type' => 'Video',
                'duration' => '01:00:00',
                'learning_hours' => $rawValue,
                'from_date' => null,
                'to_date' => null,
                'favorites_count' => 0,
                'is_favorite' => false,
            ]);

            $contentUser = (object) [
                'progress' => 50,
                'last_page' => 0,
                'total_pages' => 0,
                'learning_status' => 'in_progress',
            ];

            $html = view('user.content.components.content-learning-sidebar', [
                'content' => $content,
                'content_user' => $contentUser,
                'bookmarkCount' => 0,
                'discussionCount' => 0,
                'isBookmarked' => false,
                'assessment' => null,
            ])->render();

            $this->assertStringContainsString(
                $expectedText,
                $html,
                "Failed for sidebar card with raw value {$rawValue}"
            );
        }
    }

    /**
     * Test normal decimal rounding behavior (half-up, not truncate).
     */
    public function test_decimal_rounding_boundary_rules(): void
    {
        $roundingCases = [
            '0.034'  => '0.03 Hours',
            '0.035'  => '0.04 Hours',
            '0.076'  => '0.08 Hours',
            '1.234'  => '1.23 Hours',
            '1.235'  => '1.24 Hours',
            '4.6175' => '4.62 Hours',
            '4.6972' => '4.70 Hours',
            '2.7906' => '2.79 Hours',
        ];

        foreach ($roundingCases as $input => $expected) {
            $html = Blade::render(
                '<x-learning.ongoing-card :card-type="\'content\'" :title="\'Rounding Test\'" :learning-hours="$lh" />',
                ['lh' => $input]
            );

            $this->assertStringContainsString(
                'Learning Hours: ' . $expected,
                $html,
                "Boundary rounding failed for input {$input}"
            );
        }
    }

    /**
     * Test that underlying database values remain unrounded and intact.
     */
    public function test_database_values_remain_unmodified_and_unrounded(): void
    {
        $rawPrecisionValue = 4.6175;

        // 1. Content in database
        $content = Content::create([
            'title' => 'Precision Test Content',
            'type' => 'Video',
            'duration' => '04:37:03',
            'learning_hours' => $rawPrecisionValue,
            'has_deadline' => false,
            'slug' => 'precision-test-content-' . uniqid(),
        ]);

        // Render card
        $htmlContent = view('user.content.components.card-video', ['item' => $content])->render();
        $this->assertStringContainsString('Learning Hours: 4.62 Hours', $htmlContent);

        // Verify DB value has NOT been rounded to 4.62
        $contentFresh = $content->fresh();
        $this->assertEqualsWithDelta($rawPrecisionValue, (float) $contentFresh->learning_hours, 0.0001);

        // 2. TrainingPlan in database
        $playlist = TrainingPlan::create([
            'title' => 'Precision Test Playlist',
            'description' => 'Test',
            'slug' => 'precision-test-playlist-' . uniqid(),
            'status' => 1,
            'total_duration_seconds' => 16623,
            'learning_hours' => $rawPrecisionValue,
        ]);

        $stats = [
            'totalContent' => 5,
            'completedContent' => 2,
            'progress' => 40,
            'total_duration_seconds' => 16623,
            'duration' => '04:37:03',
            'learning_hours' => $playlist->learning_hours,
            'firstContent' => null,
            'has_deadline' => false,
        ];

        $htmlPlaylist = view('user.playlist.components.playlist-card', [
            'playlist' => $playlist,
            'stats' => $stats,
        ])->render();
        $this->assertStringContainsString('Learning Hours: 4.62 Hours', $htmlPlaylist);

        // Verify DB value has NOT been rounded
        $playlistFresh = $playlist->fresh();
        $this->assertEqualsWithDelta($rawPrecisionValue, (float) $playlistFresh->learning_hours, 0.0001);
    }
}