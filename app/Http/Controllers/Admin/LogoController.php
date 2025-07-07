<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogoController extends Controller
{
    /**
     * Hiển thị danh sách logo
     */
    public function index()
    {
        $logoDir = storage_path('app/logos');
        $logos = [];
        
        if (File::isDirectory($logoDir)) {
            $logoFiles = File::glob($logoDir . '/*.{png,jpg,jpeg,gif,svg}', GLOB_BRACE);
            
            foreach ($logoFiles as $logoPath) {
                $logos[] = [
                    'name' => basename($logoPath),
                    'path' => $logoPath,
                    'size' => File::size($logoPath),
                    'size_formatted' => $this->formatBytes(File::size($logoPath)),
                    'created' => File::lastModified($logoPath),
                    'created_formatted' => date('d/m/Y H:i:s', File::lastModified($logoPath)),
                    'url' => route('admin.logo.serve', basename($logoPath))
                ];
            }
            
            // Sắp xếp theo thời gian tạo mới nhất
            usort($logos, function($a, $b) {
                return $b['created'] - $a['created'];
            });
        }

        return view('admin.logos.index', compact('logos'));
    }

    /**
     * Upload logo mới
     */
    public function upload(Request $request)
    {
        $request->validate([
            'logo_file' => 'required|file|mimes:png,jpg,jpeg,gif,svg|max:5120', // 5MB
            'logo_name' => 'nullable|string|max:100'
        ]);

        try {
            $file = $request->file('logo_file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            
            // Tạo tên file
            $fileName = $request->logo_name 
                ? Str::slug($request->logo_name) . '.' . $extension
                : Str::slug($originalName) . '_' . time() . '.' . $extension;

            // Tạo thư mục nếu chưa có
            $logoDir = storage_path('app/logos');
            if (!File::isDirectory($logoDir)) {
                File::makeDirectory($logoDir, 0755, true);
            }

            // Lưu file
            $file->move($logoDir, $fileName);

            return redirect()->route('admin.logos.index')
                ->with('success', "Đã upload logo thành công: {$fileName}");

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Xóa logo
     */
    public function delete(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        try {
            $filePath = storage_path('app/logos/' . $request->filename);
            
            if (File::exists($filePath)) {
                File::delete($filePath);
                return response()->json([
                    'success' => true,
                    'message' => "Đã xóa logo: {$request->filename}"
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Logo không tồn tại'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa logo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Serve logo file
     */
    public function serve($filename)
    {
        $filePath = storage_path('app/logos/' . $filename);
        
        if (!File::exists($filePath)) {
            abort(404, 'Logo không tồn tại');
        }

        $mimeType = File::mimeType($filePath);
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000'
        ]);
    }

    /**
     * Download logo
     */
    public function download($filename)
    {
        $filePath = storage_path('app/logos/' . $filename);
        
        if (!File::exists($filePath)) {
            abort(404, 'Logo không tồn tại');
        }

        return response()->download($filePath);
    }

    /**
     * Lấy danh sách logo cho API
     */
    public function getLogos()
    {
        $logoDir = storage_path('app/logos');
        $logos = [];
        
        if (File::isDirectory($logoDir)) {
            $logoFiles = File::glob($logoDir . '/*.{png,jpg,jpeg,gif,svg}', GLOB_BRACE);
            
            foreach ($logoFiles as $logoPath) {
                $logos[] = [
                    'name' => basename($logoPath),
                    'display_name' => pathinfo(basename($logoPath), PATHINFO_FILENAME),
                    'url' => route('admin.logo.serve', basename($logoPath)),
                    'path' => $logoPath
                ];
            }
        }

        return response()->json($logos);
    }

    /**
     * Format bytes thành đơn vị dễ đọc
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Preview logo với các vị trí
     */
    public function preview(Request $request)
    {
        $request->validate([
            'logo' => 'required|string',
            'position' => 'required|in:top-left,top-right,bottom-left,bottom-right,center',
            'size' => 'required|numeric|between:10,500'
        ]);

        $logoPath = storage_path('app/logos/' . $request->logo);
        
        if (!File::exists($logoPath)) {
            return response()->json(['error' => 'Logo không tồn tại'], 404);
        }

        return response()->json([
            'success' => true,
            'logo_url' => route('admin.logo.serve', $request->logo),
            'position' => $request->position,
            'size' => $request->size
        ]);
    }
}
