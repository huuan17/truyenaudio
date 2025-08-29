<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class SyncDatabaseCommand extends Command
{
    protected $signature = 'db:sync {--source=audio_13_08} {--target=audio} {--dry-run}';
    protected $description = 'Đồng bộ database hiện tại với database cũ';

    private $sourceDb;
    private $targetDb;
    private $dryRun;

    public function handle()
    {
        $this->sourceDb = $this->option('source');
        $this->targetDb = $this->option('target');
        $this->dryRun = $this->option('dry-run');

        $this->info("🔄 Đồng bộ database {$this->targetDb} với {$this->sourceDb}");
        
        if ($this->dryRun) {
            $this->warn("⚠️  Chế độ DRY RUN - Chỉ hiển thị thay đổi, không thực hiện");
        }

        try {
            // 1. Chạy các migration an toàn
            $this->runSafeMigrations();

            // 2. Thêm các cột thiếu vào bảng hiện có
            $this->addMissingColumns();

            // 3. Tạo các bảng thiếu
            $this->createMissingTables();

            // 4. Đồng bộ dữ liệu
            $this->syncData();

            $this->info("✅ Đồng bộ database hoàn tất!");

        } catch (\Exception $e) {
            $this->error("❌ Lỗi: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function runSafeMigrations()
    {
        $this->info("\n📋 Chạy các migration an toàn...");

        $safeMigrations = [
            '2025_06_07_072314_create_genres_table',
            '2025_06_07_072411_create_genre_story_table',
            '2025_07_02_070738_create_channels_table',
            '2025_07_02_070751_create_scheduled_posts_table',
            '2025_07_08_190826_create_bulk_tts_tasks_table',
            '2025_07_09_100000_create_authors_table',
            '2025_07_09_110000_create_settings_table',
            '2025_07_14_192448_create_roles_and_permissions_tables',
            '2025_07_15_033407_create_sessions_table',
            '2025_07_22_172106_create_video_templates_table',
            '2025_07_22_223600_create_audio_libraries_table',
            '2025_07_23_002558_create_audio_upload_batches_table',
        ];

        foreach ($safeMigrations as $migration) {
            try {
                if (!$this->dryRun) {
                    $this->line("   Chạy migration: {$migration}");
                    Artisan::call('migrate', [
                        '--path' => "database/migrations/{$migration}.php",
                        '--force' => true
                    ]);
                } else {
                    $this->line("   [DRY RUN] Sẽ chạy migration: {$migration}");
                }
            } catch (\Exception $e) {
                $this->warn("   ⚠️  Bỏ qua migration {$migration}: " . $e->getMessage());
            }
        }
    }

    private function addMissingColumns()
    {
        $this->info("\n🔧 Thêm các cột thiếu...");

        $columnUpdates = [
            'chapters' => [
                'is_crawled' => 'BOOLEAN DEFAULT FALSE',
                'file_path' => 'VARCHAR(500) NULL',
                'crawled_at' => 'TIMESTAMP NULL',
                'tts_voice' => 'VARCHAR(100) NULL',
                'tts_bitrate' => 'INT NULL',
                'tts_speed' => 'DECIMAL(3,2) NULL',
                'tts_volume' => 'DECIMAL(3,2) NULL',
                'tts_progress' => 'DECIMAL(5,2) DEFAULT 0.00',
                'tts_error' => 'TEXT NULL',
                'tts_started_at' => 'TIMESTAMP NULL',
                'tts_completed_at' => 'TIMESTAMP NULL',
                'audio_file_path' => 'VARCHAR(500) NULL'
            ],
            'stories' => [
                'author_id' => 'BIGINT UNSIGNED NULL',
                'cover_image' => 'VARCHAR(500) NULL',
                'slug' => 'VARCHAR(255) NULL',
                'status' => 'VARCHAR(50) DEFAULT "active"',
                'start_chapter' => 'INT NULL',
                'end_chapter' => 'INT NULL',
                'crawl_path' => 'VARCHAR(500) NULL',
                'folder_name' => 'VARCHAR(255) NULL',
                'crawl_status' => 'VARCHAR(50) DEFAULT "pending"',
                'auto_crawl' => 'BOOLEAN DEFAULT FALSE',
                'auto_tts' => 'BOOLEAN DEFAULT FALSE',
                'default_tts_voice' => 'VARCHAR(100) NULL',
                'default_tts_bitrate' => 'INT NULL',
                'default_tts_speed' => 'DECIMAL(3,2) NULL',
                'default_tts_volume' => 'DECIMAL(3,2) NULL',
                'crawl_job_id' => 'VARCHAR(255) NULL',
                'missing_chapters_info' => 'TEXT NULL',
                'is_public' => 'BOOLEAN DEFAULT TRUE',
                'is_active' => 'BOOLEAN DEFAULT TRUE'
            ],
            'users' => [
                'first_name' => 'VARCHAR(100) NULL',
                'last_name' => 'VARCHAR(100) NULL',
                'phone' => 'VARCHAR(20) NULL',
                'avatar' => 'VARCHAR(500) NULL',
                'is_active' => 'BOOLEAN DEFAULT TRUE',
                'last_login_at' => 'TIMESTAMP NULL',
                'last_login_ip' => 'VARCHAR(45) NULL',
                'role' => 'VARCHAR(50) DEFAULT "user"'
            ],
            'generated_videos' => [
                'channel_published_at' => 'TIMESTAMP NULL',
                'channel_publish_error' => 'TEXT NULL',
                'channel_id' => 'BIGINT UNSIGNED NULL',
                'auto_publish' => 'BOOLEAN DEFAULT FALSE',
                'publish_to_channel' => 'BOOLEAN DEFAULT FALSE'
            ]
        ];

        foreach ($columnUpdates as $table => $columns) {
            if (!$this->tableExists($table)) {
                $this->warn("   ⚠️  Bảng {$table} không tồn tại, bỏ qua");
                continue;
            }

            $this->line("   📝 Cập nhật bảng: {$table}");
            
            foreach ($columns as $column => $definition) {
                if (!$this->columnExists($table, $column)) {
                    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
                    
                    if (!$this->dryRun) {
                        try {
                            DB::statement($sql);
                            $this->line("      ✅ Thêm cột: {$column}");
                        } catch (\Exception $e) {
                            $this->warn("      ❌ Lỗi thêm cột {$column}: " . $e->getMessage());
                        }
                    } else {
                        $this->line("      [DRY RUN] Sẽ thêm cột: {$column}");
                    }
                } else {
                    $this->line("      ⏭️  Cột {$column} đã tồn tại");
                }
            }
        }
    }

    private function createMissingTables()
    {
        $this->info("\n🏗️  Tạo các bảng thiếu...");

        $missingTables = [
            'audio_libraries',
            'audio_upload_batches', 
            'authors',
            'bulk_tts_tasks',
            'channels',
            'genres',
            'genre_story',
            'permissions',
            'roles',
            'role_permissions',
            'scheduled_posts',
            'sessions',
            'settings',
            'tiktok_videos',
            'user_roles',
            'video_templates',
            'youtube_uploads'
        ];

        foreach ($missingTables as $table) {
            if (!$this->tableExists($table)) {
                $this->line("   🔨 Tạo bảng: {$table}");
                
                if (!$this->dryRun) {
                    $this->createTableFromSource($table);
                } else {
                    $this->line("      [DRY RUN] Sẽ tạo bảng: {$table}");
                }
            } else {
                $this->line("   ⏭️  Bảng {$table} đã tồn tại");
            }
        }
    }

    private function syncData()
    {
        $this->info("\n📊 Đồng bộ dữ liệu...");
        
        if ($this->dryRun) {
            $this->line("   [DRY RUN] Sẽ đồng bộ dữ liệu từ {$this->sourceDb}");
            return;
        }

        // Đồng bộ dữ liệu cho các bảng quan trọng
        $this->syncTableData('genres');
        $this->syncTableData('authors');
        $this->syncTableData('settings');
        $this->syncTableData('roles');
        $this->syncTableData('permissions');
    }

    private function tableExists($table)
    {
        return Schema::hasTable($table);
    }

    private function columnExists($table, $column)
    {
        return Schema::hasColumn($table, $column);
    }

    private function createTableFromSource($table)
    {
        try {
            // Lấy cấu trúc bảng từ database nguồn
            $createStatement = DB::select("SHOW CREATE TABLE `{$this->sourceDb}`.`{$table}`")[0];
            $createSql = $createStatement->{'Create Table'};
            
            // Thay đổi tên database trong câu lệnh CREATE
            $createSql = str_replace("`{$this->sourceDb}`.", "", $createSql);
            
            // Thực hiện tạo bảng
            DB::statement($createSql);
            $this->line("      ✅ Tạo bảng {$table} thành công");
            
        } catch (\Exception $e) {
            $this->warn("      ❌ Lỗi tạo bảng {$table}: " . $e->getMessage());
        }
    }

    private function syncTableData($table)
    {
        try {
            if (!$this->tableExists($table)) {
                return;
            }

            // Đếm số record trong bảng nguồn
            $sourceCount = DB::select("SELECT COUNT(*) as count FROM `{$this->sourceDb}`.`{$table}`")[0]->count;
            $targetCount = DB::table($table)->count();

            $this->line("   📋 Bảng {$table}: Nguồn({$sourceCount}) -> Đích({$targetCount})");

            if ($sourceCount > $targetCount) {
                // Copy dữ liệu từ database nguồn
                $data = DB::select("SELECT * FROM `{$this->sourceDb}`.`{$table}`");
                
                foreach ($data as $row) {
                    $rowArray = (array) $row;
                    
                    // Kiểm tra xem record đã tồn tại chưa
                    $exists = DB::table($table)->where('id', $rowArray['id'])->exists();
                    
                    if (!$exists) {
                        DB::table($table)->insert($rowArray);
                    }
                }
                
                $newCount = DB::table($table)->count();
                $this->line("      ✅ Đồng bộ hoàn tất: {$newCount} records");
            }
            
        } catch (\Exception $e) {
            $this->warn("      ❌ Lỗi đồng bộ bảng {$table}: " . $e->getMessage());
        }
    }
}
