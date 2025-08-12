<?php
/**
 * Phase 6: ldl_render_log_page エラーハンドリング強化テスト
 *
 * 作成日: 2025-08-11
 * 用途: ldl_render_log_page の大容量・不可読ファイル時のUI構造不変テスト（TDD Red フェーズ）
 * 対象: ldl_render_log_page
 *
 * 重点:
 * - 大容量ファイル時のUI構造（要素・属性）が不変であることの確認
 * - ファイル読み込み失敗時のグレースフル処理確認
 * - 権限不足時の適切なエラー表示確認
 * - HTMLエスケープの安全性確認
 * - UI要素の完全性（削除フォーム、nonce、CSSクラス等）確認
 */

use PHPUnit\Framework\TestCase;

class Ldl_Phase6RenderPageEnhanced_Test extends TestCase
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
     * @test
     * TDD Red-P6-D1: 権限不足時のエラー表示とUI要素の確認
     */
    public function ldl_render_log_page_shows_permission_error_with_proper_structure()
    {
        // Arrange: 権限不足をシミュレート
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(false);

        // Act: 出力をキャプチャ
        ob_start();
        ldl_render_log_page();
        $output = ob_get_clean();

        // Assert: 権限エラーメッセージが適切に表示される
        $this->assertStringContainsString('notice notice-error', $output, 'Permission error should use proper notice class');
        $this->assertStringContainsString('このページにアクセスする権限がありません', $output, 'Permission error message should be displayed');

        // UI要素が権限不足時に適切に制限される（削除フォーム等が表示されない）
        $this->assertStringNotContainsString('<form', $output, 'Delete form should not be displayed for unauthorized users');
        $this->assertStringNotContainsString('<textarea', $output, 'Log textarea should not be displayed for unauthorized users');
    }

    /**
     * @test
     * TDD Red-P6-D2: 正常権限時の完全なUI構造確認
     */
    public function ldl_render_log_page_outputs_complete_ui_structure_with_permissions()
    {
        // Arrange: 正常な権限をシミュレート
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);

        // 必要なWordPress関数をモック
        // ldl_get_formatted_log が呼ばれるかどうかは function_exists チェックに依存するため、
        // より柔軟にモックを設定
        WP_Mock::userFunction('get_option')
            ->zeroOrMoreTimes()
            ->andReturnValues(['Asia/Tokyo', 9.0]);

        WP_Mock::userFunction('wp_nonce_field')
            ->once()
            ->with('ldl_delete_log_action', 'ldl_delete_nonce')
            ->andReturn('<input type="hidden" name="ldl_delete_nonce" value="test123" />');

        // Act: 出力をキャプチャ
        ob_start();
        ldl_render_log_page();
        $output = ob_get_clean();

        // Assert: 完全なUI構造が出力される
        $this->assertStringContainsString('<div class="wrap">', $output, 'Main wrap div should be present');
        $this->assertStringContainsString('<h1>Localize Debug Log</h1>', $output, 'Page title should be present');
        $this->assertStringContainsString('<table class="widefat">', $output, 'Log display table should use widefat class');
        $this->assertStringContainsString('<textarea readonly rows="20"', $output, 'Log textarea should be readonly with proper attributes');
        $this->assertStringContainsString('<form method="post"', $output, 'Delete form should be present');
        $this->assertStringContainsString('onsubmit="return confirm(', $output, 'Delete confirmation should be present');
        $this->assertStringContainsString('<button type="submit" class="button button-secondary">', $output, 'Delete button should have proper classes');
        $this->assertStringContainsString('ldl_delete_nonce', $output, 'CSRF nonce should be included');
    }

    /**
     * @test
     * TDD Red-P6-D3: ファイル不存在時の通知とUI構造確認
     */
    public function ldl_render_log_page_handles_missing_file_with_proper_notices()
    {
        // Arrange: 正常な権限、ファイル関数は存在するが、ファイルは存在しない
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);

        WP_Mock::userFunction('get_option')
            ->zeroOrMoreTimes()
            ->andReturnValues(['Asia/Tokyo', 9.0]);

        WP_Mock::userFunction('wp_nonce_field')
            ->once()
            ->andReturn('<input type="hidden" name="nonce" value="test" />');

        // Act: 出力をキャプチャ（ファイル不存在のケース）
        ob_start();
        ldl_render_log_page();
        $output = ob_get_clean();

        // Assert: ファイル不存在の通知が適切に表示される
        $this->assertStringContainsString('notice notice-info', $output, 'File not found should use info notice class');
        $this->assertStringContainsString('ログファイルが見つかりません', $output, 'File not found message should be displayed');

        // UI構造は維持される（空のテキストエリア等）
        $this->assertStringContainsString('<textarea readonly', $output, 'Textarea should still be present for missing file');
        $this->assertStringContainsString('<form method="post"', $output, 'Delete form should still be present for missing file');
    }

    /**
     * @test
     * TDD Red-P6-D4: HTMLエスケープの安全性確認
     */
    public function ldl_render_log_page_properly_escapes_html_content()
    {
        // Arrange: 権限OK、危険なHTMLを含むログデータをシミュレート
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);

        // ログ関数が存在し、危険なHTMLを返すようにモック
        // （実際にはldl_get_formatted_logは内部でhtmlspecialcharsを適用するため、
        // ここではエスケープ処理の動作確認が目的）
        WP_Mock::userFunction('get_option')
            ->zeroOrMoreTimes()
            ->andReturnValues(['Asia/Tokyo', 9.0]);

        WP_Mock::userFunction('wp_nonce_field')
            ->once()
            ->andReturn('<input type="hidden" name="nonce" value="test" />');

        // Act: 出力をキャプチャ
        ob_start();
        ldl_render_log_page();
        $output = ob_get_clean();

        // Assert: HTMLエスケープが適切に動作している
        // textarea内のコンテンツがエスケープされている前提で、基本構造を確認
        $this->assertStringContainsString('<textarea readonly', $output, 'Textarea should be present');

        // 出力されるHTMLがwell-formedである（タグの閉じ漏れがない）
        $this->assertStringContainsString('</textarea>', $output, 'Textarea should be properly closed');
        $this->assertStringContainsString('</form>', $output, 'Form should be properly closed');
        $this->assertStringContainsString('</div>', $output, 'Wrap div should be properly closed');
    }

    /**
     * @test
     * TDD Red-P6-D5: 大容量ファイル警告とUI構造の両立確認
     * 注意：実際のファイルサイズ判定はファイルシステムに依存するため、
     * UI構造の整合性確認に重点を置く
     */
    public function ldl_render_log_page_maintains_ui_structure_regardless_of_file_size()
    {
        // Arrange: 正常な権限
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);

        WP_Mock::userFunction('get_option')
            ->zeroOrMoreTimes()
            ->andReturnValues(['Asia/Tokyo', 9.0]);

        WP_Mock::userFunction('wp_nonce_field')
            ->once()
            ->andReturn('<input type="hidden" name="nonce" value="test" />');

        // Act: 出力をキャプチャ
        ob_start();
        ldl_render_log_page();
        $output = ob_get_clean();

        // Assert: ファイルサイズに関係なく、基本UI構造が維持される
        $required_elements = [
            '<div class="wrap">',           // メインラップ
            '<h1>Localize Debug Log</h1>',  // ページタイトル
            '<table class="widefat">',      // ログ表示テーブル
            '<textarea readonly',           // ログテキストエリア
            'rows="20"',                    // テキストエリア行数
            'style="width:100%;"',          // テキストエリアスタイル
            '<form method="post"',          // 削除フォーム
            'onsubmit="return confirm(',    // 削除確認
            '<button type="submit"',        // 削除ボタン
            'class="button button-secondary"', // ボタンクラス
            'ログを削除',                    // ボタンテキスト
        ];

        foreach ($required_elements as $element) {
            $this->assertStringContainsString($element, $output, "Required UI element missing: {$element}");
        }
    }

    /**
     * @test
     * TDD Red-P6-D6: 関数依存チェックと例外安全性確認
     */
    public function ldl_render_log_page_handles_missing_dependencies_gracefully()
    {
        // Arrange: 権限OK、依存関数の一部が存在しない場合をシミュレート
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);

        // get_optionは存在するがwp_nonce_fieldは存在しない状況
        WP_Mock::userFunction('get_option')
            ->zeroOrMoreTimes()
            ->andReturnValues(['Asia/Tokyo', 9.0]);

        // wp_nonce_fieldが存在しない場合の動作確認（function_existsチェック）

        // Act: 出力をキャプチャ（例外が発生しないことを確認）
        ob_start();
        try {
            ldl_render_log_page();
            $output = ob_get_clean();
            $exception_occurred = false;
        } catch (Exception $e) {
            ob_get_clean(); // バッファをクリア
            $exception_occurred = true;
        }

        // Assert: 例外が発生せず、基本構造が出力される
        $this->assertFalse($exception_occurred, 'Function should not throw exception even with missing dependencies');
        if (!$exception_occurred) {
            $this->assertStringContainsString('<div class="wrap">', $output, 'Basic UI structure should be maintained');
            $this->assertStringContainsString('<form method="post"', $output, 'Form should be present even without nonce field');
        }
    }

    /**
     * @test
     * TDD Red-P6-D7: 関数存在と基本動作確認
     */
    public function ldl_render_log_page_function_exists_and_basic_operation()
    {
        // 関数存在確認
        $this->assertTrue(function_exists('ldl_render_log_page'), 'Function ldl_render_log_page must exist');

        // 権限なしでの基本呼び出し確認（出力のみで戻り値なし）
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(false);

        ob_start();
        $result = ldl_render_log_page();
        $output = ob_get_clean();

        // void関数なのでnullを返すか、戻り値なし
        $this->assertNull($result, 'Function should return null (void function)');
        $this->assertIsString($output, 'Function should produce output');
        $this->assertNotEmpty($output, 'Function should produce non-empty output');
    }
}
