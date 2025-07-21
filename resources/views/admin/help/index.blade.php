@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Trung tâm Hướng dẫn
                    </h4>
                    <p class="mb-0 mt-2 opacity-75">
                        Hướng dẫn sử dụng đầy đủ các tính năng của hệ thống Audio Lara
                    </p>
                </div>
                <div class="card-body">
                    <!-- Quick Search -->
                    <div class="row mb-4">
                        <div class="col-md-8 mx-auto">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="helpSearch" 
                                       placeholder="Tìm kiếm hướng dẫn..." onkeyup="filterHelp()">
                            </div>
                        </div>
                    </div>

                    <!-- Help Sections Grid -->
                    <div class="row" id="helpGrid">
                        @foreach($helpSections as $key => $section)
                        <div class="col-lg-4 col-md-6 mb-4 help-section" data-keywords="{{ strtolower($section['title'] . ' ' . $section['description']) }}">
                            <div class="card h-100 border-0 shadow-sm help-card" data-section="{{ $key }}">
                                <div class="card-body text-center">
                                    <div class="help-icon mb-3">
                                        <i class="{{ $section['icon'] }} fa-3x text-{{ $section['color'] }}"></i>
                                    </div>
                                    <h5 class="card-title">{{ $section['title'] }}</h5>
                                    <p class="card-text text-muted">{{ $section['description'] }}</p>
                                    @if(isset($section['md_file']))
                                        <div class="mb-2">
                                            <span class="badge badge-info">
                                                <i class="fas fa-file-alt"></i> Markdown Guide
                                            </span>
                                        </div>
                                    @endif
                                    <a href="{{ route('admin.help.show', $key) }}"
                                       class="btn btn-{{ $section['color'] }} btn-sm">
                                        <i class="fas fa-arrow-right me-1"></i>
                                        Xem hướng dẫn
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-5">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-bolt text-warning me-2"></i>
                                        Thao tác nhanh
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ route('admin.stories.create') }}" class="btn btn-outline-success btn-sm w-100">
                                                <i class="fas fa-plus me-1"></i>Tạo truyện mới
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ route('admin.video-generator.index') }}" class="btn btn-outline-danger btn-sm w-100">
                                                <i class="fas fa-video me-1"></i>Tạo video
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ route('admin.video-queue.index') }}" class="btn btn-outline-info btn-sm w-100">
                                                <i class="fas fa-tasks me-1"></i>Xem queue
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ route('admin.channels.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                                                <i class="fas fa-broadcast-tower me-1"></i>Quản lý kênh
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Trạng thái hệ thống
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <span>Story Visibility: <strong>Active</strong></span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <span>Audio Player: <strong>Enhanced</strong></span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <span>Video Generator: <strong>Universal</strong></span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <span>Bulk Actions: <strong>Ready</strong></span>
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
</div>
@endsection

@push('styles')
<style>
    .help-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .help-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }

    .help-icon {
        transition: transform 0.3s ease;
    }

    .help-card:hover .help-icon {
        transform: scale(1.1);
    }

    .help-section.hidden {
        display: none;
    }

    .card-title {
        color: #2c3e50;
        font-weight: 600;
    }

    .card-text {
        font-size: 0.9rem;
        line-height: 1.5;
    }

    #helpSearch {
        border-radius: 25px;
        padding: 12px 20px;
        border: 2px solid #e9ecef;
        transition: border-color 0.3s ease;
    }

    #helpSearch:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .input-group-text {
        border-radius: 25px 0 0 25px;
        border: 2px solid #e9ecef;
        border-right: none;
        background: #f8f9fa;
    }

    .btn {
        border-radius: 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .bg-light {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    }

    .border-info {
        border-color: #17a2b8 !important;
    }

    .bg-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
    }

    @media (max-width: 768px) {
        .help-card {
            margin-bottom: 1rem;
        }
        
        .col-md-3 {
            margin-bottom: 0.5rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function filterHelp() {
        const searchTerm = document.getElementById('helpSearch').value.toLowerCase();
        const helpSections = document.querySelectorAll('.help-section');
        
        helpSections.forEach(section => {
            const keywords = section.getAttribute('data-keywords');
            if (keywords.includes(searchTerm)) {
                section.classList.remove('hidden');
            } else {
                section.classList.add('hidden');
            }
        });
        
        // Show "no results" message if needed
        const visibleSections = document.querySelectorAll('.help-section:not(.hidden)');
        const helpGrid = document.getElementById('helpGrid');
        
        // Remove existing no-results message
        const existingMessage = document.getElementById('no-results');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        if (visibleSections.length === 0 && searchTerm.length > 0) {
            const noResultsDiv = document.createElement('div');
            noResultsDiv.id = 'no-results';
            noResultsDiv.className = 'col-12 text-center py-5';
            noResultsDiv.innerHTML = `
                <div class="text-muted">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h5>Không tìm thấy kết quả</h5>
                    <p>Thử tìm kiếm với từ khóa khác hoặc <a href="#" onclick="clearSearch()">xóa bộ lọc</a></p>
                </div>
            `;
            helpGrid.appendChild(noResultsDiv);
        }
    }
    
    function clearSearch() {
        document.getElementById('helpSearch').value = '';
        filterHelp();
    }
    
    // Add click handlers to cards
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.help-card').forEach(card => {
            card.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                window.location.href = `{{ route('admin.help.show', '') }}/${section}`;
            });
        });
    });
</script>
@endpush
