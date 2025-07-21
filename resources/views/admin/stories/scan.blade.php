@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Quét Chapter từ Storage - {{ $story->title }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.stories.show', $story) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            
            <div class="row">
                <div class="col-md-8">
                    <form action="{{ route('admin.stories.scan', $story) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="storage_path">Thư mục chứa file text</label>
                            @php
                                $storagePath = 'storage/app/content/' . $story->folder_name;
                                $fullPath = storage_path('app/content/' . $story->folder_name);
                            @endphp
                            <input type="text" class="form-control" value="{{ $storagePath }}" readonly>
                            <small class="form-text text-muted">
                                Đường dẫn: {{ $fullPath }}<br>
                                Thư mục chứa các file .txt đã crawl
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="force" value="1" class="form-check-input" id="force">
                                <label class="form-check-label" for="force">
                                    Quét lại tất cả chapter (bao gồm cả những chapter đã có trong database)
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Nếu không chọn, chỉ quét những chapter chưa có trong database
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="with_content" value="1" class="form-check-input" id="with_content">
                                <label class="form-check-label" for="with_content">
                                    Import nội dung chapter vào database
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                <strong>Mặc định:</strong> Chỉ lưu thông tin file (tiêu đề, số chương, đường dẫn file).
                                <strong>Nếu chọn:</strong> Sẽ lưu cả nội dung chapter vào database (tốn nhiều dung lượng).
                            </small>
                        </div>
                        
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Thông tin quét chapter:</h5>
                            <ul class="mb-0">
                                <li>Hệ thống sẽ quét tất cả file .txt trong thư mục <code>storage/app/content/{{ $story->folder_name }}</code></li>
                                <li>Tên file phải theo định dạng: <code>chuong-{số}.txt</code> hoặc <code>chuong_{số}.txt</code></li>
                                <li>Tiêu đề chapter sẽ được tự động trích xuất từ nội dung file</li>
                                <li><strong>Mặc định:</strong> Chỉ lưu thông tin file (tiết kiệm dung lượng database)</li>
                                <li><strong>Tùy chọn:</strong> Có thể lưu cả nội dung vào database nếu cần</li>
                                <li><strong>⚠️ Lưu ý:</strong> Quá trình này có thể mất thời gian với truyện có nhiều chapter (vài phút)</li>
                                <li><strong>🔧 Cải tiến:</strong> Xử lý theo batch để tránh timeout, có logging chi tiết</li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Bắt đầu quét chapter
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Thống kê hiện tại</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $textFolder = storage_path('app/content/' . $story->folder_name);
                                $textFiles = \Illuminate\Support\Facades\File::isDirectory($textFolder)
                                    ? \Illuminate\Support\Facades\File::glob("$textFolder/*.txt")
                                    : [];
                                $chaptersInDb = \App\Models\Chapter::where('story_id', $story->id)->count();
                                $chaptersWithContent = \App\Models\Chapter::where('story_id', $story->id)
                                    ->whereNotNull('content')
                                    ->where('content', '!=', '')
                                    ->count();
                                $chaptersFileOnly = $chaptersInDb - $chaptersWithContent;
                            @endphp
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info"><i class="fas fa-file-alt"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">File text trong storage</span>
                                            <span class="info-box-number">{{ count($textFiles) }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success"><i class="fas fa-database"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Chapter trong database</span>
                                            <span class="info-box-number">{{ $chaptersInDb }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary"><i class="fas fa-file-text"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Có nội dung trong DB</span>
                                            <span class="info-box-number">{{ $chaptersWithContent }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-secondary"><i class="fas fa-link"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Chỉ liên kết file</span>
                                            <span class="info-box-number">{{ $chaptersFileOnly }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Chưa quét</span>
                                            <span class="info-box-number">{{ max(0, count($textFiles) - $chaptersInDb) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if(count($textFiles) > 0)
                                <div class="mt-3">
                                    <h6>Một số file mẫu:</h6>
                                    <ul class="list-unstyled">
                                        @foreach(array_slice($textFiles, 0, 5) as $file)
                                            <li><small><code>{{ basename($file) }}</code></small></li>
                                        @endforeach
                                        @if(count($textFiles) > 5)
                                            <li><small>... và {{ count($textFiles) - 5 }} file khác</small></li>
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
