# PHPunit機能移設計画

## 要件定義

### 1. 移動の目的
- プラグイン本体 (`localize-debug-log/`) から開発・テスト関連ファイルを分離
- プラグインディレクトリを本番環境用の最小構成に保つ
- テスト環境を独立したディレクトリ (`dev/`) で管理
- ルートから npm scripts 経由でテスト実行可能にする

### 2. 移動対象ファイル

#### 移動するファイル・ディレクトリ
以下のファイル・ディレクトリを `localize-debug-log/` から `dev/` に移動：

- `phpunit.xml` - PHPUnit設定ファイル（パス修正必要）
- `composer.json` - Composer設定ファイル（スクリプトパス修正必要）
- `composer.lock` - Composer ロックファイル（移動のみ）
- `.phpunit.result.cache` - PHPUnit キャッシュファイル（移動のみ）
- `vendor/` - Composer依存関係ディレクトリ（移動のみ）
- `tests/` - テストディレクトリ全体（移動のみ）

#### 新規作成ファイル
- `package.json` （ルート） - npm scripts でテスト実行のショートカットコマンド

### 3. パス修正が必要な箇所

#### 3-1. phpunit.xml
- `bootstrap="tests/bootstrap.php"` → 移動後の相対パス
- テストスイートのディレクトリパス (`tests/active/`, `tests/pending/`, `tests/archive/`)
- カバレッジ対象ディレクトリ (`src/` → プラグインディレクトリへの参照)
- ログ出力先 (`tests/logs/coverage`, `tests/logs/testdox.txt`)

#### 3-2. composer.json
- 各テストスクリプトの `tests/` パス参照

#### 3-3. tests/bootstrap.php
- `define('THEME_ROOT', dirname(__DIR__));` → `define('THEME_ROOT', dirname(__DIR__, 2) . '/localize-debug-log');`

#### 3-4. package.json（新規作成）
- `npm run test` → `cd dev && composer test`
- `npm run test-active` → `cd dev && composer test-active`
- `npm run test-pending` → `cd dev && composer test-pending`
- `npm run test-all` → `cd dev && composer test-all`

### 4. 移動後のディレクトリ構造
```
プロジェクトルート/
├── package.json                 # npm scripts（テスト実行のショートカット）
├── _doc/                        # プロジェクト文書（変更なし）
├── localize-debug-log/          # プラグイン本体（本番用最小構成）
│   ├── localize-debug-log.php
│   ├── logs/
│   ├── readme.md
│   └── LICENSE
└── dev/                         # 開発・テスト環境
    ├── phpunit.xml
    ├── composer.json
    ├── composer.lock
    ├── .phpunit.result.cache
    ├── vendor/
    └── tests/
```

### 5. 制約・注意事項
- 技術仕様書では「追加ライブラリ・Composer依存なし」とあるが、これは本番プラグインに対する制約
- テスト環境は開発用途のため、この制約の対象外
- プラグイン本体は単一ファイル構成を維持
- 移動後のテスト実行方法：
  - **推奨**: ルートから `npm run test` （ショートカット）
  - **直接**: `cd dev && composer test`

### 6. テスト実行コマンド一覧
- `npm run test` - アクティブテストのみ実行
- `npm run test-active` - アクティブテストのみ実行
- `npm run test-pending` - ペンディングテストのみ実行
- `npm run test-all` - 全テスト実行


## チェックボックス付き作業計画書

## 作業計画立案ルール
- 詳細なステップバイステップの計画書
- とにかく小さい単位
- 明確な開始と終了がある
- １つの関心事に集中

---

## 計画書

### フェーズ1: 環境準備
- [x] 1-1. プロジェクトルートの`dev/`ディレクトリを確認する
**作業報告**: dev/ディレクトリが存在することを確認済み

- [x] 1-2. 現在の `localize-debug-log/` の作業状況を確認する（未コミットの変更がないか）
**作業報告**: localize-debug-log/の状況確認済み、移動準備完了

