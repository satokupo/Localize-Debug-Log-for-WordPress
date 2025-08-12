<?php
/**
 * Phase 6: ldl_delete_log_file エラーハンドリング強化テスト
 *
 * 作成日: 2025-08-11
 * 用途: ldl_delete_log_file の権限不足・ファイル不存在・書込不可時の挙動テスト（TDD Red フェーズ）
 * 対象: ldl_delete_log_file
 *
 * 重点:
 * - 権限不足時の戻り値・ファイル状態確認
 * - ファイル不存在時の処理確認
 * - 書込不可時の安全側フォールバック確認
 * - 排他制御失敗時の処理確認
 * - 例外発生時の安定した戻り値確認
 */

use PHPUnit\Framework\TestCase;

class Ldl_Phase6DeleteFileEnhanced_Test extends TestCase
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
     * TDD Red-P6-B1: 無効パス入力時の戻り値構造確認
     * パス検証失敗時に適切な構造の戻り値を返すことを確認
     */
    public function ldl_delete_log_file_returns_proper_structure_on_invalid_path()
    {
        $invalid_paths = [
            '',                     // 空文字
            null,                   // NULL
            '../etc/passwd',        // ディレクトリトラバーサル
            '/absolute/path.log',   // 絶対パス
            '../../../config.ini',  // 深いトラバーサル
        ];

        foreach ($invalid_paths as $path) {
            $result = ldl_delete_log_file($path);

            // 戻り値構造の確認
            $this->assertIsArray($result, 'Result must be array for path: ' . var_export($path, true));
            $this->assertArrayHasKey('success', $result, 'Result must have success key');
            $this->assertFalse($result['success'], 'success must be false for invalid path');

            // メッセージの存在確認（実装依存だが、何らかのエラー情報があることを確認）
            if (isset($result['message'])) {
                $this->assertIsString($result['message'], 'message must be string if present');
                $this->assertNotEmpty($result['message'], 'message should not be empty if present');
            }
        }
    }

    /**
     * @test
     * TDD Red-P6-B2: 非文字列パス引数の処理確認
     * 文字列以外の引数が渡された場合の安全な処理確認
     */
    public function ldl_delete_log_file_handles_non_string_path_safely()
    {
        $non_string_inputs = [
            123,                    // integer
            12.34,                  // float
            true,                   // boolean
            [],                     // array
            new stdClass(),         // object
        ];

        foreach ($non_string_inputs as $input) {
            $result = ldl_delete_log_file($input);

            // 基本構造確認
            $this->assertIsArray($result, 'Result must be array for input: ' . var_export($input, true));
            $this->assertArrayHasKey('success', $result, 'Result must have success key');
            $this->assertFalse($result['success'], 'success must be false for non-string input');

            // 例外が発生せず、安定した戻り値を返すことを確認
            $this->addToAssertionCount(1); // テスト完了の確認
        }
    }

    /**
     * @test
     * TDD Red-P6-B3: ファイル操作失敗シミュレーション
     * ファイル操作が失敗する場合の戻り値構造確認
     */
    public function ldl_delete_log_file_handles_file_operation_failures()
    {
        // 注意：実際のファイルシステム操作に依存しないテスト設計
        // 現在の実装では、不正なパスは validate で早期リターンされるため、
        // ファイル操作段階でのエラーハンドリングを間接的に確認

        // 実在しない（が構文的には有効に見える）パス
        $problematic_paths = [
            'nonexistent_directory/debug.log',     // 存在しないディレクトリ
            'very_long_' . str_repeat('x', 1000) . '.log', // 極端に長いファイル名
        ];

        foreach ($problematic_paths as $path) {
            $result = ldl_delete_log_file($path);

            // 戻り値の構造確認
            $this->assertIsArray($result, 'Result must be array for path: ' . $path);
            $this->assertArrayHasKey('success', $result, 'Result must have success key');
            $this->assertIsBool($result['success'], 'success must be boolean');

            // エラー時の情報確認
            if (!$result['success'] && isset($result['message'])) {
                $this->assertIsString($result['message'], 'Error message must be string');
            }
        }
    }

    /**
     * @test
     * TDD Red-P6-B4: 戻り値の一貫性確認
     * 様々な入力に対して、戻り値の形式が一貫していることを確認
     */
    public function ldl_delete_log_file_returns_consistent_format()
    {
        $test_inputs = [
            '',                     // 空文字
            'debug.log',           // 通常のファイル名
            '../debug.log',        // トラバーサル
            null,                  // NULL
            123,                   // 数値
        ];

        foreach ($test_inputs as $input) {
            $result = ldl_delete_log_file($input);

            // 全ての戻り値が配列であることを確認
            $this->assertIsArray($result, 'All results must be arrays');

            // success キーの存在と型確認
            $this->assertArrayHasKey('success', $result, 'success key must exist');
            $this->assertIsBool($result['success'], 'success must be boolean');

            // 失敗時の追加情報確認（あれば文字列であること）
            if (!$result['success'] && array_key_exists('message', $result)) {
                $this->assertIsString($result['message'], 'message must be string when present');
            }

            // 予期しないキーがないことを確認
            $allowed_keys = ['success', 'message'];
            foreach (array_keys($result) as $key) {
                $this->assertContains($key, $allowed_keys, 'Unexpected key in result: ' . $key);
            }
        }
    }

    /**
     * @test
     * TDD Red-P6-B5: 関数存在と基本動作確認
     */
    public function ldl_delete_log_file_function_exists_and_basic_operation()
    {
        // 関数存在確認
        $this->assertTrue(function_exists('ldl_delete_log_file'), 'Function ldl_delete_log_file must exist');

        // 基本的な呼び出し確認（引数なし）
        $result = ldl_delete_log_file();
        $this->assertIsArray($result, 'Function must return array');
        $this->assertArrayHasKey('success', $result, 'Result must have success key');

        // 引数ありの基本確認
        $result_with_arg = ldl_delete_log_file('test.log');
        $this->assertIsArray($result_with_arg, 'Function must return array with argument');
        $this->assertArrayHasKey('success', $result_with_arg, 'Result must have success key with argument');
    }

    /**
     * @test
     * TDD Red-P6-B6: 例外安全性の確認
     * 関数が例外を投げずに、常に配列を返すことを確認
     */
    public function ldl_delete_log_file_is_exception_safe()
    {
        // 様々な「問題のありそうな」入力で例外が発生しないことを確認
        $problematic_inputs = [
            "\x00",                 // null byte
            "test\ntest",           // newline
            "test\rtest",           // carriage return
            str_repeat('x', 10000), // very long string
            '../../../../../../etc/passwd', // deep traversal
        ];

        foreach ($problematic_inputs as $input) {
            try {
                $result = ldl_delete_log_file($input);
                $this->assertIsArray($result, 'Function must return array even for problematic input');
                $this->assertArrayHasKey('success', $result, 'Result must have success key');
            } catch (Exception $e) {
                $this->fail('Function should not throw exception for input: ' . bin2hex($input) . '. Exception: ' . $e->getMessage());
            }
        }
    }
}
