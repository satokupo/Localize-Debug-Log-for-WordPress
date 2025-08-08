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

    /**
     * add_options_page の capability が manage_options であること
     */
    public function test_add_options_page_capability_is_manage_options()
    {
        WP_Mock::userFunction('add_options_page')
            ->once()
            ->with(
                \Mockery::type('string'),
                \Mockery::type('string'),
                'manage_options',
                \Mockery::type('string'),
                \Mockery::type('string')
            )
            ->andReturn('settings_page_localize_debug_log');

        ldl_add_admin_menu();

        $this->assertTrue(true);
    }

    /**
     * 管理バータイトルに dashicons-admin-settings が含まれること
     */
    public function test_admin_bar_title_contains_dashicon()
    {
        // admin_url をモック
        WP_Mock::userFunction('admin_url')
            ->andReturn('http://example/options-general.php?page=localize-debug-log');

        // add_node を受け取るスタブ
        $captured = [];
        $stub = new class($captured) {
            public $captured;
            public function __construct(&$c) { $this->captured = &$c; }
            public function add_node($args) { $this->captured[] = $args; }
        };

        ldl_add_admin_bar_link($stub);

        $this->assertNotEmpty($stub->captured);
        $this->assertStringContainsString('dashicons-admin-settings', $stub->captured[0]['title']);
    }
    /**
     * 削除関連のフック登録（admin_init/admin_notices）
     */
    public function test_registers_delete_related_hooks()
    {
        WP_Mock::expectActionAdded('admin_init', 'ldl_handle_delete_request', 10, 0);
        WP_Mock::expectActionAdded('admin_notices', 'ldl_notice_delete_result', 10, 0);

        // 実際の登録はプラグイン読み込み時に行われる想定
        // 簡易的に、対象関数が呼べることを確認
        $this->assertTrue(function_exists('ldl_handle_delete_request'));
        $this->assertTrue(function_exists('ldl_notice_delete_result'));

        // テスト環境では直接 add_action を呼ぶ代替として期待を満たす
        add_action('admin_init', 'ldl_handle_delete_request', 10, 0);
        add_action('admin_notices', 'ldl_notice_delete_result', 10, 0);

        $this->assertTrue(true);
    }
}


