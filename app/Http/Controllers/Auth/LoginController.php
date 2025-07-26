<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Hiển thị form login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Xử lý login
     */
    public function login(Request $request)
    {
        // Log login attempt
        \Log::info('Login attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'csrf_token_present' => $request->has('_token'),
            'csrf_token_valid' => hash_equals($request->session()->token(), $request->input('_token', '')),
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không đúng định dạng',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            // Regenerate session to prevent session fixation
            $request->session()->regenerate();

            // Clear any previous intended URL
            $request->session()->forget('url.intended');

            // Log successful login
            \Log::info('Login successful', [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'is_admin' => Auth::user()->isAdmin(),
            ]);

            // Force redirect to admin dashboard
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Đăng nhập thành công!');
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ])->withInput($request->except('password'));
    }

    /**
     * Xử lý logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')
            ->with('success', 'Đăng xuất thành công!');
    }
}
