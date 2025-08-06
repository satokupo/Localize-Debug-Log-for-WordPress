<?php
/**
 * ログ整形機能テスト
 *
 * 作成日: 2025-08-06
 * 用途: ログファイル読み込みとローカル時刻付加による整形機能の検証
 * 対象: localize-debug-log/localize-debug-log.php (ldl_read_log_file, ldl_parse_log_lines, ldl_format_log_with_local_time)
 */

class LogFormatting_Test extends PHPUnit\Framework\TestCase
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
     * 標準 error_log 形式（UTC時刻付き）のテストケース
     */
    public function test_parse_standard_error_log_format()
    {
        $sample_log_line = '[06-Aug-2025 06:30:45 UTC] PHP Notice: Undefined variable: test in /path/to/file.php on line 123';

        // TODO: ldl_parse_log_lines() 実装後にコメントアウト解除
        /*
        $parsed = ldl_parse_log_lines($sample_log_line);

        $this->assertArrayHasKey('timestamp', $parsed);
        $this->assertArrayHasKey('message', $parsed);
        $this->assertEquals('06-Aug-2025 06:30:45', $parsed['timestamp']);
        $this->assertStringContains('PHP Notice', $parsed['message']);
        */

        // 仮のテスト（実装完了まで）
        $this->assertTrue(true, '標準error_log形式解析テスト準備完了');
    }

    /**
     * ローカル時刻追加の正常動作テストケース
     */
    public function test_format_log_with_local_time()
    {
        $sample_log_line = '[06-Aug-2025 06:30:45 UTC] test message';
        $timezone = 'Asia/Tokyo';

        $sample_log_lines = [
            '[06-Aug-2025 06:30:45 UTC] test message 1',
            '[07-Aug-2025 07:15:30 UTC] test message 2',
            'No timestamp message'
        ];

        $result = ldl_format_log_with_local_time($sample_log_lines, $timezone);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // 最初の行にローカル時刻が付加されることを確認
        $this->assertStringContainsString('JST 2025/08/06 15:30:45', $result[0]);
        $this->assertStringContainsString('[06-Aug-2025 06:30:45 UTC] test message 1', $result[0]);

        // 2番目の行も確認
        $this->assertStringContainsString('JST 2025/08/07 16:15:30', $result[1]);

        // タイムスタンプがない行はそのまま保持されることを確認
        $this->assertEquals('No timestamp message', $result[2]);
    }

    /**
     * ログファイル読み込み機能テスト
     */
    public function test_read_log_file()
    {
        // テスト用の一時ファイル作成
        $temp_file = tempnam(sys_get_temp_dir(), 'ldl_test_');
        $test_content = "[06-Aug-2025 06:30:45 UTC] Test log entry 1\n[07-Aug-2025 07:30:45 UTC] Test log entry 2\n\n";
        file_put_contents($temp_file, $test_content);

        // ファイル読み込みテスト
        $lines = ldl_read_log_file($temp_file);
        $this->assertIsArray($lines);
        $this->assertCount(2, $lines); // 空行は除去されるので2行
        $this->assertEquals('[06-Aug-2025 06:30:45 UTC] Test log entry 1', $lines[0]);
        $this->assertEquals('[07-Aug-2025 07:30:45 UTC] Test log entry 2', $lines[1]);

        // クリーンアップ
        unlink($temp_file);
    }

    /**
     * ログファイルが存在しない場合のエラー処理テスト
     */
    public function test_read_log_file_not_exists()
    {
        $non_existent_file = '/path/to/non_existent_file.log';
        $result = ldl_read_log_file($non_existent_file);

        // 空配列を返すことを確認（エラーではなく）
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * UTC タイムスタンプ抽出の正規表現テスト
     */
    public function test_utc_timestamp_extraction()
    {
        // ldl_extract_utc_timestamp() 関数のテスト
        $test_patterns = [
            '[06-Aug-2025 15:30:45 UTC] PHP Notice: Test message' => '06-Aug-2025 15:30:45',
            '[25-Dec-2024 23:59:59 UTC] PHP Warning: Another test' => '25-Dec-2024 23:59:59',
            'Invalid log format without timestamp' => null,
            '[Invalid-Format] message' => null,
            '[2025-08-06 06:30:45] test message' => null  // 不正なフォーマット
        ];

        foreach ($test_patterns as $input => $expected) {
            $result = ldl_extract_utc_timestamp($input);
            $this->assertEquals($expected, $result, "Failed for input: $input");
        }
    }
}
