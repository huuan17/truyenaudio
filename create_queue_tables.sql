-- Create video_generation_tasks table
CREATE TABLE IF NOT EXISTS `video_generation_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `platform` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `priority` int(11) NOT NULL DEFAULT 2,
  `parameters` json NOT NULL,
  `result` json DEFAULT NULL,
  `progress` int(11) NOT NULL DEFAULT 0,
  `estimated_duration` int(11) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `batch_id` varchar(255) DEFAULT NULL,
  `batch_index` int(11) DEFAULT NULL,
  `total_in_batch` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_generation_tasks_user_id_foreign` (`user_id`),
  KEY `video_generation_tasks_status_priority_created_at_index` (`status`,`priority`,`created_at`),
  KEY `video_generation_tasks_user_id_status_index` (`user_id`,`status`),
  KEY `video_generation_tasks_platform_status_index` (`platform`,`status`),
  KEY `video_generation_tasks_batch_id_batch_index_index` (`batch_id`,`batch_index`),
  KEY `video_generation_tasks_created_at_index` (`created_at`),
  CONSTRAINT `video_generation_tasks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create jobs table if not exists
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
