<?php
namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Story;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $storyCount = \App\Models\Story::count();
        $chapterCount = \App\Models\Chapter::count();
        return view('dashboard.index', compact('storyCount', 'chapterCount'));
    }

}
