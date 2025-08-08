<?php
/**
 * ログ削除機能テスト（Phase 3, TDD重点）
 */

use PHPUnit\Framework\TestCase;

class LogDeletion_Test extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock::setUp();
        require_once dirname(__DIR__, 3) . '/localize-debug-log/localize-debug-log.php';
    }

    protected function tearDown(): void
    {
        // POST汚染をクリア
        $_POST = [];
        WP_Mock::tearDown();
    }

    /**
     * ファイル削除→空ファイル再生成
     */
    public function test_delete_log_file_recreates_empty_file()
    {
        $temp = tempnam(sys_get_temp_dir(), 'ldl_del_');
        file_put_contents($temp, "dummy\n");

        // ログパスをテスト用に固定
        WP_Mock::userFunction('ldl_get_log_path')->andReturn($temp);

        $result = ldl_delete_log_file();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertFileExists($temp);
        $this->assertEquals(0, filesize($temp));

        unlink($temp);
    }

    /**
     * 正常系: 管理者+nonce一致→削除成功→成功通知
     */
    public function test_handle_delete_request_success_shows_success_notice()
    {
        // POSTセット
        $_POST['ldl_delete_log'] = '1';
        $_POST['ldl_delete_nonce'] = 'dummy';

        // 権限/nonceのモック
        WP_Mock::userFunction('current_user_can')->with('manage_options')->andReturn(true);
        WP_Mock::userFunction('check_admin_referer')
            ->with('ldl_delete_log_action', 'ldl_delete_nonce')
            ->andReturn(true);

        // ファイルパスモック
        $temp = tempnam(sys_get_temp_dir(), 'ldl_del_');
        file_put_contents($temp, "dummy\n");
        WP_Mock::userFunction('ldl_get_log_path')->andReturn($temp);

        // 実行
        ldl_handle_delete_request();

        // 通知HTMLを出力させて検証
        ob_start();
        ldl_notice_delete_result();
        $html = ob_get_clean();

        $this->assertStringContainsString('notice-success', $html);

        unlink($temp);
    }

    /**
     * 異常系: nonce不一致→エラー通知
     */
    public function test_handle_delete_request_nonce_mismatch_shows_error_notice()
    {
        $_POST['ldl_delete_log'] = '1';
        $_POST['ldl_delete_nonce'] = 'invalid';

        WP_Mock::userFunction('current_user_can')->with('manage_options')->andReturn(true);
        WP_Mock::userFunction('check_admin_referer')
            ->with('ldl_delete_log_action', 'ldl_delete_nonce')
            ->andReturn(false);

        ldl_handle_delete_request();

        ob_start();
        ldl_notice_delete_result();
        $html = ob_get_clean();

        $this->assertStringContainsString('notice-error', $html);
    }

    /**
     * 異常系: 権限不足→エラー通知
     */
    public function test_handle_delete_request_permission_denied_shows_error_notice()
    {
        $_POST['ldl_delete_log'] = '1';
        $_POST['ldl_delete_nonce'] = 'dummy';

        WP_Mock::userFunction('current_user_can')->with('manage_options')->andReturn(false);
        // nonce は呼ばれない想定だが、呼ばれてもよい
        WP_Mock::userFunction('check_admin_referer')
            ->with('ldl_delete_log_action', 'ldl_delete_nonce')
            ->andReturn(true);

        ldl_handle_delete_request();

        ob_start();
        ldl_notice_delete_result();
        $html = ob_get_clean();

        $this->assertStringContainsString('notice-error', $html);
    }

    /**
     * 未POST時: 通知は出力されない
     */
    public function test_notice_not_rendered_without_post()
    {
        ob_start();
        ldl_notice_delete_result();
        $html = ob_get_clean();

        $this->assertEquals('', trim($html));
    }
}


