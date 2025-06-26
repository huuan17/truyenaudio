<?php

return [

    'CRAWL_STATUS' => [
        'VALUES' => [
            'NOT_CRAWLED' => 0,
            'CRAWLED'     => 1,
            'RE_CRAWL'    => 2,
        ],

        // Mapping từ giá trị => nhãn hiển thị
        'LABELS' => [
            0 => 'Chưa crawl',
            1 => 'Đã crawl',
            2 => 'Cần crawl lại',
        ],

        // Mapping từ giá trị => class màu (ví dụ để badge bootstrap)
        'COLORS' => [
            0 => 'secondary',
            1 => 'success',
            2 => 'warning',
        ]
    ],
    
    'STORAGE_PATHS' => [
        'TEXT' => 'storage/truyen/',
        'AUDIO' => 'storage/truyen/mp3-',
        'VIDEO' => 'storage/truyen/mp4-',
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
