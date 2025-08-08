<?php
/**
 * タイムゾーン処理機能テスト
 *
 * 作成日: 2025-08-06
 * 用途: WordPressタイムゾーン設定に基づくローカル時刻変換機能の検証
 * 対象: localize-debug-log/localize-debug-log.php (ldl_get_wordpress_timezone, ldl_convert_utc_to_local, ldl_format_local_timestamp)
 */

class TimezoneProcessing_Test extends PHPUnit\Framework\TestCase
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
     * timezone_string設定時のテストケース（Asia/Tokyo）
     */
    public function test_get_wordpress_timezone_with_timezone_string()
    {
        // Asia/Tokyo が設定されている場合のモック
        WP_Mock::userFunction('get_option')
            ->with('timezone_string')
            ->once()
            ->andReturn('Asia/Tokyo');

        // 実装予定の関数をテスト（Red段階）
        $timezone = ldl_get_wordpress_timezone();
        $this->assertEquals('Asia/Tokyo', $timezone);
    }

    /**
     * gmt_offset設定時のテストケース（+9）
     */
    public function test_get_wordpress_timezone_with_gmt_offset()
    {
        // timezone_string が空で、gmt_offset が 9.0 の場合のモック
        WP_Mock::userFunction('get_option')
            ->with('timezone_string')
            ->once()
            ->andReturn('');

        WP_Mock::userFunction('get_option')
            ->with('gmt_offset')
            ->once()
            ->andReturn(9.0);

        $timezone = ldl_get_wordpress_timezone();
        $this->assertEquals('Etc/GMT-9', $timezone);
    }

    /**
     * 未設定時（UTC）のフォールバックテストケース
     */
    public function test_get_wordpress_timezone_fallback_to_utc()
    {
        // timezone_string も gmt_offset も未設定の場合のモック
        WP_Mock::userFunction('get_option')
            ->with('timezone_string')
            ->once()
            ->andReturn('');

        WP_Mock::userFunction('get_option')
            ->with('gmt_offset')
            ->once()
            ->andReturn(0);

        $timezone = ldl_get_wordpress_timezone();
        $this->assertEquals('UTC', $timezone);
    }

    /**
     * UTCタイムスタンプ→ローカル時刻変換のテスト
     */
    public function test_convert_utc_to_local()
    {
                $utc_timestamp = '2025-08-06 06:30:45';
        $timezone = 'Asia/Tokyo';

        $local_time = ldl_convert_utc_to_local($utc_timestamp, $timezone);
        $this->assertStringContainsString('2025-08-06 15:30:45', $local_time);
    }

    /**
     * ローカル時刻フォーマット出力テスト（JST 2025/08/06 15:30:45 形式）
     */
    public function test_format_local_timestamp()
    {
                $utc_timestamp = '2025-08-06 06:30:45';
        $timezone = 'Asia/Tokyo';

        $formatted = ldl_format_local_timestamp($utc_timestamp, $timezone);
        $this->assertEquals('JST 2025/08/06 15:30:45', $formatted);
    }
}
