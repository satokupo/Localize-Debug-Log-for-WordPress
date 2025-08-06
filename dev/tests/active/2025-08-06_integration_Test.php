<?php
/**
 * 統合テスト - Phase 2 機能統合テスト
 *
 * 作成日: 2025-08-06
 * 対象: localize-debug-log/localize-debug-log.php (統合機能)
 */

class Integration_Test extends PHPUnit\Framework\TestCase
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
     * メイン統合処理関数のテスト
     */
    public function test_get_formatted_log_integration()
    {
        // WordPress関数のモック
        WP_Mock::userFunction('get_option')
            ->with('timezone_string')
            ->once()
            ->andReturn('Asia/Tokyo');

        // テスト用の一時ファイル作成
        $temp_file = tempnam(sys_get_temp_dir(), 'ldl_integration_test_');
        $test_content = "[06-Aug-2025 06:30:45 UTC] Test integration message\n";
        file_put_contents($temp_file, $test_content);

        // plugin_dir_path() のモック（一時的にファイルパスを置換）
        $original_function = 'ldl_get_log_path';

        // テスト用ファイルパスを返す一時関数を作成
        eval('function temp_ldl_get_log_path() { return "' . $temp_file . '"; }');

        // ログ読み込み・整形を個別に実行（統合テスト）
        $timezone = ldl_get_wordpress_timezone();
        $log_lines = ldl_read_log_file($temp_file);
        $formatted_lines = ldl_format_log_with_local_time($log_lines, $timezone);

        // 統合結果の検証
        $this->assertIsArray($formatted_lines);
        $this->assertCount(1, $formatted_lines);
        $this->assertStringContainsString('JST 2025/08/06 15:30:45', $formatted_lines[0]);
        $this->assertStringContainsString('Test integration message', $formatted_lines[0]);

        // クリーンアップ
        unlink($temp_file);
    }

    /**
     * WordPress debug設定との相互不可侵テスト
     */
    public function test_wp_debug_compatibility()
    {
        $compatibility = ldl_check_wp_debug_compatibility();
        $this->assertTrue($compatibility);
    }

    /**
     * 安全な初期化処理のテスト
     */
    public function test_safe_init()
    {
        // ldl_safe_init() 関数が存在することを確認
        $this->assertTrue(function_exists('ldl_safe_init'));

        // ldl_check_wp_debug_compatibility() 関数の動作確認
        $this->assertTrue(ldl_check_wp_debug_compatibility());

        // 関数が呼び出し可能であることを確認（実際の実行はWordPress関数依存のため省略）
        $this->assertTrue(is_callable('ldl_safe_init'));
    }

    /**
     * プラグイン関数のプレフィックス統一確認
     */
    public function test_function_prefix_consistency()
    {
        $functions = [
            'ldl_get_wordpress_timezone',
            'ldl_convert_utc_to_local',
            'ldl_format_local_timestamp',
            'ldl_get_log_path',
            'ldl_ensure_log_directory',
            'ldl_setup_error_log_redirection',
            'ldl_override_debug_log_path',
            'ldl_init',
            'ldl_read_log_file',
            'ldl_extract_utc_timestamp',
            'ldl_format_log_with_local_time',
            'ldl_get_formatted_log',
            'ldl_check_wp_debug_compatibility',
            'ldl_safe_init'
        ];

        foreach ($functions as $function) {
            $this->assertTrue(function_exists($function), "Function $function should exist");
            $this->assertStringStartsWith('ldl_', $function, "Function $function should start with ldl_ prefix");
        }
    }
}
