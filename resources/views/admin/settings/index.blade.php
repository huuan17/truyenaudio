@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'Cài đặt hệ thống']
    ]" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>⚙️ Cài đặt hệ thống</h2>
        <div>
            <a href="{{ route('admin.settings.create', ['group' => $group]) }}" class="btn btn-success">
                <i class="fas fa-plus mr-1"></i>Thêm cài đặt
            </a>
            <a href="{{ route('admin.settings.initialize') }}" class="btn btn-info">
                <i class="fas fa-sync mr-1"></i>Khởi tạo mặc định
            </a>
            <a href="{{ route('test.tracking') }}" class="btn btn-warning" target="_blank">
                <i class="fas fa-bug mr-1"></i>Test Tracking
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <!-- Group Navigation -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Nhóm cài đặt</h5>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($groups as $groupKey => $groupName)
                        <a href="{{ route('admin.settings.index', ['group' => $groupKey]) }}" 
                           class="list-group-item list-group-item-action {{ $group === $groupKey ? 'active' : '' }}">
                            @switch($groupKey)
                                @case('general')
                                    <i class="fas fa-cog mr-2"></i>
                                    @break
                                @case('seo')
                                    <i class="fas fa-search mr-2"></i>
                                    @break
                                @case('tracking')
                                    <i class="fas fa-chart-line mr-2"></i>
                                    @break
                                @case('social')
                                    <i class="fas fa-share-alt mr-2"></i>
                                    @break
                                @case('appearance')
                                    <i class="fas fa-palette mr-2"></i>
                                    @break
                                @default
                                    <i class="fas fa-folder mr-2"></i>
                            @endswitch
                            {{ $groupName }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Settings Form -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $groups[$group] ?? 'Cài đặt' }}</h5>
                </div>
                <div class="card-body">
                    @if($settings->count() > 0)
                        <form action="{{ route('admin.settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="group" value="{{ $group }}">

                            @foreach($settings as $setting)
                                <div class="form-group">
                                    <label for="setting_{{ $setting->key }}">
                                        {{ $setting->label }}
                                        @if($setting->description)
                                            <small class="text-muted d-block">{{ $setting->description }}</small>
                                        @endif
                                    </label>

                                    @switch($setting->type)
                                        @case('textarea')
                                        @case('code')
                                            <textarea name="settings[{{ $setting->key }}]" 
                                                      id="setting_{{ $setting->key }}" 
                                                      class="form-control {{ $setting->type === 'code' ? 'font-monospace' : '' }}" 
                                                      rows="{{ $setting->type === 'code' ? '8' : '4' }}"
                                                      placeholder="{{ $setting->description }}">{{ old('settings.' . $setting->key, $setting->value) }}</textarea>
                                            @break

                                        @case('boolean')
                                            <div class="form-check">
                                                <input type="checkbox" 
                                                       name="settings[{{ $setting->key }}]" 
                                                       id="setting_{{ $setting->key }}" 
                                                       class="form-check-input" 
                                                       value="1"
                                                       {{ old('settings.' . $setting->key, $setting->value) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                    Kích hoạt
                                                </label>
                                            </div>
                                            @break

                                        @case('url')
                                            <input type="url" 
                                                   name="settings[{{ $setting->key }}]" 
                                                   id="setting_{{ $setting->key }}" 
                                                   class="form-control" 
                                                   value="{{ old('settings.' . $setting->key, $setting->value) }}"
                                                   placeholder="{{ $setting->description }}">
                                            @break

                                        @case('email')
                                            <input type="email" 
                                                   name="settings[{{ $setting->key }}]" 
                                                   id="setting_{{ $setting->key }}" 
                                                   class="form-control" 
                                                   value="{{ old('settings.' . $setting->key, $setting->value) }}"
                                                   placeholder="{{ $setting->description }}">
                                            @break

                                        @default
                                            <input type="text" 
                                                   name="settings[{{ $setting->key }}]" 
                                                   id="setting_{{ $setting->key }}" 
                                                   class="form-control" 
                                                   value="{{ old('settings.' . $setting->key, $setting->value) }}"
                                                   placeholder="{{ $setting->description }}">
                                    @endswitch

                                    @error('settings.' . $setting->key)
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror

                                    <!-- Edit/Delete buttons -->
                                    <div class="mt-2">
                                        <a href="{{ route('admin.settings.edit', $setting->id) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Sửa
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger delete-setting-btn"
                                                data-setting-id="{{ $setting->id }}"
                                                data-setting-name="{{ $setting->label }}"
                                                data-setting-group="{{ $setting->group }}">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>

                                        <!-- Hidden form for DELETE -->
                                        <form id="delete-form-{{ $setting->id }}"
                                              action="{{ url('admin/settings/' . $setting->id) }}"
                                              method="POST"
                                              style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </div>
                                <hr>
                            @endforeach

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Lưu cài đặt
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-cog fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có cài đặt nào</h5>
                            <p class="text-muted">Hãy thêm cài đặt đầu tiên cho nhóm này.</p>
                            <a href="{{ route('admin.settings.create', ['group' => $group]) }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>Thêm cài đặt
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Initial resize
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    });

    // Handle delete buttons
    const deleteButtons = document.querySelectorAll('.delete-setting-btn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const settingId = this.dataset.settingId;
            const settingName = this.dataset.settingName;
            const settingGroup = this.dataset.settingGroup;
            const confirmMessage = `Bạn có chắc chắn muốn xóa cài đặt "${settingName}"?`;

            if (confirm(confirmMessage)) {
                // Submit the hidden form
                const form = document.getElementById('delete-form-' + settingId);
                if (form) {
                    form.submit();
                } else {
                    // Fallback: create and submit form dynamically
                    const deleteForm = document.createElement('form');
                    deleteForm.method = 'POST';
                    deleteForm.action = '/admin/settings/' + settingId;

                    // Add CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (csrfToken) {
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = csrfToken.getAttribute('content');
                        deleteForm.appendChild(csrfInput);
                    }

                    // Add method spoofing
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    deleteForm.appendChild(methodInput);

                    document.body.appendChild(deleteForm);
                    deleteForm.submit();
                }
            }
        });
    });
});
</script>
@endpush
@endsection
