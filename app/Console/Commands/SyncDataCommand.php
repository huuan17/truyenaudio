<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncDataCommand extends Command
{
    protected $signature = 'data:sync {--source=audio_13_08} {--target=audio} {--table=} {--dry-run}';
    protected $description = 'Đồng bộ dữ liệu từ database cũ sang database mới';

    private $sourceDb;
    private $targetDb;
    private $dryRun;

    public function handle()
    {
        $this->sourceDb = $this->option('source');
        $this->targetDb = $this->option('target');
        $this->dryRun = $this->option('dry-run');
        $specificTable = $this->option('table');

        $this->info("📊 Đồng bộ dữ liệu từ {$this->sourceDb} sang {$this->targetDb}");
        
        if ($this->dryRun) {
            $this->warn("⚠️  Chế độ DRY RUN - Chỉ hiển thị thay đổi, không thực hiện");
        }

        try {
            if ($specificTable) {
                $this->syncSpecificTable($specificTable);
            } else {
                $this->syncAllTables();
            }

            $this->info("✅ Đồng bộ dữ liệu hoàn tất!");

        } catch (\Exception $e) {
            $this->error("❌ Lỗi: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function syncAllTables()
    {
        $tables = [
            'genres',
            'authors', 
            'stories',
            'chapters',
            'settings',
            'roles',
            'permissions',
            'users',
            'audio_libraries',
            'video_templates',
            'channels'
        ];

        foreach ($tables as $table) {
            $this->syncTable($table);
        }
    }

    private function syncSpecificTable($table)
    {
        $this->syncTable($table);
    }

    private function syncTable($table)
    {
        try {
            // Kiểm tra bảng tồn tại
            if (!$this->tableExists($table, $this->sourceDb)) {
                $this->warn("⚠️  Bảng {$table} không tồn tại trong {$this->sourceDb}");
                return;
            }

            if (!$this->tableExists($table, $this->targetDb)) {
                $this->warn("⚠️  Bảng {$table} không tồn tại trong {$this->targetDb}");
                return;
            }

            // Đếm số record
            $sourceCount = $this->getRecordCount($table, $this->sourceDb);
            $targetCount = $this->getRecordCount($table, $this->targetDb);

            $this->line("\n📋 Bảng {$table}: Nguồn({$sourceCount}) -> Đích({$targetCount})");

            if ($sourceCount == 0) {
                $this->line("   ⏭️  Không có dữ liệu để đồng bộ");
                return;
            }

            if ($this->dryRun) {
                $this->line("   [DRY RUN] Sẽ đồng bộ {$sourceCount} records");
                return;
            }

            // Đồng bộ dữ liệu
            $this->syncTableData($table, $sourceCount, $targetCount);

        } catch (\Exception $e) {
            $this->error("   ❌ Lỗi đồng bộ bảng {$table}: " . $e->getMessage());
        }
    }

    private function syncTableData($table, $sourceCount, $targetCount)
    {
        // Lấy cấu trúc cột của bảng đích
        $targetColumns = $this->getTableColumns($table, $this->targetDb);
        $targetColumnNames = array_column($targetColumns, 'COLUMN_NAME');

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        // Điều chỉnh batch size dựa trên kích thước bảng
        $batchSize = $sourceCount > 50000 ? 500 : 1000;
        $offset = 0;

        $this->line("      📋 Sử dụng batch size: {$batchSize}");

        while ($offset < $sourceCount) {
            // Lấy dữ liệu từ bảng nguồn theo batch
            $sourceData = DB::select("SELECT * FROM `{$this->sourceDb}`.`{$table}` ORDER BY id LIMIT {$batchSize} OFFSET {$offset}");

            if (empty($sourceData)) {
                break;
            }

            $this->line("      📦 Xử lý batch " . ($offset / $batchSize + 1) . " ({$offset}-" . ($offset + count($sourceData)) . "/{$sourceCount})");

            foreach ($sourceData as $row) {
                $rowArray = (array) $row;

                // Lọc chỉ các cột tồn tại trong bảng đích
                $filteredRow = [];
                foreach ($rowArray as $column => $value) {
                    if (in_array($column, $targetColumnNames)) {
                        $filteredRow[$column] = $value;
                    }
                }

                if (empty($filteredRow)) {
                    $skippedCount++;
                    continue;
                }

                try {
                    // Kiểm tra record đã tồn tại
                    $exists = DB::table($table)->where('id', $filteredRow['id'])->exists();

                    if (!$exists) {
                        // Insert record mới
                        DB::table($table)->insert($filteredRow);
                        $insertedCount++;

                        // Hiển thị tiến độ cho bảng lớn
                        if ($sourceCount > 10000 && $insertedCount % 1000 == 0) {
                            $this->line("         ✅ Đã insert {$insertedCount} records");
                        }
                    } else {
                        // Update record hiện có (nếu cần)
                        $updated = DB::table($table)
                            ->where('id', $filteredRow['id'])
                            ->update(array_diff_key($filteredRow, ['id' => '']));

                        if ($updated) {
                            $updatedCount++;
                        }
                    }

                } catch (\Exception $e) {
                    $this->warn("      ⚠️  Lỗi với record ID {$filteredRow['id']}: " . $e->getMessage());
                    $skippedCount++;
                }
            }

            $offset += $batchSize;

            // Giải phóng memory và tạm dừng ngắn cho bảng lớn
            unset($sourceData);
            gc_collect_cycles();

            if ($sourceCount > 50000) {
                usleep(50000); // 0.05 giây
            }
        }

        $newCount = $this->getRecordCount($table, $this->targetDb);
        $this->line("      ✅ Kết quả: +{$insertedCount} mới, ~{$updatedCount} cập nhật, !{$skippedCount} bỏ qua");
        $this->line("      📊 Tổng records hiện tại: {$newCount}");
    }

    private function tableExists($table, $database)
    {
        $result = DB::select("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [$database, $table]);
        return $result[0]->count > 0;
    }

    private function getRecordCount($table, $database)
    {
        $result = DB::select("SELECT COUNT(*) as count FROM `{$database}`.`{$table}`");
        return $result[0]->count;
    }

    private function getTableColumns($table, $database)
    {
        return DB::select("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$database, $table]);
    }
}
