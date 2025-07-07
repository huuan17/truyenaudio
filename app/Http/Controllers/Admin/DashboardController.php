<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $stats = [
            'total_stories' => Story::count(),
            'total_chapters' => Chapter::count(),
            'total_users' => User::count(),
            'chapters_with_audio' => Chapter::whereNotNull('audio_file_path')->count(),
        ];

        return view('admin.dashboard.index', compact('stats'));
    }
}
