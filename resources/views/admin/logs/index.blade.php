@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-admin-breadcrumb :items="[
        ['title' => 'System Logs']
    ]" />

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-alt mr-2"></i>System Logs Viewer
                        </h5>
                        <div>
                            <button class="btn btn-light btn-sm" onclick="refreshLogs()">
                                <i class="fas fa-sync mr-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="row mb-4">
                        <div class="col-md-4">
                            <label for="context" class="form-label">Context</label>
                            <select name="context" id="context" class="form-control">
                                @foreach($contexts as $key => $label)
                                    <option value="{{ $key }}" {{ $context === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="days" class="form-label">Days to Show</label>
                            <select name="days" id="days" class="form-control">
                                <option value="1" {{ $days == 1 ? 'selected' : '' }}>Today</option>
                                <option value="3" {{ $days == 3 ? 'selected' : '' }}>Last 3 days</option>
                                <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check">
                                <input type="checkbox" id="errorsOnly" class="form-check-input">
                                <label for="errorsOnly" class="form-check-label">Errors Only</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i>Filter
                            </button>
                        </div>
                    </form>

                    <!-- Recent Errors Summary -->
                    @if(count($recentErrors) > 0)
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle mr-2"></i>Recent Errors (Last 24h)</h6>
                        <div class="mt-2">
                            @foreach(array_slice($recentErrors, 0, 3) as $error)
                                <div class="small mb-1">
                                    <strong>{{ $error['timestamp']->format('H:i:s') }}:</strong>
                                    {{ Str::limit($error['message'], 100) }}
                                </div>
                            @endforeach
                            @if(count($recentErrors) > 3)
                                <div class="small text-muted">... and {{ count($recentErrors) - 3 }} more errors</div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>No errors in the last 24 hours!
                    </div>
                    @endif

                    <!-- Log Files -->
                    <div class="row">
                        @forelse($logFiles as $file)
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-calendar mr-2"></i>{{ $file['date'] }}
                                    </h6>
                                    <div>
                                        <span class="badge badge-info">{{ number_format($file['size'] / 1024, 1) }} KB</span>
                                        <a href="{{ route('admin.logs.download', ['context' => $context, 'date' => $file['date']]) }}" 
                                           class="btn btn-sm btn-outline-primary ml-1">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="log-content" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.8rem;">
                                        @php
                                            $lines = explode("\n", $file['content']);
                                            $lines = array_filter($lines);
                                            $recentLines = array_slice($lines, -20); // Show last 20 lines
                                        @endphp
                                        
                                        @if(count($lines) > 20)
                                            <div class="text-muted small">... showing last 20 lines of {{ count($lines) }} total ...</div>
                                        @endif
                                        
                                        @foreach($recentLines as $line)
                                            <div class="log-line 
                                                @if(strpos($line, 'ERROR.') !== false) text-danger
                                                @elseif(strpos($line, 'WARNING.') !== false) text-warning
                                                @elseif(strpos($line, 'INFO.') !== false) text-info
                                                @endif">
                                                {{ $line }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No log files found</h4>
                                <p class="text-muted">No logs found for context "{{ $contexts[$context] }}" in the last {{ $days }} days.</p>
                            </div>
                        </div>
                        @endforelse
                    </div>

                    <!-- Actions -->
                    @if(count($logFiles) > 0)
                    <div class="mt-4 pt-3 border-top">
                        <h6>Actions</h6>
                        <form method="POST" action="{{ route('admin.logs.clear') }}" class="d-inline" 
                              onsubmit="return confirm('Are you sure you want to clear old logs?')">
                            @csrf
                            <input type="hidden" name="context" value="{{ $context }}">
                            <input type="hidden" name="days" value="30">
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="fas fa-trash mr-1"></i>Clear logs older than 30 days
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.log-line {
    white-space: pre-wrap;
    word-break: break-all;
    margin-bottom: 2px;
    padding: 1px 0;
}

.log-content {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.5rem;
}

.card-header h6 {
    color: inherit;
}
</style>
@endpush

@push('scripts')
<script>
function refreshLogs() {
    window.location.reload();
}

// Auto-refresh every 30 seconds
setInterval(function() {
    if (document.getElementById('errorsOnly').checked) {
        refreshLogs();
    }
}, 30000);
</script>
@endpush
