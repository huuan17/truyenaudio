{{-- Toast Trigger Component --}}
{{-- Usage: <x-toast-trigger type="success" message="Your message" title="Optional Title" /> --}}

@props(['type' => 'info', 'message', 'title' => null, 'timeout' => null])

<script>
    $(document).ready(function() {
        @if($message)
            @php
                $titles = [
                    'success' => $title ?? 'Thành công!',
                    'error' => $title ?? 'Lỗi!',
                    'warning' => $title ?? 'Cảnh báo!',
                    'info' => $title ?? 'Thông tin!'
                ];
                $toastTitle = $titles[$type] ?? 'Thông báo!';
            @endphp
            
            var options = {};
            @if($timeout)
                options.timeOut = {{ $timeout }};
            @endif
            
            showToast.{{ $type }}('{!! addslashes($message) !!}', '{{ $toastTitle }}', options);
        @endif
    });
</script>
