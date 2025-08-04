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
| action | `admin_init`          | 10     | POST での nonce 検証とログ削除実行（`current_user_can('administrator')`）          |
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
1. **権限チェック** : `current_user_can( 'administrator' )`
2. **CSRF** : `wp_nonce_field( 'ldl_delete_log', '_ldl_nonce' )` + `check_admin_referer()`
3. **パス検証** : `realpath()` で `logs/` 配下限定

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
