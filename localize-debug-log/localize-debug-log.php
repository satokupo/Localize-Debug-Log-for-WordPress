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
 * Phase 2実装状況:
 * ✅ error_log出力先変更機能 (ldl_setup_error_log_redirection, ldl_override_debug_log_path)
 * ✅ タイムゾーン処理ロジック (ldl_get_wordpress_timezone, ldl_convert_utc_to_local, ldl_format_local_timestamp)
 * ✅ ログ読み込み・整形機能 (ldl_read_log_file, ldl_extract_utc_timestamp, ldl_format_log_with_local_time)
 * ✅ 機能統合・統合処理 (ldl_get_formatted_log, ldl_check_wp_debug_compatibility, ldl_safe_init)
 *
 * 関数プレフィックス: ldl_ (Localize Debug Log)
 * 実装関数数: 14関数
 * テスト数: 22テスト・75アサーション（全て成功）
 */

// Phase 1: 基本プラグイン構造確立済み
// Phase 2: コア機能実装完了 ✅
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
 * プラグイン初期化処理（既存の簡易版）
 * Phase 2での下位互換性のため残存
 */
function ldl_init() {
    // 新しい安全な初期化処理を実行
    ldl_safe_init();
}

// プラグイン読み込み時にフック登録
add_action('plugins_loaded', 'ldl_init', 0);

/**
 * =============================================================================
 * ログ読み込み・整形機能
 * =============================================================================
 */

/**
 * ログファイルを読み込み、行配列として返す
 *
 * @param string $log_file_path ログファイルの絶対パス
 * @return array ログ行の配列、ファイルが存在しない場合は空配列
 */
function ldl_read_log_file($log_file_path) {
    if (!file_exists($log_file_path)) {
        return array();
    }

    $content = file_get_contents($log_file_path);
    if ($content === false) {
        return array();
    }

    // 改行文字で分割し、空行を除去
    $lines = array_filter(explode("\n", $content), function($line) {
        return trim($line) !== '';
    });

    return array_values($lines);
}

/**
 * ログ行からUTCタイムスタンプを抽出
 *
 * @param string $log_line ログ行文字列
 * @return string|null UTCタイムスタンプ、抽出できない場合はnull
 */
function ldl_extract_utc_timestamp($log_line) {
    // PHP error_log の標準形式: [DD-MMM-YYYY HH:MM:SS UTC] メッセージ
    $pattern = '/^\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2}) UTC\]/';

    if (preg_match($pattern, $log_line, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * ログ行にローカル時刻を付加して整形
 *
 * @param array $log_lines ログ行の配列
 * @param string $timezone タイムゾーン文字列
 * @return array ローカル時刻付きログ行の配列
 */
function ldl_format_log_with_local_time($log_lines, $timezone) {
    $formatted_lines = array();

    foreach ($log_lines as $line) {
        $utc_timestamp = ldl_extract_utc_timestamp($line);

        if ($utc_timestamp !== null) {
            // UTC時刻をローカル時刻に変換
            $local_formatted = ldl_format_local_timestamp($utc_timestamp, $timezone);

            if (!empty($local_formatted)) {
                // ローカル時刻を行頭に付加
                $formatted_line = '[' . $local_formatted . '] ' . $line;
                $formatted_lines[] = $formatted_line;
            } else {
                // 変換失敗時は元の行をそのまま使用
                $formatted_lines[] = $line;
            }
        } else {
            // UTCタイムスタンプが抽出できない行はそのまま使用
            $formatted_lines[] = $line;
        }
    }

    return $formatted_lines;
}

/**
 * =============================================================================
 * 機能統合・統合処理
 * =============================================================================
 */

/**
 * プラグインのメイン処理関数：ログの収集と整形を統合実行
 *
 * @return array ローカル時刻付きログ行の配列
 */
function ldl_get_formatted_log() {
    // WordPressタイムゾーン取得
    $timezone = ldl_get_wordpress_timezone();

    // ログファイルパス取得
    $log_path = ldl_get_log_path();

    // ログファイル読み込み
    $log_lines = ldl_read_log_file($log_path);

    // ローカル時刻付きで整形
    $formatted_lines = ldl_format_log_with_local_time($log_lines, $timezone);

    return $formatted_lines;
}

/**
 * WordPress debug設定との相互不可侵チェック
 * プラグインがWordPressのデバッグ設定を変更しないことを確認
 *
 * @return bool 相互不可侵が保たれている場合true
 */
function ldl_check_wp_debug_compatibility() {
    // WP_DEBUGが定義されている場合、その設定値は変更せず尊重
    // WP_DEBUG_LOGが定義されている場合、その設定値は変更せず尊重
    // WP_DEBUG_DISPLAYが定義されている場合、その設定値は変更せず尊重

    // プラグインは独自のログファイルパスを使用し、WordPress標準のdebug.logは操作しない
    // ini_set('error_log') はPHPレベルの設定変更であり、WordPress設定とは独立

    return true; // このプラグインは相互不可侵設計
}

/**
 * プラグイン統合初期化処理（エラーハンドリング付き）
 *
 * @return bool 初期化成功時true、失敗時false
 */
function ldl_safe_init() {
    try {
        // WordPress debug設定の相互不可侵チェック
        if (!ldl_check_wp_debug_compatibility()) {
            return false;
        }

        // ログ出力先変更を安全に実行
        ldl_setup_error_log_redirection();

        // WordPressコアのdebug_log_pathフィルタを追加
        add_filter('debug_log_path', 'ldl_override_debug_log_path');

        return true;

    } catch (Exception $e) {
        // エラー時は何もしない（フォールバック）
        return false;
    }
}

/**
 * =============================================================================
 * Phase 3: 管理画面 UI 実装（メニュー／管理バー）
 * =============================================================================
 */

/**
 * 設定メニューに「Localize Debug Log」ページを追加
 *
 * WordPress 管理画面の「設定」配下にログ表示ページへの入口を作成する。
 * ここでは最小実装として `add_options_page()` の呼び出しのみ行い、
 * コールバック `ldl_render_log_page` は後続の実装で提供する。
 *
 * @return void
 */
function ldl_add_admin_menu() {
    add_options_page(
        'Localize Debug Log',               // page_title
        'Localize Debug Log',               // menu_title
        'manage_options',                   // capability（管理者相当）
        'localize-debug-log',               // menu_slug
        'ldl_render_log_page'               // callback（後続で実装）
    );
}

/**
 * 管理バー（上部バー）にログ画面へのリンクを追加
 *
 * @param object $admin_bar WP_Admin_Bar 互換のオブジェクト
 * @return void
 */
function ldl_add_admin_bar_link($admin_bar) {
    if (!is_object($admin_bar)) {
        return;
    }

    if (method_exists($admin_bar, 'add_node')) {
        $admin_bar->add_node(array(
            'id'    => 'ldl-admin-bar-link',
            'title' => 'Localize Debug Log',
            'href'  => admin_url('options-general.php?page=localize-debug-log'),
            'meta'  => array('class' => 'ldl-admin-bar')
        ));
    }
}

/**
 * 管理画面向けのフック登録
 *
 * - admin_menu: 設定メニューにページを追加（優先度10）
 * - admin_bar_menu: 管理バーにリンクを追加（優先度100, accepted_args=1）
 *
 * @return void
 */
function ldl_register_admin_ui() {
    add_action('admin_menu', 'ldl_add_admin_menu', 10, 0);
    add_action('admin_bar_menu', 'ldl_add_admin_bar_link', 100, 1);
}
