core: WordPressプラグイン「Localize Debug Log for WordPress」要件定義（MVP）
プラグイン表示名: Localize Debug Log for WordPress
ディレクトリ名（スラッグ）: localize-debug-log
メインファイル名: localize-debug-log.php

目的:
  - error_log() によるログを WordPress 管理画面から安全かつ簡単に閲覧できるようにする
  - ログファイル自体は改変せず、閲覧時に WordPress のタイムゾーン設定に従ってタイムスタンプを追記表示する

機能要件:
  - error_log() の出力先をプラグイン内 `logs/debug.log` に固定
    - `ini_set()` および `add_filter('debug_log_path', …)` により実現（WP 5.1 以降）
  - WP_DEBUG / WP_DEBUG_DISPLAY の設定状態に関係なく動作（上書きは行わない）
  - ログファイルはそのまま保存し、表示時のみ整形
    - 各行の先頭に WordPress のローカルタイムゾーンでの時刻を追記
    - 表示形式例: `JST 2025/08/04 11:00:00 | UTC [2025-08-04 02:00:00] Error...`
    - 「JST」表記は WPのタイムゾーン設定から自動で取得

管理画面表示:
  - 表示場所:
    - サイドメニューに「設定」の下部として項目追加（`dashicons-admin-settings` を使用）
    - 上部の **ヘッダーメニューにも必ずリンクを追加**
  - アクセス権限: `manage_options` 権限を持つユーザーに限定
  - 表示構成:
    - ログ内容は `<textarea readonly>` を使用して表示し、コピペ可能とする
    - ページ下部に「ログ削除」ボタンを設置（確認付き）

ログ削除機能:
  - POST処理による削除アクション
  - WordPressの `wp_nonce_field()` による CSRF 対策
  - JavaScriptの `confirm()` によるユーザー確認プロンプトあり
  - 実行後、`admin_notices` にて削除成功／失敗を表示

ファイル構成（ディレクトリツリー）:
  localize-debug-log/
  ├── localize-debug-log.php         # メインプラグインファイル（Plugin Name: に正式名を記載）
  ├── logs/
  │   ├── debug.log                  # error_log 出力先
  │   └── .htaccess                  # 外部アクセス遮断用（Deny from all）
  └── readme.md                      # プラグイン説明用ファイル（Markdown形式）

設計仕様:
  - タイムゾーン取得:
    - `get_option('timezone_string')` を使用
    - 空の場合は `get_option('gmt_offset')` により DateTimeZone を組み立て
  - タイムゾーン記号は `DateTimeZone::getName()` に基づき自動表示
  - logs/ ディレクトリには `.htaccess` を同梱して外部からの直接アクセスを防止
  - `custom_log()` のような独自ロガー関数は提供しない（`error_log()` のみを利用）

制約・前提:
  - 対応環境:
      - 最適動作環境: WordPress 6.8.2、PHP 8.2
      - 最低動作保証: WordPress 5.1 以上、PHP 7.4 以上（debug_log_path 対応のため）
  - WordPressコアファイル（例：wp-config.php）には一切手を加えない
  - 外部のPHP拡張、サーバープロセス、CLIツール（inotify など）は使用しない

MVP以降の拡張案（保留）:
  - ログ検索機能（キーワード、期間フィルタ）
  - ログのCSV / JSON出力機能
  - Ajax（非同期）によるログの段階読み込み

## AI開発ガイドライン

### Phase 2実装範囲（厳密定義）
- error_log()出力先変更機能（ini_set、debug_log_pathフィルタ）
- タイムゾーン処理ロジック（WordPress設定取得、UTC変換）
- ログ読み込み・整形機能（ファイル読込、ローカル時刻付加）
- **含まない**: 管理画面、通知機能、詳細エラーハンドリング、Git操作

### 判断原則
- マイルストーン記載事項が最優先仕様
- MVP段階では「動作する最小実装」を目指す
- Phase境界を越える機能提案は行わない
