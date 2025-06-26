<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TextToSpeechController extends Controller
{
    public function index()
    {
        $stories = Story::orderBy('id', 'desc')->get();
        $voices = [
            'hn_female_ngochuyen_full_48k-fhg' => 'Ngọc Huyền (Nữ - Hà Nội)',
            'hn_male_manhtung_full_48k-fhg' => 'Mạnh Tùng (Nam - Hà Nội)',
            'sg_female_thaotrinh_full_48k-fhg' => 'Thảo Trinh (Nữ - Sài Gòn)',
            'sg_male_minhhoang_full_48k-fhg' => 'Minh Hoàng (Nam - Sài Gòn)'
        ];
        
        return view('tts.index', compact('stories', 'voices'));
    }

    public function convert(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'voice' => 'required|string',
            'bitrate' => 'required|numeric|in:64,128,192,256,320',
            'speed' => 'required|numeric|between:0.5,2.0',
        ]);

        // Lấy thông tin truyện
        $story = Story::findOrFail($request->story_id);
        
        // Chạy command trong background
        $exitCode = Artisan::call('vbee:tts', [
            'story_id' => $request->story_id,
            '--voice' => $request->voice,
            '--bitrate' => $request->bitrate,
            '--speed' => $request->speed,
        ]);
        
        $output = Artisan::output();
        
        if ($exitCode === 0) {
            return redirect()->route('tts.index')
                ->with('success', "Đã bắt đầu chuyển đổi truyện '{$story->title}' thành audio.");
        } else {
            return redirect()->route('tts.index')
                ->with('error', "Lỗi khi chuyển đổi: $output");
        }
    }
}