### フェーズ2: ユーザーによるファイル・ディレクトリ移動指示

**ユーザーが以下のファイル・ディレクトリを手動で移動してください：**

#### 移動対象リスト
```
移動元: localize-debug-log/  →  移動先: dev/

設定ファイル類:
- composer.json
- composer.lock
- phpunit.xml
- .phpunit.result.cache

ディレクトリ類:
- vendor/ (ディレクトリ全体)
- tests/ (ディレクトリ全体)
```

#### 移動完了後の構造確認
移動完了後、以下の構造になっていることを確認してください：
```
dev/
├── composer.json
├── composer.lock
├── phpunit.xml
├── .phpunit.result.cache
├── vendor/
└── tests/
```

- [x] 2-1. 上記ファイル・ディレクトリの移動をユーザーが実行する
**作業報告**: ユーザーが手動移動を完了

- [x] 2-2. ユーザーから移動完了の報告を受ける
**作業報告**: 移動完了の報告を受領

- [x] 2-3. `dev/` ディレクトリ内の構造が正しいことを確認する
**作業報告**: dev/内の構造確認完了。必要なファイル・ディレクトリがすべて正しく移動されていることを確認
- composer.json ✓
- composer.lock ✓
- phpunit.xml ✓
- .phpunit.result.cache ✓
- vendor/ ✓
- tests/ ✓

### フェーズ3: パス修正 - bootstrap.php
- [x] 3-1. `dev/tests/bootstrap.php` を開く
**作業報告**: bootstrap.phpファイルを読み込み完了

- [x] 3-2. `define('THEME_ROOT', dirname(__DIR__));` の行を特定する
**作業報告**: 17行目で該当行を特定完了

- [x] 3-3. `define('THEME_ROOT', dirname(__DIR__, 2) . '/localize-debug-log');` に修正する
**作業報告**: THEME_ROOTのパス定義を修正完了。プラグインディレクトリへの正しいパスに更新

- [x] 3-4. 修正内容を保存する
**作業報告**: ファイル保存完了

### フェーズ4: パス修正 - phpunit.xml
- [x] 4-1. `dev/phpunit.xml` を開く
**作業報告**: phpunit.xmlファイルを読み込み完了

- [x] 4-2. `bootstrap="tests/bootstrap.php"` の設定を確認する（変更不要）
**作業報告**: bootstrapパス確認完了。相対パスで正しく設定済み

- [x] 4-3. `<directory>src/</directory>` を `<directory>../localize-debug-log/</directory>` に修正する
**作業報告**: カバレッジ対象ディレクトリパスを修正完了（33行目）

- [x] 4-4. カバレッジ対象が正しくプラグインファイルを参照することを確認する
**作業報告**: プラグインディレクトリへの正しいパス参照を確認完了

- [x] 4-5. 修正内容を保存する
**作業報告**: ファイル保存完了

### フェーズ5: パス修正 - composer.json
- [x] 5-1. `dev/composer.json` を開く
**作業報告**: composer.jsonファイルを読み込み完了

- [x] 5-2. `"test": "phpunit tests/active/"` の設定を確認する（変更不要）
**作業報告**: testスクリプト確認完了。相対パスで正しく設定済み

- [x] 5-3. 各テストスクリプトのパスが `tests/` 相対参照であることを確認する
**作業報告**: 全テストスクリプトの確認完了。すべて`tests/`相対参照で正しく設定済み
- test: "phpunit tests/active/" ✓
- test-active: "phpunit tests/active/" ✓
- test-all: "phpunit tests/" ✓
- test-archive: "phpunit tests/archive/" ✓
- test-pending: "phpunit tests/pending/" ✓

- [x] 5-4. 必要に応じて調整する
**作業報告**: 調整不要。すべて正しく設定済み

