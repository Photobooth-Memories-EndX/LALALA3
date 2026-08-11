<?php

namespace Tests\Unit;

use App\Models\ContentUser;
use App\Models\TrainingPlan;
use PHPUnit\Framework\TestCase;

class PlaylistProgressTest extends TestCase
{
    public function test_it_calculates_progress_percentage_with_strict_finished_item_ratio(): void
    {
        // 2 of 7 items completed = 28.57% -> 29%
        $this->assertSame(29, TrainingPlan::calculateProgressPercentage(2, 7));

        // 0 of 7 items completed = 0%
        $this->assertSame(0, TrainingPlan::calculateProgressPercentage(0, 7));

        // 7 of 7 items completed = 100%
        $this->assertSame(100, TrainingPlan::calculateProgressPercentage(7, 7));

        // Edge case: 0 total items = 0%
        $this->assertSame(0, TrainingPlan::calculateProgressPercentage(0, 0));

        // 1 of 3 items completed = 33.33% -> 33%
        $this->assertSame(33, TrainingPlan::calculateProgressPercentage(1, 3));
    }

    public function test_it_determines_finished_status_from_finish_flag_or_full_progress(): void
    {
        $finishedByFlag = new ContentUser(['finish' => 1, 'progress' => 50]);
        $this->assertTrue($finishedByFlag->isFinished());

        $finishedByProgress = new ContentUser(['finish' => 0, 'progress' => 100]);
        $this->assertTrue($finishedByProgress->isFinished());

        $unfinished = new ContentUser(['finish' => 0, 'progress' => 50]);
        $this->assertFalse($unfinished->isFinished());
    }
}
