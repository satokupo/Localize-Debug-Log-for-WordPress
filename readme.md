# Localize Debug Log for WordPress

> **現在の状態:** 過去に作成した開発・検証用プラグインです。現在は使用・保守していません。応募審査のために旧コードを公開しています。現行案件・運用中のコードではありません。

このリポジトリは、WordPress の `error_log()` をローカル時刻で表示する管理者向けプラグインの開発用プロジェクトです。

PHPのデバッグログはUTCで記録されるため、日本時間で出来事の順序を把握するのに手間がかかります。当時、必要な種類のエラーをまとめて収集し、日本時間とUTCを並べて確認できるプラグインが見つからなかったため作成しました。

プラグイン本体は `localize-debug-log/` 配下にあります。

## 技術構成

| 分類 | 使用技術 |
| --- | --- |
| 実装 | PHP、WordPress Plugin API |
| 日時処理 | PHP `DateTime` / `DateTimeZone`、WordPressのタイムゾーン設定 |
| テスト | PHPUnit 9.6、10up WP Mock |
| 依存管理・実行 | Composer、npm scripts |

## 実装されている機能

- プラグイン専用ログディレクトリの作成と`error_log`出力先の切り替え
- PHPログからUTCタイムスタンプを抽出し、WordPress設定のタイムゾーンへ変換
- UTCとローカル時刻を併記したログの読み込み・整形
- WordPress管理画面と管理バーからのログ確認
- ログ削除、表示設定、終了時エラーの取得処理

## 実装構成

- プラグイン本体は`localize-debug-log/localize-debug-log.php`の単一ファイルに、`ldl_`接頭辞の関数として実装しています。
- `plugins_loaded`から初期化し、`debug_log_path`、管理画面、管理バーなどのWordPressフックを登録します。
- ログは独自DBテーブルではなく、プラグイン配下の`logs/debug.log`へ保存します。WordPressのoptionsは表示順などの設定保存に使用します。
- ログ削除と設定保存では、管理権限、POST、nonce、ログディレクトリ内のパスであることを検証します。
- ログディレクトリの`.htaccess`はApache系サーバー向けのアクセス制限です。

## ディレクトリ構成
- `localize-debug-log/` プラグイン本体（`readme.md`, `localize-debug-log.php`, `logs/.htaccess`）
- `dev/` テスト環境（PHPUnit, Composer, npm scripts）
- `_doc/` 要件定義・技術仕様・マイルストーン・作業計画

## 動作要件
- WordPress 5.1 以上
- PHP 7.4 以上（推奨 8.2）
- 詳細は `localize-debug-log/readme.md` を参照

上記はプラグインヘッダーに記載している要件です。現在の`dev/tests/active/`にある環境確認テストはPHP 8.0以上を前提としており、PHP 7.4での互換性はこの公開時点では再検証していません。

## 開発・テスト

`dev/tests/active/`には現在、PHPUnit環境を確認するサンプルテストがあります。機能開発時のテストは`dev/tests/archive/`、未完了のテストは`dev/tests/pending/`に残しています。

- 現在のactiveテスト:
```sh
npm run test
```
- active、archive、pendingを含むテスト一式:
```sh
npm run test-all
```
- テスト詳細: `dev/tests/_README.md`

## リリース手順
1. `localize-debug-log/` を ZIP 化
2. WordPress 管理画面 → プラグイン → 新規追加 → プラグインのアップロード で導入
- 含めるファイル: `localize-debug-log.php`, `readme.md`, `LICENSE`, `logs/.htaccess`
- 含めない: `dev/`, `_doc/`, `package.json`, `vendor/`

## ドキュメント

`_doc/`には当時の要件、技術仕様、構成、マイルストーン、作業記録を残しています。開発途中の記録であり、現在の実装と一致しない箇所があります。実装の現在値は`localize-debug-log/localize-debug-log.php`を基準とします。

## プラグイン利用者向けREADME
- 実際のプラグインの使い方・特徴は `localize-debug-log/readme.md` に記載

## ライセンス
- 個人利用に限り使用・改変可。再配布・商用利用は禁止
- 詳細は `localize-debug-log/LICENSE`
