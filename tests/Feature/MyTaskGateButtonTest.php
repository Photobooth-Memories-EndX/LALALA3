<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MyTaskGateButtonTest extends TestCase
{
    public function test_my_task_gate_button_disabled_and_matches_certificate_behavior_when_locked(): void
    {
        $htmlLocked = Blade::render(
            "@include('components.my-task-gate-button', ['planId' => 42])",
            ['playlistUiState' => ['learning_progress' => 35]]
        );

        $this->assertStringContainsString('is-disabled', $htmlLocked);
        $this->assertStringContainsString('certificate-action-wrapper', $htmlLocked);
        $this->assertStringContainsString('disabled', $htmlLocked);
        $this->assertStringContainsString('aria-disabled="true"', $htmlLocked);
        $this->assertStringContainsString('ti-lock', $htmlLocked);
        $this->assertStringContainsString('title="Complete the course 100% first to unlock Task."', $htmlLocked);
        $this->assertStringNotContainsString('onclick="alert(', $htmlLocked);
    }

    public function test_my_task_gate_button_enabled_when_completed(): void
    {
        $htmlCompleted = Blade::render(
            "@include('components.my-task-gate-button', ['planId' => 42])",
            ['playlistUiState' => ['learning_progress' => 100]]
        );

        $this->assertStringNotContainsString('disabled', $htmlCompleted);
        $this->assertStringNotContainsString('ti-lock', $htmlCompleted);
        $this->assertStringContainsString('ti-list-check', $htmlCompleted);
        $this->assertStringContainsString('/my-task/unlock-playlist/42', $htmlCompleted);
    }
}

