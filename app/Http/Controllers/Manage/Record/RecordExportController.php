<?php

namespace App\Http\Controllers\Manage\Record;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RecordExportController extends Controller
{
    public function index(Request $request)
    {
        return view('main.record.export-all', [
            'title' => 'Export KP OWL',
            'subtitle' => 'Learning Record Export Center',
        ]);
    }
}
