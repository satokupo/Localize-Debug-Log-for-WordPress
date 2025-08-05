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
{{ここに作業計画予定(要件定義の内容をユーザーに承認を得たあとに作成)}}
