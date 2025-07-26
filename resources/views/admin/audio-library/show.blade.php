@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Thư viện Audio', 'url' => route('admin.audio-library.index')],
        ['title' => $audioLibrary->title]
    ]" />

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-music mr-2"></i>{{ $audioLibrary->title }}
                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-cog mr-1"></i>Thao tác
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="{{ route('admin.audio-library.edit', $audioLibrary) }}">
                                <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                            </a>
                            <a class="dropdown-item" href="{{ route('admin.audio-library.download', $audioLibrary) }}">
                                <i class="fas fa-download mr-2"></i>Tải xuống
                            </a>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item" onclick="useInVideoGenerator()">
                                <i class="fas fa-video mr-2"></i>Dùng tạo video
                            </button>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('admin.audio-library.destroy', $audioLibrary) }}" 
                                  onsubmit="return confirm('Bạn có chắc muốn xóa audio này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-trash mr-2"></i>Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Audio Player -->
                    <div class="audio-player-section mb-4">
                        <audio controls class="w-100" preload="metadata">
                            <source src="{{ $audioLibrary->file_url }}" type="audio/{{ $audioLibrary->file_extension }}">
                            Your browser does not support the audio element.
                        </audio>
                    </div>

                    <!-- Description -->
                    @if($audioLibrary->description)
                    <div class="description-section mb-4">
                        <h6>Mô tả</h6>
                        <p class="text-muted">{{ $audioLibrary->description }}</p>
                    </div>
                    @endif

                    <!-- Tags -->
                    @if($audioLibrary->tags && count($audioLibrary->tags) > 0)
                    <div class="tags-section mb-4">
                        <h6>Tags</h6>
                        <div>
                            @foreach($audioLibrary->tags as $tag)
                                <span class="badge badge-outline-primary mr-1">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Source Information -->
                    @if($audioLibrary->source_type !== 'upload')
                    <div class="source-section mb-4">
                        <h6>Thông tin nguồn</h6>
                        <div class="alert alert-info">
                            <strong>Loại nguồn:</strong> {{ \App\Models\AudioLibrary::getSourceTypes()[$audioLibrary->source_type] }}
                            
                            @if($audioLibrary->source_type === 'story' && $audioLibrary->sourceStory)
                                <br><strong>Truyện:</strong> 
                                <a href="{{ route('admin.stories.show', $audioLibrary->sourceStory) }}" target="_blank">
                                    {{ $audioLibrary->sourceStory->title }}
                                </a>
                            @elseif($audioLibrary->source_type === 'chapter' && $audioLibrary->sourceChapter)
                                <br><strong>Chương:</strong> 
                                <a href="{{ route('admin.chapters.show', $audioLibrary->sourceChapter) }}" target="_blank">
                                    {{ $audioLibrary->sourceChapter->title }}
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Usage Statistics -->
                    <div class="usage-section">
                        <h6>Thống kê sử dụng</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="stat-item">
                                    <strong>Số lần sử dụng:</strong> {{ $audioLibrary->usage_count }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-item">
                                    <strong>Lần cuối sử dụng:</strong> 
                                    {{ $audioLibrary->last_used_at ? $audioLibrary->last_used_at->format('d/m/Y H:i') : 'Chưa sử dụng' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Technical Information -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Thông tin kỹ thuật</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Định dạng:</strong></td>
                            <td>{{ $audioLibrary->format }}</td>
                        </tr>
                        <tr>
                            <td><strong>Thời lượng:</strong></td>
                            <td>{{ $audioLibrary->formatted_duration }}</td>
                        </tr>
                        <tr>
                            <td><strong>Kích thước:</strong></td>
                            <td>{{ $audioLibrary->formatted_file_size }}</td>
                        </tr>
                        @if($audioLibrary->bitrate)
                        <tr>
                            <td><strong>Bitrate:</strong></td>
                            <td>{{ $audioLibrary->bitrate }} kbps</td>
                        </tr>
                        @endif
                        @if($audioLibrary->sample_rate)
                        <tr>
                            <td><strong>Sample Rate:</strong></td>
                            <td>{{ number_format($audioLibrary->sample_rate) }} Hz</td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Ngôn ngữ:</strong></td>
                            <td>{{ $audioLibrary->language === 'vi' ? 'Tiếng Việt' : 'English' }}</td>
                        </tr>
                        @if($audioLibrary->voice_type)
                        <tr>
                            <td><strong>Loại giọng:</strong></td>
                            <td>{{ \App\Models\AudioLibrary::getVoiceTypes()[$audioLibrary->voice_type] }}</td>
                        </tr>
                        @endif
                        @if($audioLibrary->mood)
                        <tr>
                            <td><strong>Tâm trạng:</strong></td>
                            <td>{{ \App\Models\AudioLibrary::getMoodTypes()[$audioLibrary->mood] }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Classification -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-tags mr-2"></i>Phân loại</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Danh mục:</strong>
                        <span class="badge badge-{{ $audioLibrary->category === 'story' ? 'warning' : ($audioLibrary->category === 'music' ? 'info' : 'secondary') }}">
                            {{ \App\Models\AudioLibrary::getCategories()[$audioLibrary->category] }}
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong>Trạng thái:</strong>
                        <span class="badge badge-{{ $audioLibrary->is_active ? 'success' : 'secondary' }}">
                            {{ $audioLibrary->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong>Quyền truy cập:</strong>
                        <span class="badge badge-{{ $audioLibrary->is_public ? 'info' : 'warning' }}">
                            {{ $audioLibrary->is_public ? 'Công khai' : 'Riêng tư' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Upload Information -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-upload mr-2"></i>Thông tin upload</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Người upload:</strong><br>
                        <small class="text-muted">{{ $audioLibrary->uploader->name ?? 'Unknown' }}</small>
                    </div>
                    <div class="mb-2">
                        <strong>Ngày tạo:</strong><br>
                        <small class="text-muted">{{ $audioLibrary->created_at->format('d/m/Y H:i:s') }}</small>
                    </div>
                    <div class="mb-2">
                        <strong>Cập nhật cuối:</strong><br>
                        <small class="text-muted">{{ $audioLibrary->updated_at->format('d/m/Y H:i:s') }}</small>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bolt mr-2"></i>Thao tác nhanh</h6>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary btn-block mb-2" onclick="useInVideoGenerator()">
                        <i class="fas fa-video mr-2"></i>Dùng tạo video
                    </button>
                    <a href="{{ route('admin.audio-library.download', $audioLibrary) }}" class="btn btn-success btn-block mb-2">
                        <i class="fas fa-download mr-2"></i>Tải xuống
                    </a>
                    <a href="{{ route('admin.audio-library.edit', $audioLibrary) }}" class="btn btn-warning btn-block">
                        <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.audio-player-section audio {
    height: 50px;
    border-radius: 0.35rem;
}

.badge-outline-primary {
    color: #007bff;
    border: 1px solid #007bff;
    background: transparent;
}

.stat-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e3e6f0;
}

.stat-item:last-child {
    border-bottom: none;
}
</style>
@endpush

@push('scripts')
<script>
function useInVideoGenerator() {
    // Open video generator in new tab with audio pre-selected
    const url = '{{ route("admin.video-generator.index") }}' + 
                '?audio_source=library&library_audio_id={{ $audioLibrary->id }}';
    window.open(url, '_blank');
}
</script>
@endpush
