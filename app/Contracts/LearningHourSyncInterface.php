<?php

namespace App\Contracts;

use App\DataTransferObjects\LearningHourSyncResult;

interface LearningHourSyncInterface
{
    public function syncAll(): array;

    public function syncSingle(mixed $model): LearningHourSyncResult;
}
