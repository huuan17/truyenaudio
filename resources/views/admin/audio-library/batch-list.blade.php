@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Thư viện Audio', 'url' => route('admin.audio-library.index')],
        ['title' => 'Lịch sử Upload Batch']
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-history mr-2"></i>Lịch sử Upload Batch</h2>
        <a href="{{ route('admin.audio-library.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Upload Audio Mới
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($batches->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Thời gian</th>
                                <th>Tổng files</th>
                                <th>Tiến trình</th>
                                <th>Trạng thái</th>
                                <th>Kết quả</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batches as $batch)
                            <tr>
                                <td>
                                    <strong>#{{ $batch->id }}</strong>
                                </td>
                                <td>
                                    <div>{{ $batch->created_at->format('d/m/Y H:i:s') }}</div>
                                    @if($batch->completed_at)
                                        <small class="text-muted">
                                            Hoàn thành: {{ $batch->completed_at->format('d/m/Y H:i:s') }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ $batch->total_files }} files</span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px; width: 100px;">
                                        <div class="progress-bar 
                                            @if($batch->status === 'completed') bg-success
                                            @elseif($batch->status === 'completed_with_errors') bg-warning
                                            @elseif($batch->status === 'failed') bg-danger
                                            @elseif($batch->status === 'processing') bg-info progress-bar-striped progress-bar-animated
                                            @else bg-secondary
                                            @endif"
                                             role="progressbar" 
                                             style="width: {{ $batch->progress_percentage }}%">
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $batch->progress_percentage }}%</small>
                                </td>
                                <td>
                                    {!! $batch->status_badge !!}
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="text-success">✓ {{ $batch->completed_files }}</span>
                                        @if($batch->processing_files > 0)
                                            <span class="text-info">⟳ {{ $batch->processing_files }}</span>
                                        @endif
                                        @if($batch->failed_files > 0)
                                            <span class="text-danger">✗ {{ $batch->failed_files }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.audio-library.batch-status', $batch->id) }}" 
                                           class="btn btn-outline-primary" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($batch->isCompleted() && $batch->completed_files > 0)
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="viewSuccessfulAudios({{ $batch->id }})" 
                                                    title="Xem audio đã upload">
                                                <i class="fas fa-music"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $batches->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-upload fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có batch upload nào</h5>
                    <p class="text-muted">Bắt đầu upload audio để xem lịch sử tại đây.</p>
                    <a href="{{ route('admin.audio-library.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>Upload Audio Đầu Tiên
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal for Successful Audios -->
<div class="modal fade" id="successfulAudiosModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audio đã upload thành công</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="successful-audios-content">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <a href="{{ route('admin.audio-library.index') }}" class="btn btn-primary">
                    Xem thư viện Audio
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function viewSuccessfulAudios(batchId) {
    const modal = $('#successfulAudiosModal');
    const content = $('#successful-audios-content');
    
    modal.modal('show');
    content.html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>');
    
    fetch(`/admin/api/audio-library/batch-status/${batchId}`)
        .then(response => response.json())
        .then(data => {
            const successfulFiles = data.files.filter(file => file.status === 'completed');
            
            if (successfulFiles.length === 0) {
                content.html('<div class="text-center text-muted">Không có audio nào được upload thành công.</div>');
                return;
            }
            
            let html = '<div class="row">';
            successfulFiles.forEach(file => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">${file.title || file.original_name}</h6>
                                <p class="card-text small text-muted">${file.original_name}</p>
                                ${file.audio_id ? `
                                    <a href="/admin/audio-library/${file.audio_id}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fas fa-eye mr-1"></i>Xem chi tiết
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            content.html(html);
        })
        .catch(error => {
            console.error('Error loading successful audios:', error);
            content.html('<div class="text-center text-danger">Có lỗi khi tải dữ liệu.</div>');
        });
}

// Auto refresh processing batches
setInterval(function() {
    const processingRows = document.querySelectorAll('tr:has(.progress-bar-animated)');
    if (processingRows.length > 0) {
        location.reload();
    }
}, 10000); // Refresh every 10 seconds if there are processing batches
</script>
@endpush
