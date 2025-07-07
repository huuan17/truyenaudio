<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $genres = Genre::latest()->paginate(10);
        return view('admin.genres.index', compact('genres'));
    }

    public function create()
    {
        $genre = new Genre();
        return view('admin.genres.create', compact('genre'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name',
        ]);

        Genre::create($data);

        return redirect()->route('admin.genres.index')->with('success', 'Thêm thể loại thành công');
    }

    public function show(Genre $genre)
    {
        return view('admin.genres.show', compact('genre'));
    }

    public function edit(Genre $genre)
    {
        return view('admin.genres.edit', compact('genre'));
    }

    public function update(Request $request, Genre $genre)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
        ]);

        $genre->update($data);

        return redirect()->route('admin.genres.index')->with('success', 'Cập nhật thể loại thành công');
    }

    public function destroy(Genre $genre)
    {
        $genre->delete();
        return redirect()->route('admin.genres.index')->with('success', 'Xóa thể loại thành công');
    }
}
