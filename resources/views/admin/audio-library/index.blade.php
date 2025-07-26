@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Thư viện Audio']
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-music mr-2"></i>Thư viện Audio</h2>
        <div>
            <div class="btn-group mr-2">
                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-cog mr-1"></i>Quản lý
                </button>
                <div class="dropdown-menu">
                    <button type="button" class="dropdown-item" data-toggle="modal" data-target="#importStoryModal">
                        <i class="fas fa-download mr-2"></i>Import từ Truyện
                    </button>
                    <a href="{{ route('admin.audio-library.export') }}" class="dropdown-item">
                        <i class="fas fa-file-export mr-2"></i>Export danh sách
                    </a>
                    <a href="{{ route('admin.audio-library.batch-list') }}" class="dropdown-item">
                        <i class="fas fa-history mr-2"></i>Lịch sử Upload
                    </a>
                    <div class="dropdown-divider"></div>
                    <button type="button" class="dropdown-item" onclick="selectAll()">
                        <i class="fas fa-check-square mr-2"></i>Chọn tất cả
                    </button>
                    <button type="button" class="dropdown-item" onclick="clearSelection()">
                        <i class="fas fa-square mr-2"></i>Bỏ chọn tất cả
                    </button>
                </div>
            </div>
            <a href="{{ route('admin.audio-library.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>Thêm Audio
            </a>

            <!-- Debug dropdown test -->
            <div class="btn-group ml-2">
                <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" id="test-dropdown">
                    Test Dropdown
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="alert('Dropdown works!')">Test Item</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Display upload errors if any -->
    @if(session('errors'))
        <div class="alert alert-warning alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h6><i class="fas fa-exclamation-triangle mr-2"></i>Một số file không thể upload:</h6>
            <ul class="mb-0">
                @foreach(session('errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-0">{{ number_format($stats['total_files']) }}</h5>
                            <small>Tổng số file</small>
                        </div>
                        <div class="ml-3">
                            <i class="fas fa-file-audio fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-0">{{ gmdate('H:i:s', $stats['total_duration']) }}</h5>
                            <small>Tổng thời lượng</small>
                        </div>
                        <div class="ml-3">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-0">{{ number_format($stats['total_size'] / 1048576, 1) }} MB</h5>
                            <small>Tổng dung lượng</small>
                        </div>
                        <div class="ml-3">
                            <i class="fas fa-hdd fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-0">{{ number_format($stats['story_audios']) }}</h5>
                            <small>Audio truyện</small>
                        </div>
                        <div class="ml-3">
                            <i class="fas fa-book-open fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label for="search" class="form-label">Tìm kiếm</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           value="{{ $query }}" placeholder="Tìm theo tên, mô tả, tags...">
                </div>
                <div class="col-md-2">
                    <label for="category" class="form-label">Danh mục</label>
                    <select name="category" id="category" class="form-control">
                        <option value="">Tất cả</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="source_type" class="form-label">Nguồn</label>
                    <select name="source_type" id="source_type" class="form-control">
                        <option value="">Tất cả</option>
                        @foreach($sourceTypes as $key => $label)
                            <option value="{{ $key }}" {{ $sourceType === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="voice_type" class="form-label">Loại giọng</label>
                    <select name="voice_type" id="voice_type" class="form-control">
                        <option value="">Tất cả</option>
                        @foreach($voiceTypes as $key => $label)
                            <option value="{{ $key }}" {{ $voiceType === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label for="min_duration" class="form-label">Từ (s)</label>
                    <input type="number" name="min_duration" id="min_duration" class="form-control" 
                           value="{{ $minDuration }}" placeholder="0">
                </div>
                <div class="col-md-1">
                    <label for="max_duration" class="form-label">Đến (s)</label>
                    <input type="number" name="max_duration" id="max_duration" class="form-control" 
                           value="{{ $maxDuration }}" placeholder="∞">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card mb-4" id="bulk-actions-card" style="display: none;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Đã chọn: <span id="selected-count">0</span> audio</strong>
                </div>
                <div>
                    <button type="button" class="btn btn-warning btn-sm" onclick="bulkUpdateCategory()">
                        <i class="fas fa-tags mr-1"></i>Đổi danh mục
                    </button>
                    <button type="button" class="btn btn-info btn-sm ml-1" onclick="bulkTogglePublic()">
                        <i class="fas fa-eye mr-1"></i>Đổi trạng thái
                    </button>
                    <button type="button" class="btn btn-danger btn-sm ml-1" onclick="bulkDelete()">
                        <i class="fas fa-trash mr-1"></i>Xóa
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm ml-1" onclick="clearSelection()">
                        <i class="fas fa-times mr-1"></i>Bỏ chọn
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio Files Grid -->
    <div class="row">
        @forelse($audioFiles as $audio)
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card audio-card h-100" data-audio-id="{{ $audio->id }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <input type="checkbox" class="audio-checkbox mr-2" value="{{ $audio->id }}" onchange="updateBulkActions()">
                        <span class="badge badge-{{ $audio->category === 'story' ? 'warning' : ($audio->category === 'music' ? 'info' : 'secondary') }}">
                            {{ $categories[$audio->category] }}
                        </span>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.audio-library.show', $audio) }}"
                           class="btn btn-info btn-sm" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.audio-library.download', $audio) }}"
                           class="btn btn-success btn-sm" title="Tải xuống">
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="{{ route('admin.audio-library.edit', $audio) }}"
                           class="btn btn-warning btn-sm" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.audio-library.destroy', $audio) }}"
                              onsubmit="return confirm('Bạn có chắc muốn xóa audio này?')" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card-body">
                    <h6 class="card-title">{{ $audio->title }}</h6>
                    <p class="card-text text-muted">{{ Str::limit($audio->description, 80) }}</p>
                    
                    <!-- Audio Player -->
                    <div class="audio-player mb-3">
                        <audio controls class="w-100" preload="none">
                            <source src="{{ $audio->file_url }}" type="audio/{{ $audio->file_extension }}">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                    
                    <div class="audio-info">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Thời lượng</small>
                                <strong>{{ $audio->formatted_duration }}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Kích thước</small>
                                <strong>{{ $audio->formatted_file_size }}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Định dạng</small>
                                <strong>{{ $audio->format }}</strong>
                            </div>
                        </div>
                    </div>
                    
                    @if($audio->voice_type)
                    <div class="mt-2">
                        <span class="badge badge-light">{{ $voiceTypes[$audio->voice_type] }}</span>
                    </div>
                    @endif
                    
                    @if($audio->tags && count($audio->tags) > 0)
                    <div class="mt-2">
                        @foreach(array_slice($audio->tags, 0, 3) as $tag)
                            <span class="badge badge-outline-primary">{{ $tag }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                
                <div class="card-footer text-muted">
                    <small>
                        <i class="fas fa-user mr-1"></i>{{ $audio->uploader->name ?? 'Unknown' }}
                        <span class="float-right">
                            <i class="fas fa-eye mr-1"></i>{{ $audio->usage_count }}
                        </span>
                    </small>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-music fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Chưa có audio nào</h4>
                <p class="text-muted">Thêm audio đầu tiên vào thư viện của bạn.</p>
                <a href="{{ route('admin.audio-library.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>Thêm Audio
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($audioFiles->hasPages())
    <div class="d-flex justify-content-center">
        {{ $audioFiles->appends(request()->query())->links() }}
    </div>
    @endif
</div>

<!-- Import Story Modal -->
<div class="modal fade" id="importStoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Audio từ Truyện</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.audio-library.import-story') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="story_id">Chọn truyện</label>
                        <select name="story_id" id="story_id" class="form-control" required>
                            <option value="">Chọn truyện có audio...</option>
                            @foreach(\App\Models\Story::whereHas('chapters', function($q) { $q->whereNotNull('audio_file_path'); })->get() as $story)
                                <option value="{{ $story->id }}">{{ $story->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Chỉ import các chương có file audio. Audio đã import sẽ không bị trùng lặp.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.audio-card {
    transition: transform 0.2s ease-in-out;
    border: 1px solid #e3e6f0;
}

.audio-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.audio-player audio {
    height: 40px;
}

.audio-info {
    background: #f8f9fc;
    padding: 0.75rem;
    border-radius: 0.35rem;
    margin: 0.5rem 0;
}

.badge-outline-primary {
    color: #007bff;
    border: 1px solid #007bff;
    background: transparent;
}

.audio-card.selected {
    border: 2px solid #007bff;
    box-shadow: 0 0 10px rgba(0,123,255,0.3);
}

/* Dropdown fixes */
.dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
    float: left;
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    font-size: 0.875rem;
    color: #212529;
    text-align: left;
    list-style: none;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.175);
}

.dropdown-menu.show {
    display: block !important;
}

.dropdown-menu-right {
    right: 0;
    left: auto;
}

.dropdown-item {
    display: block;
    width: 100%;
    padding: 0.25rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
}

.dropdown-item:hover,
.dropdown-item:focus {
    color: #16181b;
    text-decoration: none;
    background-color: #f8f9fa;
}

.dropdown-divider {
    height: 0;
    margin: 0.5rem 0;
    overflow: hidden;
    border-top: 1px solid #e9ecef;
}

/* 4 cards per row layout improvements */
@media (min-width: 992px) {
    .col-lg-3 .audio-card {
        min-height: 320px;
    }

    .col-lg-3 .card-title {
        font-size: 0.9rem;
        line-height: 1.3;
        height: 2.6rem;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .col-lg-3 .card-text {
        font-size: 0.8rem;
        line-height: 1.2;
        height: 2.4rem;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .col-lg-3 .badge {
        font-size: 0.7rem;
    }

    /* Button group improvements for 4-card layout */
    .col-lg-3 .btn-group-sm .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .col-md-6 .audio-card {
        min-height: 280px;
    }
}

@media (max-width: 575px) {
    .col-sm-12 .audio-card {
        min-height: auto;
    }
}

/* Button group enhancements */
.btn-group-sm .btn {
    transition: all 0.2s ease-in-out;
    border-radius: 0.2rem !important;
    margin-right: 1px;
}

.btn-group-sm .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-group-sm .btn:last-child {
    margin-right: 0;
}

/* Color-coded action buttons */
.btn-group-sm .btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

.btn-group-sm .btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-group-sm .btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn-group-sm .btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-group-sm .btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}
</style>
@endpush

@push('scripts')
<script>
// Initialize Bootstrap dropdowns
$(document).ready(function() {
    console.log('🔧 Initializing dropdowns...');

    // Force reload Bootstrap if needed
    if (typeof $.fn.dropdown === 'undefined') {
        console.warn('Bootstrap dropdown not available, loading fallback...');
    } else {
        // Initialize all dropdowns
        $('.dropdown-toggle').dropdown();
    }

    // Debug dropdown functionality
    console.log('  - jQuery version:', $.fn.jquery);
    console.log('  - Bootstrap dropdown available:', typeof $.fn.dropdown !== 'undefined');
    console.log('  - Dropdown elements found:', $('.dropdown-toggle').length);

    // Manual dropdown toggle for problematic dropdowns
    $('.dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $dropdown = $(this).next('.dropdown-menu');
        const isVisible = $dropdown.hasClass('show');

        console.log('Dropdown clicked:', this, 'Visible:', isVisible);

        // Hide all other dropdowns
        $('.dropdown-menu').removeClass('show');

        // Toggle current dropdown
        if (!isVisible) {
            $dropdown.addClass('show');
            console.log('Showing dropdown');
        }
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });

    // Prevent dropdown menu from closing when clicking inside
    $('.dropdown-menu').on('click', function(e) {
        e.stopPropagation();
    });

    // Fallback: Vanilla JavaScript dropdown toggle
    document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const dropdown = this.nextElementSibling;
            const isVisible = dropdown.classList.contains('show');

            // Hide all dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                menu.classList.remove('show');
            });

            // Show current dropdown if it wasn't visible
            if (!isVisible) {
                dropdown.classList.add('show');
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                menu.classList.remove('show');
            });
        }
    });
});

// Bulk actions functionality
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.audio-checkbox:checked');
    const count = checkboxes.length;
    const bulkCard = document.getElementById('bulk-actions-card');
    const countSpan = document.getElementById('selected-count');

    countSpan.textContent = count;

    if (count > 0) {
        bulkCard.style.display = 'block';
        // Highlight selected cards
        document.querySelectorAll('.audio-card').forEach(card => {
            const checkbox = card.querySelector('.audio-checkbox');
            if (checkbox && checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
    } else {
        bulkCard.style.display = 'none';
        document.querySelectorAll('.audio-card').forEach(card => {
            card.classList.remove('selected');
        });
    }
}

function clearSelection() {
    document.querySelectorAll('.audio-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateBulkActions();
}

function selectAll() {
    document.querySelectorAll('.audio-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateBulkActions();
}

function getSelectedIds() {
    const checkboxes = document.querySelectorAll('.audio-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function bulkUpdateCategory() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        alert('Vui lòng chọn ít nhất một audio');
        return;
    }

    const categories = @json($categories);
    let options = '';
    Object.keys(categories).forEach(key => {
        options += `<option value="${key}">${categories[key]}</option>`;
    });

    const category = prompt(`Chọn danh mục mới cho ${selectedIds.length} audio:\n\n${Object.values(categories).join('\n')}\n\nNhập key (${Object.keys(categories).join(', ')}):`);

    if (category && categories[category]) {
        bulkAction('update-category', { category: category, ids: selectedIds });
    }
}

function bulkTogglePublic() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        alert('Vui lòng chọn ít nhất một audio');
        return;
    }

    const action = confirm(`Bạn muốn đổi trạng thái công khai/riêng tư cho ${selectedIds.length} audio?`);
    if (action) {
        bulkAction('toggle-public', { ids: selectedIds });
    }
}

function bulkDelete() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        alert('Vui lòng chọn ít nhất một audio');
        return;
    }

    const confirmed = confirm(`Bạn có chắc muốn xóa ${selectedIds.length} audio? Hành động này không thể hoàn tác!`);
    if (confirmed) {
        bulkAction('delete', { ids: selectedIds });
    }
}

function bulkAction(action, data) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.audio-library.bulk-action") }}';

    // CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    // Action
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);

    // Data
    Object.keys(data).forEach(key => {
        if (Array.isArray(data[key])) {
            data[key].forEach(value => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `${key}[]`;
                input.value = value;
                form.appendChild(input);
            });
        } else {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = data[key];
            form.appendChild(input);
        }
    });

    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
