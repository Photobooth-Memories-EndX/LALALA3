<?php

namespace Tests\Unit;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\Learning\LearningHourService;
use PHPUnit\Framework\TestCase;

class LearningHourServiceTest extends TestCase
{
    public function test_it_applies_the_learning_hour_bucket_rule(): void
    {
        $service = new LearningHourService();

        $this->assertSame(0.5, $service->calculate('00:06:26'));
        $this->assertSame(0.5, $service->calculate('00:30:00'));
        $this->assertSame(1.0, $service->calculate('00:30:01'));
    }

    public function test_canonical_total_counts_each_completed_content_once(): void
    {
        $service = new LearningHourService();
        $user = new User();
        $user->setAttribute('id', 10);

        $first = $this->contentUser(1, '00:06:26', true);
        $duplicate = $this->contentUser(1, '00:06:26', true);
        $second = $this->contentUser(2, '00:30:01', true);
        $unfinished = $this->contentUser(3, '10:00:00', false);

        $this->assertSame(
            1.5,
            $service->getTotalLearningHours($user, collect([$first, $duplicate, $second, $unfinished]))
        );
    }

    private function contentUser(int $contentId, string $duration, bool $finished): ContentUser
    {
        $content = new Content();
        $content->setAttribute('id', $contentId);
        $content->setAttribute('duration', $duration);

        $contentUser = new ContentUser();
        $contentUser->setAttribute('content_id', $contentId);
        $contentUser->setAttribute('finish', $finished ? '1' : '0');
        $contentUser->setRelation('content', $content);

        return $contentUser;
    }
}
