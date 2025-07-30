/**
 * Video Preview System
 * Generates preview videos when components are added/changed
 */

class VideoPreview {
    constructor(options = {}) {
        this.currentPreviewId = null;
        this.components = {
            images: [],
            audio: null,
            subtitle: null
        };
        this.previewContainer = null;
        this.isGenerating = false;

        // Configuration options
        this.options = {
            containerSelector: options.containerSelector || '.col-lg-4', // Default sidebar
            insertPosition: options.insertPosition || 'beforeend', // beforeend, afterbegin, etc.
            autoDetectInputs: options.autoDetectInputs !== false, // Default true
            customSelectors: options.customSelectors || {},
            platform: options.platform || 'auto', // auto, tiktok, youtube
            formType: options.formType || 'template', // template, generator, creator
            ...options
        };

        this.init();
    }
    
    init() {
        this.createPreviewContainer();
        this.bindEvents();
    }
    
    /**
     * Create preview container in the UI
     */
    createPreviewContainer() {
        // Find container to insert preview
        const container = document.querySelector(this.options.containerSelector);
        if (!container) {
            console.warn('Preview container not found:', this.options.containerSelector);
            return;
        }

        // Get template resolution info
        const templateInfo = this.getTemplateInfo();

        // Determine layout based on form type
        const isFullWidth = this.options.formType === 'generator' || !container.classList.contains('col-lg-4');
        const cardClass = isFullWidth ? 'col-lg-6 col-md-12' : '';
        const videoHeight = isFullWidth ? '400px' : '300px';

        const previewHtml = `
            <div id="video-preview-container" class="card mt-3 ${cardClass}">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-play-circle"></i> Preview Video
                        <span class="badge badge-info ml-2">${templateInfo.resolution}</span>
                        <span class="badge badge-secondary ml-1">${templateInfo.platform}</span>
                        ${this.options.formType === 'generator' ? '<span class="badge badge-success ml-1">Live Preview</span>' : ''}
                    </h6>
                </div>
                <div class="card-body">
                    <div id="preview-status" class="text-center py-3">
                        <p class="text-muted mb-2">Thêm hình ảnh để xem preview</p>
                        <small class="text-info">
                            <i class="fas fa-info-circle"></i>
                            ${templateInfo.resolution} (${templateInfo.aspectRatio})
                        </small>
                    </div>
                    <div id="preview-video" style="display: none;">
                        <div class="video-container position-relative">
                            <video controls class="w-100" style="max-height: ${videoHeight}; background: #000; border-radius: 8px;">
                                <source src="" type="video/mp4">
                                Trình duyệt không hỗ trợ video.
                            </video>
                            <div class="video-info-overlay position-absolute" style="top: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                                ${templateInfo.resolution}
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted d-block">Preview tự động cập nhật</small>
                            <small class="text-info">
                                <i class="fas fa-expand-arrows-alt"></i> ${templateInfo.resolution}
                            </small>
                        </div>
                    </div>
                    <div id="preview-loading" style="display: none;">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="sr-only">Đang tạo preview...</span>
                            </div>
                            <p class="mt-2 mb-1 text-muted">Đang tạo preview...</p>
                            <small class="text-info">${templateInfo.resolution}</small>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert preview container
        if (isFullWidth) {
            // For full-width layouts, create a row and insert as column
            const existingRow = container.querySelector('.row');
            if (existingRow) {
                existingRow.insertAdjacentHTML('beforeend', previewHtml);
            } else {
                container.insertAdjacentHTML(this.options.insertPosition, `<div class="row">${previewHtml}</div>`);
            }
        } else {
            // For sidebar layouts, insert directly
            container.insertAdjacentHTML(this.options.insertPosition, previewHtml);
        }

        this.previewContainer = document.getElementById('video-preview-container');
    }
    
    /**
     * Bind events to form elements
     */
    bindEvents() {
        if (!this.options.autoDetectInputs) return;

        // Image upload events - enhanced selectors
        const imageSelectors = [
            'input[type="file"][accept*="image"]',
            'input[name*="image"]',
            'input[name*="product_image"]',
            'input[id*="image"]',
            ...(this.options.customSelectors.images || [])
        ];

        imageSelectors.forEach(selector => {
            const inputs = document.querySelectorAll(selector);
            inputs.forEach(input => {
                input.addEventListener('change', (e) => {
                    this.handleImageChange(e);
                });
            });
        });

        // Audio upload events - enhanced selectors
        const audioSelectors = [
            'input[type="file"][accept*="audio"]',
            'input[name*="audio"]',
            'input[name*="background_audio"]',
            'input[id*="audio"]',
            ...(this.options.customSelectors.audio || [])
        ];

        audioSelectors.forEach(selector => {
            const inputs = document.querySelectorAll(selector);
            inputs.forEach(input => {
                input.addEventListener('change', (e) => {
                    this.handleAudioChange(e);
                });
            });
        });

        // Subtitle text events - enhanced selectors
        const subtitleSelectors = [
            'textarea[name*="sub"], input[name*="sub"]',
            'textarea[id*="sub"], input[id*="sub"]',
            'textarea[name*="subtitle"], input[name*="subtitle"]',
            'textarea[id*="subtitle"], input[id*="subtitle"]',
            ...(this.options.customSelectors.subtitle || [])
        ];

        subtitleSelectors.forEach(selector => {
            const inputs = document.querySelectorAll(selector);
            inputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    this.handleSubtitleChange(e);
                });
            });
        });

        // TTS text events - enhanced selectors
        const ttsSelectors = [
            'textarea[name*="text"], textarea[name*="script"]',
            'textarea[id*="text"], textarea[id*="script"]',
            'textarea[name*="content"]',
            'input[name*="title"]', // For video titles
            ...(this.options.customSelectors.tts || [])
        ];

        ttsSelectors.forEach(selector => {
            const inputs = document.querySelectorAll(selector);
            inputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    this.handleTTSChange(e);
                });
            });
        });

        console.log('VideoPreview: Event binding completed for', this.options.formType);
    }
    
    /**
     * Handle image upload change
     */
    async handleImageChange(event) {
        const files = Array.from(event.target.files);
        if (files.length > 0) {
            this.showLoading();
            try {
                // Upload files to server first
                this.components.images = await this.uploadFiles(files, 'images');
                if (this.components.images.length > 0) {
                    this.generatePreview();
                } else {
                    this.showError('Không thể upload hình ảnh');
                }
            } catch (error) {
                this.showError('Lỗi upload hình ảnh: ' + error.message);
            }
        }
    }
    
    /**
     * Handle audio upload change
     */
    async handleAudioChange(event) {
        const file = event.target.files[0];
        if (file) {
            // Upload file to server first
            const uploadedFiles = await this.uploadFiles([file], 'audio');
            this.components.audio = {
                type: 'file',
                file: uploadedFiles[0]
            };
            this.generatePreview();
        }
    }
    
    /**
     * Handle subtitle text change
     */
    handleSubtitleChange(event) {
        const text = event.target.value.trim();
        console.log('Subtitle change detected:', text);

        if (text) {
            this.components.subtitle = {
                text: text,
                size: 24
            };
            console.log('Subtitle component set:', this.components.subtitle);
        } else {
            this.components.subtitle = null;
            console.log('Subtitle component cleared');
        }

        // Debounce preview generation for text inputs
        clearTimeout(this.subtitleTimeout);
        this.subtitleTimeout = setTimeout(() => {
            console.log('Generating preview with subtitle:', this.components.subtitle);
            this.generatePreview();
        }, 1000);
    }
    
    /**
     * Handle TTS text change
     */
    handleTTSChange(event) {
        const text = event.target.value.trim();
        if (text) {
            this.components.audio = {
                type: 'tts',
                text: text
            };
        } else {
            this.components.audio = null;
        }
        
        // Debounce preview generation for text inputs
        clearTimeout(this.ttsTimeout);
        this.ttsTimeout = setTimeout(() => {
            this.generatePreview();
        }, 2000);
    }
    
    /**
     * Upload files to server for preview
     */
    async uploadFiles(files, type) {
        const formData = new FormData();
        files.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });
        formData.append('type', type);

        try {
            const response = await fetch('/admin/video-preview/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                return result.file_paths;
            } else {
                throw new Error(result.error || 'Upload failed');
            }
        } catch (error) {
            console.error('File upload error:', error);
            this.showError('Lỗi upload file: ' + error.message);
            return [];
        }
    }

    /**
     * Generate preview video
     */
    async generatePreview() {
        if (this.isGenerating) return;
        if (this.components.images.length === 0) {
            this.showStatus('Thêm hình ảnh để xem preview');
            return;
        }
        
        this.isGenerating = true;
        this.showLoading();
        
        try {
            // Clean up previous preview
            if (this.currentPreviewId) {
                await this.deletePreview(this.currentPreviewId);
            }
            
            const response = await fetch('/admin/video-preview/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    components: this.components
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.currentPreviewId = result.preview_id;
                this.showVideo(result.preview_url);
            } else {
                this.showError('Lỗi tạo preview: ' + result.error);
            }
            
        } catch (error) {
            console.error('Preview generation error:', error);
            this.showError('Lỗi kết nối khi tạo preview');
        } finally {
            this.isGenerating = false;
        }
    }
    
    /**
     * Delete preview file
     */
    async deletePreview(previewId) {
        try {
            await fetch('/admin/video-preview/delete', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    preview_id: previewId
                })
            });
        } catch (error) {
            console.error('Error deleting preview:', error);
        }
    }
    
    /**
     * Show loading state
     */
    showLoading() {
        document.getElementById('preview-status').style.display = 'none';
        document.getElementById('preview-video').style.display = 'none';
        document.getElementById('preview-loading').style.display = 'block';
    }
    
    /**
     * Show video preview
     */
    showVideo(videoUrl) {
        const video = document.querySelector('#preview-video video');
        video.src = videoUrl;
        
        document.getElementById('preview-status').style.display = 'none';
        document.getElementById('preview-loading').style.display = 'none';
        document.getElementById('preview-video').style.display = 'block';
    }
    
    /**
     * Show status message
     */
    showStatus(message) {
        const statusEl = document.getElementById('preview-status');
        statusEl.innerHTML = `<p class="text-muted">${message}</p>`;
        
        statusEl.style.display = 'block';
        document.getElementById('preview-video').style.display = 'none';
        document.getElementById('preview-loading').style.display = 'none';
    }
    
    /**
     * Show error message
     */
    showError(message) {
        const statusEl = document.getElementById('preview-status');
        statusEl.innerHTML = `<p class="text-danger"><i class="fas fa-exclamation-triangle"></i> ${message}</p>`;
        
        statusEl.style.display = 'block';
        document.getElementById('preview-video').style.display = 'none';
        document.getElementById('preview-loading').style.display = 'none';
    }
    
    /**
     * Get template information from page
     */
    getTemplateInfo() {
        // Try to extract template info from page elements
        const templateCard = document.querySelector('.template-stats, .card-body');
        let platform = 'tiktok'; // default
        let resolution = '1080x1920'; // default TikTok

        // Check if we can find platform info in the page
        const platformBadge = document.querySelector('.badge');
        if (platformBadge) {
            const text = platformBadge.textContent.toLowerCase();
            if (text.includes('youtube')) {
                platform = 'youtube';
                resolution = '1920x1080';
            } else if (text.includes('tiktok')) {
                platform = 'tiktok';
                resolution = '1080x1920';
            }
        }

        // Check for resolution in settings preview
        const settingsPreview = document.querySelector('.settings-preview');
        if (settingsPreview) {
            const settingItems = settingsPreview.querySelectorAll('.setting-item');
            settingItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes('platform')) {
                    if (text.includes('youtube')) {
                        platform = 'youtube';
                        resolution = '1920x1080';
                    } else if (text.includes('tiktok')) {
                        platform = 'tiktok';
                        resolution = '1080x1920';
                    }
                }
            });
        }

        const aspectRatio = platform === 'youtube' ? '16:9' : '9:16';

        return {
            platform: platform.charAt(0).toUpperCase() + platform.slice(1),
            resolution: resolution,
            aspectRatio: aspectRatio
        };
    }

    /**
     * Cleanup when page unloads
     */
    cleanup() {
        if (this.currentPreviewId) {
            this.deletePreview(this.currentPreviewId);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const preview = new VideoPreview();
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        preview.cleanup();
    });
});
