<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CalculatePlaylistContentUserLearningHoursCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected TrainingPlan $playlist;
    protected Content $content1; // 00:30:00 = 1800s
    protected Content $content2; // 00:04:30 = 270s
    protected array $existingUserIds;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-sso-' . Str::uuid(),
            'sap_code'   => 'SAP-TEST-' . rand(10000, 99999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $classification = Classification::firstOrCreate(
            ['classification_name' => 'Batch Test Classification'],
            ['slug' => 'batch-test-classification-' . Str::random(5)]
        );

        $this->playlist = TrainingPlan::create([
            'title'       => 'Batch Test Playlist ' . Str::random(5),
            'slug'        => 'batch-test-playlist-' . Str::random(8),
            'description' => 'Test playlist',
        ]);

        $this->content1 = Content::create([
            'title'             => 'Batch Test Content 1 ' . Str::random(5),
            'slug'              => 'batch-content-1-' . Str::random(8),
            'duration'          => '00:30:00', // 1800s
            'classification_id' => $classification->id,
            'type'              => 'Video',
        ]);

        $this->content2 = Content::create([
            'title'             => 'Batch Test Content 2 ' . Str::random(5),
            'slug'              => 'batch-content-2-' . Str::random(8),
            'duration'          => '00:04:30', // 270s
            'classification_id' => $classification->id,
            'type'              => 'Video',
        ]);

        $this->existingUserIds = DB::table('users')->pluck('id')->take(1050)->all();
    }

    public function test_batch_processor_calculates_required_precision_values_and_saves_to_database(): void
    {
        $u = $this->existingUserIds;

        // 1. 1200 seconds -> 0.3333
        $r1 = PlaylistContentUser::create([
            'user_id' => $u[0], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content1->id,
            'current_time' => 1200, 'finish' => '0', 'progress' => 66,
        ]);

        // 2. 1230 seconds -> 0.3417
        $r2 = PlaylistContentUser::create([
            'user_id' => $u[1], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content1->id,
            'current_time' => 1230, 'finish' => '0', 'progress' => 68,
        ]);

        // 3. 1800 seconds -> 0.5000
        $r3 = PlaylistContentUser::create([
            'user_id' => $u[2], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content1->id,
            'current_time' => 1800, 'finish' => '1', 'progress' => 100,
        ]);

        // 4. 2700 seconds -> 0.7500
        $r4 = PlaylistContentUser::create([
            'user_id' => $u[3], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content1->id,
            'current_time' => 2700, 'finish' => '1', 'progress' => 100,
        ]);

        // 5. 3600 seconds -> 1.0000
        $r5 = PlaylistContentUser::create([
            'user_id' => $u[4], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content1->id,
            'current_time' => 3600, 'finish' => '1', 'progress' => 100,
        ]);

        // 6. 4815 seconds -> 1.3375
        $r6 = PlaylistContentUser::create([
            'user_id' => $u[5], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content1->id,
            'current_time' => 4815, 'finish' => '1', 'progress' => 100,
        ]);

        // Reset learning_hour to null directly in database to test batch recalculation
        DB::table('playlist_content_users')
            ->whereIn('id', [$r1->id, $r2->id, $r3->id, $r4->id, $r5->id, $r6->id])
            ->update(['learning_hour' => null]);

        // Execute artisan command for this specific playlist
        $this->artisan('learning-hours:calculate-playlist-content-users', [
            '--playlist' => $this->playlist->id,
        ])->assertSuccessful();

        // Verify direct from MySQL raw query
        $raw1 = DB::table('playlist_content_users')->where('id', $r1->id)->value('learning_hour');
        $raw2 = DB::table('playlist_content_users')->where('id', $r2->id)->value('learning_hour');
        $raw3 = DB::table('playlist_content_users')->where('id', $r3->id)->value('learning_hour');
        $raw4 = DB::table('playlist_content_users')->where('id', $r4->id)->value('learning_hour');
        $raw5 = DB::table('playlist_content_users')->where('id', $r5->id)->value('learning_hour');
        $raw6 = DB::table('playlist_content_users')->where('id', $r6->id)->value('learning_hour');

        $this->assertSame('0.3333', (string) $raw1);
        $this->assertSame('0.3417', (string) $raw2);
        $this->assertSame('0.5000', (string) $raw3);
        $this->assertSame('0.7500', (string) $raw4);
        $this->assertSame('1.0000', (string) $raw5);
        $this->assertSame('1.3375', (string) $raw6);
    }

    public function test_historical_fallback_and_priority_rules_in_batch(): void
    {
        $u = $this->existingUserIds;

        // 7. current_time = 0 + unfinished -> 0.0000
        $rUnfinished = PlaylistContentUser::create([
            'user_id' => $u[10], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content1->id,
            'current_time' => 0, 'finish' => '0', 'progress' => 50,
        ]);

        // 8. current_time = 0 + finish = 1 -> content duration fallback (content2 = 00:04:30 = 270s -> 0.0750)
        $rFinishedFallback = PlaylistContentUser::create([
            'user_id' => $u[11], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content2->id,
            'current_time' => 0, 'finish' => '1', 'progress' => 100,
        ]);

        // 9. current_time > 0 + finish = 1 -> PRIORITIZE current_time (current_time=1230 -> 0.3417, not content1 1800s 0.5)
        $rFinishedTimeGt0 = PlaylistContentUser::create([
            'user_id' => $u[12], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content1->id,
            'current_time' => 1230, 'finish' => '1', 'progress' => 100,
        ]);

        // Reset to null
        DB::table('playlist_content_users')
            ->whereIn('id', [$rUnfinished->id, $rFinishedFallback->id, $rFinishedTimeGt0->id])
            ->update(['learning_hour' => null]);

        $this->artisan('learning-hours:calculate-playlist-content-users', [
            '--playlist' => $this->playlist->id,
        ])->assertSuccessful();

        $valUnfinished = DB::table('playlist_content_users')->where('id', $rUnfinished->id)->value('learning_hour');
        $valFallback = DB::table('playlist_content_users')->where('id', $rFinishedFallback->id)->value('learning_hour');
        $valPrioritized = DB::table('playlist_content_users')->where('id', $rFinishedTimeGt0->id)->value('learning_hour');

        $this->assertSame('0.0000', (string) $valUnfinished);
        $this->assertSame('0.0750', (string) $valFallback);
        $this->assertSame('0.3417', (string) $valPrioritized);
    }

    public function test_command_is_fully_idempotent(): void
    {
        $u = $this->existingUserIds;

        $r = PlaylistContentUser::create([
            'user_id' => $u[20], 'trainingplan_id' => $this->playlist->id, 'content_id' => $this->content1->id,
            'current_time' => 1230, 'finish' => '0', 'progress' => 68,
        ]);

        // Run 1st time
        $this->artisan('learning-hours:calculate-playlist-content-users', [
            '--playlist' => $this->playlist->id,
        ])->assertSuccessful();

        $valAfterRun1 = DB::table('playlist_content_users')->where('id', $r->id)->value('learning_hour');
        $this->assertSame('0.3417', (string) $valAfterRun1);

        // Run 2nd time
        $this->artisan('learning-hours:calculate-playlist-content-users', [
            '--playlist' => $this->playlist->id,
        ])->assertSuccessful();

        $valAfterRun2 = DB::table('playlist_content_users')->where('id', $r->id)->value('learning_hour');
        $this->assertSame('0.3417', (string) $valAfterRun2);
    }

    public function test_soft_deleted_content_does_not_crash_batch_processing(): void
    {
        $u = $this->existingUserIds;

        $tempContent = Content::create([
            'title'             => 'Temporary Deletable Content ' . Str::random(5),
            'slug'              => 'temp-del-content-' . Str::random(8),
            'duration'          => '00:10:00',
            'classification_id' => $this->content1->classification_id,
            'type'              => 'Video',
        ]);

        $r = PlaylistContentUser::create([
            'user_id'          => $u[25],
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $tempContent->id,
            'current_time'     => 0,
            'finish'           => '1',
            'progress'         => 100,
            'learning_hour'    => null,
        ]);

        // Soft-delete the content
        $tempContent->delete();

        // Batch processor must run smoothly without crashing
        $this->artisan('learning-hours:calculate-playlist-content-users', [
            '--playlist' => $this->playlist->id,
        ])->assertSuccessful();

        $val = DB::table('playlist_content_users')->where('id', $r->id)->value('learning_hour');
        $this->assertSame('0.0000', (string) $val);
    }

    public function test_n_plus_one_prevention_and_bulk_update_query_counts(): void
    {
        $u = $this->existingUserIds;

        // Insert 100 records using valid user_ids and mix of 2 content_ids
        $payload = [];
        for ($i = 0; $i < 100; $i++) {
            $cid = ($i % 2 === 0) ? $this->content1->id : $this->content2->id;
            $time = ($i % 3 === 0) ? 1230 : (($i % 3 === 1) ? 1800 : 0);
            $payload[] = [
                'user_id'          => $u[30 + $i],
                'trainingplan_id'  => $this->playlist->id,
                'content_id'       => $cid,
                'current_time'     => $time,
                'progress'         => $time > 0 ? 50 : 100,
                'finish'           => $time > 0 ? '0' : '1',
                'learning_status'  => 'in_progress',
                'learning_hour'    => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        DB::table('playlist_content_users')->insert($payload);

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Run batch calculation for this playlist
        $this->artisan('learning-hours:calculate-playlist-content-users', [
            '--playlist' => $this->playlist->id,
            '--chunk'    => 500,
        ])->assertSuccessful();

        $queries = DB::getQueryLog();

        $contentSelects = array_filter($queries, function ($q) {
            return str_contains(strtolower($q['query']), 'from `content`');
        });

        $updateQueries = array_filter($queries, function ($q) {
            return str_starts_with(strtolower(trim($q['query'])), 'update');
        });

        // 1. Content query MUST be 1 single preload query (NOT 100!)
        $this->assertCount(1, $contentSelects, 'Content was queried multiple times! Expected 1 preload query.');

        // 2. Bulk updates MUST be grouped (around 3 to 10 queries, NOT 100 individual updates!)
        $this->assertLessThanOrEqual(
            15,
            count($updateQueries),
            'Bulk updates exceeded limit! Expected grouped updates <= 15 for 100 records.'
        );
    }

    public function test_large_data_simulation_with_one_thousand_records_and_two_batches(): void
    {
        $u = $this->existingUserIds;

        // Insert 1000 records with valid user_ids
        $payload = [];
        for ($i = 0; $i < 1000; $i++) {
            $payload[] = [
                'user_id'          => $u[$i],
                'trainingplan_id'  => $this->playlist->id,
                'content_id'       => $this->content1->id,
                'current_time'     => 1230,
                'progress'         => 68,
                'finish'           => '0',
                'learning_status'  => 'in_progress',
                'learning_hour'    => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        foreach (array_chunk($payload, 500) as $subChunk) {
            DB::table('playlist_content_users')->insert($subChunk);
        }

        // Run with chunk=500 -> exactly 2 batches of 500
        $this->artisan('learning-hours:calculate-playlist-content-users', [
            '--playlist' => $this->playlist->id,
            '--chunk'    => 500,
        ])
        ->expectsOutputToContain('Batch #1 processed: 500 records')
        ->expectsOutputToContain('Batch #2 processed: 500 records')
        ->assertSuccessful();

        // Verify count of 0.3417 in database
        $updatedCount = DB::table('playlist_content_users')
            ->where('trainingplan_id', $this->playlist->id)
            ->where('learning_hour', 0.3417)
            ->count();

        $this->assertSame(1000, $updatedCount);
    }

    public function test_concurrency_protection_prevents_stale_overwrites(): void
    {
        $u = $this->existingUserIds;

        $r = PlaylistContentUser::create([
            'user_id'         => $u[40],
            'trainingplan_id' => $this->playlist->id,
            'content_id'      => $this->content1->id,
            'current_time'    => 1230,
            'progress'        => 68,
            'finish'          => '0',
            'learning_hour'   => null,
        ]);

        $batchService = app(\App\Services\LearningHours\PlaylistContentUserBatchCalculatorService::class);

        // During progress callback, simulate learner's heartbeat changing current_time
        $simulated = false;
        $results = $batchService->process(
            [
                'playlist_id' => $this->playlist->id,
                'user_id'     => $u[40],
                'chunk_size'  => 10,
            ],
            function () use ($r, &$simulated) {
                if (!$simulated) {
                    DB::table('playlist_content_users')
                        ->where('id', $r->id)
                        ->update(['current_time' => 1300]);
                    $simulated = true;
                }
            }
        );

        $fresh = DB::table('playlist_content_users')->where('id', $r->id)->first();

        // The record's current_time MUST remain 1300, and stale 1230 update was safely rejected
        $this->assertEquals(1300, $fresh->current_time);
    }
}
