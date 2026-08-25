# Localize Debug Log for WordPress

WordPressのデバッグログをローカル時刻で確認するために、過去に作成した管理者向けプラグインです。現在は利用も保守もしておらず、応募審査向けに当時のコードを公開しています。

WordPressのデバッグログはUTCで記録されるため、日本時間で出来事の順序を把握するには時刻の読み替えが必要でした。必要な種類のエラーを収集し、日本時間とUTCを並べて確認できるようにするため、このプラグインを作成しました。

プラグイン本体は `localize-debug-log/` 配下にあります。

## 技術構成

| 分類 | 使用技術 |
| --- | --- |
| 実装 | PHP、WordPress Plugin API |
| 日時処理 | PHP `DateTime` / `DateTimeZone`、WordPressのタイムゾーン設定 |
| テスト | PHPUnit 9.6、10up WP Mock |
| 依存管理・実行 | Composer、npm scripts |

## 実装されている機能

- プラグイン専用ログディレクトリの作成と`error_log()`出力先の切り替え
- PHPログからUTCタイムスタンプを抽出し、WordPress設定のタイムゾーンへ変換
- UTCとローカル時刻を併記したログの読み込み・整形
- WordPress管理画面と管理バーからのログ確認
- ログ削除、表示設定、終了時エラーの取得処理

## 実装構成

- プラグイン本体は`localize-debug-log/localize-debug-log.php`の単一ファイルで構成し、各処理を`ldl_`接頭辞の関数として実装しています。
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

上記はプラグインヘッダーに記載している要件です。`dev/tests/active/`の環境確認テストはPHP 8.0以上を前提としており、PHP 7.4との互換性は再検証していません。

## 開発・テスト

テストコードは状態別に分けています。`dev/tests/active/`にはPHPUnit環境を確認するサンプルテスト、`dev/tests/archive/`には機能開発時のテスト、`dev/tests/pending/`には未完了のテストがあります。

- `active`のテスト:

```sh
npm run test
```

- `active`、`archive`、`pending`を含むテスト一式:

```sh
npm run test-all
```

- テスト詳細: `dev/tests/_README.md`

## 配布用ZIPの構成

配布用ZIPは`localize-debug-log/`以下のファイルで構成します。

- 含めるファイル: `localize-debug-log.php`, `readme.md`, `LICENSE`, `logs/.htaccess`
- 含めないファイル: `dev/`, `_doc/`, `package.json`, `vendor/`

WordPress管理画面の「プラグイン」から「新規追加」、「プラグインのアップロード」の順に進むと導入できます。

## ドキュメント

`_doc/`には当時の要件、技術仕様、構成、マイルストーン、作業記録があります。開発途中の記録を含むため、コードと記載が異なる場合は`localize-debug-log/localize-debug-log.php`を基準とします。

## プラグインの使い方

機能と操作方法は`localize-debug-log/readme.md`に記載しています。

## ライセンス

本人作成コードは、個人利用に限り使用、改変できます。再配布と商用利用は許可していません。詳しい条件は`localize-debug-log/LICENSE`を参照してください。

外部ライブラリには、それぞれのライセンスが適用されます。
