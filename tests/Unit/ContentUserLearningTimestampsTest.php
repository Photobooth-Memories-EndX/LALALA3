<?php

namespace Tests\Unit;

use App\Models\ContentUser;
use Tests\TestCase;

class ContentUserLearningTimestampsTest extends TestCase
{
    public function test_it_sets_started_at_when_learning_record_is_created(): void
    {
        $contentUser = new ContentUser(['finish' => '0']);

        $this->dispatchModelEvent('creating', $contentUser);

        $this->assertNotNull($contentUser->started_at);
        $this->assertNull($contentUser->finished_at);
    }

    public function test_it_records_completion_once_when_finish_changes(): void
    {
        $contentUser = new ContentUser(['finish' => '0']);
        $contentUser->syncOriginal();
        $contentUser->finish = '1';

        $this->dispatchModelEvent('updating', $contentUser);
        $finishedAt = $contentUser->finished_at;

        $contentUser->syncOriginal();
        $contentUser->progress = 100;
        $this->dispatchModelEvent('updating', $contentUser);

        $this->assertNotNull($finishedAt);
        $this->assertTrue($finishedAt->equalTo($contentUser->finished_at));
    }

    private function dispatchModelEvent(string $event, ContentUser $contentUser): void
    {
        ContentUser::getEventDispatcher()->dispatch(
            'eloquent.' . $event . ': ' . ContentUser::class,
            $contentUser
        );
    }
}