### フェーズ6: package.json作成
- [x] 6-1. プロジェクトルートに `package.json` を新規作成する
**作業報告**: プロジェクトルートにpackage.jsonファイルを新規作成完了

- [x] 6-2. 基本情報（name, private）を設定する
**作業報告**: 基本情報設定完了
- name: "localize-debug-log-workspace"
- private: true

- [x] 6-3. `npm run test` スクリプトを `cd dev && composer test` で設定する
**作業報告**: testスクリプト設定完了

- [x] 6-4. `npm run test-active` スクリプトを `cd dev && composer test-active` で設定する
**作業報告**: test-activeスクリプト設定完了

- [x] 6-5. `npm run test-pending` スクリプトを `cd dev && composer test-pending` で設定する
**作業報告**: test-pendingスクリプト設定完了

- [x] 6-6. `npm run test-all` スクリプトを `cd dev && composer test-all` で設定する
**作業報告**: test-allスクリプト設定完了

### フェーズ7: 動作確認
- [x] 7-1. `cd dev && composer install` を実行して依存関係を確認する
**作業報告**: composer install実行完了。26パッケージのインストール成功

- [x] 7-2. `cd dev && composer test` を実行してテストが動作することを確認する
**作業報告**: dev/でのテスト実行成功。3 tests, 7 assertions すべてOK

- [x] 7-3. プロジェクトルートで `npm run test` を実行してショートカットが動作することを確認する
**作業報告**: ルートからのnpm run test実行成功
**重要な修正**: PowerShellで`&&`が使用不可のため、package.jsonを`cmd /c "cd dev && composer test"`形式に修正

- [x] 7-4. `npm run test-active` の動作を確認する
**作業報告**: npm run test-active実行成功。3 tests, 7 assertions すべてOK

- [x] 7-5. エラーが発生した場合はパス設定を再確認する
**作業報告**: PowerShell対応により、エラー解決済み

### フェーズ8: 最終確認
- [x] 8-1. プロジェクト全体のディレクトリ構造が設計通りになっているか確認する
**作業報告**: ディレクトリ構造確認完了
```
プロジェクトルート/
├── package.json ✅ (新規作成)
├── _doc/ ✅ (変更なし)
├── localize-debug-log/ ✅ (プラグイン本体のみ)
└── dev/ ✅ (開発・テスト環境)
```

- [x] 8-2. 全てのテストコマンドが正常に動作することを確認する
**作業報告**: 全テストコマンド動作確認完了
- `npm run test` → 3 tests, 7 assertions OK ✅
- `npm run test-active` → 3 tests, 7 assertions OK ✅
- `npm run test-all` → 3 tests, 7 assertions OK ✅
- `npm run test-pending` → No tests executed (正常) ✅

- [x] 8-3. プラグイン本体（`localize-debug-log/`）に開発関連ファイルが残っていないことを確認する
**作業報告**: プラグイン本体クリーンアップ確認完了
- 開発関連ファイルはすべてdev/に移動済み
- プラグイン本体は本番用最小構成を維持

- [x] 8-4. 設定ファイルの変更をコミットする
**作業報告**: **ユーザーによるコミット作業が必要**
移行作業はすべて完了しましたが、変更のコミットはユーザーが実行してください

---

## コミットコメント

```
feat: PHPUnitテスト環境をdev/ディレクトリに移設

- localize-debug-log/からdev/にテスト関連ファイルを移動
  - composer.json, composer.lock, phpunit.xml
  - .phpunit.result.cache, vendor/, tests/
- パス修正を実施
  - bootstrap.php: THEME_ROOT定義をプラグインディレクトリへの正しいパスに修正
  - phpunit.xml: カバレッジ対象を../localize-debug-log/に修正
- package.jsonを新規作成してルートからのテスト実行を可能に
  - npm run test, test-active, test-pending, test-all
  - PowerShell対応でcmd /c使用
- プラグイン本体を本番用最小構成に整理
- 動作確認済み：全テストコマンドが正常動作
```
