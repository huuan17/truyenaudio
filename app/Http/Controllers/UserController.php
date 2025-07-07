<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Hiển thị danh sách users
     */
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(15);
        return view('users.index', compact('users'));
    }

    /**
     * Hiển thị form tạo user mới
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Lưu user mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,user',
        ], [
            'name.required' => 'Tên là bắt buộc',
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã tồn tại',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'role.required' => 'Vai trò là bắt buộc',
            'role.in' => 'Vai trò không hợp lệ',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Tạo user thành công!');
    }

    /**
     * Hiển thị thông tin user
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Hiển thị form chỉnh sửa user
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Cập nhật user
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:admin,user',
        ], [
            'name.required' => 'Tên là bắt buộc',
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã tồn tại',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'role.required' => 'Vai trò là bắt buộc',
            'role.in' => 'Vai trò không hợp lệ',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'Cập nhật user thành công!');
    }

    /**
     * Xóa user
     */
    public function destroy(User $user)
    {
        // Không cho phép xóa chính mình
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Không thể xóa chính mình!');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Xóa user thành công!');
    }
}
