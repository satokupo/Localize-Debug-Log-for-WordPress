<?php
/**
 * PHPUnit Bootstrap File
 * テスト環境の初期化設定
 *
 * このファイルは PHPUnit テスト実行時に最初に読み込まれ、
 * 本番環境のコードをテスト環境で使用可能にするためのブリッジ役を担う
 */


// エラーレポートを有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Composer autoload（WP_Mock用）
require_once __DIR__ . '/../vendor/autoload.php';

// WP_Mock の初期化
WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

// テストルートディレクトリの定義
define('TESTS_ROOT', __DIR__);
define('THEME_ROOT', dirname(__DIR__, 2) . '/localize-debug-log');

// WordPress環境の基本設定（簡素版）
if (!defined('ABSPATH')) {
	define('ABSPATH', true);
}

/**---------------------------------------------------------------------------
 * WordPress関数のモック実装
 * 本番環境のコードがWordPress関数に依存している部分をテスト環境で解決
 *---------------------------------------------------------------------------*/

/**
 * HTML エスケープ関数のモック
 *
 * @param string $text エスケープ対象の文字列
 * @return string エスケープ済み文字列
 */
if (!function_exists('esc_html')) {
	function esc_html($text) {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}

/**
 * WordPress エラークラスのモック
 * catalog.php のエラーハンドリングで使用
 */
if (!class_exists('WP_Error')) {
	class WP_Error {
		private $code;
		private $message;

		public function __construct($code, $message) {
			$this->code    = $code;
			$this->message = $message;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}
	}
}

/**
 * WP_Error 判定関数のモック
 *
 * @param mixed $thing 判定対象
 * @return bool WP_Error インスタンスかどうか
 */
if (!function_exists('is_wp_error')) {
	function is_wp_error($thing) {
		return $thing instanceof WP_Error;
	}
}

/**
 * テーマディレクトリ取得関数のモック
 *
 * @return string テーマディレクトリの絶対パス
 */
if (!function_exists('get_stylesheet_directory')) {
	function get_stylesheet_directory() {
		return THEME_ROOT;
	}
}

/**
 * テーマディレクトリURI取得関数のモック
 *
 * @return string テーマディレクトリのURI
 */
if (!function_exists('get_stylesheet_directory_uri')) {
	function get_stylesheet_directory_uri() {
		// テスト環境では仮のURIを返す
		return 'http://localhost/wp-content/themes/swell_child';
	}
}

/**
 * プラグインディレクトリパス取得関数のモック
 *
 * @param string $file プラグインファイルのパス
 * @return string プラグインディレクトリの絶対パス
 */
if (!function_exists('plugin_dir_path')) {
	function plugin_dir_path($file) {
		// テスト環境では固定パスを返す
		return '/test/path/to/plugin/';
	}
}


