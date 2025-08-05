# AI向けテストワークフローガイド

## 🎯 このガイドの目的
PHPUnitテスト環境での作業時に、AIが効率的かつ正確にテスト作成・実行を行うためのワークフロー指南書

## 📋 プロジェクト構造の理解

### 基本構成
```
プロジェクトルート/
├── package.json          # npm scripts（推奨実行方法）
├── dev/                  # 開発・テスト環境
│   ├── composer.json     # PHP依存関係管理
│   ├── phpunit.xml       # テスト設定
│   ├── vendor/
│   └── tests/            # テストファイル群
│       ├── active/       # 現在実行中のテスト
│       ├── archive/      # 保管用テスト
│       ├── pending/      # 保留中テスト
│       └── bootstrap.php
└── localize-debug-log/   # プラグイン本体（テスト対象）
```

### 🔑 重要な理解ポイント
- **プラグイン本体**: `localize-debug-log/` 配下
- **テスト環境**: `dev/` 配下に完全分離
- **実行場所**: プロジェクトルートから `npm run test`

## 🚀 テスト作成ワークフロー

### Step 1: テストファイル作成
```bash
# 必須命名規則: yyyy-mm-dd_機能名Test.php
# 例: 2025-01-05_password_encryption_Test.php

# 作成場所: dev/tests/active/ 配下
dev/tests/active/2025-01-05_user_authentication_Test.php
```

### Step 2: テストファイルテンプレート
```php
<?php
/**
 * 機能名テスト
 *
 * 作成日: YYYY-MM-DD
 * 用途: [テストの目的を記載]
 */

class 機能名Test extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        // テスト前の準備処理
    }

    public function test機能名_正常系()
    {
        // テストケース
        $this->assertTrue(true);
    }

    public function test機能名_異常系()
    {
        // エラーケースのテスト
        $this->assertFalse(false);
    }
}
```

### Step 3: テスト実行コマンド
```bash
# 【重要】必ずプロジェクトルートから実行
npm run test         # アクティブテストのみ（推奨・日常用）
npm run test-all     # 全テスト実行（リリース前）
npm run test-pending # 保留テストのみ
```

## ⚠️ AI作業時の重要な注意点

### 🔴 絶対にやってはいけないこと
1. **dev/ディレクトリ内でテスト実行** → 必ずルートから `npm run test`
2. **ファイル名に日付プレフィックスなし** → `yyyy-mm-dd_` 必須
3. **archive/に新規テスト作成** → 新規は必ず `active/` に
4. **PowerShellで `cd dev && composer test`** → `&&` 記号使用不可

### 🟡 よくある間違いと対策

#### 間違い例1: 直接composer実行
```bash
# ❌ 間違い
cd dev && composer test

# ✅ 正解
npm run test
```

#### 間違い例2: ファイル名規則違反
```bash
# ❌ 間違い
dev/tests/active/UserTest.php
dev/tests/active/TestUser.php

# ✅ 正解
dev/tests/active/2025-01-05_user_authentication_Test.php
```

#### 間違い例3: 配置場所の間違い
```bash
# ❌ 間違い
localize-debug-log/tests/UserTest.php  # プラグイン本体側
dev/tests/archive/2025-01-05_new_Test.php  # アーカイブに新規

# ✅ 正解
dev/tests/active/2025-01-05_user_authentication_Test.php
```

## 📂 ディレクトリ使い分けルール

### 🟢 active/ - 現在実行中
- **用途**: 開発中・確認中のテスト
- **AI作業**: 新規テストは必ずここに作成
- **実行**: `npm run test` で実行される

### 🔵 archive/ - 保管用
- **用途**: 完了済みテスト、参考用テスト
- **AI作業**: 移動のみ（新規作成禁止）
- **実行**: `npm run test-all` でのみ実行

### 🟠 pending/ - 保留中
- **用途**: エラー中・修正待ちテスト
- **AI作業**: 問題解決後に active/ へ移動
- **実行**: `npm run test-pending` で確認

## 🔧 テスト実行時のチェックリスト

### 実行前確認
- [ ] プロジェクトルートにいるか確認: `pwd` または `Get-Location`
- [ ] package.json が存在するか確認: `ls package.json`
- [ ] テストファイルが dev/tests/active/ 配下にあるか確認

### 実行コマンド
```bash
# 基本実行（日常開発）
npm run test

# 実行結果確認ポイント
# - エラーがないか
# - テスト件数が想定通りか
# - 新規作成したテストが実行されているか
```

### エラー時の対応
```bash
# Node.js/npm 環境確認
node --version
npm --version

# dev/ディレクトリ確認
ls dev/

# package.json 設定確認
cat package.json
```

## 📝 テスト作成時のベストプラクティス

### 1. 命名規則の厳守
```
✅ 2025-01-05_password_encryption_Test.php
✅ 2025-01-05_user_login_validation_Test.php
✅ 2025-01-05_admin_permission_check_Test.php

❌ UserTest.php
❌ test_user.php
❌ 20250105_user_Test.php  # ハイフンなし
```

### 2. テスト目的の明記
```php
/**
 * ユーザー認証機能テスト
 *
 * 作成日: 2025-01-05
 * 用途: ログイン機能の正常性とセキュリティ検証
 * 対象: localize-debug-log/functions/auth.php
 */
```

### 3. 適切なディレクトリ配置
- 新規開発 → `active/`
- 完了・安定 → 手動で `archive/` へ移動
- 問題発生 → 手動で `pending/` へ移動

## 🚨 トラブルシューティング

### PowerShell環境エラー
```powershell
# 症状: `&&` 記号エラー
# 解決: npm scripts使用（内部でcmd /c対応済み）
npm run test
```

### テストが見つからない
```bash
# 確認1: ファイル配置
ls dev/tests/active/

# 確認2: ファイル名規則
# yyyy-mm-dd_名前Test.php になっているか

# 確認3: PHPUnit設定
cat dev/phpunit.xml
```

### 実行権限エラー
```bash
# dev/ディレクトリアクセス確認
cd dev
composer --version
cd ..

# npm権限確認
npm run --help
```

## 📋 作業完了チェック

### テスト作成完了時
- [ ] ファイル名が yyyy-mm-dd_名前Test.php 形式
- [ ] dev/tests/active/ 配下に配置
- [ ] `npm run test` で正常実行確認
- [ ] テスト内容が目的に適合

### 定期メンテナンス
- [ ] active/ のテストを archive/ へ整理
- [ ] pending/ の課題解決
- [ ] 不要なテストファイルの削除検討

---

**重要**: このワークフローに従うことで、AI作業時の間違いを最小限に抑え、効率的なテスト開発が可能になります。
