<?php
namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Story;
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
            'total_stories' => \App\Models\Story::count(),
            'total_chapters' => \App\Models\Chapter::count(),
            'total_users' => \App\Models\User::count(),
            'chapters_with_audio' => \App\Models\Chapter::whereNotNull('audio_file_path')->count(),
        ];

        return view('dashboard.index', compact('stats'));
    }

}
