<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedUploadService
{
    protected $chunkPath = 'chunks';
    protected $maxChunkSize = 5 * 1024 * 1024; // 5MB per chunk
    
    /**
     * Handle chunked file upload
     */
    public function handleChunk(Request $request)
    {
        $chunkIndex = $request->input('chunk_index', 0);
        $totalChunks = $request->input('total_chunks', 1);
        $fileId = $request->input('file_id');
        $originalName = $request->input('original_name');
        
        if (!$fileId) {
            $fileId = Str::uuid();
        }
        
        // Validate chunk
        if (!$request->hasFile('chunk')) {
            return ['error' => 'No chunk file provided'];
        }
        
        $chunk = $request->file('chunk');
        
        // Store chunk
        $chunkFileName = "{$fileId}.part{$chunkIndex}";
        $chunkPath = "{$this->chunkPath}/{$chunkFileName}";
        
        Storage::disk('local')->put($chunkPath, file_get_contents($chunk->getRealPath()));
        
        // Check if all chunks are uploaded
        if ($this->allChunksUploaded($fileId, $totalChunks)) {
            // Merge chunks
            $finalPath = $this->mergeChunks($fileId, $totalChunks, $originalName);
            
            // Clean up chunks
            $this->cleanupChunks($fileId, $totalChunks);
            
            return [
                'success' => true,
                'completed' => true,
                'file_path' => $finalPath,
                'file_id' => $fileId
            ];
        }
        
        return [
            'success' => true,
            'completed' => false,
            'chunk_index' => $chunkIndex,
            'file_id' => $fileId
        ];
    }
    
    /**
     * Check if all chunks are uploaded
     */
    protected function allChunksUploaded($fileId, $totalChunks)
    {
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$this->chunkPath}/{$fileId}.part{$i}";
            if (!Storage::disk('local')->exists($chunkPath)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Merge all chunks into final file
     */
    protected function mergeChunks($fileId, $totalChunks, $originalName)
    {
        $finalPath = "uploads/" . Str::uuid() . "_" . $originalName;
        $finalFullPath = Storage::disk('local')->path($finalPath);
        
        // Create directory if not exists
        $directory = dirname($finalFullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $finalFile = fopen($finalFullPath, 'wb');
        
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$this->chunkPath}/{$fileId}.part{$i}";
            $chunkFullPath = Storage::disk('local')->path($chunkPath);
            
            $chunkFile = fopen($chunkFullPath, 'rb');
            stream_copy_to_stream($chunkFile, $finalFile);
            fclose($chunkFile);
        }
        
        fclose($finalFile);
        
        return $finalPath;
    }
    
    /**
     * Clean up chunk files
     */
    protected function cleanupChunks($fileId, $totalChunks)
    {
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$this->chunkPath}/{$fileId}.part{$i}";
            Storage::disk('local')->delete($chunkPath);
        }
    }
    
    /**
     * Calculate optimal chunk size based on file size
     */
    public function calculateChunkSize($fileSize)
    {
        // For files under 50MB, use 2MB chunks
        if ($fileSize < 50 * 1024 * 1024) {
            return 2 * 1024 * 1024;
        }
        
        // For files under 200MB, use 5MB chunks
        if ($fileSize < 200 * 1024 * 1024) {
            return 5 * 1024 * 1024;
        }
        
        // For larger files, use 10MB chunks
        return 10 * 1024 * 1024;
    }
    
    /**
     * Calculate total chunks needed
     */
    public function calculateTotalChunks($fileSize, $chunkSize = null)
    {
        if (!$chunkSize) {
            $chunkSize = $this->calculateChunkSize($fileSize);
        }
        
        return ceil($fileSize / $chunkSize);
    }
    
    /**
     * Get upload progress
     */
    public function getUploadProgress($fileId, $totalChunks)
    {
        $uploadedChunks = 0;
        
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$this->chunkPath}/{$fileId}.part{$i}";
            if (Storage::disk('local')->exists($chunkPath)) {
                $uploadedChunks++;
            }
        }
        
        return [
            'uploaded_chunks' => $uploadedChunks,
            'total_chunks' => $totalChunks,
            'progress_percentage' => round(($uploadedChunks / $totalChunks) * 100, 2)
        ];
    }
    
    /**
     * Cancel upload and cleanup
     */
    public function cancelUpload($fileId, $totalChunks)
    {
        $this->cleanupChunks($fileId, $totalChunks);
        
        return ['success' => true, 'message' => 'Upload cancelled and cleaned up'];
    }
}
