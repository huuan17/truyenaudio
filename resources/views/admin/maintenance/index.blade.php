@extends('layouts.app')

@section('title', 'Story Maintenance')

@section('content')
<div class="container-fluid">
    <x-admin-breadcrumb :items="[
        ['title' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['title' => 'Story Maintenance', 'url' => null]
    ]" />

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-tools mr-2"></i>Story Maintenance
                    </h3>
                    <button type="button" class="btn btn-primary" onclick="runAutoMaintenance()">
                        <i class="fas fa-magic mr-1"></i>Run Auto Maintenance
                    </button>
                </div>
                <div class="card-body">
                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-book"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Stories</span>
                                    <span class="info-box-number">{{ number_format($stats['total_stories']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Crawl Completed</span>
                                    <span class="info-box-number">{{ number_format($stats['completed_crawl']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Crawl Pending</span>
                                    <span class="info-box-number">{{ number_format($stats['pending_crawl']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-file-text"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Chapters</span>
                                    <span class="info-box-number">{{ number_format($stats['total_chapters']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-secondary"><i class="fas fa-file-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">With Content</span>
                                    <span class="info-box-number">{{ number_format($stats['chapters_with_content']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-volume-up"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">With Audio</span>
                                    <span class="info-box-number">{{ number_format($stats['chapters_with_audio']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-hourglass-half"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">TTS Pending</span>
                                    <span class="info-box-number">{{ number_format($stats['pending_tts']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-cog fa-spin"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">TTS Processing</span>
                                    <span class="info-box-number">{{ number_format($stats['processing_tts']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <!-- Form-based buttons (CSRF safe) -->
                                    <form method="POST" action="{{ route('admin.maintenance.cancel-all-tts') }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-warning mr-2"
                                                onclick="return confirm('Cancel all pending TTS requests?')">
                                            <i class="fas fa-times mr-1"></i>Cancel All Pending TTS
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.maintenance.fix-stuck-tts') }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger mr-2"
                                                onclick="return confirm('Fix all stuck TTS requests?')">
                                            <i class="fas fa-wrench mr-1"></i>Fix Stuck TTS
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.maintenance.auto') }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-info mr-2"
                                                onclick="return confirm('Run auto maintenance? This will fix chapter counts, update crawl status, and cancel pending TTS.')">
                                            <i class="fas fa-sync mr-1"></i>Auto Maintenance
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Problem Stories -->
                    @if(count($problemStories) > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Stories with Chapter Count Issues</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Story</th>
                                                    <th>Expected</th>
                                                    <th>Actual</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($problemStories as $story)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $story->title }}</strong><br>
                                                        <small class="text-muted">{{ $story->slug }}</small>
                                                    </td>
                                                    <td>{{ $story->expected_chapters }}</td>
                                                    <td>{{ $story->actual_chapters }}</td>
                                                    <td>
                                                        @if($story->crawl_status == 1)
                                                            <span class="badge badge-success">Completed</span>
                                                        @else
                                                            <span class="badge badge-warning">Pending</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                                onclick="fixChapterCount({{ $story->id }})">
                                                            Fix Count
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-success"
                                                                onclick="updateCrawlStatus({{ $story->id }})">
                                                            Update Status
                                                        </button>

                                                        <!-- Form-based Cancel TTS (CSRF safe) -->
                                                        <form method="POST" action="{{ route('admin.maintenance.cancel-pending-tts', $story->id) }}" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-warning"
                                                                    onclick="return confirm('Cancel all pending TTS requests for this story?')">
                                                                Cancel TTS
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Stuck TTS -->
                    @if(count($stuckTTS) > 0)
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Stuck TTS Requests</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Chapter</th>
                                                    <th>Story</th>
                                                    <th>Started At</th>
                                                    <th>Duration</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($stuckTTS as $chapter)
                                                <tr>
                                                    <td>{{ $chapter->title }}</td>
                                                    <td>{{ $chapter->story->title }}</td>
                                                    <td>{{ $chapter->tts_started_at->format('Y-m-d H:i:s') }}</td>
                                                    <td>{{ $chapter->tts_started_at->diffForHumans() }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Debug CSRF token
console.log('CSRF Token from meta:', $('meta[name="csrf-token"]').attr('content'));
console.log('CSRF Token length:', $('meta[name="csrf-token"]').attr('content')?.length || 0);

// Setup CSRF token for all AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

function runAutoMaintenance() {
    if (!confirm('Run auto maintenance? This will fix chapter counts, update crawl status, and cancel pending TTS.')) {
        return;
    }

    $.post('{{ route("admin.maintenance.auto") }}', {
        _token: '{{ csrf_token() }}'
    })
        .done(function(response) {
            if (response.success) {
                alert('Auto maintenance completed:\n' +
                      'Chapter counts fixed: ' + response.results.chapter_counts_fixed + '\n' +
                      'Crawl status fixed: ' + response.results.crawl_status_fixed + '\n' +
                      'Pending TTS cancelled: ' + response.results.pending_tts_cancelled + '\n' +
                      'Stuck TTS fixed: ' + response.results.stuck_tts_fixed);
                location.reload();
            }
        })
        .fail(function(xhr) {
            console.error('Error:', xhr.responseText);
            alert('Error running auto maintenance: ' + (xhr.responseJSON?.message || 'Unknown error'));
        });
}

function cancelAllPendingTTS() {
    if (!confirm('Cancel all pending TTS requests?')) return;

    $.post('{{ route("admin.maintenance.cancel-all-tts") }}')
        .done(function(response) {
            alert(response.message);
            location.reload();
        })
        .fail(function(xhr) {
            console.error('Error:', xhr.responseText);
            alert('Error: ' + (xhr.responseJSON?.message || 'CSRF token mismatch or server error'));
        });
}

function fixStuckTTS() {
    if (!confirm('Fix all stuck TTS requests?')) return;

    $.post('{{ route("admin.maintenance.fix-stuck-tts") }}')
        .done(function(response) {
            alert(response.message);
            location.reload();
        })
        .fail(function(xhr) {
            console.error('Error:', xhr.responseText);
            alert('Error: ' + (xhr.responseJSON?.message || 'CSRF token mismatch or server error'));
        });
}

function fixChapterCount(storyId) {
    $.post('{{ route("admin.maintenance.fix-chapter-count", ":id") }}'.replace(':id', storyId), {
        _token: '{{ csrf_token() }}'
    })
        .done(function(response) {
            alert(response.message);
            if (response.success) location.reload();
        })
        .fail(function(xhr) {
            console.error('Error:', xhr.responseText);
            alert('Error: ' + (xhr.responseJSON?.message || 'CSRF token mismatch or server error'));
        });
}

function updateCrawlStatus(storyId) {
    $.post('{{ route("admin.maintenance.update-crawl-status", ":id") }}'.replace(':id', storyId), {
        _token: '{{ csrf_token() }}'
    })
        .done(function(response) {
            alert(response.message);
            if (response.success) location.reload();
        })
        .fail(function(xhr) {
            console.error('Error:', xhr.responseText);
            alert('Error: ' + (xhr.responseJSON?.message || 'CSRF token mismatch or server error'));
        });
}

function cancelStoryTTS(storyId) {
    if (!confirm('Cancel all pending TTS requests for this story?')) return;

    $.post('{{ route("admin.maintenance.cancel-pending-tts", ":id") }}'.replace(':id', storyId))
        .done(function(response) {
            alert(response.message);
            if (response.success) location.reload();
        })
        .fail(function(xhr) {
            console.error('Error:', xhr.responseText);
            alert('Error: ' + (xhr.responseJSON?.message || 'CSRF token mismatch or server error'));
        });
}
</script>
@endpush
