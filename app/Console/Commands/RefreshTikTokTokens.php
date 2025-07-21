<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Services\TikTokTokenManager;
use Illuminate\Support\Facades\Log;

class RefreshTikTokTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:refresh-tokens
                            {--force : Force refresh tất cả tokens}
                            {--check-only : Chỉ kiểm tra, không refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra và refresh TikTok access tokens sắp hết hạn';

    private $tokenManager;

    public function __construct(TikTokTokenManager $tokenManager)
    {
        parent::__construct();
        $this->tokenManager = $tokenManager;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $checkOnly = $this->option('check-only');

        $this->info('🔍 Đang kiểm tra TikTok tokens...');

        // Lấy tất cả channels TikTok có credentials
        $tikTokChannels = Channel::where('platform', 'tiktok')
            ->whereNotNull('api_credentials')
            ->where('is_active', true)
            ->get();

        if ($tikTokChannels->isEmpty()) {
            $this->info('✅ Không có TikTok channels nào cần kiểm tra');
            return 0;
        }

        $this->info("📋 Tìm thấy {$tikTokChannels->count()} TikTok channels:");

        $refreshed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($tikTokChannels as $channel) {
            $this->line("  🔍 Kiểm tra: {$channel->name}");

            try {
                $tokenInfo = $this->tokenManager->getTokenInfo($channel);

                if (!$tokenInfo['success']) {
                    $this->error("    ❌ Lỗi lấy thông tin token: " . $tokenInfo['error']);
                    $failed++;
                    continue;
                }

                $info = $tokenInfo['data'];
                
                if (isset($info['expires_at'])) {
                    $this->line("    📅 Hết hạn: {$info['expires_at']} ({$info['expires_in_minutes']} phút nữa)");
                    
                    if ($info['is_expired']) {
                        $this->warn("    ⚠️  Token đã hết hạn!");
                    } elseif ($info['expires_soon']) {
                        $this->warn("    ⚠️  Token sắp hết hạn (< 30 phút)");
                    }
                }

                // Quyết định có refresh không
                $shouldRefresh = $force || 
                    (isset($info['expires_soon']) && $info['expires_soon']) ||
                    (isset($info['is_expired']) && $info['is_expired']);

                if (!$shouldRefresh) {
                    $this->info("    ✅ Token còn hiệu lực");
                    $skipped++;
                    continue;
                }

                if ($checkOnly) {
                    $this->warn("    🔄 Cần refresh (check-only mode)");
                    continue;
                }

                // Refresh token
                $this->line("    🔄 Đang refresh token...");
                $refreshResult = $this->tokenManager->refreshToken($channel);

                if ($refreshResult['success']) {
                    $this->info("    ✅ Refresh thành công!");
                    $refreshed++;
                } else {
                    $this->error("    ❌ Refresh thất bại: " . $refreshResult['error']);
                    $failed++;
                }

            } catch (\Exception $e) {
                $this->error("    ❌ Exception: " . $e->getMessage());
                Log::error('TikTok token refresh command exception', [
                    'channel_id' => $channel->id,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failed++;
            }
        }

        // Tóm tắt kết quả
        $this->info("\n🎉 Hoàn thành kiểm tra tokens:");
        $this->info("  ✅ Refreshed: {$refreshed}");
        $this->info("  ⏭️  Skipped: {$skipped}");
        $this->info("  ❌ Failed: {$failed}");

        if ($checkOnly) {
            $this->warn("  🔍 Check-only mode - không thực hiện refresh");
        }

        return $failed > 0 ? 1 : 0;
    }
}
