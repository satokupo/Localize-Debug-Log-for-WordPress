# PHPUnitテスト構造管理ガイドライン

## 初回セットアップ手順

### 新規プロジェクトでの環境構築
1. **Composerインストール**（未導入の場合）
2. **dev/composer.json編集**（`dev/`ディレクトリ内で作業）
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
3. **PHPUnitインストール**: `cd dev; composer install` (PowerShell) または `cd dev && composer install` (CMD/Bash)
4. **ディレクトリ作成**: `dev/tests/active/`, `dev/tests/archive/`, `dev/tests/pending/`, `dev/tests/logs/`
5. **設定ファイル作成**: `dev/phpunit.xml`, `dev/tests/bootstrap.php`（AI作成推奨）
6. **ルートpackage.json作成**（プロジェクトルートで作業）
   ```json
   {
     "name": "project-workspace",
     "private": true,
     "scripts": {
       "test": "cmd /c \"cd dev && composer test\"",
       "test-active": "cmd /c \"cd dev && composer test-active\"",
       "test-pending": "cmd /c \"cd dev && composer test-pending\"",
       "test-all": "cmd /c \"cd dev && composer test-all\""
     }
   }
   ```

## プロジェクト全体構造

### 移設後のディレクトリ構成
```
プロジェクトルート/
├── package.json          # npm scripts（推奨実行方法）
├── _doc/                  # プロジェクト文書
├── localize-debug-log/    # プラグイン本体（本番用最小構成）
│   ├── localize-debug-log.php
│   ├── logs/
│   └── readme.md
└── dev/                   # 開発・テスト環境
    ├── composer.json      # PHP依存関係管理
    ├── composer.lock
    ├── phpunit.xml        # テスト設定
    ├── vendor/
    └── tests/             # このREADMEで管理するテストファイル群
        ├── bootstrap.php
        ├── logs/
        ├── active/
        ├── archive/
        └── pending/
```

### 各ディレクトリの役割

| ディレクトリ | 役割 | 実行コマンド |
|-------------|------|------------|
| **プロジェクトルート** | npm scriptsによるショートカット実行 | `npm run test` |
| **dev/** | PHP開発・テスト環境 | `composer test` |
| **localize-debug-log/** | WordPress プラグイン本体 | - |

### 設定ファイルの関係性

- **package.json（ルート）**: `npm run test` → `dev/`でcomposerを実行
- **dev/composer.json**: PHPUnitの依存関係とテストスクリプト定義
- **dev/phpunit.xml**: テスト実行設定（bootstrap、カバレッジ等）

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

## スクリプト設定

### dev/composer.json

PHPUnitの依存関係とテストスクリプト定義：

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

### package.json（プロジェクトルート）

npm scriptsによるショートカット実行（推奨）：

```json
{
  "name": "project-workspace",
  "private": true,
  "scripts": {
    "test": "cmd /c \"cd dev && composer test\"",
    "test-active": "cmd /c \"cd dev && composer test-active\"",
    "test-pending": "cmd /c \"cd dev && composer test-pending\"",
    "test-all": "cmd /c \"cd dev && composer test-all\""
  }
}
```

## 実行コマンド一覧

### 推奨実行方法（プロジェクトルートから）

| コマンド | 対象 | 用途 |
|---------|------|------|
| `npm run test` | active/ | 日常開発（デフォルト） |
| `npm run test-active` | active/ | 日常開発（明示的） |
| `npm run test-all` | tests/ | 全テスト実行（リリース前） |
| `npm run test-pending` | pending/ | 保留テストのみ |

**PowerShell環境での注意**: Windows PowerShellでは`cmd /c`を使用して`&&`記号に対応

### 直接実行方法（dev/ディレクトリから）

| コマンド | 対象 | 用途 |
|---------|------|------|
| `composer test` | active/ | 日常開発（デフォルト） |
| `composer test-active` | active/ | 日常開発（明示的） |
| `composer test-all` | tests/ | 全テスト実行（リリース前） |
| `composer test-archive` | archive/ | アーカイブテストのみ |
| `composer test-pending` | pending/ | 保留テストのみ |

## 運用ルール

### 💻 実行環境の選択

#### 推奨実行方法（npm run）の利点
- **ワンコマンド実行**: プロジェクトルートから直接実行可能
- **環境統一**: 開発者間で実行方法が統一される
- **PowerShell対応**: Windows環境での`&&`記号問題を自動解決
- **統合しやすい**: CI/CDやエディタとの連携が容易

#### 直接実行方法（composer）を使う場面
- **デバッグ時**: PHPUnitの詳細オプションを直接指定したい場合
- **IDE連携**: PhpStormなどのIDEから直接実行する場合
- **学習目的**: PHPUnitの動作を直接理解したい場合
- **トラブルシューティング**: npm scriptsで問題が起きた場合

#### 環境による違い

| 環境 | 推奨方法 | 注意点 |
|------|---------|--------|
| **Windows PowerShell** | `npm run test` | `&&`記号は`cmd /c`で解決 |
| **Windows CMD** | `npm run test` または `composer test` | どちらでも問題なし |
| **Linux/Mac** | `npm run test` | どちらでも問題なし |

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
   npm run test  # active/のみ実行（高速・推奨）
   # または
   cd dev; composer test  # 直接実行（PowerShell）
   cd dev && composer test  # 直接実行（CMD/Bash）
   ```

2. **新機能テスト作成**
   - `dev/tests/active/` 配下に作成
   - 機能完成後もしばらくはactive/で保持

3. **リリース前確認**
   ```bash
   npm run test-all  # 全テスト実行（安全確認・推奨）
   # または
   cd dev; composer test-all  # 直接実行（PowerShell）
   cd dev && composer test-all  # 直接実行（CMD/Bash）
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

### npm run test でエラーが発生する

#### PowerShell環境での問題
- **症状**: `&&` 記号が認識されない、`指定されたパスが見つかりません` エラー
- **原因**: Windows PowerShellでは `&&` 記号がサポートされていない
- **解決策**: package.jsonで `cmd /c "cd dev && composer test"` 形式を使用

#### package.jsonの設定確認
```bash
# package.jsonの内容確認
cat package.json
```

```json
# 正しい設定例
{
  "scripts": {
    "test": "cmd /c \"cd dev && composer test\""
  }
}
```

#### その他のnpm run関連問題
- **Node.jsがインストールされているか確認**: `node --version`
- **npmが利用可能か確認**: `npm --version`
- **dev/ディレクトリが存在するか確認**: `ls dev/` または `dir dev\`

### composer test で一部のテストが実行されない
- `dev/tests/active/` 配下にファイルが正しく配置されているか確認
- ファイル名が `*Test.php` になっているか確認

### 全テスト実行に時間がかかりすぎる
- archive/のテスト数が多すぎる場合は整理を検討
- 重いテストをpending/に一時移動

### pending/のテストが溜まりすぎた
- 定期的に解決可能なものを active/ へ移動
- 長期間解決見込みのないものは削除を検討
