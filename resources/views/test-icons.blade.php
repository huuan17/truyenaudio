@extends('layouts.frontend')

@section('title', 'Test FontAwesome Icons')

@section('content')
<div class="container my-5">
    <h1>🧪 Test FontAwesome Icons</h1>
    <p class="text-muted">Kiểm tra các icon được sử dụng trong audio player và toàn bộ website</p>

    <!-- Audio Player Icons -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>🎵 Audio Player Icons</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-step-backward fa-2x mb-2"></i>
                        <br><code>fas fa-step-backward</code>
                        <br><small class="text-muted">Previous Chapter</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-step-forward fa-2x mb-2"></i>
                        <br><code>fas fa-step-forward</code>
                        <br><small class="text-muted">Next Chapter</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-play fa-2x mb-2"></i>
                        <br><code>fas fa-play</code>
                        <br><small class="text-muted">Play</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-pause fa-2x mb-2"></i>
                        <br><code>fas fa-pause</code>
                        <br><small class="text-muted">Pause</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-volume-up fa-2x mb-2"></i>
                        <br><code>fas fa-volume-up</code>
                        <br><small class="text-muted">Volume</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-headphones fa-2x mb-2"></i>
                        <br><code>fas fa-headphones</code>
                        <br><small class="text-muted">Audio Section</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-play-circle fa-2x mb-2"></i>
                        <br><code>fas fa-play-circle</code>
                        <br><small class="text-muted">Playlist Item</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-list fa-2x mb-2"></i>
                        <br><code>fas fa-list</code>
                        <br><small class="text-muted">Playlist</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Common Website Icons -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>🌐 Common Website Icons</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-book fa-2x mb-2"></i>
                        <br><code>fas fa-book</code>
                        <br><small class="text-muted">Book</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-book-open fa-2x mb-2"></i>
                        <br><code>fas fa-book-open</code>
                        <br><small class="text-muted">Read</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-user fa-2x mb-2"></i>
                        <br><code>fas fa-user</code>
                        <br><small class="text-muted">Author</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-tags fa-2x mb-2"></i>
                        <br><code>fas fa-tags</code>
                        <br><small class="text-muted">Genres</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <br><code>fas fa-clock</code>
                        <br><small class="text-muted">Time</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <br><code>fas fa-info-circle</code>
                        <br><small class="text-muted">Status</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-forward fa-2x mb-2"></i>
                        <br><code>fas fa-forward</code>
                        <br><small class="text-muted">Latest</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-chevron-right fa-2x mb-2"></i>
                        <br><code>fas fa-chevron-right</code>
                        <br><small class="text-muted">Arrow</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Icons -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>⚙️ Admin Icons</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-cog fa-2x mb-2"></i>
                        <br><code>fas fa-cog</code>
                        <br><small class="text-muted">Settings</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-edit fa-2x mb-2"></i>
                        <br><code>fas fa-edit</code>
                        <br><small class="text-muted">Edit</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-trash fa-2x mb-2"></i>
                        <br><code>fas fa-trash</code>
                        <br><small class="text-muted">Delete</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded text-center">
                        <i class="fas fa-plus fa-2x mb-2"></i>
                        <br><code>fas fa-plus</code>
                        <br><small class="text-muted">Add</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Loading Test -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>🔍 Font Loading Test</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h6>Font Information:</h6>
                <p><strong>CSS File:</strong> fontawesome-6.4.0-all.min.css</p>
                <p><strong>Font Files:</strong> fa-solid-900.woff2, fa-regular-400.woff2, fa-brands-400.woff2</p>
                <p><strong>Font Path:</strong> ../fonts/ (relative to CSS)</p>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <h6>Solid Icons (fas)</h6>
                    <div class="d-flex gap-2 mb-2">
                        <i class="fas fa-home"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-heart"></i>
                        <i class="fas fa-check"></i>
                        <i class="fas fa-times"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6>Regular Icons (far)</h6>
                    <div class="d-flex gap-2 mb-2">
                        <i class="far fa-star"></i>
                        <i class="far fa-heart"></i>
                        <i class="far fa-circle"></i>
                        <i class="far fa-square"></i>
                        <i class="far fa-file"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6>Brand Icons (fab)</h6>
                    <div class="d-flex gap-2 mb-2">
                        <i class="fab fa-facebook"></i>
                        <i class="fab fa-twitter"></i>
                        <i class="fab fa-youtube"></i>
                        <i class="fab fa-github"></i>
                        <i class="fab fa-google"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Buttons -->
    <div class="card">
        <div class="card-header">
            <h5>🎮 Interactive Test</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Audio Player Simulation</h6>
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-outline-secondary">
                            <i class="fas fa-step-backward"></i>
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="fas fa-step-forward"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>Admin Actions</h6>
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Add
                        </button>
                        <button class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if FontAwesome is loaded
    const testElement = document.createElement('i');
    testElement.className = 'fas fa-home';
    testElement.style.position = 'absolute';
    testElement.style.left = '-9999px';
    document.body.appendChild(testElement);
    
    setTimeout(() => {
        const computedStyle = window.getComputedStyle(testElement);
        const fontFamily = computedStyle.fontFamily;
        
        console.log('FontAwesome Test Results:');
        console.log('Font Family:', fontFamily);
        console.log('Font Weight:', computedStyle.fontWeight);
        console.log('Font Style:', computedStyle.fontStyle);
        
        if (fontFamily.includes('Font Awesome')) {
            console.log('✅ FontAwesome is loaded correctly');
        } else {
            console.log('❌ FontAwesome is NOT loaded correctly');
            console.log('Expected: Font Awesome 6 Free');
            console.log('Actual:', fontFamily);
        }
        
        document.body.removeChild(testElement);
    }, 1000);
});
</script>
@endsection
