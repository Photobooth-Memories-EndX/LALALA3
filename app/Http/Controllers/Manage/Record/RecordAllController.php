<?php

namespace App\Http\Controllers\Manage\Record;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RecordAllController extends Controller
{
    public function index(Request $request)
    {
        return view('main.record.all', [
            'title' => 'All KP OWL',
            'subtitle' => 'Combined Online & Offline Learning Record',
        ]);
    }
}
