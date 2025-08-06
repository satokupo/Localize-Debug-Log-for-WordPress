<?php
/**
 * ログ出力先変更機能テスト
 *
 * 作成日: 2025-08-06
 * 用途: error_log()出力先をlogs/debug.logに変更する機能の検証
 * 対象: localize-debug-log/localize-debug-log.php (ldl_get_log_path, ldl_ensure_log_directory, ldl_setup_error_log_redirection)
 */

class LogRedirection_Test extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        WP_Mock::setUp();

        // メインプラグインファイルを読み込み
        require_once dirname(__DIR__, 3) . '/localize-debug-log/localize-debug-log.php';
    }

    protected function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    /**
     * ログパス取得の正常動作テストケース
     */
    public function test_get_log_path_returns_correct_path()
    {
        // plugin_dir_path() のモック（テスト環境用）
        $test_plugin_path = '/test/path/to/plugin/';

        // ldl_get_log_path() の動作確認
        $actual_path = ldl_get_log_path();
        $this->assertStringEndsWith('logs/debug.log', $actual_path);
    }

    /**
     * ディレクトリ作成の基本テストケース
     */
    public function test_ensure_log_directory_creates_directory()
    {
        // file_exists() は PHP 標準関数なので実際に動作し、ディレクトリが存在しない場合のテスト
        // wp_mkdir_p() 関数のモック
        WP_Mock::userFunction('wp_mkdir_p')
            ->with(\Mockery::type('string'))
            ->once()
            ->andReturn(true);

        $result = ldl_ensure_log_directory();
        $this->assertTrue($result);
    }

    /**
     * ini_set による error_log 設定変更テスト
     */
    public function test_setup_error_log_redirection()
    {
                // 関数実行前の状態取得
        $original_error_log = ini_get('error_log');

        // 関数実行
        ldl_setup_error_log_redirection();

        // 設定が変更されたことを確認
        $new_error_log = ini_get('error_log');
        $this->assertNotEquals($original_error_log, $new_error_log);
        $this->assertStringContainsString('logs/debug.log', $new_error_log);
    }

    /**
     * debug_log_path フィルタ機能テスト
     */
    public function test_override_debug_log_path_filter()
    {
                $original_path = '/var/log/debug.log';
        $filtered_path = ldl_override_debug_log_path($original_path);

        $this->assertNotEquals($original_path, $filtered_path);
        $this->assertStringContainsString('logs/debug.log', $filtered_path);
    }

    /**
     * プラグイン初期化処理テスト
     */
    public function test_plugin_initialization()
    {
        // ldl_init関数が存在することを確認
        $this->assertTrue(function_exists('ldl_init'));

        // ldl_init関数が呼び出し可能であることを確認
        $this->assertTrue(is_callable('ldl_init'));

        // bootstrap.phpでadd_filterが実装されているので、実際に実行しても問題なし
        ldl_init();

        // 実行後のテスト（エラーが発生しないことを確認）
        $this->assertTrue(true, 'ldl_init関数が正常実行された');
    }
}
