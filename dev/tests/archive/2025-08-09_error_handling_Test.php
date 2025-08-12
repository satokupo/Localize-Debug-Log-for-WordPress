<?php
/**
 * エラーハンドリング再現テスト（フェーズ５：例外・境界値処理）
 *
 * 作成日: 2025-08-09
 * 用途: ファイル権限エラー・大容量ログファイル・例外時フォールバック処理の検証
 * 対象: ldl_delete_log_file, ldl_render_log_page, 各種エラー処理
 *
 * 重点:
 * - 戻り値の安定性確認
 * - エラー時のグレースフル・デグラデーション
 * - ユーザー影響の最小化
 */

use PHPUnit\Framework\TestCase;

class Ldl_ErrorHandling_Test extends TestCase
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
     * ldl_delete_log_file: パス検証NGで validate エラーを安定して返す
     */
    public function ldl_delete_log_file_returns_validate_error_consistently()
    {
        // Arrange: 明らかに不正なパス
        $invalid_paths = [
            '/etc/passwd',
            'C:\\Windows\\System32\\hosts',
            '',
            null
        ];

        foreach ($invalid_paths as $path) {
            // Act
            $result = ldl_delete_log_file($path);

            // Assert: 必ず配列で失敗を返す（エラーキーは実装依存）
            $this->assertIsArray($result, "Result should be array for path: " . var_export($path, true));
            $this->assertArrayHasKey('success', $result, "Result should have 'success' key for path: " . var_export($path, true));
            $this->assertFalse($result['success'], "Success should be false for invalid path: " . var_export($path, true));

            // error キーがある場合は validate であることを確認
            if (isset($result['error'])) {
                $this->assertEquals('validate', $result['error'], "Error should be 'validate' for path: " . var_export($path, true));
            }
        }
    }

    /**
     * @test
     * ldl_render_log_page: ファイル読み込みエラー時のグレースフル処理
     */
    public function ldl_render_log_page_handles_file_read_errors_gracefully()
    {
        // Arrange: WordPress関数をモック
        WP_Mock::userFunction('get_option')
            ->with('timezone_string')
            ->once()
            ->andReturn('Asia/Tokyo');

        WP_Mock::userFunction('current_user_can')
            ->with('manage_options')
            ->once()
            ->andReturn(true);

        WP_Mock::userFunction('wp_nonce_field')
            ->once()
            ->andReturn('<input type="hidden" name="ldl_delete_nonce" value="test123" />');

        // Act: 出力をキャプチャ（ファイルが存在しない状況）
        ob_start();
        ldl_render_log_page();
        $output = ob_get_clean();

        // Assert: エラー時でも基本UI構造は出力される
        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('<h1>Localize Debug Log</h1>', $output);
        $this->assertStringContainsString('<textarea', $output);
        $this->assertStringContainsString('readonly', $output);

        // エラー時の通知メッセージが表示される
        $this->assertStringContainsString('ログファイルが見つかりません', $output);

        // 削除フォームも正常に出力される（エラー時でもUI破綻しない）
        $this->assertStringContainsString('type="submit"', $output);
    }

    /**
     * @test
     * タイムゾーン処理: 例外発生時の安定フォールバック
     */
    public function timezone_processing_handles_exceptions_gracefully()
    {
        // ldl_convert_utc_to_local と ldl_format_local_timestamp の例外処理

        // 不正なタイムゾーン文字列
        $invalid_timezones = [
            'Invalid/Timezone',
            'Not/A/Zone',
            'Completely_Wrong_Format'
        ];

        foreach ($invalid_timezones as $tz) {
            // Act & Assert: 例外時は空文字を返すべき
            $convert_result = ldl_convert_utc_to_local('2025-08-09 12:00:00', $tz);
            $format_result = ldl_format_local_timestamp('2025-08-09 12:00:00', $tz);

            $this->assertEquals('', $convert_result, "Convert should return empty string for invalid timezone: $tz");
            $this->assertEquals('', $format_result, "Format should return empty string for invalid timezone: $tz");
        }

        // 不正なタイムスタンプ文字列
        $invalid_timestamps = [
            'not-a-date',
            '2025-13-40 25:70:70', // 不正な日時
            'completely wrong format'
        ];

        foreach ($invalid_timestamps as $ts) {
            // Act & Assert: 例外時は空文字を返すべき
            $convert_result = ldl_convert_utc_to_local($ts, 'Asia/Tokyo');
            $format_result = ldl_format_local_timestamp($ts, 'Asia/Tokyo');

            $this->assertEquals('', $convert_result, "Convert should return empty string for invalid timestamp: $ts");
            $this->assertEquals('', $format_result, "Format should return empty string for invalid timestamp: $ts");
        }
    }

    /**
     * @test
     * ログ整形処理: 不正入力に対する安定性
     */
    public function log_formatting_handles_edge_cases_robustly()
    {
        // ldl_extract_utc_timestamp の境界値テスト
        $edge_cases = [
            '',
            null,
            'No timestamp here',
            '[Wrong format] message',
            '[Almost-correct but not UTC] message',
            str_repeat('x', 10000) // 非常に長い文字列
        ];

        foreach ($edge_cases as $input) {
            $result = ldl_extract_utc_timestamp($input);
            $this->assertNull($result, "Should return null for edge case: " . var_export($input, true));
        }

        // ldl_format_log_with_local_time の空配列・空行処理
        $empty_result = ldl_format_log_with_local_time([], 'Asia/Tokyo');
        $this->assertIsArray($empty_result);
        $this->assertEmpty($empty_result);

        // 空行混在
        $mixed_lines = ['', '  ', '[12-Aug-2025 01:23:45 UTC] Valid line', ''];
        $mixed_result = ldl_format_log_with_local_time($mixed_lines, 'Asia/Tokyo');
        $this->assertIsArray($mixed_result);
        $this->assertCount(4, $mixed_result); // 空行も維持される
    }

    /**
     * @test
     * CSRF保護: 不正パラメータでの安定動作
     */
    public function csrf_protect_handles_invalid_parameters_safely()
    {
        // 不正なアクション・フィールド名（空文字・null・特殊文字）
        $edge_cases = [
            ['', '', 'field'],
            [null, null, 'field'],
            ['action<script>', 'field"quotes', 'field'],
            ['normal_action', 'normal_field', 'invalid_mode']
        ];

        foreach ($edge_cases as [$action, $field, $mode]) {
            // WordPress関数が呼ばれることを期待（モック設定）
            WP_Mock::userFunction('wp_nonce_field')
                ->once()
                ->andReturn('<input type="hidden" name="test_field" value="test_value" />');

            // Act: 例外が発生せず、何らかの結果が返る
            $result = ldl_csrf_protect($action, $field, $mode);

            // Assert: 結果が文字列として返る（例外で停止しない）
            $this->assertIsString($result, 'CSRF protect should return string even with edge case parameters');
        }
    }

    /**
     * @test
     * 統合エラーケース: 複数エラーの同時発生
     */
    public function multiple_errors_do_not_cascade_failures()
    {
        // 複数の問題が同時発生した場合のテスト

        // 1. 不正なタイムゾーン + 不正なタイムスタンプ
        $result1 = ldl_format_local_timestamp('invalid-ts', 'Invalid/TZ');
        $this->assertEquals('', $result1, 'Multiple invalid inputs should still return empty string');

        // 2. 空のログ配列 + 不正なタイムゾーン
        $result2 = ldl_format_log_with_local_time([], 'Invalid/TZ');
        $this->assertIsArray($result2);
        $this->assertEmpty($result2);

        // 3. 関数の存在確認（致命的エラー防止）
        $critical_functions = [
            'ldl_get_wordpress_timezone',
            'ldl_convert_utc_to_local',
            'ldl_format_local_timestamp',
            'ldl_extract_utc_timestamp',
            'ldl_format_log_with_local_time',
            'ldl_csrf_protect',
            'ldl_validate_log_path',
            'ldl_delete_log_file'
        ];

        foreach ($critical_functions as $func) {
            $this->assertTrue(function_exists($func), "Critical function $func must exist");
        }
    }
}
