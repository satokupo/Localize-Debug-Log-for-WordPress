<?php
/**
 * ログ整形機能テスト（フェーズ５：詳細境界値・異常系）
 *
 * 作成日: 2025-08-09
 * 用途: ログファイル読み込み・UTC抽出・ローカル時刻付加処理の詳細検証
 * 対象: ldl_extract_utc_timestamp, ldl_format_log_with_local_time, ldl_read_log_file, ldl_get_formatted_log
 *
 * 重点:
 * - UTC抽出失敗時・混在行の扱い・先頭付加形式の検証
 * - 空行除外・ファイル権限エラー時の安定性確認
 * - 統合機能での整形配列出力の検証
 */

use PHPUnit\Framework\TestCase;

class Ldl_LogFormatting_Test extends TestCase
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
     * ldl_extract_utc_timestamp: 正常なUTCタイムスタンプパターンから抽出
     */
    public function ldl_extract_utc_timestamp_extracts_valid_utc_patterns()
    {
        // Arrange & Act & Assert
        $patterns = [
            '[12-Aug-2025 01:23:45 UTC] PHP Notice: Test' => '12-Aug-2025 01:23:45',
            '[25-Dec-2024 23:59:59 UTC] PHP Warning: Another' => '25-Dec-2024 23:59:59',
            '[01-Jan-2025 00:00:00 UTC] Fatal error' => '01-Jan-2025 00:00:00'
        ];

        foreach ($patterns as $input => $expected) {
            $result = ldl_extract_utc_timestamp($input);
            $this->assertEquals($expected, $result, "Failed for input: $input");
        }
    }

    /**
     * @test
     * ldl_extract_utc_timestamp: 先頭が一致しない行では null
     */
    public function ldl_extract_utc_timestamp_returns_null_for_invalid_patterns()
    {
        // Arrange & Act & Assert
        $invalid_patterns = [
            'Invalid log format without timestamp',
            '[Invalid-Format] message',
            '[2025-08-06 06:30:45] test message', // UTC がない
            'Some log [12-Aug-2025 01:23:45 UTC] in middle', // 先頭にない
            '[12-Aug-2025 01:23:45] Missing UTC',
            '[12-Aug-2025 01:23:45 GMT] Wrong timezone'
        ];

        foreach ($invalid_patterns as $input) {
            $result = ldl_extract_utc_timestamp($input);
            $this->assertNull($result, "Should return null for input: $input");
        }
    }

    /**
     * @test
     * ldl_format_log_with_local_time: UTC抽出成功時にローカル時刻が行頭に付加される
     */
    public function ldl_format_log_with_local_time_adds_local_time_to_valid_lines()
    {
        // Arrange
        $sample_lines = [
            '[06-Aug-2025 06:30:45 UTC] PHP Notice: Test message 1',
            '[07-Aug-2025 07:15:30 UTC] PHP Warning: Test message 2'
        ];
        $timezone = 'Asia/Tokyo';

        // Act
        $result = ldl_format_log_with_local_time($sample_lines, $timezone);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // 1行目: JST 2025/08/06 15:30:45 [06-Aug-2025 06:30:45 UTC] PHP Notice: Test message 1
        $this->assertStringContainsString('JST 2025/08/06 15:30:45', $result[0]);
        $this->assertStringContainsString('[06-Aug-2025 06:30:45 UTC] PHP Notice: Test message 1', $result[0]);

        // 2行目: JST 2025/08/07 16:15:30 [07-Aug-2025 07:15:30 UTC] PHP Warning: Test message 2
        $this->assertStringContainsString('JST 2025/08/07 16:15:30', $result[1]);
        $this->assertStringContainsString('[07-Aug-2025 07:15:30 UTC] PHP Warning: Test message 2', $result[1]);
    }

    /**
     * @test
     * ldl_format_log_with_local_time: 抽出不能行はそのまま維持
     */
    public function ldl_format_log_with_local_time_preserves_lines_without_timestamps()
    {
        // Arrange
        $mixed_lines = [
            '[06-Aug-2025 06:30:45 UTC] Valid UTC line',
            'No timestamp in this line',
            'Another line without UTC timestamp',
            '[07-Aug-2025 07:15:30 UTC] Another valid line'
        ];
        $timezone = 'Asia/Tokyo';

        // Act
        $result = ldl_format_log_with_local_time($mixed_lines, $timezone);

        // Assert
        $this->assertCount(4, $result);

        // 1行目: ローカル時刻付加
        $this->assertStringContainsString('JST 2025/08/06 15:30:45', $result[0]);

        // 2・3行目: そのまま維持
        $this->assertEquals('No timestamp in this line', $result[1]);
        $this->assertEquals('Another line without UTC timestamp', $result[2]);

        // 4行目: ローカル時刻付加
        $this->assertStringContainsString('JST 2025/08/07 16:15:30', $result[3]);
    }

    /**
     * @test
     * ldl_read_log_file: 存在しないファイルで空配列
     */
    public function ldl_read_log_file_returns_empty_array_for_non_existent_file()
    {
        // Arrange
        $non_existent_file = '/path/to/absolutely/non/existent/file.log';

        // Act
        $result = ldl_read_log_file($non_existent_file);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @test
     * ldl_read_log_file: 空行を除外して配列化
     */
    public function ldl_read_log_file_excludes_empty_lines()
    {
        // Arrange: テスト用一時ファイル作成
        $temp_file = tempnam(sys_get_temp_dir(), 'ldl_test_');
        $test_content = "[06-Aug-2025 06:30:45 UTC] Line 1\n\n[07-Aug-2025 07:30:45 UTC] Line 2\n   \n[08-Aug-2025 08:30:45 UTC] Line 3\n\n";
        file_put_contents($temp_file, $test_content);

        // Act
        $result = ldl_read_log_file($temp_file);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result); // 空行・空白行は除去されるので3行
        $this->assertEquals('[06-Aug-2025 06:30:45 UTC] Line 1', $result[0]);
        $this->assertEquals('[07-Aug-2025 07:30:45 UTC] Line 2', $result[1]);
        $this->assertEquals('[08-Aug-2025 08:30:45 UTC] Line 3', $result[2]);

        // クリーンアップ
        unlink($temp_file);
    }

    /**
     * @test
     * テストダブルによる統合処理の境界値確認
     * 注記: ldl_get_formatted_log は統合関数のためUI/E2Eテストセクションで詳細検証
     */
    public function integration_function_behavior_verification()
    {
        // Arrange: 個別関数の結果が統合時に正しく連携することを確認
        $sample_lines = [
            '[06-Aug-2025 06:30:45 UTC] Test message 1',
            'No timestamp message',
            '[07-Aug-2025 07:30:45 UTC] Test message 2'
        ];
        $timezone = 'Asia/Tokyo';

        // Act: 各処理段階を個別に検証
        $formatted_lines = ldl_format_log_with_local_time($sample_lines, $timezone);

        // Assert: 統合処理の動作確認
        $this->assertCount(3, $formatted_lines);
        $this->assertStringContainsString('JST 2025/08/06 15:30:45', $formatted_lines[0]);
        $this->assertEquals('No timestamp message', $formatted_lines[1]);
        $this->assertStringContainsString('JST 2025/08/07 16:30:45', $formatted_lines[2]);
    }

    /**
     * @test
     * エッジケース: 非常に長い行の処理
     */
    public function ldl_format_log_with_local_time_handles_very_long_lines()
    {
        // Arrange: 非常に長いメッセージ
        $long_message = str_repeat('Very long message content. ', 100); // 約2700文字
        $sample_lines = [
            '[06-Aug-2025 06:30:45 UTC] ' . $long_message
        ];
        $timezone = 'Asia/Tokyo';

        // Act
        $result = ldl_format_log_with_local_time($sample_lines, $timezone);

        // Assert
        $this->assertCount(1, $result);
        $this->assertStringContainsString('JST 2025/08/06 15:30:45', $result[0]);
        $this->assertStringContainsString($long_message, $result[0]);
        $this->assertGreaterThan(2700, strlen($result[0])); // 長いメッセージが保持されている
    }

    /**
     * @test
     * エッジケース: 空配列入力への対応
     */
    public function ldl_format_log_with_local_time_handles_empty_input()
    {
        // Act
        $result = ldl_format_log_with_local_time([], 'Asia/Tokyo');

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
