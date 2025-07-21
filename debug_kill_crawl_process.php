<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG KILL CRAWL PROCESS ===\n";

// Test 1: Find running Node.js processes
echo "1. 🔍 Finding Running Node.js Processes:\n";
try {
    // Get all Node.js processes with details
    $command = 'wmic process where "name=\'node.exe\'" get ProcessId,CommandLine,CreationDate /format:csv';
    $output = shell_exec($command);
    
    if ($output) {
        $lines = explode("\n", trim($output));
        $nodeProcesses = [];
        
        foreach ($lines as $line) {
            if (empty(trim($line)) || strpos($line, 'CommandLine') !== false) continue;
            
            $parts = str_getcsv($line);
            if (count($parts) >= 3 && !empty($parts[1])) {
                $nodeProcesses[] = [
                    'pid' => trim($parts[3]),
                    'command' => trim($parts[1]),
                    'created' => trim($parts[2])
                ];
            }
        }
        
        echo "  Found " . count($nodeProcesses) . " Node.js processes:\n";
        
        foreach ($nodeProcesses as $process) {
            echo "    PID: {$process['pid']}\n";
            echo "    Command: " . substr($process['command'], 0, 100) . "...\n";
            
            // Check if this is a crawl process
            if (strpos($process['command'], 'crawl') !== false) {
                echo "    ✅ This is a CRAWL process\n";
                
                // Extract story info if possible
                if (preg_match('/than-dao-dan-ton/', $process['command'])) {
                    echo "    📚 Story: Thần đạo đan tôn\n";
                }
            }
            echo "    ---\n";
        }
        
    } else {
        echo "  ❌ No Node.js processes found or command failed\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Check story crawl status and job info
echo "\n2. 📚 Story Crawl Status:\n";
try {
    $story = \App\Models\Story::find(5);
    
    if ($story) {
        echo "  Story: {$story->title}\n";
        echo "  Crawl Status: {$story->crawl_status}\n";
        echo "  Job ID: {$story->crawl_job_id}\n";
        echo "  Updated: {$story->updated_at}\n";
        
        // Check if job exists in queue
        if ($story->crawl_job_id) {
            $job = \DB::table('jobs')->where('id', $story->crawl_job_id)->first();
            if ($job) {
                echo "  ✅ Job exists in queue\n";
                echo "  Job queue: {$job->queue}\n";
                echo "  Job attempts: {$job->attempts}\n";
            } else {
                echo "  ❌ Job not found in queue (may have completed/failed)\n";
            }
        } else {
            echo "  ❌ No job ID stored\n";
        }
        
    } else {
        echo "  ❌ Story not found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Test kill process methods
echo "\n3. 🔪 Kill Process Methods Test:\n";
try {
    echo "  Available kill methods:\n";
    
    // Method 1: taskkill by image name
    echo "    A. Kill all Node.js processes:\n";
    echo "       Command: taskkill /F /IM node.exe\n";
    echo "       Risk: Kills ALL Node.js processes (dangerous)\n";
    
    // Method 2: taskkill by PID
    echo "    B. Kill specific PID:\n";
    echo "       Command: taskkill /F /PID <process_id>\n";
    echo "       Risk: Safe, kills only specific process\n";
    
    // Method 3: wmic terminate
    echo "    C. WMIC terminate:\n";
    echo "       Command: wmic process where ProcessId=<pid> delete\n";
    echo "       Risk: Safe, alternative method\n";
    
    // Test if we can identify crawl processes
    if (isset($nodeProcesses)) {
        $crawlProcesses = array_filter($nodeProcesses, function($process) {
            return strpos($process['command'], 'crawl') !== false;
        });
        
        if (!empty($crawlProcesses)) {
            echo "  \n";
            echo "  🎯 Identified crawl processes to kill:\n";
            foreach ($crawlProcesses as $process) {
                echo "    PID: {$process['pid']}\n";
                echo "    Command: " . substr($process['command'], 0, 80) . "...\n";
                echo "    Kill command: taskkill /F /PID {$process['pid']}\n";
                echo "    ---\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Safe kill process function
echo "\n4. 🛡️ Safe Kill Process Function:\n";
try {
    function killCrawlProcesses($storyId = null) {
        $killedProcesses = [];
        
        // Get Node.js processes
        $command = 'wmic process where "name=\'node.exe\'" get ProcessId,CommandLine /format:csv';
        $output = shell_exec($command);
        
        if ($output) {
            $lines = explode("\n", trim($output));
            
            foreach ($lines as $line) {
                if (empty(trim($line)) || strpos($line, 'CommandLine') !== false) continue;
                
                $parts = str_getcsv($line);
                if (count($parts) >= 3 && !empty($parts[1])) {
                    $pid = trim($parts[3]);
                    $commandLine = trim($parts[1]);
                    
                    // Check if this is a crawl process
                    if (strpos($commandLine, 'crawl') !== false) {
                        // If story ID specified, check if it matches
                        if ($storyId === null || strpos($commandLine, "story_id={$storyId}") !== false) {
                            echo "    Found crawl process PID: {$pid}\n";
                            echo "    Command: " . substr($commandLine, 0, 80) . "...\n";
                            
                            // Kill the process
                            $killCommand = "taskkill /F /PID {$pid} 2>nul";
                            $killResult = shell_exec($killCommand);
                            
                            if (strpos($killResult, 'SUCCESS') !== false || empty($killResult)) {
                                echo "    ✅ Process {$pid} killed successfully\n";
                                $killedProcesses[] = $pid;
                            } else {
                                echo "    ❌ Failed to kill process {$pid}: {$killResult}\n";
                            }
                        }
                    }
                }
            }
        }
        
        return $killedProcesses;
    }
    
    echo "  Testing safe kill function (DRY RUN):\n";
    
    // Simulate the function without actually killing
    $command = 'wmic process where "name=\'node.exe\'" get ProcessId,CommandLine /format:csv';
    $output = shell_exec($command);
    
    if ($output) {
        $lines = explode("\n", trim($output));
        $crawlProcessCount = 0;
        
        foreach ($lines as $line) {
            if (empty(trim($line)) || strpos($line, 'CommandLine') !== false) continue;
            
            $parts = str_getcsv($line);
            if (count($parts) >= 3 && !empty($parts[1])) {
                $pid = trim($parts[3]);
                $commandLine = trim($parts[1]);
                
                if (strpos($commandLine, 'crawl') !== false) {
                    $crawlProcessCount++;
                    echo "    Would kill PID: {$pid}\n";
                    echo "    Command: " . substr($commandLine, 0, 60) . "...\n";
                }
            }
        }
        
        echo "  Total crawl processes that would be killed: {$crawlProcessCount}\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 5: Integration with Laravel
echo "\n5. 🔗 Laravel Integration Plan:\n";
echo "  A. Update cancelCrawl method in StoryController:\n";
echo "    1. Kill Node.js crawl processes\n";
echo "    2. Update story crawl_status to FAILED or NOT_CRAWLED\n";
echo "    3. Clear crawl_job_id\n";
echo "    4. Log the cancellation\n";
echo "  \n";
echo "  B. Add process tracking:\n";
echo "    1. Store process PID when starting crawl\n";
echo "    2. Use PID for targeted killing\n";
echo "    3. Clean up stale PIDs\n";
echo "  \n";
echo "  C. Safety measures:\n";
echo "    1. Only kill processes with 'crawl' in command line\n";
echo "    2. Verify process belongs to current story\n";
echo "    3. Log all kill operations\n";
echo "    4. Handle kill failures gracefully\n";

// Test 6: Recommendations
echo "\n6. 💡 Recommendations:\n";
echo "  A. Immediate fix:\n";
echo "    - Implement killCrawlProcesses() in StoryController\n";
echo "    - Update cancelCrawl method to use it\n";
echo "    - Test with current running process\n";
echo "  \n";
echo "  B. Long-term improvements:\n";
echo "    - Store process PID in database\n";
echo "    - Implement process monitoring\n";
echo "    - Add timeout for stuck processes\n";
echo "    - Queue-based crawl management\n";
echo "  \n";
echo "  C. Testing:\n";
echo "    - Test kill functionality with running crawl\n";
echo "    - Verify status updates correctly\n";
echo "    - Check for orphaned processes\n";

echo "\n7. 🚨 IMMEDIATE ACTION NEEDED:\n";
if (isset($crawlProcesses) && !empty($crawlProcesses)) {
    echo "  ⚠️ Active crawl processes detected!\n";
    echo "  To stop current crawl manually:\n";
    foreach ($crawlProcesses as $process) {
        echo "    taskkill /F /PID {$process['pid']}\n";
    }
    echo "  \n";
    echo "  Then update story status:\n";
    echo "    UPDATE stories SET crawl_status = 0, crawl_job_id = NULL WHERE id = 5;\n";
} else {
    echo "  ✅ No active crawl processes found\n";
}

echo "\n✅ Kill crawl process debugging completed!\n";

?>
