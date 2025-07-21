@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-gradient-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Reference - Tham khảo nhanh
                    </h4>
                    <p class="mb-0 mt-2 opacity-75">
                        Các thao tác và shortcut thường dùng nhất
                    </p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Story Management -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-book me-2"></i>
                                        Quản lý Truyện
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions">
                                        <div class="action-item">
                                            <strong>Tạo truyện mới:</strong>
                                            <code>/admin/stories/create</code>
                                        </div>
                                        <div class="action-item">
                                            <strong>Visibility Control:</strong>
                                            <span class="badge badge-success">Public + Active</span> = Hiển thị
                                        </div>
                                        <div class="action-item">
                                            <strong>URL Pattern:</strong>
                                            <code>/admin/stories/{slug}/chapters</code>
                                        </div>
                                        <div class="action-item">
                                            <strong>Filter Stories:</strong>
                                            Tất cả | Hiển thị | Ẩn | Tạm dừng
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chapter Bulk Actions -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-file-alt me-2"></i>
                                        Chapter Bulk Actions
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions">
                                        <div class="action-item">
                                            <strong>Select All:</strong>
                                            Click checkbox header
                                        </div>
                                        <div class="action-item">
                                            <strong>Bulk TTS:</strong>
                                            <span class="badge badge-success">Safe</span> - Có thể retry
                                        </div>
                                        <div class="action-item">
                                            <strong>Bulk Delete:</strong>
                                            <span class="badge badge-danger">Danger</span> - Double confirm
                                        </div>
                                        <div class="action-item">
                                            <strong>Visual Feedback:</strong>
                                            Selected rows = Blue highlight
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Audio Player -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fas fa-volume-up me-2"></i>
                                        Enhanced Audio Player
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions">
                                        <div class="action-item">
                                            <strong>Keyboard Shortcuts:</strong>
                                            <div class="mt-1">
                                                <kbd>Space</kbd> Play/Pause
                                                <kbd>←→</kbd> Skip 10s
                                                <kbd>↑↓</kbd> Volume
                                            </div>
                                        </div>
                                        <div class="action-item">
                                            <strong>Speed Control:</strong>
                                            0.5x → 2x (7 levels)
                                        </div>
                                        <div class="action-item">
                                            <strong>Auto-Next:</strong>
                                            ☑️ Checkbox + 5s countdown
                                        </div>
                                        <div class="action-item">
                                            <strong>Settings:</strong>
                                            Saved in localStorage
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Video Generator -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-video me-2"></i>
                                        Universal Video Generator
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions">
                                        <div class="action-item">
                                            <strong>Access:</strong>
                                            <code>/admin/video-generator</code>
                                        </div>
                                        <div class="action-item">
                                            <strong>Platforms:</strong>
                                            <span class="badge badge-dark">TikTok</span>
                                            <span class="badge badge-danger">YouTube</span>
                                        </div>
                                        <div class="action-item">
                                            <strong>Features:</strong>
                                            Subtitle, Logo, Multiple scripts
                                        </div>
                                        <div class="action-item">
                                            <strong>Queue:</strong>
                                            <code>/admin/video-queue</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Queue Management -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-dark">
                                <div class="card-header bg-dark text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-tasks me-2"></i>
                                        Queue Management
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions">
                                        <div class="action-item">
                                            <strong>Auto-refresh:</strong>
                                            Every 5s (pauses on interaction)
                                        </div>
                                        <div class="action-item">
                                            <strong>Actions:</strong>
                                            Retry | Cancel | Delete
                                        </div>
                                        <div class="action-item">
                                            <strong>Shortcuts:</strong>
                                            <kbd>Ctrl+R</kbd> <kbd>F5</kbd> <kbd>Esc</kbd>
                                        </div>
                                        <div class="action-item">
                                            <strong>Notifications:</strong>
                                            Toast + Sound feedback
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Troubleshooting -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-secondary">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-tools me-2"></i>
                                        Quick Fixes
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="quick-actions">
                                        <div class="action-item">
                                            <strong>Route Error:</strong>
                                            Use <code>admin.stories.store</code>
                                        </div>
                                        <div class="action-item">
                                            <strong>Checkbox not working:</strong>
                                            Refresh page (F5)
                                        </div>
                                        <div class="action-item">
                                            <strong>TTS Failed:</strong>
                                            Check content + retry
                                        </div>
                                        <div class="action-item">
                                            <strong>Debug:</strong>
                                            F12 Console + Laravel logs
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Navigation -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-compass me-2"></i>
                                        Quick Navigation
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-2 mb-2">
                                            <a href="{{ route('admin.stories.index') }}" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fas fa-book me-1"></i>Stories
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <a href="{{ route('admin.video-generator.index') }}" class="btn btn-outline-danger btn-sm w-100">
                                                <i class="fas fa-video me-1"></i>Video Gen
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <a href="{{ route('admin.video-queue.index') }}" class="btn btn-outline-info btn-sm w-100">
                                                <i class="fas fa-tasks me-1"></i>Queue
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <a href="{{ route('admin.channels.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                                                <i class="fas fa-broadcast-tower me-1"></i>Channels
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <a href="{{ route('admin.genres.index') }}" class="btn btn-outline-success btn-sm w-100">
                                                <i class="fas fa-tags me-1"></i>Genres
                                            </a>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <a href="{{ route('admin.help.index') }}" class="btn btn-outline-warning btn-sm w-100">
                                                <i class="fas fa-question me-1"></i>Help
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .quick-actions {
        font-size: 0.9rem;
    }
    
    .action-item {
        margin-bottom: 8px;
        padding-bottom: 8px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .action-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    code {
        background: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #e83e8c;
    }
    
    kbd {
        background: #212529;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.8rem;
        margin: 0 2px;
    }
    
    .badge {
        font-size: 0.75rem;
    }
    
    .card {
        transition: transform 0.2s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
    }
</style>
@endpush
