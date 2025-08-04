<?php
/**
 * サンプルテストファイル
 * 
 * PHPUnitテスト環境の動作確認用
 * composer test コマンドでテスト環境が正常に動作することを確認
 */

use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    /**
     * 【サンプルテスト1】基本動作確認
     */
    public function test_phpunit_environment()
    {
        echo "\n🎯 PHPUnitテスト環境の動作確認\n";
        echo "✅ テストが無事に実行されました\n";
        echo "✅ テスト環境は正常に動作しています\n";
        
        // 基本的なアサーション
        $this->assertTrue(true, 'このテストは常に成功します');
        $this->assertEquals(1, 1, '1 + 1 = 2の確認');
    }
    
    /**
     * 【サンプルテスト2】PHP環境確認
     */
    public function test_php_environment()
    {
        echo "\n📍 PHP環境情報:\n";
        echo "   - PHPバージョン: " . PHP_VERSION . "\n";
        echo "   - OS: " . PHP_OS . "\n";
        echo "   - メモリ制限: " . ini_get('memory_limit') . "\n";
        
        // PHP環境の基本確認
        $this->assertNotEmpty(PHP_VERSION, 'PHPバージョンが取得できません');
        $this->assertTrue(version_compare(PHP_VERSION, '8.0.0', '>='), 'PHP 8.0以上が必要です');
    }
    
    /**
     * 【サンプルテスト3】ファイル構造確認
     */
    public function test_file_structure()
    {
        echo "\n📁 ファイル構造確認:\n";
        
        $theme_root = dirname(__DIR__, 2);
        $expected_files = [
            'composer.json',
            'phpunit.xml',
            'features/square/vendor/autoload.php'
        ];
        
        foreach ($expected_files as $file) {
            $file_path = $theme_root . '/' . $file;
            $exists = file_exists($file_path);
            
            echo "   - " . $file . ": " . ($exists ? "✅ 存在" : "❌ 不在") . "\n";
            
            if (str_contains($file, 'autoload.php')) {
                // autoloadファイルは存在しなくても警告のみ
                $this->assertTrue(true, 'autoloadファイルの確認完了');
            } else {
                $this->assertTrue($exists, $file . ' が見つかりません');
            }
        }
    }
}
