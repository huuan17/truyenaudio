@php
    use App\Helpers\AdminHelper;
@endphp

<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-left">
            <span class="copyright">
                © {{ date('Y') }} {{ AdminHelper::config('name') }}. 
                <strong>Version {{ AdminHelper::config('version') }}</strong>
            </span>
        </div>
        
        <div class="footer-right">
            <div class="system-info">
                <span class="info-item" data-bs-toggle="tooltip" title="Thời gian phản hồi">
                    <i class="fas fa-clock"></i>
                    {{ number_format((microtime(true) - LARAVEL_START) * 1000, 2) }}ms
                </span>
                
                <span class="info-item" data-bs-toggle="tooltip" title="Bộ nhớ sử dụng">
                    <i class="fas fa-memory"></i>
                    {{ AdminHelper::formatFileSize(memory_get_peak_usage(true)) }}
                </span>
                
                <span class="info-item" data-bs-toggle="tooltip" title="Số truy vấn database">
                    <i class="fas fa-database"></i>
                    {{ DB::getQueryLog() ? count(DB::getQueryLog()) : 0 }} queries
                </span>
            </div>
            
            <div class="footer-links">
                <a href="#" class="footer-link">Hỗ trợ</a>
                <a href="#" class="footer-link">Tài liệu</a>
                <a href="#" class="footer-link">Phiên bản</a>
            </div>
        </div>
    </div>
</footer>


