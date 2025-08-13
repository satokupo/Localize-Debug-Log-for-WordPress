<?php

/**
 * Phase 7: 強制キャプチャーモード - ストレージ/UI機能のテスト
 *
 * 目的: option読み書きユーティリティとUIトグル+説明文+nonce保存のTDDテスト
 * Red段階: 未実装関数のテストで意図的に失敗させる
 */

use PHPUnit\Framework\TestCase;

class Phase7_Force_Capture_Storage_Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // メインプラグインファイルの読み込み（相対パス修正）
        $plugin_file = dirname(dirname(__DIR__)) . '/localize-debug-log/localize-debug-log.php';

        // ファイル存在確認
        if (!file_exists($plugin_file)) {
            $this->markTestSkipped('プラグインファイルが見つかりません: ' . $plugin_file);
        }

        require_once $plugin_file;
    }

    /**
     * Red: ldl_get_option_bool関数のテスト（未実装）
     *
     * 期待動作: boolean型optionの安全な読み取り
     * デフォルト値の正しい適用
     */
    public function test_ldl_get_option_bool_function_exists()
    {
        // Red段階: 未実装関数が存在しないことを確認
        $this->assertFalse(function_exists('ldl_get_option_bool'), 'ldl_get_option_bool関数は未実装のはず');
    }

    public function test_ldl_get_option_bool_returns_true_when_option_is_truthy()
    {
        Functions\expect('get_option')
            ->once()
            ->with('ldl_force_capture', false)
            ->andReturn('1'); // 文字列の'1'もtrueとして扱う

        $result = ldl_get_option_bool('ldl_force_capture', false);

        $this->assertTrue($result);
    }

    public function test_ldl_get_option_bool_handles_non_boolean_values_safely()
    {
        Functions\expect('get_option')
            ->once()
            ->with('ldl_test_option', true)
            ->andReturn('invalid_string');

        $result = ldl_get_option_bool('ldl_test_option', true);

        // 非boolean値は安全にdefaultに戻す
        $this->assertTrue($result);
    }

    /**
     * Red: UIトグル表示のテスト（未実装）
     *
     * 期待動作: ldl_render_log_pageに強制キャプチャートグルが追加表示
     */
    public function test_ldl_render_log_page_displays_force_capture_toggle()
    {
        Functions\expect('current_user_can')->once()->with('manage_options')->andReturn(true);
        Functions\expect('get_option')->with('ldl_force_capture', false)->andReturn(false);
        Functions\expect('wp_nonce_field')->once()->andReturn('<input type="hidden" name="ldl_nonce" value="test">');

        // 既存のログ表示関数をモック
        Functions\when('ldl_get_formatted_log')->justReturn(array('test log line'));
        Functions\when('ldl_get_log_path')->justReturn('/test/path/debug.log');

        ob_start();
        ldl_render_log_page();
        $output = ob_get_clean();

        // トグルUIの存在確認
        $this->assertStringContainsString('強制キャプチャーモード', $output);
        $this->assertStringContainsString('ldl_force_capture', $output);
        $this->assertStringContainsString('type="checkbox"', $output);
    }

    public function test_ldl_render_log_page_shows_force_capture_explanation()
    {
        Functions\expect('current_user_can')->once()->with('manage_options')->andReturn(true);
        Functions\expect('get_option')->with('ldl_force_capture', false)->andReturn(false);

        Functions\when('ldl_get_formatted_log')->justReturn(array());
        Functions\when('ldl_get_log_path')->justReturn('/test/path/debug.log');

        ob_start();
        ldl_render_log_page();
        $output = ob_get_clean();

        // 説明文の存在確認
        $this->assertStringContainsString('WP_DEBUG=false', $output);
        $this->assertStringContainsString('警告', $output);
        $this->assertStringContainsString('注意', $output);
    }

    /**
     * Red: POST保存処理のテスト（未実装）
     *
     * 期待動作: nonce付きPOSTでoption更新
     */
    public function test_force_capture_option_saves_on_valid_post()
    {
        // POSTデータのシミュレート
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['ldl_save_settings'] = '1';
        $_POST['ldl_force_capture'] = '1';
        $_POST['ldl_settings_nonce'] = 'valid_nonce';

        Functions\expect('check_admin_referer')
            ->once()
            ->with('ldl_save_settings_action', 'ldl_settings_nonce')
            ->andReturn(true);

        Functions\expect('update_option')
            ->once()
            ->with('ldl_force_capture', true)
            ->andReturn(true);

        // 未実装の保存処理関数を呼び出し
        $result = ldl_handle_settings_save();

        $this->assertTrue($result);

        // クリーンアップ
        unset($_SERVER['REQUEST_METHOD']);
        unset($_POST['ldl_save_settings']);
        unset($_POST['ldl_force_capture']);
        unset($_POST['ldl_settings_nonce']);
    }

    public function test_force_capture_option_rejects_invalid_nonce()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['ldl_save_settings'] = '1';
        $_POST['ldl_force_capture'] = '1';
        $_POST['ldl_settings_nonce'] = 'invalid_nonce';

        Functions\expect('check_admin_referer')
            ->once()
            ->with('ldl_save_settings_action', 'ldl_settings_nonce')
            ->andReturn(false); // nonce検証失敗

        Functions\expect('update_option')->never(); // option更新されない

        $result = ldl_handle_settings_save();

        $this->assertFalse($result);

        // クリーンアップ
        unset($_SERVER['REQUEST_METHOD']);
        unset($_POST['ldl_save_settings']);
        unset($_POST['ldl_force_capture']);
        unset($_POST['ldl_settings_nonce']);
    }
}
