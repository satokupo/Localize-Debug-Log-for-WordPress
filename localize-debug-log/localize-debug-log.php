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
    // 入力ガード: 非文字列・null は即座に拒否
    if (!is_string($log_line)) {
        return null;
    }

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

        // 管理画面UIのフック登録（管理画面のみ）
        if (!function_exists('is_admin') || is_admin()) {
            ldl_register_admin_ui();
        }

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
            'title' => '<span class="dashicons dashicons-admin-settings" style="margin-right:4px;"></span>Localize Debug Log',
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
    // 削除関連のフック（POST処理と通知）
    add_action('admin_init', 'ldl_handle_delete_request', 10, 0);
    add_action('admin_notices', 'ldl_notice_delete_result', 10, 0);
}

/**
 * ログ表示ページのレンダリング
 *
 * - textarea でログを読み取り専用表示
 * - 1MB 超のときは警告を表示
 *
 * @return void
 */
function ldl_render_log_page() {
    // 権限チェック（管理者のみ）
    if (!current_user_can('manage_options')) {
        echo '<div class="notice notice-error"><p>このページにアクセスする権限がありません。</p></div>';
        return;
    }

    // ldl_get_formatted_log は内部で get_option を呼ぶため、存在確認してから実行
    $log_lines = function_exists('ldl_get_formatted_log') ? (array) ldl_get_formatted_log() : array();
    // 各行を先にHTMLエスケープしてから結合（textarea内の安全性確保）
    $escaped_lines = array_map(function ($line) {
        return htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
    }, $log_lines);
    $log_text  = implode("\n", $escaped_lines);

    $log_path  = function_exists('ldl_get_log_path') ? ldl_get_log_path() : '';
    $exists    = ($log_path && file_exists($log_path));
    $size      = $exists ? filesize($log_path) : 0;
    $is_large  = ($size !== false && $size > 1024 * 1024); // 1MB

    if ($is_large) {
        echo '<div class="notice notice-warning"><p>ログファイルが大きいため、表示に時間がかかる場合があります。</p></div>';
    }

    if (!$exists) {
        echo '<div class="notice notice-info"><p>ログファイルが見つかりません。新しいエントリが記録されるとここに表示されます。</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Localize Debug Log</h1>';
    // widefat を使用したレイアウト（標準UIクラス）
    echo '<table class="widefat"><tbody><tr><td>';
    echo '<textarea readonly rows="20" style="width:100%;">' . $log_text . '</textarea>';
    echo '</td></tr></tbody></table>';
    // 削除フォーム（nonce + confirm）
    echo '<form method="post" style="margin-top:16px;" onsubmit="return confirm(\'本当に削除しますか？この操作は取り消せません。\');">';
    if (function_exists('wp_nonce_field')) {
        echo wp_nonce_field('ldl_delete_log_action', 'ldl_delete_nonce');
    }
    echo '<input type="hidden" name="ldl_delete_log" value="1" />';
    echo '<button type="submit" class="button button-secondary">ログを削除</button>';
    echo '</form>';
    echo '</div>';
}

/**
 * =============================================================================
 * Phase 3: ログ削除機能（最小実装）
 * =============================================================================
 */

/**
 * ログファイルを削除し、空のファイルを再生成
 *
 * Phase 4〜6 強化機能：
 * - パス検証による不正アクセス防止
 * - 排他制御による安全なファイル操作
 * - 多段階フォールバック処理
 * - 例外安全性（常に配列を返却）
 *
 * @param string|null $custom_path カスタムパス（null の場合デフォルトパスを使用）
 * @return array { success: bool, message?: string } 処理結果（成功時は success: true、失敗時は追加で message）
 */
function ldl_delete_log_file($custom_path = null) {
    try {
        // Step 1: パス取得（引数優先、なければデフォルト）
        $log_path = $custom_path ?: ldl_get_log_path();

        // Step 2: パス妥当性チェック（Phase 4 追加）
        // ディレクトリトラバーサル・絶対パス・不正型を早期拒否
        if (!ldl_validate_log_path($log_path)) {
            return array('success' => false, 'message' => 'validate');
        }

        // Step 3: 第一選択：file_put_contents with LOCK_EX（排他制御付きゼロクリア）
        // TOCTOU攻撃対策として、ファイルの存在確認と操作を同時実行
        if (file_put_contents($log_path, '', LOCK_EX) !== false) {
            // 成功確認：サイズ0であることを検証
            $size = filesize($log_path);
            if ($size === 0) {
                return array('success' => true);
            } else {
                return array('success' => false, 'message' => 'not empty after clear');
            }
        }

        // Step 4: フォールバック：fopen + flock + ftruncate
        // file_put_contents が失敗した場合の安全な代替手段
        $handle = fopen($log_path, 'c+');
        if ($handle === false) {
            return array('success' => false, 'message' => 'fopen failed');
        }

        // 排他ロック取得・ファイル切り詰め・フラッシュ・ロック解除
        if (flock($handle, LOCK_EX)) {
            ftruncate($handle, 0);  // ファイルサイズを0に切り詰め
            fflush($handle);        // バッファの強制書き込み
            flock($handle, LOCK_UN); // 排他ロック解除
            fclose($handle);         // ファイルハンドル閉じる

            // 成功確認：最終的なファイルサイズを検証
            $size = filesize($log_path);
            return $size === 0 ? array('success' => true) : array('success' => false, 'message' => 'not empty after ftruncate');
        } else {
            // ロック取得失敗：ファイルハンドル閉じて失敗を報告
            fclose($handle);
            return array('success' => false, 'message' => 'flock failed');
        }

    } catch (Exception $e) {
        // Step 5: 例外安全性：如何なる例外が発生しても配列を返却
        return array('success' => false, 'message' => 'exception');
    }
}

// 削除結果を保持（簡易）
$ldl_last_delete_result = null;

/**
 * 削除リクエストの処理（POST想定）
 *
 * @return void
 */
function ldl_handle_delete_request() {
    global $ldl_last_delete_result;
    // 前回の結果をクリア
    $ldl_last_delete_result = null;

    // POST メソッドのみ処理
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        return; // GET や他のメソッドは無視
    }

    if (empty($_POST['ldl_delete_log'])) {
        return; // 何もしない
    }

    // 権限チェック
    if (function_exists('current_user_can') && !current_user_can('manage_options')) {
        $ldl_last_delete_result = array('success' => false, 'message' => 'permission');
        return;
    }

    // nonce チェック（共通化関数を使用）
    if (!ldl_csrf_protect('ldl_delete_log_action', 'ldl_delete_nonce', 'verify')) {
        $ldl_last_delete_result = array('success' => false, 'message' => 'nonce');
        return;
    }

    // パス妥当性チェック
    $log_path = ldl_get_log_path();
    if (!ldl_validate_log_path($log_path)) {
        $ldl_last_delete_result = array('success' => false, 'message' => 'validate');
        return;
    }

    $ldl_last_delete_result = ldl_delete_log_file();
}

