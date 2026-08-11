<?php

namespace App\DataTransferObjects;

class LearningHourSyncResult
{
    public function __construct(
        public readonly bool $success,
        public readonly bool $updated,
        public readonly float $learningHours,
        public readonly string $message,
        public readonly array $meta = []
    ) {}

    public function toArray(): array
    {
        return array_merge([
            'success'        => $this->success,
            'updated'        => $this->updated,
            'learning_hours' => $this->learningHours,
            'message'        => $this->message,
        ], $this->meta);
    }
}
