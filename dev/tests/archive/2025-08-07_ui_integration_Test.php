<?php
/**
 * UI統合テスト（Phase 3）
 */

use PHPUnit\Framework\TestCase;

class Ldl_Ui_Test extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock::setUp();
        require_once dirname(__DIR__, 3) . '/localize-debug-log/localize-debug-log.php';
    }

    protected function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    /**
     * 管理画面表示の統合テスト
     */
    public function test_admin_ui_integration()
    {
        // Phase 2関数が利用可能であることを確認
        $this->assertTrue(function_exists('ldl_get_formatted_log'));

        // Phase 3関数の統合確認
        $this->assertTrue(function_exists('ldl_add_admin_menu'));
        $this->assertTrue(function_exists('ldl_render_log_page'));
        $this->assertTrue(function_exists('ldl_handle_delete_request'));
        $this->assertTrue(function_exists('ldl_delete_log_file'));
        $this->assertTrue(function_exists('ldl_notice_delete_result'));

        $this->assertTrue(true);
    }

    /**
     * 権限チェック統合テスト
     */
    public function test_permission_integration()
    {
        // 管理者権限をモック
        WP_Mock::userFunction('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        // add_options_page が管理者権限を要求することを確認
        WP_Mock::userFunction('add_options_page')
            ->with(
                \Mockery::type('string'),
                \Mockery::type('string'),
                'manage_options', // capability
                \Mockery::type('string'),
                \Mockery::type('string')
            )
            ->andReturn('settings_page_localize_debug_log');

        ldl_add_admin_menu();

        $this->assertTrue(true);
    }

    /**
     * エラーハンドリング統合テスト
     */
    public function test_error_handling_integration()
    {
        // ファイル削除失敗の場合
        $temp = tempnam(sys_get_temp_dir(), 'ldl_err_');
        chmod($temp, 0444); // 読み取り専用にして削除失敗を誘発

        WP_Mock::userFunction('ldl_get_log_path')->andReturn($temp);

        $result = ldl_delete_log_file();
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);

        chmod($temp, 0644);
        unlink($temp);
    }
}
