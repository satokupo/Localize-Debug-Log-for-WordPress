<?php
/**
 * Plugin Name: Localize Debug Log for WordPress
 * Description: WordPress のタイムゾーン設定に基づいて、PHP の error_log() 出力を収集し、ローカル時間付きで表示する管理用プラグイン
 * Version: 1.0.0
 * Author: satokupo
 * Requires at least: 5.1
 * Requires PHP: 7.4
 * License: All rights reserved
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

/**
 * プラグイン関数は一貫性と名前空間の安全性のため 'ldl_' プレフィックスを使用
 *
 * これはPhase 1の骨格プラグインファイルです。
 * コア機能（error_log リダイレクション、管理画面UI等）はPhase 2で実装予定。
 *
 * 関数プレフィックス: ldl_ (Localize Debug Log)
 * - 例: ldl_init(), ldl_get_log_path(), ldl_display_admin_page()
 */

// Phase 1: 基本プラグイン構造確立済み
// Phase 2: コア機能実装中
// Phase 3: 管理画面UI実装予定
// Phase 4: セキュリティ強化予定

/**
 * =============================================================================
 * Phase 2: コア機能実装（ログ収集・処理）
 * =============================================================================
 */

/**
 * WordPressのタイムゾーン設定を取得
 *
 * @return string タイムゾーン文字列
 */
function ldl_get_wordpress_timezone() {
    $tz_string = get_option('timezone_string');

    if (!empty($tz_string)) {
        return $tz_string;
    }

    // timezone_string が空の場合は gmt_offset から構築
    $offset = get_option('gmt_offset');
    if ($offset == 0) {
        return 'UTC';
    }

    // Etc/GMT は符号が逆になることに注意
    return sprintf('Etc/GMT%+d', -$offset);
}

/**
 * UTCタイムスタンプをローカル時刻に変換
 *
 * @param string $utc_timestamp UTC形式のタイムスタンプ
 * @param string $timezone タイムゾーン文字列
 * @return string ローカル時刻文字列
 */
function ldl_convert_utc_to_local($utc_timestamp, $timezone) {
    try {
        $utc_date = new DateTime($utc_timestamp, new DateTimeZone('UTC'));
        $local_tz = new DateTimeZone($timezone);
        $utc_date->setTimezone($local_tz);

        return $utc_date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return '';
    }
}

/**
 * ローカル時刻をフォーマットして表示用文字列に変換
 *
 * @param string $utc_timestamp UTC形式のタイムスタンプ
 * @param string $timezone タイムゾーン文字列
 * @return string フォーマット済みローカル時刻文字列
 */
function ldl_format_local_timestamp($utc_timestamp, $timezone) {
    try {
        $utc_date = new DateTime($utc_timestamp, new DateTimeZone('UTC'));
        $local_tz = new DateTimeZone($timezone);
        $utc_date->setTimezone($local_tz);

        // タイムゾーン略称を取得
        $timezone_abbr = $utc_date->format('T');

        // JST 2025/08/06 15:30:45 形式
        return $timezone_abbr . ' ' . $utc_date->format('Y/m/d H:i:s');
    } catch (Exception $e) {
        return '';
    }
}

/**
 * =============================================================================
 * ログ出力先変更機能
 * =============================================================================
 */

/**
 * ログファイルのパスを取得
 *
 * @return string ログファイルの絶対パス
 */
function ldl_get_log_path() {
    return plugin_dir_path(__FILE__) . 'logs/debug.log';
}

/**
 * ログディレクトリの存在確認と作成
 *
 * @return bool 作成成功またはすでに存在する場合はtrue
 */
function ldl_ensure_log_directory() {
    $log_dir = plugin_dir_path(__FILE__) . 'logs';

    if (!file_exists($log_dir)) {
        return wp_mkdir_p($log_dir);
    }

    return true;
}

/**
 * error_log出力先をプラグインのlogディレクトリに設定
 */
function ldl_setup_error_log_redirection() {
    // ディレクトリ確保
    ldl_ensure_log_directory();

    // ini_set で error_log 出力先を変更
    $log_path = ldl_get_log_path();
    ini_set('error_log', $log_path);
}

/**
 * debug_log_path フィルタでWordPressコアのログ出力先を変更
 *
 * @param string $original_path 元のログパス
 * @return string 変更後のログパス
 */
function ldl_override_debug_log_path($original_path) {
    return ldl_get_log_path();
}

/**
 * プラグイン初期化処理
 */
function ldl_init() {
    // ログ出力先変更を即座に実行
    ldl_setup_error_log_redirection();

    // WordPressコアのdebug_log_pathもフィルタで変更
    add_filter('debug_log_path', 'ldl_override_debug_log_path');
}

// プラグイン読み込み時にフック登録
add_action('plugins_loaded', 'ldl_init', 0);
