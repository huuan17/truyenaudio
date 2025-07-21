<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateQueueTables extends Command
{
    protected $signature = 'queue:create-tables';
    protected $description = 'Create queue and video generation tables';

    public function handle()
    {
        try {
            $this->info('Creating video_generation_tasks table...');
            
            // Create video_generation_tasks table
            if (!Schema::hasTable('video_generation_tasks')) {
                DB::statement("
                    CREATE TABLE `video_generation_tasks` (
                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` bigint(20) unsigned NOT NULL,
                      `platform` varchar(255) NOT NULL,
                      `type` varchar(255) NOT NULL,
                      `status` varchar(255) NOT NULL DEFAULT 'pending',
                      `priority` int(11) NOT NULL DEFAULT 2,
                      `parameters` json NOT NULL,
                      `result` json DEFAULT NULL,
                      `progress` int(11) NOT NULL DEFAULT 0,
                      `estimated_duration` int(11) DEFAULT NULL,
                      `started_at` timestamp NULL DEFAULT NULL,
                      `completed_at` timestamp NULL DEFAULT NULL,
                      `batch_id` varchar(255) DEFAULT NULL,
                      `batch_index` int(11) DEFAULT NULL,
                      `total_in_batch` int(11) DEFAULT NULL,
                      `created_at` timestamp NULL DEFAULT NULL,
                      `updated_at` timestamp NULL DEFAULT NULL,
                      `deleted_at` timestamp NULL DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `video_generation_tasks_user_id_foreign` (`user_id`),
                      KEY `video_generation_tasks_status_priority_created_at_index` (`status`,`priority`,`created_at`),
                      KEY `video_generation_tasks_user_id_status_index` (`user_id`,`status`),
                      KEY `video_generation_tasks_platform_status_index` (`platform`,`status`),
                      KEY `video_generation_tasks_batch_id_batch_index_index` (`batch_id`,`batch_index`),
                      KEY `video_generation_tasks_created_at_index` (`created_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $this->info('✅ video_generation_tasks table created successfully');
            } else {
                $this->info('ℹ️  video_generation_tasks table already exists');
            }

            // Create jobs table
            if (!Schema::hasTable('jobs')) {
                DB::statement("
                    CREATE TABLE `jobs` (
                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                      `queue` varchar(255) NOT NULL,
                      `payload` longtext NOT NULL,
                      `attempts` tinyint(3) unsigned NOT NULL,
                      `reserved_at` int(10) unsigned DEFAULT NULL,
                      `available_at` int(10) unsigned NOT NULL,
                      `created_at` int(10) unsigned NOT NULL,
                      PRIMARY KEY (`id`),
                      KEY `jobs_queue_index` (`queue`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $this->info('✅ jobs table created successfully');
            } else {
                $this->info('ℹ️  jobs table already exists');
            }

            // Add foreign key constraint if users table exists
            if (Schema::hasTable('users')) {
                try {
                    DB::statement("
                        ALTER TABLE `video_generation_tasks` 
                        ADD CONSTRAINT `video_generation_tasks_user_id_foreign` 
                        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                    ");
                    $this->info('✅ Foreign key constraint added');
                } catch (\Exception $e) {
                    $this->warn('⚠️  Foreign key constraint already exists or failed to add: ' . $e->getMessage());
                }
            }

            $this->info('🎉 All tables created successfully!');
            
            // Update queue connection in .env if needed
            $this->updateQueueConnection();
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error creating tables: ' . $e->getMessage());
            return 1;
        }
    }

    private function updateQueueConnection()
    {
        $envPath = base_path('.env');
        
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            // Update QUEUE_CONNECTION to database
            if (strpos($envContent, 'QUEUE_CONNECTION=sync') !== false) {
                $envContent = str_replace('QUEUE_CONNECTION=sync', 'QUEUE_CONNECTION=database', $envContent);
                file_put_contents($envPath, $envContent);
                $this->info('✅ Updated QUEUE_CONNECTION to database in .env file');
            } elseif (strpos($envContent, 'QUEUE_CONNECTION=database') !== false) {
                $this->info('ℹ️  QUEUE_CONNECTION already set to database');
            } else {
                $this->warn('⚠️  Could not find QUEUE_CONNECTION in .env file');
            }
        }
    }
}
