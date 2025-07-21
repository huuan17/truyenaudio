<?php

return [

    'CRAWL_STATUS' => [
        'VALUES' => [
            'NOT_CRAWLED' => 0,
            'PENDING'     => 1,
            'CRAWLED'     => 2,
            'CRAWLING'    => 3,
            'FAILED'      => 4,
            'RE_CRAWL'    => 5,
        ],

        // Mapping từ giá trị => nhãn hiển thị
        'LABELS' => [
            0 => 'Chưa crawl',
            1 => 'Đang chờ',
            2 => 'Đã crawl',
            3 => 'Đang crawl',
            4 => 'Thất bại',
            5 => 'Cần crawl lại',
        ],

        // Mapping từ giá trị => class màu (ví dụ để badge bootstrap)
        'COLORS' => [
            0 => 'secondary',
            1 => 'primary',
            2 => 'success',
            3 => 'info',
            4 => 'danger',
            5 => 'warning',
        ]
    ],

    'TTS_STATUS' => [
        'VALUES' => [
            'NOT_STARTED' => 0,
            'PENDING'     => 1,
            'PROCESSING'  => 2,
            'COMPLETED'   => 3,
            'FAILED'      => 4,
            'PARTIAL'     => 5,
        ],

        'LABELS' => [
            0 => 'Chưa TTS',
            1 => 'Chờ TTS',
            2 => 'Đang TTS',
            3 => 'Hoàn thành',
            4 => 'Thất bại',
            5 => 'Một phần',
        ],

        'COLORS' => [
            0 => 'secondary',
            1 => 'warning',
            2 => 'info',
            3 => 'success',
            4 => 'danger',
            5 => 'primary',
        ]
    ],

    'STORAGE_PATHS' => [
        'TEXT' => 'storage/app/content/',
        'AUDIO' => 'storage/app/audio/',
        'VIDEO' => 'storage/app/videos/',
        'IMAGES' => 'storage/app/images/',
        'TEMP' => 'storage/app/temp/',
    ],
];/** 
 * sử dụng trong Controller
 *      $statusLabels = config('constants.CRAWL_STATUS.LABELS');
 *      $colorMap     = config('constants.CRAWL_STATUS.COLORS');
 *      $currentStatus = $story->crawl_status ?? 0;
 *      $label = $statusLabels[$currentStatus];
 *      $badgeClass = $colorMap[$currentStatus];
 */

 /**
  * Sử dụng trong view
  * @php
  * $statusLabels = config('constants.CRAWL_STATUS.LABELS');
  * $colorMap = config('constants.CRAWL_STATUS.COLORS');
  * $currentStatus = $story->crawl_status ?? 0;
  * @endphp

  * <span class="badge bg-{{ $colorMap[$currentStatus] }}">
  *     {{ $statusLabels[$currentStatus] }}
  * </span>
  */
  /**
   * Sử dụng trong model
   * public function getStatusLabelAttribute()
   * {
   *     $statusLabels = config('constants.CRAWL_STATUS.LABELS');
   *     return $statusLabels[$this->crawl_status ?? 0];
   * }
   */
/** 
 * Sử dụng trong view
 * <span class="badge bg-{{ $story->status_color }}">
 *     {{ $story->status_label }}
 * </span>
 */

/*
sử dụng trong form
@php
    $statusValues = config('constants.CRAWL_STATUS.VALUES');
    $statusLabels = config('constants.CRAWL_STATUS.LABELS');
@endphp

<select name="crawl_status" class="form-control">
    @foreach ($statusValues as $key => $value)
        <option value="{{ $value }}" {{ old('crawl_status', $story->crawl_status ?? 0) == $value ? 'selected' : '' }}>
            {{ $statusLabels[$value] }}
        </option>
    @endforeach
</select>
*/
