<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\Content;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DuplicateActiveContentAssessmentsToPlaylistsCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_default_invocation_is_safe_dry_run_and_modifies_zero_records(): void
    {
        $assessmentCountBefore = Assesment::count();
        $questionCountBefore   = AssesmentQuestion::count();

        $this->artisan('assessment:duplicate-active-to-playlists')
            ->assertSuccessful()
            ->expectsOutputToContain('AUTO DISCOVERY ASSESSMENT CONTENT → PLAYLIST')
            ->expectsOutputToContain('DRY RUN ONLY.')
            ->expectsOutputToContain('NO DATABASE CHANGES.')
            ->expectsOutputToContain('Database Modified        : 0');

        $this->assertSame($assessmentCountBefore, Assesment::count());
        $this->assertSame($questionCountBefore, AssesmentQuestion::count());
    }

    public function test_dry_run_flag_detects_complete_and_incomplete_assessments(): void
    {
        $this->artisan('assessment:duplicate-active-to-playlists', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('AUTO DISCOVERY ASSESSMENT CONTENT → PLAYLIST')
            ->expectsOutputToContain('Ready for Creation')
            ->expectsOutputToContain('Ready for Repair')
            ->expectsOutputToContain('Already Exists Complete')
            ->expectsOutputToContain('Database Modified        : 0');
    }

    public function test_discovery_skips_inactive_assessments(): void
    {
        $content = Content::create([
            'title' => 'Test Inactive Content ' . uniqid(),
            'slug'  => 'test-inactive-content-' . uniqid(),
        ]);

        $playlist = TrainingPlan::create([
            'title' => 'Test Inactive Playlist ' . uniqid(),
            'slug'  => 'test-inactive-playlist-' . uniqid(),
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $playlist->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        $inactiveAssessment = Assesment::create([
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'status'                => 'NOT',
            'content_id'            => $content->id,
            'title'                 => 'Inactive Assessment ' . uniqid(),
        ]);

        $this->artisan('assessment:duplicate-active-to-playlists', ['--dry-run' => true])
            ->assertSuccessful()
            ->doesntExpectOutputToContain("Source Assessment : #{$inactiveAssessment->id}");
    }

    public function test_discovery_flags_multiple_active_assessments_as_conflict(): void
    {
        $content = Content::create([
            'title' => 'Test Conflict Content ' . uniqid(),
            'slug'  => 'test-conflict-content-' . uniqid(),
        ]);

        $playlist = TrainingPlan::create([
            'title' => 'Test Conflict Playlist ' . uniqid(),
            'slug'  => 'test-conflict-playlist-' . uniqid(),
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $playlist->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        $assessment1 = Assesment::create([
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'content_id'            => $content->id,
            'title'                 => 'Conflict Assessment 1 ' . uniqid(),
        ]);

        $assessment2 = Assesment::create([
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'content_id'            => $content->id,
            'title'                 => 'Conflict Assessment 2 ' . uniqid(),
        ]);

        $this->artisan('assessment:duplicate-active-to-playlists', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain("Content           : #{$content->id}")
            ->expectsOutputToContain('SKIPPED - MULTIPLE ACTIVE ASSESSMENTS');
    }

    public function test_repairs_incomplete_existing_assessment_in_place(): void
    {
        // Setup a test content and question bank
        $qb = QuestionBank::create([
            'title'        => 'Chapter 99 - Special Testing Subject',
            'slug'         => 'chapter-99-special-' . uniqid(),
            'created_date' => now()->toDateString(),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Question::create([
                'question_bank_id' => $qb->id,
                'question'         => "Question $i text",
                'option'           => ['A' => 'Opt A', 'B' => 'Opt B'],
                'answer'           => 'A',
            ]);
        }

        $content = Content::create([
            'title' => 'Chapter 99 - Special Testing Subject',
            'slug'  => 'chapter-99-special-' . uniqid(),
        ]);

        $playlist = TrainingPlan::create([
            'title' => 'Special Testing Playlist ' . uniqid(),
            'slug'  => 'special-testing-playlist-' . uniqid(),
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $playlist->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        // Source assessment (shell)
        $source = Assesment::create([
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'content_id'            => $content->id,
            'title'                 => 'Chapter 99 - Special Testing Subject',
        ]);

        // Existing playlist assessment (INCOMPLETE shell: 0 questions, 0 QBs, null pass)
        $incompletePlaylistAssessment = Assesment::create([
            'type'                  => 'content',
            'assasment_content_for' => 'playlist',
            'status'                => 'OK',
            'content_id'            => $content->id,
            'trainingplan_id'       => $playlist->id,
            'title'                 => $source->title,
            'passing_grade'         => null,
            'timer'                 => 'n',
        ]);

        $originalId = $incompletePlaylistAssessment->id;

        // 1. Dry run should detect as INCOMPLETE and READY FOR REPAIR
        $this->artisan('assessment:duplicate-active-to-playlists', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain("#{$originalId}")
            ->expectsOutputToContain('Assessment Status:')
            ->expectsOutputToContain('INCOMPLETE')
            ->expectsOutputToContain('Action:')
            ->expectsOutputToContain('READY FOR REPAIR');

        // 2. Run execute
        $this->artisan('assessment:duplicate-active-to-playlists', ['--execute' => true])
            ->assertSuccessful();

        // 3. Verify IN-PLACE REPAIR: ID must NOT change, no 3rd assessment created!
        $matchingAssessments = Assesment::where('assasment_content_for', 'playlist')
            ->where('trainingplan_id', $playlist->id)
            ->where('content_id', $content->id)
            ->get();

        $this->assertCount(1, $matchingAssessments, 'Exactly 1 playlist assessment should exist (repaired in-place, not duplicated into 3rd record)');
        $repaired = $matchingAssessments->first();
        $this->assertSame($originalId, $repaired->id, 'The existing record ID must be preserved during repair');
        $this->assertSame(80, (int) $repaired->passing_grade);
        $this->assertSame('y', $repaired->timer);
        $this->assertSame(1, $repaired->assessment_question_banks()->count());
        $this->assertSame(5, $repaired->questions()->count());

        // Child questions belong exclusively to $originalId
        $this->assertSame(5, AssesmentQuestion::where('assesment_id', $originalId)->count());

        // 4. Idempotency test: subsequent dry-run now reports COMPLETE!
        $this->artisan('assessment:duplicate-active-to-playlists', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain("#{$originalId}")
            ->expectsOutputToContain('Assessment Status:')
            ->expectsOutputToContain('COMPLETE')
            ->expectsOutputToContain('Already Exists Complete');
    }
}
