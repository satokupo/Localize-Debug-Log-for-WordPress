<?php
/**
 * パス検証 ldl_validate_log_path のテスト
 *
 * 仕様:
 * - 正常: ldl_get_log_path() を渡すと正規化された絶対パスを返す
 * - 異常: ../ などで logs/ 外を指すパスは false
 * - シンボリックリンクで logs/ 外を指す場合は false
 * - ファイル不存在時は dirname($path) が logs/ 配下なら許可、そうでなければ false
 */

use PHPUnit\Framework\TestCase;

class Ldl_Validate_Log_Path_Test extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock::setUp();

        // メインプラグインファイル読み込み（パス検証のため最小限のみ読み込み）
        require_once dirname(__DIR__, 3) . '/localize-debug-log/localize-debug-log.php';
    }

    protected function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    /**
     * @test
     * 正常: logs ディレクトリ配下のパスを渡すと正規化された絶対パスを返す
     */
    public function validate_log_path_accepts_valid_log_path()
    {
        // Arrange: bootstrap.phpで定義済みのplugin_dir_pathを使用
        $valid_log_path = '/test/path/to/plugin/logs/debug.log';

        // Act
        $result = ldl_validate_log_path($valid_log_path);

        // Assert
        $this->assertNotFalse($result);
        $this->assertStringContainsString('logs', $result);
    }

    /**
     * @test
     * 異常: ../ などで logs/ 外を指すパスは false
     */
    public function validate_log_path_rejects_path_traversal()
    {
        // Arrange
        $malicious_path = '/test/path/to/plugin/logs/../../../etc/passwd';

        // Act
        $result = ldl_validate_log_path($malicious_path);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     * 異常: logs/ 外の絶対パスは false
     */
    public function validate_log_path_rejects_outside_logs_directory()
    {
        // Arrange
        $outside_path = '/var/log/apache2/access.log';

        // Act
        $result = ldl_validate_log_path($outside_path);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     * ファイル不存在時: dirname($path) が logs/ 配下なら許可
     */
    public function validate_log_path_allows_nonexistent_file_in_logs_dir()
    {
        // Arrange
        $nonexistent_path = '/test/path/to/plugin/logs/custom.log';

        // Act
        $result = ldl_validate_log_path($nonexistent_path);

        // Assert
        $this->assertNotFalse($result);
        $this->assertStringContainsString('logs', $result);
    }

    /**
     * @test
     * ファイル不存在時: dirname($path) が logs/ 外ならば false
     */
    public function validate_log_path_rejects_nonexistent_file_outside_logs()
    {
        // Arrange
        $nonexistent_outside = '/var/log/nonexistent.log';

        // Act
        $result = ldl_validate_log_path($nonexistent_outside);

        // Assert
        $this->assertFalse($result);
    }
}
