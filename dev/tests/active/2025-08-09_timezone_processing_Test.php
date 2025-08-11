<?php
/**
 * タイムゾーン処理機能テスト（フェーズ５：詳細境界値・異常系）
 *
 * 作成日: 2025-08-09
 * 用途: WordPress タイムゾーン設定による詳細境界値と異常系の検証
 * 対象: ldl_get_wordpress_timezone, ldl_convert_utc_to_local, ldl_format_local_timestamp
 *
 * 重点:
 * - gmt_offset フォールバック（正負オフセット双方）
 * - 不正フォーマット入力での空文字返却
 * - エッジケース処理の安定性確認
 */

use PHPUnit\Framework\TestCase;

class Ldl_TimezoneProcessing_Test extends TestCase
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
     * ldl_get_wordpress_timezone: timezone_string 設定時に同値を返す
     */
    public function ldl_get_wordpress_timezone_returns_timezone_string_when_set()
    {
        // Arrange
        WP_Mock::userFunction('get_option')
            ->with('timezone_string')
            ->once()
            ->andReturn('Asia/Tokyo');

        // Act
        $result = ldl_get_wordpress_timezone();

        // Assert
        $this->assertEquals('Asia/Tokyo', $result);
    }

    /**
     * @test
     * ldl_get_wordpress_timezone: timezone_string 未設定時に gmt_offset から正のオフセット
     */
    public function ldl_get_wordpress_timezone_returns_positive_gmt_offset()
    {
        // Arrange: timezone_string空、gmt_offset=9.0（JST）
        WP_Mock::userFunction('get_option')
            ->with('timezone_string')
            ->once()
            ->andReturn('');

        WP_Mock::userFunction('get_option')
            ->with('gmt_offset')
            ->once()
            ->andReturn(9.0);

        // Act
        $result = ldl_get_wordpress_timezone();

        // Assert
        $this->assertEquals('Etc/GMT-9', $result);
    }

    /**
     * @test
     * ldl_get_wordpress_timezone: timezone_string 未設定時に gmt_offset から負のオフセット
     */
    public function ldl_get_wordpress_timezone_returns_negative_gmt_offset()
    {
        // Arrange: timezone_string空、gmt_offset=-5.0（EST）
        WP_Mock::userFunction('get_option')
            ->with('timezone_string')
            ->once()
            ->andReturn('');

        WP_Mock::userFunction('get_option')
            ->with('gmt_offset')
            ->once()
            ->andReturn(-5.0);

        // Act
        $result = ldl_get_wordpress_timezone();

        // Assert
        $this->assertEquals('Etc/GMT+5', $result);
    }

    /**
     * @test
     * ldl_get_wordpress_timezone: timezone_string 未設定時に gmt_offset 小数点オフセット
     */
    public function ldl_get_wordpress_timezone_handles_decimal_gmt_offset()
    {
        // Arrange: timezone_string空、gmt_offset=5.5（インド標準時）
        WP_Mock::userFunction('get_option')
            ->with('timezone_string')
            ->once()
            ->andReturn('');

        WP_Mock::userFunction('get_option')
            ->with('gmt_offset')
            ->once()
            ->andReturn(5.5);

        // Act
        $result = ldl_get_wordpress_timezone();

        // Assert: 小数点は整数に丸められるべき
        $this->assertEquals('Etc/GMT-5', $result);
    }

    /**
     * @test
     * ldl_convert_utc_to_local: 正常ケース
     */
    public function ldl_convert_utc_to_local_converts_normally()
    {
        // Act
        $result = ldl_convert_utc_to_local('2025-08-06 12:34:56', 'Asia/Tokyo');

        // Assert: JST = UTC+9 なので 21:34:56 になるはず
        $this->assertStringContainsString('2025-08-06 21:34:56', $result);
    }

    /**
     * @test
     * ldl_convert_utc_to_local: 不正フォーマット入力で空文字を返す
     */
    public function ldl_convert_utc_to_local_returns_empty_on_invalid_format()
    {
        // Act: 不正なタイムスタンプフォーマット
        $result = ldl_convert_utc_to_local('invalid-timestamp', 'Asia/Tokyo');

        // Assert
        $this->assertEquals('', $result);
    }

    /**
     * @test
     * ldl_convert_utc_to_local: 空文字入力は現在時刻として解釈される
     */
    public function ldl_convert_utc_to_local_handles_empty_input()
    {
        // Act
        $result = ldl_convert_utc_to_local('', 'Asia/Tokyo');

        // Assert: 空文字は現在時刻として解釈されるため、日付文字列が返る
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
        $this->assertNotEmpty($result);
    }

    /**
     * @test
     * ldl_convert_utc_to_local: 不正タイムゾーン入力での例外処理
     */
    public function ldl_convert_utc_to_local_handles_invalid_timezone()
    {
        // Act: 存在しないタイムゾーン
        $result = ldl_convert_utc_to_local('2025-08-06 12:34:56', 'Invalid/Timezone');

        // Assert: エラー時は空文字を返すべき
        $this->assertEquals('', $result);
    }

    /**
     * @test
     * ldl_format_local_timestamp: 正常ケース
     */
    public function ldl_format_local_timestamp_formats_normally()
    {
        // Act
        $result = ldl_format_local_timestamp('2025-08-06 12:34:56', 'Asia/Tokyo');

        // Assert: "T YYYY/MM/DD HH:MM:SS" 形式
        $this->assertEquals('JST 2025/08/06 21:34:56', $result);
    }

    /**
     * @test
     * ldl_format_local_timestamp: 不正フォーマット入力で空文字
     */
    public function ldl_format_local_timestamp_returns_empty_on_invalid_format()
    {
        // Act
        $result = ldl_format_local_timestamp('invalid-format', 'Asia/Tokyo');

        // Assert
        $this->assertEquals('', $result);
    }

    /**
     * @test
     * ldl_format_local_timestamp: 空文字入力は現在時刻として解釈される
     */
    public function ldl_format_local_timestamp_handles_empty_input()
    {
        // Act
        $result = ldl_format_local_timestamp('', 'Asia/Tokyo');

        // Assert: 空文字は現在時刻として解釈されるため、フォーマット済み文字列が返る
        $this->assertMatchesRegularExpression('/^JST \d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}$/', $result);
        $this->assertNotEmpty($result);
    }

    /**
     * @test
     * ldl_format_local_timestamp: 不正タイムゾーンでの例外処理
     */
    public function ldl_format_local_timestamp_handles_invalid_timezone()
    {
        // Act
        $result = ldl_format_local_timestamp('2025-08-06 12:34:56', 'Invalid/Timezone');

        // Assert
        $this->assertEquals('', $result);
    }
}
