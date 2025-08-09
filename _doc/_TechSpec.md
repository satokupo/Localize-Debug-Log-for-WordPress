# Localize Debug Log for WordPress — 技術仕様書

## 1. バージョン要件
- **WordPress** : 5.1 以上（`debug_log_path` フィルタが導入されたバージョン）
- **PHP** : 7.4 以上（推奨 8.2）

## 2. 主要フック / フィルタ
| 種別   | フック名              | 優先度 | 用途                                                                              |
|--------|-----------------------|--------|-----------------------------------------------------------------------------------|
| action | `plugins_loaded`      | **0**  | `ini_set()` と `debug_log_path` でログ出力を最速設定                               |
| filter | `debug_log_path`      | n/a    | Core が書き込む debug.log のパスを `logs/debug.log` に上書き                       |
| action | `admin_menu`          | 10     | 「設定」配下にサブメニューを追加（`add_options_page`）                              |
| action | `admin_bar_menu`      | 100    | 管理バーにログ閲覧リンクを追加                                                    |
| action | `admin_init`          | 10     | POST での nonce 検証とログ削除実行（`current_user_can('manage_options')`）          |
| action | `admin_notices`       | 10     | 削除成功 / 失敗の通知表示                                                         |

## 3. ファイル操作
- **ログ出力パス** : `plugin_dir_path( __FILE__ ) . 'logs/debug.log'`
- **.htaccess** : `Deny from all`
- **ログ削除** : `unlink()` → 存在しない場合は `file_put_contents()` で空ファイル再生成

## 4. タイムゾーン処理
~~~php
$tz_string = get_option( 'timezone_string' );
if ( empty( $tz_string ) ) {
    $offset    = get_option( 'gmt_offset' );   // 例: 9.0
    $tz_string = sprintf( 'Etc/GMT%+d', -$offset );
}
$tz = new DateTimeZone( $tz_string );
~~~
- ローカル表記例 : `JST 2025/08/04 11:00:00`
- UTC 部分はログ生データ `[YYYY-MM-DD HH:MM:SS]` を維持

## 5. セキュリティ

### 基本セキュリティ
1. **権限チェック** : `current_user_can( 'manage_options' )`
2. **CSRF** : `wp_nonce_field( 'ldl_delete_log', '_ldl_nonce' )` + `check_admin_referer()`
3. **パス検証** : `realpath()` で `logs/` 配下限定

### Phase 4 強化機能
4. **CSRF保護共通化** : `ldl_csrf_protect()` による発行・検証の統一
   - デフォルト: `action='ldl_delete_log_action'`, `field='ldl_delete_nonce'`
   - 発行モード: `wp_nonce_field()` のHTML戻り値
   - 検証モード: `check_admin_referer()` のboolean戻り値

5. **パス検証強化** : `ldl_validate_log_path()` による厳密制御
   - `realpath()` による正規化で `logs/` ディレクトリ配下の強制制限
   - パストラバーサル攻撃対策（`../` によるディレクトリ外アクセス防止）
   - シンボリックリンク経由の不正アクセス防止

6. **POST限定処理** : 削除リクエストはPOSTメソッドのみ受付
   - `$_SERVER['REQUEST_METHOD'] === 'POST'` チェック
   - GET リクエストは完全無視（セキュリティ向上）

7. **排他制御** : ファイル操作の安全化
   - 第一選択: `file_put_contents($path, '', LOCK_EX)` による排他ゼロクリア
   - フォールバック: `fopen('c+')` → `flock(LOCK_EX)` → `ftruncate(0)` → `fflush` → `fclose`
   - レースコンディション・TOCTOU攻撃対策

## 6. UI 仕様
- ログ表示 : `<textarea readonly class="widefat" rows="25">`
- 削除ボタン : `<button class="button button-primary">` + JS `confirm()`
- スタイル : WP 標準クラスのみ（独自 CSS なし）

## 7. 制限事項
- 非 ASCII / バイナリ行はエスケープされない
- 1 MB 超のログは読み込み遅延の可能性（MVP では分割読み込みなし）

## 8. 依存関係
- 追加ライブラリ・Composer 依存なし

## 9. 開発メモ
- 関数プレフィックス : `ldl_`
- 名前空間は未使用（単一ファイル構成のため）

## 10. AI開発時の注意事項

### Phase境界の厳守
- Phase 2: コア機能実装のみ（管理画面UI、セキュリティ機能、テスト実装は含まない）
- Phase 3: 管理画面UI実装（admin_menu、admin_notices等）
- Phase 4: セキュリティ・権限制御実装

### MVP開発原則
- 「まず動くもの」を優先、完璧主義は避ける
- 基本的なエラーハンドリングで十分、詳細な例外処理は後回し
- WordPress標準API（plugins_loaded等）の範囲内で実装

### 実装スコープの判断基準
- マイルストーンに明記されていない機能は実装しない
- Git操作・プルリク準備は実装フェーズに含めない
- 疑問があれば要件定義書・技術仕様書を最優先で参照
