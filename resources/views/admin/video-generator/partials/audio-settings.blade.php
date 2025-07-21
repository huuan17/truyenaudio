<!-- Audio Settings Component -->
<div class="form-section">
    <h6><i class="fas fa-volume-up mr-2"></i>Cài đặt Audio</h6>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="{{ $prefix }}_voice">Giọng đọc</label>
                <select name="voice" id="{{ $prefix }}_voice" class="form-control" required>
                    @foreach($voices as $code => $name)
                        <option value="{{ $code }}" {{ old('voice', 'hn_female_ngochuyen_full_48k-fhg') == $code ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="{{ $prefix }}_bitrate">Bitrate (kbps)</label>
                <select name="bitrate" id="{{ $prefix }}_bitrate" class="form-control" required>
                    <option value="64" {{ old('bitrate') == '64' ? 'selected' : '' }}>64 kbps</option>
                    <option value="128" {{ old('bitrate', '128') == '128' ? 'selected' : '' }}>128 kbps</option>
                    <option value="192" {{ old('bitrate') == '192' ? 'selected' : '' }}>192 kbps</option>
                    <option value="256" {{ old('bitrate') == '256' ? 'selected' : '' }}>256 kbps</option>
                    <option value="320" {{ old('bitrate') == '320' ? 'selected' : '' }}>320 kbps</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="{{ $prefix }}_speed">Tốc độ đọc</label>
                <select name="speed" id="{{ $prefix }}_speed" class="form-control" required>
                    <option value="0.5" {{ old('speed') == '0.5' ? 'selected' : '' }}>0.5x (Chậm)</option>
                    <option value="0.75" {{ old('speed') == '0.75' ? 'selected' : '' }}>0.75x</option>
                    <option value="1.0" {{ old('speed', '1.0') == '1.0' ? 'selected' : '' }}>1.0x (Bình thường)</option>
                    <option value="1.25" {{ old('speed') == '1.25' ? 'selected' : '' }}>1.25x</option>
                    <option value="1.5" {{ old('speed') == '1.5' ? 'selected' : '' }}>1.5x</option>
                    <option value="2.0" {{ old('speed') == '2.0' ? 'selected' : '' }}>2.0x (Nhanh)</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="{{ $prefix }}_volume">Âm lượng (dB)</label>
                <input type="range" name="volume" id="{{ $prefix }}_volume" class="form-control-range" 
                       min="-30" max="30" value="{{ old('volume', '18') }}" 
                       oninput="updateVolumeDisplay('{{ $prefix }}', this.value)">
                <div class="d-flex justify-content-between">
                    <small>-30dB</small>
                    <small id="{{ $prefix }}_volume_display" class="font-weight-bold">{{ old('volume', '18') }}dB</small>
                    <small>+30dB</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="form-control-plaintext">
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Khuyến nghị: 18dB cho TikTok, 15dB cho YouTube
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateVolumeDisplay(prefix, value) {
    document.getElementById(prefix + '_volume_display').textContent = value + 'dB';
    
    // Update color based on value
    const slider = document.getElementById(prefix + '_volume');
    const percentage = ((value - (-30)) / (30 - (-30))) * 100;
    
    if (value < -10) {
        slider.style.background = `linear-gradient(to right, #dc3545 0%, #dc3545 ${percentage}%, #e9ecef ${percentage}%, #e9ecef 100%)`;
    } else if (value > 20) {
        slider.style.background = `linear-gradient(to right, #ffc107 0%, #ffc107 ${percentage}%, #e9ecef ${percentage}%, #e9ecef 100%)`;
    } else {
        slider.style.background = `linear-gradient(to right, #28a745 0%, #28a745 ${percentage}%, #e9ecef ${percentage}%, #e9ecef 100%)`;
    }
}

// Initialize volume display on page load
document.addEventListener('DOMContentLoaded', function() {
    const volumeSliders = document.querySelectorAll('input[name="volume"]');
    volumeSliders.forEach(function(slider) {
        const prefix = slider.id.replace('_volume', '');
        updateVolumeDisplay(prefix, slider.value);
    });
});
</script>
