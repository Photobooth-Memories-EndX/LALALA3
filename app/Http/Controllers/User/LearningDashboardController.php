<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\LearningController;

/**
 * Controller alias for User Learning Dashboard module compatibility.
 */
class LearningDashboardController extends LearningController
{
    /**
     * Delegate ongoing Non KP OWL fragment.
     */
    public function nonKpOwl()
    {
        return app(\App\Http\Controllers\LearningOngoingController::class)->nonKpOwl();
    }
}
