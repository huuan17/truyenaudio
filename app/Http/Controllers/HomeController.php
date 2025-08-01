<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\Genre;
use App\Models\Chapter;
use App\Models\Author;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Hiển thị trang chủ
     */
    public function index()
    {
        // Truyện hot (có nhiều chapter và được cập nhật gần đây)
        $hotStories = Story::visible()
            ->withCount('chapters')
            ->whereHas('chapters')
            ->orderBy('updated_at', 'desc')
            ->limit(12)
            ->get();

        // Truyện mới cập nhật
        $recentStories = Story::visible()
            ->with(['chapters' => function($query) {
                $query->orderBy('chapter_number', 'desc')->limit(1);
            }])
            ->whereHas('chapters')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        // Truyện hoàn thành
        $completedStories = Story::visible()
            ->where('status', 'completed')
            ->withCount('chapters')
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get();

        // Thể loại phổ biến
        $popularGenres = Genre::public()->withCount('stories')
            ->having('stories_count', '>', 0)
            ->orderBy('stories_count', 'desc')
            ->limit(20)
            ->get();

        return view('frontend.home', compact(
            'hotStories', 
            'recentStories', 
            'completedStories', 
            'popularGenres'
        ));
    }

    /**
     * Hiển thị danh sách truyện theo thể loại
     */
    public function genre(Request $request, $slug)
    {
        $genre = Genre::where('slug', $slug)->public()->firstOrFail();
        
        $query = $genre->stories()->visible()->with(['chapters' => function($q) {
            $q->orderBy('chapter_number', 'desc')->limit(1);
        }]);

        // Filter theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sắp xếp
        $sortBy = $request->get('sort', 'updated_at');
        $sortOrder = $request->get('order', 'desc');
        
        $stories = $query->orderBy($sortBy, $sortOrder)->paginate(20);

        return view('frontend.genre', compact('genre', 'stories'));
    }

    /**
     * Hiển thị chi tiết truyện
     */
    public function story($slug)
    {
        $story = Story::visible()
            ->where('slug', $slug)
            ->with(['genres', 'authorModel'])
            ->firstOrFail();

        // Phân trang chapters
        $chapters = Chapter::where('story_id', $story->id)
            ->orderBy('chapter_number', 'asc')
            ->paginate(20); // 20 chapters per page

        // Truyện liên quan (cùng thể loại)
        $relatedStories = Story::visible()
            ->whereHas('genres', function($query) use ($story) {
                $query->whereIn('genres.id', $story->genres->pluck('id'));
            })
            ->where('id', '!=', $story->id)
            ->limit(6)
            ->get();

        return view('frontend.story', compact('story', 'chapters', 'relatedStories'));
    }

    /**
     * Đọc chapter
     */
    public function chapter($storySlug, $chapterNumber)
    {
        $story = Story::visible()->where('slug', $storySlug)->firstOrFail();
        
        $chapter = Chapter::where('story_id', $story->id)
            ->where('chapter_number', $chapterNumber)
            ->firstOrFail();

        // Chapter trước và sau
        $prevChapter = Chapter::where('story_id', $story->id)
            ->where('chapter_number', '<', $chapterNumber)
            ->orderBy('chapter_number', 'desc')
            ->first();

        $nextChapter = Chapter::where('story_id', $story->id)
            ->where('chapter_number', '>', $chapterNumber)
            ->orderBy('chapter_number', 'asc')
            ->first();

        return view('frontend.chapter', compact('story', 'chapter', 'prevChapter', 'nextChapter'));
    }

    /**
     * Hiển thị danh sách tác giả
     */
    public function authors()
    {
        $authors = Author::active()
            ->withCount('publishedStories')
            ->orderBy('name')
            ->paginate(12);

        return view('frontend.authors', compact('authors'));
    }

    /**
     * Hiển thị chi tiết tác giả
     */
    public function author($slug)
    {
        $author = Author::active()
            ->where('slug', $slug)
            ->firstOrFail();

        // Lấy truyện của tác giả với phân trang (sử dụng stories thay vì publishedStories để test)
        $stories = $author->stories()
            ->where('is_public', true)
            ->where('is_active', true)
            ->with(['genres'])
            ->withCount('chapters')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Thống kê
        $stats = [
            'total_stories' => $author->stories()->where('is_public', true)->where('is_active', true)->count(),
            'total_chapters' => $author->stories()->where('is_public', true)->where('is_active', true)->withCount('chapters')->get()->sum('chapters_count'),
            'latest_story' => $author->stories()->where('is_public', true)->where('is_active', true)->latest()->first(),
        ];

        return view('frontend.author', compact('author', 'stories', 'stats'));
    }

    /**
     * Tìm kiếm truyện
     */
    public function search(Request $request)
    {
        $keyword = $request->get('q');
        $stories = collect();

        if ($keyword) {
            $stories = Story::visible()
                ->where(function($query) use ($keyword) {
                    $query->where('title', 'LIKE', "%{$keyword}%")
                          ->orWhere('author', 'LIKE', "%{$keyword}%")
                          ->orWhere('description', 'LIKE', "%{$keyword}%");
                })
                ->with(['chapters' => function($q) {
                    $q->orderBy('chapter_number', 'desc')->limit(1);
                }])
                ->paginate(20);
        }

        return view('frontend.search', compact('stories', 'keyword'));
    }

    /**
     * Danh sách truyện hot
     */
    public function hot()
    {
        $stories = Story::visible()
            ->withCount('chapters')
            ->whereHas('chapters')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('frontend.hot', compact('stories'));
    }

    /**
     * Danh sách truyện hoàn thành
     */
    public function completed()
    {
        $stories = Story::visible()
            ->where('status', 'completed')
            ->withCount('chapters')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('frontend.completed', compact('stories'));
    }

    /**
     * Danh sách truyện mới cập nhật
     */
    public function recent()
    {
        $stories = Story::visible()
            ->with(['chapters' => function($query) {
                $query->orderBy('chapter_number', 'desc')->limit(1);
            }])
            ->whereHas('chapters')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('frontend.recent', compact('stories'));
    }
}