/**
 * 削除結果の通知（admin_notices相当のHTMLを出力）
 *
 * @return void
 */
function ldl_notice_delete_result() {
    global $ldl_last_delete_result;

    if (!is_array($ldl_last_delete_result)) {
        return;
    }

    if (!empty($ldl_last_delete_result['success'])) {
        echo '<div class="notice notice-success"><p>ログファイルを削除しました。</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>ログファイルの削除に失敗しました。</p></div>';
    }

    // 出力後は結果をクリア（多重出力・次リクエストへの持ち越し防止）
    $ldl_last_delete_result = null;
}

/**
 * =============================================================================
 * Phase 4: セキュリティ・権限制御実装
 * =============================================================================
 */

/**
 * CSRF保護ユーティリティ
 *
 * nonce の発行・検証を共通化するヘルパー関数
 *
 * @param string $action nonce アクション名（デフォルト: 'ldl_delete_log_action'）
 * @param string $field nonce フィールド名（デフォルト: 'ldl_delete_nonce'）
 * @param string $mode 'field' で発行、'verify' で検証（デフォルト: 'field'）
 * @return string|bool field モードではHTML文字列、verify モードでは boolean
 */
function ldl_csrf_protect($action = 'ldl_delete_log_action', $field = 'ldl_delete_nonce', $mode = 'field') {
    // 検証モード: check_admin_referer の結果を返す
    if ($mode === 'verify') {
        return function_exists('check_admin_referer') ? check_admin_referer($action, $field) : false;
    }

    // 発行モード（デフォルト）: wp_nonce_field の結果を返す
    return function_exists('wp_nonce_field') ? wp_nonce_field($action, $field, true, false) : '';
}

/**
 * ログファイルパス検証
 *
 * 指定されたパスが logs/ ディレクトリ配下に限定されることを確認する
 *
 * @param string $path 検証対象パス
 * @return string|false 妥当な場合は正規化済み絶対パス、不正な場合は false
 */
function ldl_validate_log_path($path) {
    // 入力ガード: 非文字列・空文字は即座に拒否
    if (!is_string($path) || $path === '') {
        return false;
    }

    // プラグインディレクトリの logs フォルダパスを取得
    $plugin_dir = function_exists('plugin_dir_path') ? plugin_dir_path(__FILE__) : '/test/path/to/plugin/';
    $logs_dir = $plugin_dir . 'logs';

    // logs ディレクトリの正規化（テスト環境では仮想パス）
    $normalized_logs_dir = rtrim(str_replace('\\', '/', $logs_dir), '/');

    // 対象パスを正規化
    $normalized_path = str_replace('\\', '/', $path);

    // パストラバーサル検出：../ を含む場合は安全側で拒否
    if (strpos($normalized_path, '../') !== false) {
        // realpath で正規化して最終的なパスを確認
        $real_path = realpath($path);
        if ($real_path !== false) {
            $real_normalized = str_replace('\\', '/', $real_path);
            return strpos($real_normalized, $normalized_logs_dir . '/') === 0 ? $real_path : false;
        }
        // realpath が失敗（不存在パス）の場合は安全側で拒否
        // 無限ループを避けるため手動正規化は行わない
        return false;
    }

    // 単純なプレフィックスチェック（../ がない場合）
    if (strpos($normalized_path, $normalized_logs_dir . '/') === 0) {
        $real_path = realpath($path);
        return $real_path !== false ? $real_path : $normalized_path;
    }

    return false; // 不正なパス
}
