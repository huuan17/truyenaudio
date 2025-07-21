<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;

class TestChannelCredentialsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:channel-credentials
                            {channel : ID của channel để test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test việc cập nhật credentials của channel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $channelId = $this->argument('channel');
        $channel = Channel::find($channelId);

        if (!$channel) {
            $this->error("Channel với ID {$channelId} không tồn tại");
            return 1;
        }

        if ($channel->platform !== 'tiktok') {
            $this->error("Channel này không phải TikTok channel");
            return 1;
        }

        $this->info("🔍 Testing Channel: {$channel->name}");
        $this->line("Platform: {$channel->platform}");
        $this->line("ID: {$channel->id}");

        // Hiển thị credentials hiện tại
        $this->info("\n📋 Credentials hiện tại:");
        $credentials = $channel->api_credentials;
        
        if ($credentials) {
            $this->line("✅ Có credentials");
            
            if (isset($credentials['access_token'])) {
                $this->line("  🔑 Access Token: " . substr($credentials['access_token'], 0, 20) . "...");
            }
            
            if (isset($credentials['refresh_token'])) {
                $this->line("  🔄 Refresh Token: " . substr($credentials['refresh_token'], 0, 20) . "...");
            }
            
            if (isset($credentials['open_id'])) {
                $this->line("  🆔 Open ID: " . $credentials['open_id']);
            }
            
            if (isset($credentials['expires_at'])) {
                $this->line("  📅 Expires At: " . $credentials['expires_at']);
            }
            
            if (isset($credentials['updated_manually'])) {
                $this->line("  ✏️  Updated Manually: " . ($credentials['updated_manually'] ? 'Yes' : 'No'));
            }
        } else {
            $this->warn("❌ Không có credentials");
        }

        // Test hasValidCredentials method
        $this->info("\n🧪 Test hasValidCredentials():");
        $hasValid = $channel->hasValidCredentials();
        $this->line($hasValid ? "✅ TRUE" : "❌ FALSE");

        // Simulate manual update
        if ($this->confirm("\nBạn có muốn test cập nhật credentials thủ công không?")) {
            $this->testManualUpdate($channel);
        }

        return 0;
    }

    private function testManualUpdate(Channel $channel)
    {
        $this->info("\n🔧 Testing manual credentials update...");

        // Backup current credentials
        $originalCredentials = $channel->api_credentials;

        // Test 1: Update access token
        $testAccessToken = 'test_access_token_' . time();
        $this->line("Test 1: Cập nhật Access Token");
        
        $currentCredentials = $channel->api_credentials ?: [];
        $currentCredentials['access_token'] = $testAccessToken;
        $currentCredentials['updated_at'] = now()->toDateTimeString();
        $currentCredentials['updated_manually'] = true;
        
        $channel->update(['api_credentials' => $currentCredentials]);
        $channel->refresh();
        
        if (isset($channel->api_credentials['access_token']) && 
            $channel->api_credentials['access_token'] === $testAccessToken) {
            $this->info("  ✅ Access Token updated successfully");
        } else {
            $this->error("  ❌ Access Token update failed");
        }

        // Test 2: Update refresh token
        $testRefreshToken = 'test_refresh_token_' . time();
        $this->line("Test 2: Cập nhật Refresh Token");
        
        $currentCredentials = $channel->api_credentials;
        $currentCredentials['refresh_token'] = $testRefreshToken;
        $currentCredentials['updated_at'] = now()->toDateTimeString();
        
        $channel->update(['api_credentials' => $currentCredentials]);
        $channel->refresh();
        
        if (isset($channel->api_credentials['refresh_token']) && 
            $channel->api_credentials['refresh_token'] === $testRefreshToken) {
            $this->info("  ✅ Refresh Token updated successfully");
        } else {
            $this->error("  ❌ Refresh Token update failed");
        }

        // Test 3: Update open_id
        $testOpenId = 'test_open_id_' . time();
        $this->line("Test 3: Cập nhật Open ID");
        
        $currentCredentials = $channel->api_credentials;
        $currentCredentials['open_id'] = $testOpenId;
        $currentCredentials['updated_at'] = now()->toDateTimeString();
        
        $channel->update(['api_credentials' => $currentCredentials]);
        $channel->refresh();
        
        if (isset($channel->api_credentials['open_id']) && 
            $channel->api_credentials['open_id'] === $testOpenId) {
            $this->info("  ✅ Open ID updated successfully");
        } else {
            $this->error("  ❌ Open ID update failed");
        }

        // Test 4: Clear credentials
        if ($this->confirm("Test xóa credentials?")) {
            $this->line("Test 4: Xóa credentials");
            
            $channel->update(['api_credentials' => null]);
            $channel->refresh();
            
            if (empty($channel->api_credentials)) {
                $this->info("  ✅ Credentials cleared successfully");
            } else {
                $this->error("  ❌ Credentials clear failed");
            }
        }

        // Restore original credentials
        if ($this->confirm("Khôi phục credentials gốc?")) {
            $channel->update(['api_credentials' => $originalCredentials]);
            $this->info("✅ Credentials đã được khôi phục");
        }

        $this->info("\n🎉 Manual update test completed!");
    }
}
