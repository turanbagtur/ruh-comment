<?php
/**
 * Ruh Comment - Uninstall Script
 * 
 * Eklenti kaldırıldığında tüm verileri temizler.
 * 
 * @package RuhComment
 * @version 7.0
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Özel tabloları sil
$custom_tables = array(
    $wpdb->prefix . 'ruh_reactions',
    $wpdb->prefix . 'ruh_user_levels',
    $wpdb->prefix . 'ruh_badges',
    $wpdb->prefix . 'ruh_user_badges',
    $wpdb->prefix . 'ruh_reports',
    $wpdb->prefix . 'ruh_notifications',
);

foreach ($custom_tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Comment meta'ları temizle
$wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '_likes'");
$wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '_dislikes'");
$wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '_user_vote_%'");
$wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE 'ruh_pinned'");
$wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE 'ruh_pinned_date'");
$wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '_original_post_url'");

// User meta'ları temizle
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ruh_ban_status'");
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ruh_timeout_until'");
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ruh_avatar_%'");

// Transient'ları temizle
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%ruh_rate_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%ruh_api_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ruh_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ruh_%'");

// Options'ı sil
delete_option('ruh_comment_options');

// Cron job'ları temizle
wp_clear_scheduled_hook('ruh_check_badges_cron');

// Object cache'i temizle
wp_cache_flush();