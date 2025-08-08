<?php
/**
 * ログ表示画面テスト（Phase 3）
 */

use PHPUnit\Framework\TestCase;

class LogDisplay_Test extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock::setUp();
        require_once dirname(__DIR__, 3) . '/localize-debug-log/localize-debug-log.php';
    }

    /**
     * 削除フォーム（nonce と confirm）が出力される
     */
    public function test_render_log_page_has_delete_form_and_nonce()
    {
        WP_Mock::userFunction('current_user_can')->with('manage_options')->andReturn(true);
        // ログは空でもOK
        WP_Mock::userFunction('ldl_get_formatted_log')->andReturn([]);

        // nonce フィールドのモック
        WP_Mock::userFunction('wp_nonce_field')
            ->with('ldl_delete_log_action', 'ldl_delete_nonce')
            ->andReturn('<input type="hidden" name="ldl_delete_nonce" value="testnonce" />');

        ob_start();
        ldl_render_log_page();
        $html = ob_get_clean();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('name="ldl_delete_log"', $html);
        $this->assertStringContainsString('ldl_delete_nonce', $html);
        $this->assertStringContainsString('confirm(', $html);
    }

    protected function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    /**
     * textarea に `ldl_get_formatted_log()` の結果が表示され、エスケープされる
     */
    public function test_render_log_page_outputs_textarea_with_escaped_log()
    {
        WP_Mock::userFunction('current_user_can')->with('manage_options')->andReturn(true);
        // モック: ログ整形結果
        WP_Mock::userFunction('ldl_get_formatted_log')
            ->once()
            ->andReturn([
                'Line 1',
                'Line <2>'
            ]);

        // モック: ログサイズ警告（<= 1MB）
        $temp = tempnam(sys_get_temp_dir(), 'ldl_ui_');
        file_put_contents($temp, str_repeat('a', 100));
        WP_Mock::userFunction('ldl_get_log_path')->andReturn($temp);

        ob_start();
        $this->assertTrue(function_exists('ldl_render_log_page'), 'ldl_render_log_page が未実装です');
        ldl_render_log_page();
        $html = ob_get_clean();

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('readonly', $html);
        $this->assertStringContainsString('Line 1', $html);
        // エスケープ確認
        $this->assertStringContainsString('Line &lt;2&gt;', $html);

        unlink($temp);
    }

    /**
     * 1MB 超のログサイズで警告メッセージが表示される
     */
    public function test_render_log_page_shows_warning_when_file_is_large()
    {
        WP_Mock::userFunction('current_user_can')->with('manage_options')->andReturn(true);
        $large = tempnam(sys_get_temp_dir(), 'ldl_large_');
        // 1.1MB のダミーファイル
        file_put_contents($large, str_repeat('b', 1153434));
        WP_Mock::userFunction('ldl_get_log_path')->andReturn($large);

        // ログ本体は空でもよい
        WP_Mock::userFunction('ldl_get_formatted_log')->andReturn([]);

        ob_start();
        ldl_render_log_page();
        $html = ob_get_clean();

        $this->assertStringContainsString('notice-warning', $html);

        unlink($large);
    }

    /**
     * ログファイルが存在しない場合の表示（空textarea）
     */
    public function test_render_log_page_when_log_missing_shows_empty_textarea()
    {
        WP_Mock::userFunction('current_user_can')->with('manage_options')->andReturn(true);
        // ログ未存在
        WP_Mock::userFunction('ldl_get_log_path')->andReturn('/path/to/missing.log');
        WP_Mock::userFunction('ldl_get_formatted_log')->andReturn([]);

        ob_start();
        ldl_render_log_page();
        $html = ob_get_clean();

        $this->assertStringContainsString('<textarea', $html);
        // 中身が空に近いこと（厳密一致は避ける）
        $this->assertStringContainsString('></textarea>', preg_replace('/\s+/', '', $html));
    }

    /**
     * ログ未存在時は notice-info メッセージを表示
     */
    public function test_render_log_page_missing_file_shows_info_notice()
    {
        WP_Mock::userFunction('current_user_can')->with('manage_options')->andReturn(true);
        WP_Mock::userFunction('ldl_get_log_path')->andReturn('/path/to/missing.log');
        WP_Mock::userFunction('ldl_get_formatted_log')->andReturn([]);

        ob_start();
        ldl_render_log_page();
        $html = ob_get_clean();

        $this->assertStringContainsString('notice-info', $html);
    }

    /**
     * 権限不足時はエラーメッセージを表示し、処理を中断
     */
    public function test_render_log_page_permission_denied_shows_error_notice()
    {
        WP_Mock::userFunction('current_user_can')->with('manage_options')->andReturn(false);

        ob_start();
        ldl_render_log_page();
        $html = ob_get_clean();

        $this->assertStringContainsString('notice-error', $html);
    }
}


