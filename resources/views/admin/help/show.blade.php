@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-{{ $currentSection['color'] }} text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Danh mục hướng dẫn
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($helpSections as $key => $section)
                        <a href="{{ route('admin.help.show', $key) }}"
                           class="list-group-item list-group-item-action {{ $key === request()->route('section') ? 'active' : '' }}">
                            <i class="{{ $section['icon'] }} me-2 text-{{ $section['color'] }}"></i>
                            {{ $section['title'] }}
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.help.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-arrow-left me-1"></i>
                        Về trang chính
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header bg-{{ $currentSection['color'] }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">
                                <i class="{{ $currentSection['icon'] }} me-2"></i>
                                {{ $currentSection['title'] }}
                            </h4>
                            <p class="mb-0 mt-2 opacity-75">{{ $currentSection['description'] }}</p>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-light btn-sm" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>In
                            </button>
                            <button class="btn btn-light btn-sm" onclick="copyToClipboard()">
                                <i class="fas fa-copy me-1"></i>Copy
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body" id="helpContent">
                    @if(isset($content['source_file']))
                        <!-- Markdown content from .md file -->
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-file-alt me-2"></i>
                            <strong>Nguồn:</strong> {{ $content['source_file'] }}
                            @if(isset($content['title']))
                                <br><strong>Tiêu đề:</strong> {{ $content['title'] }}
                            @endif
                        </div>

                        @if(isset($content['sections']) && count($content['sections']) > 0)
                            @foreach($content['sections'] as $sectionData)
                                <div class="help-section mb-4">
                                    <h5 class="text-{{ $currentSection['color'] }} border-bottom pb-2">
                                        <i class="fas fa-bookmark me-2"></i>
                                        {{ $sectionData['title'] }}
                                    </h5>
                                    <div class="markdown-content">
                                        {!! $sectionData['content'] !!}
                                    </div>
                                </div>
                            @endforeach
                        @elseif(isset($content['content']))
                            <div class="markdown-content">
                                {!! $content['content'] !!}
                            </div>
                        @endif
                    @else
                        <!-- Legacy hardcoded content -->
                        @foreach($content as $contentKey => $contentSection)
                            @if(isset($contentSection['title']))
                            <div class="help-section mb-4">
                                <h5 class="text-{{ $currentSection['color'] }} border-bottom pb-2">
                                    <i class="fas fa-bookmark me-2"></i>
                                    {{ $contentSection['title'] }}
                                </h5>

                                @if(isset($contentSection['content']))
                                <div class="text-muted mb-3">{!! $contentSection['content'] !!}</div>
                                @endif
                            
                            @if(isset($contentSection['steps']))
                            <div class="steps-container">
                                <h6 class="text-secondary">Các bước thực hiện:</h6>
                                <ol class="list-group list-group-numbered">
                                    @foreach($contentSection['steps'] as $step)
                                    <li class="list-group-item border-0 ps-0">{{ $step }}</li>
                                    @endforeach
                                </ol>
                            </div>
                            @endif
                            
                            @if(isset($contentSection['items']))
                            <div class="items-container">
                                <ul class="list-unstyled">
                                    @foreach($contentSection['items'] as $item)
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        {{ $item }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            
                            @if(isset($contentSection['features']))
                            <div class="features-container">
                                <div class="row">
                                    @foreach($contentSection['features'] as $feature)
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-star text-warning me-2"></i>
                                            <span>{{ $feature }}</span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            
                            @if(isset($contentSection['benefits']))
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    Lợi ích:
                                </h6>
                                <ul class="mb-0">
                                    @foreach($contentSection['benefits'] as $benefit)
                                    <li>{{ $benefit }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            
                            @if(isset($contentSection['options']))
                            <div class="options-container">
                                <h6 class="text-secondary">Tùy chọn có sẵn:</h6>
                                <div class="row">
                                    @foreach($contentSection['options'] as $option)
                                    <div class="col-md-6 mb-2">
                                        <div class="card border-light">
                                            <div class="card-body py-2">
                                                <small class="text-muted">{{ $option }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            
                            @if(isset($contentSection['tips']))
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Mẹo quan trọng:
                                </h6>
                                <ul class="mb-0">
                                    @foreach($contentSection['tips'] as $tip)
                                    <li>{{ $tip }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            
                            @if(isset($contentSection['contact']))
                            <div class="contact-info">
                                <h6 class="text-secondary">Thông tin liên hệ:</h6>
                                <div class="row">
                                    @foreach($contentSection['contact'] as $contact)
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-envelope text-primary me-2"></i>
                                            <span>{{ $contact }}</span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                    @endforeach
                    @endif
                    
                    <!-- Navigation -->
                    <div class="row mt-5">
                        <div class="col-6">
                            @php
                                $sectionKeys = array_keys($helpSections);
                                $currentIndex = array_search(request()->route('section'), $sectionKeys);
                                $prevSection = $currentIndex > 0 ? $sectionKeys[$currentIndex - 1] : null;
                            @endphp
                            @if($prevSection)
                            <a href="{{ route('admin.help.show', $prevSection) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                {{ $helpSections[$prevSection]['title'] }}
                            </a>
                            @endif
                        </div>
                        <div class="col-6 text-end">
                            @php
                                $nextSection = $currentIndex < count($sectionKeys) - 1 ? $sectionKeys[$currentIndex + 1] : null;
                            @endphp
                            @if($nextSection)
                            <a href="{{ route('admin.help.show', $nextSection) }}" class="btn btn-outline-secondary">
                                {{ $helpSections[$nextSection]['title'] }}
                                <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                            @endif
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
    .help-section {
        scroll-margin-top: 100px;
    }
    
    .list-group-item.active {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    
    .steps-container ol {
        counter-reset: step-counter;
    }
    
    .steps-container ol li {
        counter-increment: step-counter;
        position: relative;
        padding-left: 2rem;
    }
    
    .steps-container ol li::before {
        content: counter(step-counter);
        position: absolute;
        left: 0;
        top: 0;
        background: var(--bs-primary);
        color: white;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
    }
    
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .sticky-top {
        z-index: 1020;
    }

    /* Markdown content styling */
    .markdown-content {
        line-height: 1.6;
    }

    .markdown-content h4 {
        color: #495057;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .markdown-content h5 {
        color: #6c757d;
        margin-top: 1.25rem;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }

    .markdown-content pre {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        padding: 1rem;
        overflow-x: auto;
        font-size: 0.875rem;
    }

    .markdown-content code {
        background: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        color: #e83e8c;
    }

    .markdown-content pre code {
        background: transparent;
        padding: 0;
        color: #495057;
    }

    .markdown-content ul {
        padding-left: 1.5rem;
        margin-bottom: 1rem;
    }

    .markdown-content li {
        margin-bottom: 0.5rem;
    }

    .markdown-content strong {
        font-weight: 600;
        color: #495057;
    }

    .markdown-content em {
        font-style: italic;
        color: #6c757d;
    }

    .markdown-content a {
        color: #007bff;
        text-decoration: none;
    }

    .markdown-content a:hover {
        color: #0056b3;
        text-decoration: underline;
    }

    .markdown-content p {
        margin-bottom: 1rem;
        color: #495057;
    }

    .markdown-content blockquote {
        border-left: 4px solid #007bff;
        padding-left: 1rem;
        margin: 1rem 0;
        color: #6c757d;
        font-style: italic;
    }
    
    @media print {
        .col-lg-3 {
            display: none;
        }
        .col-lg-9 {
            width: 100%;
        }
        .btn-group {
            display: none;
        }
    }
    
    @media (max-width: 992px) {
        .sticky-top {
            position: relative !important;
            top: auto !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function copyToClipboard() {
        const content = document.getElementById('helpContent').innerText;
        navigator.clipboard.writeText(content).then(function() {
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'toast-message';
            toast.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999;">
                    <i class="fas fa-check me-2"></i>
                    Đã copy nội dung vào clipboard!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.body.appendChild(toast);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    
    // Smooth scrolling for anchor links
    document.addEventListener('DOMContentLoaded', function() {
        const links = document.querySelectorAll('a[href^="#"]');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
</script>
@endpush
