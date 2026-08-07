<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use App\Services\LearningHours\LearningHourConverterService;
use Tests\TestCase;

class LearningHoursSynchronizationTest extends TestCase
{
    public function test_converter_service_rules(): void
    {
        $converter = new LearningHourConverterService();

        $this->assertSame(0.0, $converter->convert('00:00:00'));
        $this->assertSame(0.5, $converter->convert('00:29:21'));
        $this->assertSame(1.0, $converter->convert('00:31:59'));
        $this->assertSame(1.0, $converter->convert('00:59:21'));
        $this->assertSame(1.0, $converter->convert('00:59:59'));
        $this->assertSame(1.5, $converter->convert('01:01:21'));
        $this->assertSame(2.0, $converter->convert('01:32:00'));
        $this->assertSame(2.0, $converter->convert('01:59:59'));
        $this->assertSame(2.5, $converter->convert('02:04:00'));
        $this->assertSame(3.0, $converter->convert('02:31:00'));
        $this->assertSame(3.5, $converter->convert('03:01:00'));
    }

    public function test_single_item_sync_methods(): void
    {
        $converter = new LearningHourConverterService();
        $contentService = new \App\Services\LearningHours\ContentLearningHourService($converter);

        $content = new Content([
            'duration' => '01:32:00',
            'learning_hours' => 0.0,
        ]);

        $result = $contentService->syncSingleContent($content);

        $this->assertTrue($result->success);
        $this->assertSame(2.0, $result->learningHours);
        $this->assertSame('Learning Hours synchronized successfully.', $result->message);

        $array = $result->toArray();
        $this->assertTrue($array['success']);
        $this->assertSame(2.0, $array['learning_hours']);
    }

    public function test_unauthenticated_user_cannot_access_synchronize_endpoint(): void
    {
        $response = $this->postJson('/manage/content/test-slug/synchronize-learning-hours');
        $response->assertStatus(401);
    }
}
