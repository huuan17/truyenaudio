<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Services\TikTokApiService;
use App\Services\TikTokTokenManager;
use Illuminate\Support\Facades\Storage;

class TestTikTokIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:test
                            {channel? : ID của channel để test}
                            {--all : Test tất cả TikTok channels}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test TikTok API integration và upload functionality';

    private $tikTokService;
    private $tokenManager;

    public function __construct(TikTokApiService $tikTokService, TikTokTokenManager $tokenManager)
    {
        parent::__construct();
        $this->tikTokService = $tikTokService;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 TikTok Integration Test');
        $this->line('================================');

        // Test 1: Kiểm tra cấu hình
        $this->testConfiguration();

        // Test 2: Lấy channels để test
        $channels = $this->getChannelsToTest();
        
        if ($channels->isEmpty()) {
            $this->error('❌ Không tìm thấy TikTok channels nào để test');
            return 1;
        }

        $allPassed = true;

        foreach ($channels as $channel) {
            $this->line("\n" . str_repeat('=', 50));
            $this->info("🔍 Testing Channel: {$channel->name} (ID: {$channel->id})");
            $this->line(str_repeat('=', 50));

            $passed = $this->testChannel($channel);
            $allPassed = $allPassed && $passed;
        }

        $this->line("\n" . str_repeat('=', 50));
        if ($allPassed) {
            $this->info('✅ Tất cả tests đều PASSED!');
            return 0;
        } else {
            $this->error('❌ Một số tests đã FAILED!');
            return 1;
        }
    }

    private function testConfiguration()
    {
        $this->info('📋 Test 1: Kiểm tra cấu hình...');

        $clientId = config('services.tiktok.client_id');
        $clientSecret = config('services.tiktok.client_secret');
        $redirectUri = config('services.tiktok.redirect_uri');

        if (empty($clientId)) {
            $this->error('  ❌ TIKTOK_CLIENT_ID chưa được cấu hình');
            return false;
        } else {
            $this->info('  ✅ TIKTOK_CLIENT_ID: ' . substr($clientId, 0, 8) . '...');
        }

        if (empty($clientSecret)) {
            $this->error('  ❌ TIKTOK_CLIENT_SECRET chưa được cấu hình');
            return false;
        } else {
            $this->info('  ✅ TIKTOK_CLIENT_SECRET: ' . substr($clientSecret, 0, 8) . '...');
        }

        if (empty($redirectUri)) {
            $this->error('  ❌ TIKTOK_REDIRECT_URI chưa được cấu hình');
            return false;
        } else {
            $this->info('  ✅ TIKTOK_REDIRECT_URI: ' . $redirectUri);
        }

        $this->info('  ✅ Cấu hình OK');
        return true;
    }

    private function getChannelsToTest()
    {
        $channelId = $this->argument('channel');
        $testAll = $this->option('all');

        if ($channelId) {
            $channel = Channel::find($channelId);
            if (!$channel) {
                $this->error("Channel với ID {$channelId} không tồn tại");
                return collect();
            }
            if ($channel->platform !== 'tiktok') {
                $this->error("Channel {$channelId} không phải là TikTok channel");
                return collect();
            }
            return collect([$channel]);
        }

        if ($testAll) {
            return Channel::where('platform', 'tiktok')->get();
        }

        // Interactive selection
        $channels = Channel::where('platform', 'tiktok')->get();
        
        if ($channels->isEmpty()) {
            return collect();
        }

        $this->info('📋 TikTok Channels có sẵn:');
        foreach ($channels as $index => $channel) {
            $status = $channel->hasValidCredentials() ? '🟢' : '🔴';
            $this->line("  {$index}: {$status} {$channel->name} (ID: {$channel->id})");
        }

        $choice = $this->ask('Chọn channel để test (số thứ tự, hoặc "all" cho tất cả)');
        
        if ($choice === 'all') {
            return $channels;
        }

        if (is_numeric($choice) && isset($channels[$choice])) {
            return collect([$channels[$choice]]);
        }

        $this->error('Lựa chọn không hợp lệ');
        return collect();
    }

    private function testChannel(Channel $channel)
    {
        $allPassed = true;

        // Test 1: Kiểm tra credentials
        $this->info('📋 Test: Kiểm tra credentials...');
        if (!$channel->hasValidCredentials()) {
            $this->error('  ❌ Channel chưa có credentials');
            return false;
        }
        $this->info('  ✅ Credentials có sẵn');

        // Test 2: Kiểm tra token
        $this->info('📋 Test: Kiểm tra token...');
        $tokenResult = $this->tokenManager->ensureValidToken($channel);
        if (!$tokenResult['success']) {
            $this->error('  ❌ Token không hợp lệ: ' . $tokenResult['error']);
            $allPassed = false;
        } else {
            $this->info('  ✅ Token hợp lệ');
        }

        // Test 3: Test API connection
        $this->info('📋 Test: Kết nối API...');
        $credentials = $channel->api_credentials;
        $userResult = $this->tikTokService->getUserInfo($credentials['access_token']);
        if (!$userResult['success']) {
            $this->error('  ❌ Không thể kết nối API: ' . $userResult['error']);
            $allPassed = false;
        } else {
            $user = $userResult['user'];
            $this->info('  ✅ API connection OK');
            $this->info('    👤 User: ' . ($user['display_name'] ?? 'N/A'));
            $this->info('    🆔 Username: ' . ($user['username'] ?? 'N/A'));
        }

        // Test 4: Kiểm tra token info
        $this->info('📋 Test: Token info...');
        $tokenInfo = $this->tokenManager->getTokenInfo($channel);
        if ($tokenInfo['success']) {
            $info = $tokenInfo['data'];
            if (isset($info['expires_at'])) {
                $this->info('  📅 Expires: ' . $info['expires_at']);
                $this->info('  ⏰ Minutes left: ' . $info['expires_in_minutes']);
                
                if ($info['is_expired']) {
                    $this->warn('  ⚠️  Token đã hết hạn!');
                } elseif ($info['expires_soon']) {
                    $this->warn('  ⚠️  Token sắp hết hạn!');
                }
            }
        }

        // Test 5: Test upload (optional)
        if ($this->confirm('Bạn có muốn test upload video không? (cần file video test)', false)) {
            $this->testUpload($channel);
        }

        return $allPassed;
    }

    private function testUpload(Channel $channel)
    {
        $this->info('📋 Test: Upload video...');

        // Tìm file video test
        $testVideoPath = $this->findTestVideo();
        if (!$testVideoPath) {
            $this->warn('  ⚠️  Không tìm thấy file video test, bỏ qua upload test');
            return;
        }

        $this->info('  📁 Test video: ' . $testVideoPath);

        $credentials = $channel->api_credentials;
        $title = 'Test Upload - ' . now()->format('Y-m-d H:i:s');
        $description = 'Video test từ ' . config('app.name') . ' #test #automation';

        $uploadResult = $this->tikTokService->uploadVideo(
            $credentials['access_token'],
            $testVideoPath,
            $title,
            $description,
            'SELF_ONLY' // Private để test
        );

        if ($uploadResult['success']) {
            $this->info('  ✅ Upload thành công!');
            $this->info('    🆔 Publish ID: ' . $uploadResult['publish_id']);
            if (isset($uploadResult['share_url'])) {
                $this->info('    🔗 URL: ' . $uploadResult['share_url']);
            }
        } else {
            $this->error('  ❌ Upload thất bại: ' . $uploadResult['error']);
        }
    }

    private function findTestVideo()
    {
        // Tìm trong storage
        $possiblePaths = [
            storage_path('app/test.mp4'),
            storage_path('app/public/test.mp4'),
            storage_path('app/videos/test.mp4'),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Hỏi user
        $customPath = $this->ask('Nhập đường dẫn đến file video test (hoặc Enter để bỏ qua)');
        
        if ($customPath && file_exists($customPath)) {
            return $customPath;
        }

        return null;
    }
}
