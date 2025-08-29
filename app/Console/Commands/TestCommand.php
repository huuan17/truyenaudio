<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCommand extends Command
{
    protected $signature = 'test:simple {--test-option= : Test option}';
    protected $description = 'Simple test command';

    public function handle()
    {
        $this->info('Test command is working!');
        $testOption = $this->option('test-option');
        if ($testOption) {
            $this->info('Test option value: ' . $testOption);
        }
        return 0;
    }
}
