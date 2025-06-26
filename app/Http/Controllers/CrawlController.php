<?php
namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;

class CrawlController extends Controller
{
    public function index()
    {
        $stories = Story::orderBy('id', 'desc')->get();
        return view('crawl.index', compact('stories'));
    }

    public function run(Request $request, Story $story)
    {
        $start = $request->input('start_chapter', $story->start_chapter);
        $end = $request->input('end_chapter', $story->end_chapter);

        // Dispatch Job, hoặc gọi command ở đây nếu muốn
        // Artisan::call('crawl:stories', [...]);

        return back()->with('success', "Đã gửi yêu cầu crawl truyện '{$story->title}' từ chương $start đến $end.");
    }
}
