<?php
if (!defined('ABSPATH')) exit;

function ruh_comment_activate() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Reactions table
    $table_reactions = $wpdb->prefix . 'ruh_reactions';
    $sql_reactions = "CREATE TABLE $table_reactions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        reaction varchar(50) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY post_user (post_id, user_id)
    ) $charset_collate;";
    
    // User levels table
    $table_user_levels = $wpdb->prefix . 'ruh_user_levels';
    $sql_user_levels = "CREATE TABLE $table_user_levels (
        user_id bigint(20) NOT NULL,
        xp int(11) NOT NULL DEFAULT 0,
        level int(11) NOT NULL DEFAULT 1,
        PRIMARY KEY (user_id)
    ) $charset_collate;";

    // Badges table
    $table_badges = $wpdb->prefix . 'ruh_badges';
    $sql_badges = "CREATE TABLE $table_badges (
        badge_id int(9) NOT NULL AUTO_INCREMENT,
        badge_name varchar(255) NOT NULL,
        badge_svg text NOT NULL,
        is_automated tinyint(1) NOT NULL DEFAULT 0,
        auto_condition_type varchar(50) DEFAULT NULL,
        auto_condition_value int(11) DEFAULT NULL,
        PRIMARY KEY (badge_id)
    ) $charset_collate;";

    // User badges table
    $table_user_badges = $wpdb->prefix . 'ruh_user_badges';
    $sql_user_badges = "CREATE TABLE $table_user_badges (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        badge_id int(9) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY user_badge (user_id, badge_id)
    ) $charset_collate;";
    
    // Reports table
    $table_reports = $wpdb->prefix . 'ruh_reports';
    $sql_reports = "CREATE TABLE $table_reports (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        comment_id bigint(20) NOT NULL,
        reporter_id bigint(20) NOT NULL,
        report_time datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY comment_reporter (comment_id, reporter_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql_reactions);
    dbDelta($sql_user_levels);
    dbDelta($sql_badges);
    dbDelta($sql_user_badges);
    dbDelta($sql_reports);
    
    // PERFORMANCE: İndeksler ekle
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_post_id ON {$table_reactions} (post_id)");
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_user_id_reactions ON {$table_reactions} (user_id)");
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_reaction ON {$table_reactions} (reaction)");
    
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_user_id_levels ON {$table_user_levels} (user_id)");
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_level ON {$table_user_levels} (level)");
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_xp ON {$table_user_levels} (xp)");
    
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_user_id_badges ON {$table_user_badges} (user_id)");
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_badge_id ON {$table_user_badges} (badge_id)");
    
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_comment_id ON {$table_reports} (comment_id)");
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_reporter_id ON {$table_reports} (reporter_id)");
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_report_time ON {$table_reports} (report_time)");
    
    $wpdb->query("CREATE INDEX IF NOT EXISTS idx_is_automated ON {$table_badges} (is_automated)");

    // Varsayılan ayarları ekle
    $default_options = [
        'enable_reactions' => 1,
        'enable_likes' => 1,
        'enable_sorting' => 1,
        'enable_reporting' => 1,
        'profile_page_id' => 0,
        'login_page_id' => 0,
        'register_page_id' => 0,
        'xp_per_comment' => 15,
        'profanity_filter_words' => '',
        'spam_link_limit' => 2,
        'auto_moderate_reports' => 3
    ];
    
    if (get_option('ruh_comment_options') === false) {
        update_option('ruh_comment_options', $default_options);
    }
}