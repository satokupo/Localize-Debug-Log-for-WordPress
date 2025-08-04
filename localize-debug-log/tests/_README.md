# PHPUnitテスト構造管理ガイドライン

## 初回セットアップ手順

### 新規プロジェクトでの環境構築
1. **Composerインストール**（未導入の場合）
2. **composer.json編集**
   ```json
   {
       "require-dev": {
           "phpunit/phpunit": "^9.x"
       },
       "scripts": {
           "test": "phpunit tests/active/",
           "test-active": "phpunit tests/active/",
           "test-all": "phpunit tests/",
           "test-archive": "phpunit tests/archive/",
           "test-pending": "phpunit tests/pending/"
       }
   }
   ```
3. **PHPUnitインストール**: `composer install`
4. **ディレクトリ作成**: `tests/active/`, `tests/archive/`, `tests/pending/`, `tests/logs/`
5. **設定ファイル作成**: `phpunit.xml`, `tests/bootstrap.php`（AI作成推奨）

## ファイル構成

| ファイル/フォルダ | 役割 | 備考 |
|------------------|------|------|
| `phpunit.xml` | PHPUnit設定ファイル | AI自動作成、通常は編集不要 |
| `tests/bootstrap.php` | テスト初期化ファイル | 環境設定、ライブラリ読み込み |
| `tests/logs/` | テスト実行ログ | 自動生成される |

## ディレクトリ構造

```
tests/
├── bootstrap.php           # テスト初期化ファイル
├── logs/                   # テスト実行ログ
├── active/                 # 現在実行中のテスト
│   ├── SampleTest.php
│   └── core/              # 日常的に実行するテスト
├── archive/                # 保管用テスト（完了済み・参考用）
└── pending/                # 保留中・一時停止テスト
```

## 各ディレクトリの用途

### 📁 active/ - アクティブテスト
**用途:** 現在実行が必要なテスト
- 現在開発中の機能テスト

**実行頻度:** 新機能開発時

### 📁 active/core/ - アクティブコアテスト
**用途:** 日常的な開発で実行するテスト
- 重要な回帰テスト
- 頻繁に確認が必要なAPI動作テスト

**実行頻度:** 毎日〜毎回のコミット時

### 📁 archive/ - アーカイブテスト
**用途:** 完了済みだが保管しておきたいテスト
- 過去の機能検証テスト
- 学習・参考用のサンプルテスト
- 特定の問題調査用テスト（解決済み）
- 一時的な検証用テスト（目的達成済み）

**実行頻度:** リリース前の総合テスト時のみ

### 📁 pending/ - 保留テスト
**用途:** 一時的に実行を停止しているテスト
- 修正中でエラーが出るテスト
- 外部依存の問題で実行できないテスト
- 機能仕様変更により一時停止中のテスト
- 実験的なテスト（まだ完成していない）

**実行頻度:** 問題解決後にactive/へ移動

## Composer.jsonスクリプト設定

```json
{
    "scripts": {
        "test": "phpunit tests/active/",
        "test-active": "phpunit tests/active/",
        "test-all": "phpunit tests/",
        "test-archive": "phpunit tests/archive/",
        "test-pending": "phpunit tests/pending/"
    }
}
```

## 実行コマンド一覧

| コマンド | 対象 | 用途 |
|---------|------|------|
| `composer test` | active/ | 日常開発（デフォルト） |
| `composer test-active` | active/ | 日常開発（明示的） |
| `composer test-all` | tests/ | 全テスト実行（リリース前） |
| `composer test-archive` | archive/ | アーカイブテストのみ |
| `composer test-pending` | pending/ | 保留テストのみ |

## 運用ルール

### ✅ テストファイルの移動タイミング

#### active/ → archive/ への移動
- 機能開発完了後、テストが安定した時
- 特定の問題調査が完了した時
- サンプル・学習用として保管したい時
- 日常的に実行する必要がなくなった時

#### active/ → pending/ への移動
- テストが一時的にエラーになった時
- 外部APIの問題で実行できない時
- 機能仕様変更でテスト修正が必要な時
- 実験中で未完成のテスト

#### pending/ → active/ への移動
- 問題が解決してテストが正常動作する時
- 機能仕様が確定してテストを再開する時

#### archive/ → active/ への移動
- 過去の問題が再発して再確認が必要な時
- 類似機能開発で参考テストを再利用する時

### 🔄 日常的な開発フロー

1. **通常の開発中**
   ```bash
   composer test  # active/のみ実行（高速）
   ```

2. **新機能テスト作成**
   - `tests/active/` 配下に作成
   - 機能完成後もしばらくはactive/で保持

3. **リリース前確認**
   ```bash
   composer test-all  # 全テスト実行（安全確認）
   ```

4. **定期メンテナンス**
   - 月1回程度、ファイルの整理
   - 不要になったテストをarchive/へ移動
   - pending/の課題解決状況を確認

### 📋 ファイル管理のベストプラクティス

1. **ファイル名に日付や用途を含める（archive/の場合）**
   ```
   archive/
   ├── 20250602_inventory_api_investigation.php
   ├── sample_tests_for_learning.php
   └── legacy/
       └── old_api_compatibility_test.php
   ```

2. **コメントで移動理由を記録**
   ```php
   /**
    * Inventory API基本動作テスト
    *
    * 2025-06-02: 開発完了につきarchiveへ移動
    * 目的: batchGetCountsメソッドの動作確認（完了済み）
    */
   ```

3. **定期的な見直し**
   - archive/が肥大化した場合はサブディレクトリで整理
   - pending/に長期間残っているファイルは削除を検討

## ディレクトリ移動時の注意点

### ⚠️ パス変更の影響
- relative pathを使用している場合は調整が必要
- 他のテストファイルから参照されていないか確認

### ✅ 移動前のチェックリスト
- [ ] テストが正常に動作することを確認
- [ ] 依存関係のあるテストファイルがないか確認
- [ ] 移動理由をコメントに記録
- [ ] 必要に応じてREADMEやドキュメントを更新

## トラブルシューティング

### composer test で一部のテストが実行されない
- `tests/active/` 配下にファイルが正しく配置されているか確認
- ファイル名が `*Test.php` になっているか確認

### 全テスト実行に時間がかかりすぎる
- archive/のテスト数が多すぎる場合は整理を検討
- 重いテストをpending/に一時移動

### pending/のテストが溜まりすぎた
- 定期的に解決可能なものを active/ へ移動
- 長期間解決見込みのないものは削除を検討
