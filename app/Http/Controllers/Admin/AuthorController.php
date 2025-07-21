<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Traits\SortableTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthorController extends Controller
{
    use SortableTrait;
    /**
     * Display a listing of authors
     */
    public function index(Request $request)
    {
        $query = Author::withCount('stories');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%");
        }

        // Apply sorting
        $allowedSorts = ['name', 'created_at', 'updated_at'];
        $query = $this->applySorting($query, $request, $allowedSorts, 'name', 'asc');

        $authors = $query->paginate(20);

        return view('admin.authors.index', compact('authors'));
    }

    /**
     * Show the form for creating a new author
     */
    public function create()
    {
        $author = new Author(); // Create empty author instance for form
        return view('admin.authors.create', compact('author'));
    }

    /**
     * Store a newly created author
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:authors,slug',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:255',
            'achievements' => 'nullable|string',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('authors', 'public');
        }

        // Convert achievements to array
        if (!empty($validated['achievements'])) {
            $validated['achievements'] = array_map('trim', explode("\n", $validated['achievements']));
        }

        Author::create($validated);

        return redirect()->route('admin.authors.index')
            ->with('success', 'Tác giả đã được tạo thành công!');
    }

    /**
     * Display the specified author
     */
    public function show(Author $author)
    {
        $author->load(['stories' => function($query) {
            $query->withCount('chapters')->orderBy('created_at', 'desc');
        }]);

        return view('admin.authors.show', compact('author'));
    }

    /**
     * Show the form for editing the specified author
     */
    public function edit(Author $author)
    {
        return view('admin.authors.edit', compact('author'));
    }

    /**
     * Update the specified author
     */
    public function update(Request $request, Author $author)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:authors,slug,' . $author->id,
            'bio' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:255',
            'achievements' => 'nullable|string',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($author->avatar) {
                Storage::disk('public')->delete($author->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('authors', 'public');
        }

        // Convert achievements to array
        if (!empty($validated['achievements'])) {
            $validated['achievements'] = array_map('trim', explode("\n", $validated['achievements']));
        } else {
            $validated['achievements'] = null;
        }

        $author->update($validated);

        return redirect()->route('admin.authors.index')
            ->with('success', 'Thông tin tác giả đã được cập nhật!');
    }

    /**
     * Remove the specified author
     */
    public function destroy(Author $author)
    {
        // Check if author has stories
        if ($author->stories()->count() > 0) {
            return redirect()->route('admin.authors.index')
                ->with('error', 'Không thể xóa tác giả này vì còn có truyện liên quan!');
        }

        // Delete avatar
        if ($author->avatar) {
            Storage::disk('public')->delete($author->avatar);
        }

        $author->delete();

        return redirect()->route('admin.authors.index')
            ->with('success', 'Tác giả đã được xóa thành công!');
    }

    /**
     * Toggle author status
     */
    public function toggleStatus(Author $author)
    {
        $author->update(['is_active' => !$author->is_active]);

        $status = $author->is_active ? 'kích hoạt' : 'vô hiệu hóa';
        return redirect()->back()
            ->with('success', "Đã {$status} tác giả {$author->name}!");
    }
}
