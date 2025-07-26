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

@push('styles')
<style>
.main-footer {
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
    margin-left: 250px;
    transition: margin-left 0.3s ease;
    font-size: 0.875rem;
    color: #6c757d;
}

.sidebar.collapsed ~ .main-content .main-footer {
    margin-left: 60px;
}

.footer-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.footer-left {
    flex: 1;
}

.copyright {
    font-weight: 500;
}

.footer-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.system-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    color: #6c757d;
}

.info-item i {
    width: 12px;
    text-align: center;
}

.footer-links {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.footer-link {
    color: #6c757d;
    text-decoration: none;
    font-size: 0.8rem;
    transition: color 0.2s ease;
}

.footer-link:hover {
    color: #495057;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .main-footer {
        margin-left: 0;
        padding: 0.75rem 1rem;
    }
    
    .footer-content {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .footer-right {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .system-info {
        gap: 0.5rem;
    }
    
    .footer-links {
        gap: 0.5rem;
    }
}

@media (max-width: 576px) {
    .system-info {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .footer-links {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>
@endpush
