<?php

namespace Tests\Unit;

use App\Models\Content;
use App\Models\ContentUser;
use Carbon\Carbon;
use Tests\TestCase;

class ContentDeadlineTest extends TestCase
{
    public function test_expiration_uses_to_date_and_keeps_the_deadline_day_visible(): void
    {
        Carbon::setTestNow('2026-07-30 12:00:00');

        try {
            $this->assertFalse($this->content(false, null)->isExpired());
            $this->assertFalse($this->content(true, null)->isExpired());
            $this->assertFalse($this->content(true, '2026-07-31')->isExpired());
            $this->assertFalse($this->content(true, '2026-07-30')->isExpired());
            $this->assertTrue($this->content(true, '2026-07-29')->isExpired());
            $this->assertTrue($this->content(true, '2026-07-01')->isExpired());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_not_expired_scope_builds_the_legacy_safe_visible_rule(): void
    {
        $query = Content::query()->notExpired('2026-07-30');
        $sql = $query->toSql();

        $this->assertStringContainsString('has_deadline', $sql);
        $this->assertStringContainsString('to_date', $sql);
        $this->assertStringContainsString('is null', strtolower($sql));
        $this->assertStringContainsString('>=', $sql);
        $this->assertContains('2026-07-30', $query->getBindings());
        $this->assertStringNotContainsString('content.date', $sql);
    }

    public function test_content_user_availability_scope_reuses_the_content_deadline_rule(): void
    {
        Carbon::setTestNow('2026-07-30 12:00:00');

        try {
            $query = ContentUser::query()
                ->where('user_id', 936)
                ->where('finish', '0')
                ->withAvailableContent();
            $sql = $query->toSql();

            $this->assertStringContainsString('user_id', $sql);
            $this->assertStringContainsString('finish', $sql);
            $this->assertStringContainsString('exists', strtolower($sql));
            $this->assertStringContainsString('has_deadline', $sql);
            $this->assertStringContainsString('to_date', $sql);
            $this->assertContains('2026-07-30', $query->getBindings());
        } finally {
            Carbon::setTestNow();
        }
    }

    private function content(bool $hasDeadline, ?string $toDate): Content
    {
        return new Content([
            'has_deadline' => $hasDeadline,
            'to_date' => $toDate,
        ]);
    }
}
