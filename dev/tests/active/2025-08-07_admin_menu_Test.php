<?php
/**
 * 管理画面メニュー／管理バーリンク テスト（Phase 3 - Red段階）
 *
 * 目的:
 * - 設定サブメニュー追加関数が `add_options_page()` を呼び出すこと
 * - フック登録関数が `admin_menu` / `admin_bar_menu` を適切な優先度で登録すること
 *
 * このテストは最初は失敗（Red）し、最小実装後に成功（Green）させることを意図する
 */

use PHPUnit\Framework\TestCase;

class AdminMenu_Test extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock::setUp();

        // メインプラグインファイル読み込み
        require_once dirname(__DIR__, 3) . '/localize-debug-log/localize-debug-log.php';
    }

    protected function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    /**
     * add_options_page() が呼び出されること
     */
    public function test_add_options_page_is_called_by_ldl_add_admin_menu()
    {
        // 期待: add_options_page が1回呼ばれる
        WP_Mock::userFunction('add_options_page')
            ->once()
            ->with(
                \Mockery::type('string'), // page_title
                \Mockery::type('string'), // menu_title
                \Mockery::type('string'), // capability
                \Mockery::type('string'), // menu_slug
                \Mockery::type('string') // callback（関数名文字列を許容）
            )
            ->andReturn('settings_page_localize_debug_log');

        // 実行
        $this->assertTrue(function_exists('ldl_add_admin_menu'), 'ldl_add_admin_menu が未実装です');
        ldl_add_admin_menu();

        // 成功: 期待通りに呼び出されていればテストOK（WP_Mockが検証）
        $this->assertTrue(true);
    }

    /**
     * フック登録関数が admin_menu と admin_bar_menu を登録すること
     */
    public function test_registers_admin_hooks()
    {
        // 期待: admin_menu に ldl_add_admin_menu（優先度10）
        WP_Mock::expectActionAdded('admin_menu', 'ldl_add_admin_menu', 10, 0);
        // 期待: admin_bar_menu に ldl_add_admin_bar_link（優先度100, accepted_args=1）
        WP_Mock::expectActionAdded('admin_bar_menu', 'ldl_add_admin_bar_link', 100, 1);

        $this->assertTrue(function_exists('ldl_register_admin_ui'), 'ldl_register_admin_ui が未実装です');
        ldl_register_admin_ui();

        $this->assertTrue(true);
    }
}


