<?php
/**
 * Phase 6: ldl_handle_delete_request エラーハンドリング強化テスト
 *
 * 作成日: 2025-08-11
 * 用途: ldl_handle_delete_request の非POST・nonce不正・権限不足時のテスト（TDD Red フェーズ）
 * 対象: ldl_handle_delete_request
 *
 * 重点:
 * - 非POSTメソッド時に削除処理が実行されないことの確認
 * - nonce不正時の安全側処理確認
 * - 権限不足時の適切なエラー処理確認
 * - グローバル変数 $ldl_last_delete_result の状態確認
 * - 各種エッジケースでの安定動作確認
 */

use PHPUnit\Framework\TestCase;

class Ldl_Phase6HandleDeleteEnhanced_Test extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock::setUp();

        // メインプラグインファイルを読み込み
        require_once dirname(__DIR__, 3) . '/localize-debug-log/localize-debug-log.php';

        // グローバル変数をクリア
        global $ldl_last_delete_result;
        $ldl_last_delete_result = null;
    }

    protected function tearDown(): void
    {
        // テスト後のクリーンアップ
        global $ldl_last_delete_result;
        $ldl_last_delete_result = null;

        // スーパーグローバルのクリーンアップ
        unset($_SERVER['REQUEST_METHOD']);
        unset($_POST['ldl_delete_log']);

        WP_Mock::tearDown();
    }

    /**
     * @test
     * TDD Red-P6-C1: 非POSTメソッド時に削除処理が実行されないことの確認
     */
    public function ldl_handle_delete_request_ignores_non_post_methods()
    {
        global $ldl_last_delete_result;

        $non_post_methods = ['GET', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];

        foreach ($non_post_methods as $method) {
            // Arrange
            $_SERVER['REQUEST_METHOD'] = $method;
            $_POST['ldl_delete_log'] = '1';
            $ldl_last_delete_result = null;

            // Act
            $result = ldl_handle_delete_request();

            // Assert
            $this->assertNull($result, 'Function should return null for method: ' . $method);
            $this->assertNull($ldl_last_delete_result, 'Global result should remain null for method: ' . $method);
        }
    }

    /**
     * @test
     * TDD Red-P6-C2: REQUEST_METHOD未設定時の安全な処理確認
     */
    public function ldl_handle_delete_request_handles_missing_request_method()
    {
        global $ldl_last_delete_result;

        // Arrange
        unset($_SERVER['REQUEST_METHOD']);
        $_POST['ldl_delete_log'] = '1';
        $ldl_last_delete_result = null;

        // Act
        $result = ldl_handle_delete_request();

        // Assert
        $this->assertNull($result, 'Function should return null when REQUEST_METHOD is not set');
        $this->assertNull($ldl_last_delete_result, 'Global result should remain null when REQUEST_METHOD is not set');
    }

    /**
     * @test
     * TDD Red-P6-C3: POSTパラメータ不足時の早期リターン確認
     */
    public function ldl_handle_delete_request_ignores_missing_post_parameters()
    {
        global $ldl_last_delete_result;

        $missing_parameter_cases = [
            'empty_post' => [],
            'missing_key' => ['other_param' => '1'],
            'empty_value' => ['ldl_delete_log' => ''],
            'zero_value' => ['ldl_delete_log' => '0'],
            'false_value' => ['ldl_delete_log' => false],
        ];

        foreach ($missing_parameter_cases as $case_name => $post_data) {
            // Arrange
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = $post_data;
            $ldl_last_delete_result = null;

            // Act
            $result = ldl_handle_delete_request();

            // Assert
            $this->assertNull($result, 'Function should return null for case: ' . $case_name);
            $this->assertNull($ldl_last_delete_result, 'Global result should remain null for case: ' . $case_name);
        }
    }

    /**
     * @test
     * TDD Red-P6-C4: 権限不足時のエラー処理確認
     */
    public function ldl_handle_delete_request_handles_permission_failure()
    {
        global $ldl_last_delete_result;

        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['ldl_delete_log'] = '1';
        $ldl_last_delete_result = null;

        // current_user_can をモック（権限不足をシミュレート）
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(false);

        // Act
        $result = ldl_handle_delete_request();

        // Assert
        $this->assertNull($result, 'Function should return null');
        $this->assertIsArray($ldl_last_delete_result, 'Global result should be set');
        $this->assertFalse($ldl_last_delete_result['success'], 'success should be false for permission failure');
        $this->assertEquals('permission', $ldl_last_delete_result['message'], 'message should indicate permission error');
    }

    /**
     * @test
     * TDD Red-P6-C5: nonce検証失敗時のエラー処理確認
     */
    public function ldl_handle_delete_request_handles_nonce_failure()
    {
        global $ldl_last_delete_result;

        // Arrange
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['ldl_delete_log'] = '1';
        $ldl_last_delete_result = null;

        // current_user_can をモック（権限OK）
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);

        // check_admin_referer をモック（nonce検証失敗をシミュレート）
        WP_Mock::userFunction('check_admin_referer')
            ->once()
            ->with('ldl_delete_log_action', 'ldl_delete_nonce')
            ->andReturn(false);

        // Act
        $result = ldl_handle_delete_request();

        // Assert
        $this->assertNull($result, 'Function should return null');
        $this->assertIsArray($ldl_last_delete_result, 'Global result should be set');
        $this->assertFalse($ldl_last_delete_result['success'], 'success should be false for nonce failure');
        $this->assertEquals('nonce', $ldl_last_delete_result['message'], 'message should indicate nonce error');
    }

    /**
     * @test
     * TDD Red-P6-C6: グローバル変数の初期化確認
     */
    public function ldl_handle_delete_request_initializes_global_result()
    {
        global $ldl_last_delete_result;

        // Arrange: グローバル変数に前回の値を設定
        $ldl_last_delete_result = array('success' => true, 'previous' => 'data');
        $_SERVER['REQUEST_METHOD'] = 'GET'; // 早期リターンするケース

        // Act
        ldl_handle_delete_request();

        // Assert
        $this->assertNull($ldl_last_delete_result, 'Global result should be cleared at function start');
    }

    /**
     * @test
     * TDD Red-P6-C7: 関数存在と基本動作確認
     */
    public function ldl_handle_delete_request_function_exists_and_basic_operation()
    {
        // 関数存在確認
        $this->assertTrue(function_exists('ldl_handle_delete_request'), 'Function ldl_handle_delete_request must exist');

        // 基本的な呼び出し確認（戻り値は常にnull）
        $result = ldl_handle_delete_request();
        $this->assertNull($result, 'Function should always return null (void function)');
    }

    /**
     * @test
     * TDD Red-P6-C8: 複数回呼び出し時の状態管理確認
     */
    public function ldl_handle_delete_request_handles_multiple_calls()
    {
        global $ldl_last_delete_result;

        // 1回目: 権限不足エラー
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['ldl_delete_log'] = '1';

        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(false);

        ldl_handle_delete_request();
        $first_result = $ldl_last_delete_result;

        // 2回目: 非POSTで早期リターン
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ldl_handle_delete_request();

        // Assert
        $this->assertIsArray($first_result, 'First call should set error result');
        $this->assertEquals('permission', $first_result['message'], 'First call should indicate permission error');
        $this->assertNull($ldl_last_delete_result, 'Second call should clear the global result');
    }

    /**
     * @test
     * TDD Red-P6-C9: エッジケースでの例外安全性確認
     */
    public function ldl_handle_delete_request_is_exception_safe()
    {
        global $ldl_last_delete_result;

        // 様々な「問題のありそうな」入力で例外が発生しないことを確認
        $problematic_cases = [
            'null_post' => null,
            'very_long_value' => str_repeat('x', 10000),
            'binary_data' => "\x00\x01\xFF",
            'array_value' => ['nested' => 'array'],
        ];

        foreach ($problematic_cases as $case_name => $post_value) {
            try {
                // Arrange
                $_SERVER['REQUEST_METHOD'] = 'POST';
                $_POST = ['ldl_delete_log' => $post_value];
                $ldl_last_delete_result = null;

                // Act
                $result = ldl_handle_delete_request();

                // Assert
                $this->assertNull($result, 'Function should not throw exception for case: ' . $case_name);

            } catch (Exception $e) {
                $this->fail('Function should not throw exception for case: ' . $case_name . '. Exception: ' . $e->getMessage());
            }
        }
    }
}
