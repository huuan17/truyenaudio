<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompareDatabaseCommand extends Command
{
    protected $signature = 'db:compare {--source=audio_13_08} {--target=audio}';
    protected $description = 'So sánh cấu trúc database hiện tại với database cũ';

    public function handle()
    {
        $sourceDb = $this->option('source');
        $targetDb = $this->option('target');

        $this->info("🔍 So sánh database {$targetDb} với {$sourceDb}");

        try {
            // Lấy danh sách bảng từ database nguồn
            $sourceTables = $this->getTables($sourceDb);
            $this->info("📊 Database {$sourceDb} có " . count($sourceTables) . " bảng");

            // Lấy danh sách bảng từ database đích
            $targetTables = $this->getTables($targetDb);
            $this->info("📊 Database {$targetDb} có " . count($targetTables) . " bảng");

            // So sánh bảng
            $this->compareTables($sourceTables, $targetTables, $sourceDb, $targetDb);

        } catch (\Exception $e) {
            $this->error("❌ Lỗi: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function getTables($database)
    {
        $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [$database]);
        return array_map(function($table) {
            return $table->TABLE_NAME;
        }, $tables);
    }

    private function compareTables($sourceTables, $targetTables, $sourceDb, $targetDb)
    {
        $this->line("\n" . str_repeat("=", 80));
        $this->info("📋 SO SÁNH CẤU TRÚC BẢNG");
        $this->line(str_repeat("=", 80));

        // Bảng thiếu trong database đích
        $missingTables = array_diff($sourceTables, $targetTables);
        if (!empty($missingTables)) {
            $this->warn("\n🚫 Các bảng thiếu trong {$targetDb}:");
            foreach ($missingTables as $table) {
                $this->line("   - {$table}");
            }
        }

        // Bảng thừa trong database đích
        $extraTables = array_diff($targetTables, $sourceTables);
        if (!empty($extraTables)) {
            $this->warn("\n➕ Các bảng thừa trong {$targetDb}:");
            foreach ($extraTables as $table) {
                $this->line("   - {$table}");
            }
        }

        // Bảng chung - so sánh cấu trúc
        $commonTables = array_intersect($sourceTables, $targetTables);
        if (!empty($commonTables)) {
            $this->info("\n🔄 So sánh cấu trúc các bảng chung:");
            foreach ($commonTables as $table) {
                $this->compareTableStructure($table, $sourceDb, $targetDb);
            }
        }

        // Tạo script migration cho các bảng thiếu
        if (!empty($missingTables)) {
            $this->generateMigrationScript($missingTables, $sourceDb);
        }
    }

    private function compareTableStructure($table, $sourceDb, $targetDb)
    {
        try {
            // Lấy cấu trúc cột từ database nguồn
            $sourceColumns = $this->getTableColumns($table, $sourceDb);
            
            // Lấy cấu trúc cột từ database đích
            $targetColumns = $this->getTableColumns($table, $targetDb);

            $sourceColumnNames = array_column($sourceColumns, 'COLUMN_NAME');
            $targetColumnNames = array_column($targetColumns, 'COLUMN_NAME');

            // Cột thiếu
            $missingColumns = array_diff($sourceColumnNames, $targetColumnNames);
            
            // Cột thừa
            $extraColumns = array_diff($targetColumnNames, $sourceColumnNames);

            if (!empty($missingColumns) || !empty($extraColumns)) {
                $this->warn("\n   📝 Bảng: {$table}");
                
                if (!empty($missingColumns)) {
                    $this->line("      🚫 Cột thiếu: " . implode(', ', $missingColumns));
                }
                
                if (!empty($extraColumns)) {
                    $this->line("      ➕ Cột thừa: " . implode(', ', $extraColumns));
                }
            }

        } catch (\Exception $e) {
            $this->error("   ❌ Lỗi khi so sánh bảng {$table}: " . $e->getMessage());
        }
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

    private function generateMigrationScript($missingTables, $sourceDb)
    {
        $this->line("\n" . str_repeat("=", 80));
        $this->info("🛠️  TẠO SCRIPT MIGRATION CHO CÁC BẢNG THIẾU");
        $this->line(str_repeat("=", 80));

        foreach ($missingTables as $table) {
            $this->info("\n📄 Migration cho bảng: {$table}");
            
            try {
                $columns = $this->getTableColumns($table, $sourceDb);
                $this->generateTableMigration($table, $columns);
            } catch (\Exception $e) {
                $this->error("❌ Lỗi khi tạo migration cho {$table}: " . $e->getMessage());
            }
        }
    }

    private function generateTableMigration($table, $columns)
    {
        $migrationName = "create_{$table}_table";
        $className = "Create" . str_replace('_', '', ucwords($table, '_')) . "Table";
        
        $this->line("   Tên migration: {$migrationName}");
        $this->line("   Class: {$className}");
        
        // Hiển thị cấu trúc cột
        $this->line("   Cấu trúc cột:");
        foreach ($columns as $column) {
            $nullable = $column->IS_NULLABLE === 'YES' ? 'nullable' : 'not null';
            $default = $column->COLUMN_DEFAULT ? "default: {$column->COLUMN_DEFAULT}" : '';
            $key = $column->COLUMN_KEY ? "key: {$column->COLUMN_KEY}" : '';
            
            $this->line("      - {$column->COLUMN_NAME}: {$column->DATA_TYPE} ({$nullable}) {$default} {$key}");
        }
    }
}
