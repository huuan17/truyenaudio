@props([
    'name' => 'content',
    'id' => null,
    'value' => '',
    'height' => 400,
    'placeholder' => 'Nhập nội dung...',
    'required' => false,
    'toolbar' => 'default'
])

@php
    $editorId = $id ?? $name . '_editor_' . uniqid();
    
    // Define toolbar configurations
    $toolbars = [
        'basic' => 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link',
        'default' => 'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | removeformat | help',
        'full' => 'undo redo | formatselect fontselect fontsizeselect | bold italic underline strikethrough subscript superscript | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | insertdatetime | removeformat code | fullscreen preview | help'
    ];
    
    $selectedToolbar = $toolbars[$toolbar] ?? $toolbars['default'];
@endphp

<div class="tinymce-wrapper">
    <textarea 
        name="{{ $name }}" 
        id="{{ $editorId }}" 
        class="form-control tinymce-editor"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        style="display: none;"
    >{{ old($name, $value) }}</textarea>
</div>

@push('styles')
<style>
    .tinymce-wrapper {
        position: relative;
    }
    
    .tox-tinymce {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    
    .tox-editor-header {
        border-bottom: 1px solid #ced4da;
    }
    
    .tox-statusbar {
        border-top: 1px solid #ced4da;
    }
    
    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .tox-tinymce {
            border-color: #495057;
        }
        
        .tox-editor-header,
        .tox-statusbar {
            border-color: #495057;
        }
    }
    
    /* Error state */
    .is-invalid + .tox-tinymce {
        border-color: #dc3545;
    }
    
    /* Focus state */
    .tox-tinymce.tox-edit-focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if TinyMCE is loaded
    if (typeof tinymce === 'undefined') {
        console.error('TinyMCE is not loaded!');
        return;
    }

    // Initialize TinyMCE for this specific editor
    tinymce.init({
        selector: '#{{ $editorId }}',
        height: {{ $height }},
        menubar: false,
        toolbar: '{{ $selectedToolbar }}',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        content_style: `
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; 
                font-size: 14px;
                line-height: 1.6;
                margin: 1rem;
            }
            p { margin-bottom: 1rem; }
            h1, h2, h3, h4, h5, h6 { margin-top: 1.5rem; margin-bottom: 0.5rem; }
        `,
        placeholder: '{{ $placeholder }}',
        branding: false,
        promotion: false,
        resize: 'vertical',
        statusbar: true,
        elementpath: false,
        
        // Language settings (fallback to English if Vietnamese not available)
        language: 'vi',
        language_url: '{{ asset("assets/tinymce/langs/vi.js") }}',

        // Image upload settings
        @if(Route::has('admin.upload.image'))
        images_upload_url: '{{ route("admin.upload.image") }}',
        images_upload_credentials: true,
        @endif
        @if(Route::has('admin.upload.image'))
        images_upload_handler: function (blobInfo, success, failure) {
            var xhr, formData;
            xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open('POST', '{{ route("admin.upload.image") }}');
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

            xhr.onload = function() {
                var json;
                if (xhr.status != 200) {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                }
                json = JSON.parse(xhr.responseText);
                if (!json || typeof json.location != 'string') {
                    failure('Invalid JSON: ' + xhr.responseText);
                    return;
                }
                success(json.location);
            };

            formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        },
        @endif
        
        // Auto-save settings
        autosave_ask_before_unload: true,
        autosave_interval: '30s',
        autosave_prefix: 'tinymce-autosave-{path}{query}-{{ $editorId }}-',
        autosave_restore_when_empty: false,
        autosave_retention: '2m',
        
        // Setup callback
        setup: function(editor) {
            // Add custom styles
            editor.on('init', function() {
                editor.getContainer().style.transition = 'border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out';
            });
            
            // Handle focus/blur for Bootstrap validation styling
            editor.on('focus', function() {
                var container = editor.getContainer();
                container.classList.add('tox-edit-focus');
                
                // Remove Bootstrap validation classes
                var textarea = document.getElementById('{{ $editorId }}');
                if (textarea) {
                    textarea.classList.remove('is-invalid');
                }
            });
            
            editor.on('blur', function() {
                var container = editor.getContainer();
                container.classList.remove('tox-edit-focus');
                
                // Trigger validation if needed
                var textarea = document.getElementById('{{ $editorId }}');
                if (textarea && textarea.hasAttribute('required')) {
                    var content = editor.getContent({format: 'text'}).trim();
                    if (!content) {
                        textarea.classList.add('is-invalid');
                    }
                }
            });
            
            // Handle content changes for validation
            editor.on('change keyup', function() {
                var textarea = document.getElementById('{{ $editorId }}');
                if (textarea && textarea.hasAttribute('required')) {
                    var content = editor.getContent({format: 'text'}).trim();
                    if (content) {
                        textarea.classList.remove('is-invalid');
                    }
                }
            });
        }
    });
});
</script>
@endpush
