<?php
/**
 * CSRF保護ユーティリティ ldl_csrf_protect のテスト
 *
 * 仕様:
 * - 発行モード: wp_nonce_field の戻り値を返す
 * - 検証モード: check_admin_referer の boolean を返す
 * - デフォルト: action='ldl_delete_log_action', field='ldl_delete_nonce'
 */

use PHPUnit\Framework\TestCase;

class Ldl_Csrf_Protect_Test extends TestCase
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
     * @test
     * 発行モード: フィールドHTMLが返る（name=ldl_delete_nonce を含む）
     */
    public function csrf_protect_field_mode_returns_nonce_html()
    {
        // Arrange
        $expected_html = '<input type="hidden" id="ldl_delete_nonce" name="ldl_delete_nonce" value="abc123def456" />';

        WP_Mock::userFunction('wp_nonce_field')
            ->once()
            ->with('ldl_delete_log_action', 'ldl_delete_nonce', true, false)
            ->andReturn($expected_html);

        // Act
        $result = ldl_csrf_protect();

        // Assert
        $this->assertEquals($expected_html, $result);
        $this->assertStringContainsString('name="ldl_delete_nonce"', $result);
    }

    /**
     * @test
     * 発行モード: カスタムアクション・フィールドで呼び出せる
     */
    public function csrf_protect_field_mode_with_custom_params()
    {
        // Arrange
        $custom_action = 'custom_action';
        $custom_field = 'custom_field';
        $expected_html = '<input type="hidden" id="custom_field" name="custom_field" value="xyz789" />';

        WP_Mock::userFunction('wp_nonce_field')
            ->once()
            ->with($custom_action, $custom_field, true, false)
            ->andReturn($expected_html);

        // Act
        $result = ldl_csrf_protect($custom_action, $custom_field);

        // Assert
        $this->assertEquals($expected_html, $result);
    }

    /**
     * @test
     * 検証モード（一致）: check_admin_referer をモックし true → true を返す
     */
    public function csrf_protect_verify_mode_returns_true_on_valid_nonce()
    {
        // Arrange
        WP_Mock::userFunction('check_admin_referer')
            ->once()
            ->with('ldl_delete_log_action', 'ldl_delete_nonce')
            ->andReturn(true);

        // Act
        $result = ldl_csrf_protect('ldl_delete_log_action', 'ldl_delete_nonce', 'verify');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     * 検証モード（不一致）: false → false を返す
     */
    public function csrf_protect_verify_mode_returns_false_on_invalid_nonce()
    {
        // Arrange
        WP_Mock::userFunction('check_admin_referer')
            ->once()
            ->with('ldl_delete_log_action', 'ldl_delete_nonce')
            ->andReturn(false);

        // Act
        $result = ldl_csrf_protect('ldl_delete_log_action', 'ldl_delete_nonce', 'verify');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     * 不正なモードの場合はfieldモードとして動作
     */
    public function csrf_protect_invalid_mode_defaults_to_field()
    {
        // Arrange
        $expected_html = '<input type="hidden" id="ldl_delete_nonce" name="ldl_delete_nonce" value="default123" />';

        WP_Mock::userFunction('wp_nonce_field')
            ->once()
            ->with('ldl_delete_log_action', 'ldl_delete_nonce', true, false)
            ->andReturn($expected_html);

        // Act
        $result = ldl_csrf_protect('ldl_delete_log_action', 'ldl_delete_nonce', 'invalid_mode');

        // Assert
        $this->assertEquals($expected_html, $result);
    }
}
