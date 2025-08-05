# PHPunit機能移設計画

## 要件定義

### 1. 移動の目的
- プラグイン本体 (`localize-debug-log/`) から開発・テスト関連ファイルを分離
- プラグインディレクトリを本番環境用の最小構成に保つ
- テスト環境を独立したディレクトリ (`composer/`) で管理

### 2. 移動対象ファイル
以下のファイル・ディレクトリを `localize-debug-log/` から `composer/` に移動：

#### 設定ファイル類
- `phpunit.xml` - PHPUnit設定ファイル（パス修正必要）
- `composer.json` - Composer設定ファイル（スクリプトパス修正必要）
- `composer.lock` - Composer ロックファイル（移動のみ）
- `.phpunit.result.cache` - PHPUnit キャッシュファイル（移動のみ）

#### ディレクトリ類
- `vendor/` - Composer依存関係ディレクトリ（移動のみ）
- `tests/` - テストディレクトリ全体（移動のみ）

### 3. パス修正が必要な箇所

#### 3-1. phpunit.xml
- `bootstrap="tests/bootstrap.php"` → 移動後の相対パス
- テストスイートのディレクトリパス (`tests/active/`, `tests/pending/`, `tests/archive/`)
- カバレッジ対象ディレクトリ (`src/` → プラグインディレクトリへの参照)
- ログ出力先 (`tests/logs/coverage`, `tests/logs/testdox.txt`)

#### 3-2. composer.json
- 各テストスクリプトの `tests/` パス参照

#### 3-3. tests/bootstrap.php
- `define('THEME_ROOT', dirname(__DIR__));` → プラグインディレクトリへの正しいパス

### 4. 移動後のディレクトリ構造
```
プロジェクトルート/
├── localize-debug-log/          # プラグイン本体（本番用最小構成）
│   ├── localize-debug-log.php
│   ├── logs/
│   ├── readme.md
│   └── LICENSE
└── composer/                    # 開発・テスト環境
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
- 移動後もテスト実行は `composer/` ディレクトリから行う


## チェックボックス付き作業計画書
{{ここに作業計画予定(要件定義の内容をユーザーに承認を得たあとに作成)}}
