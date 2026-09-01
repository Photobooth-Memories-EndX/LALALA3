<?php

namespace App\Http\Controllers\Manage\LearningHours;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LearningHoursLandingController extends Controller
{
    /**
     * Display the Learning Hours parent landing page.
     *
     * Pure navigation hub without heavy queries or batch calculations.
     */
    public function index(): View
    {
        return view('manage.learning-hours.index');
    }
}
