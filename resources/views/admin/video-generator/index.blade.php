@extends('layouts.app')

@section('title', 'Tạo Video Đa Nền Tảng')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        [
            'title' => 'Tạo Video',
            'badge' => 'Đa nền tảng'
        ]
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-video mr-2"></i>Tạo Video Đa Nền Tảng
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.video-templates.index') }}" class="btn btn-warning btn-sm mr-2">
                            <i class="fas fa-layer-group mr-1"></i>Templates
                        </a>
                        <a href="{{ route('admin.video-queue.index') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-tasks mr-1"></i>Trạng thái xử lý
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Video Generation Mode Selection -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-cogs mr-2"></i>Chế độ tạo video</h5>
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-outline-primary">
                                    <input type="radio" name="video_mode" id="single_video" value="single">
                                    <i class="fas fa-video mr-2"></i>Tạo 1 video
                                </label>
                                <label class="btn btn-outline-primary active">
                                    <input type="radio" name="video_mode" id="batch_video" value="batch" checked>
                                    <i class="fas fa-layer-group mr-2"></i>Tạo nhiều video
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Main Video Generation Form -->
                    <form id="videoGeneratorForm" method="POST" action="{{ route('admin.video-generator.generate') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="mode" id="form_mode" value="batch">

                        <!-- Batch Video Section (Main Content) -->
                        @include('admin.video-generator.partials.batch-section')

                        <!-- Single Video Sections (Hidden by default) -->
                        <div id="single-video-sections" style="display: none;">
                            <div class="row">
                                <!-- Left Column: Media Content -->
                                <div class="col-lg-8">
                                    @include('admin.video-generator.partials.media-section')
                                    @include('admin.video-generator.partials.audio-section')
                                    @include('admin.video-generator.partials.subtitle-section')
                                    @include('admin.video-generator.partials.logo-section')
                                </div>

                                <!-- Right Column: Platform & Settings -->
                                <div class="col-lg-4">
                                    @include('admin.video-generator.partials.platform-section')
                                    @include('admin.video-generator.partials.video-info-section')
                                    @include('admin.video-generator.partials.existing-videos')
                                </div>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-success btn-lg" id="generateBtn">
                                        <i class="fas fa-play mr-2"></i>Tạo Video
                                    </button>
                                    <button type="reset" class="btn btn-secondary btn-lg ml-2">
                                        <i class="fas fa-undo mr-2"></i>Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Shared Modals -->
@include('admin.video-generator.partials.shared-modals')

@endsection

@push('scripts')
{{-- Load page-specific JavaScript --}}
<script src="{{ asset('assets/js/video-generator-page.js') }}?v={{ filemtime(public_path('assets/js/video-generator-page.js')) }}"></script>

<script>
// Page-specific configuration
window.routes = {
    videoGeneratorGenerate: '{{ route("admin.video-generator.generate") }}',
    videoGeneratorGenerateBatch: '{{ route("admin.video-generator.generate-batch") }}',
    videoGeneratorStatus: '{{ route("admin.video-generator.status") }}',
    videoGeneratorDelete: '{{ route("admin.video-generator.delete") }}',
    audioLibraryForVideoGenerator: '{{ route("admin.audio-library.for-video-generator") }}'
};

// Initialize page when ready
$(document).ready(function() {
    console.log('Video Generator page loaded with routes:', window.routes);

    // Initialize preview for video generator
    if (typeof VideoPreview !== 'undefined') {
        window.videoPreview = new VideoPreview({
            containerSelector: '.col-lg-4',
            insertPosition: 'beforeend',
            formType: 'generator',
            platform: 'auto',
            customSelectors: {
                images: ['input[name="product_images[]"]', 'input[name="background_images[]"]'],
                audio: ['input[name="background_audio"]', 'input[name="audio_file"]'],
                subtitle: ['textarea[name="script_text"]', 'textarea[name="content"]'],
                tts: ['textarea[name="script_text"]', 'textarea[name="content"]']
            }
        });
    }
});
</script>

<!-- Video Preview Script -->
<script src="{{ asset('js/video-preview.js') }}"></script>
@endpush
