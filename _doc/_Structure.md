# Localize Debug Log for WordPress — ディレクトリ構成図（MVP）

```text
localize-debug-log/                  # プラグインルート（スラッグ）
├── localize-debug-log.php          # メインプラグインファイル
├── logs/                           # ログ保存ディレクトリ（自動生成）
│   ├── debug.log                   # error_log() 出力先
│   └── .htaccess                   # "Deny from all" で外部アクセス遮断
├── readme.md                       # 日英併記 README
└── LICENSE                         # All rights reserved
```

## ファイル責務

| パス                       | 役割                                                               |
| ------------------------ | ---------------------------------------------------------------- |
| `localize-debug-log.php` | プラグインのエントリポイント。フック登録、ログ表示、ログ削除ロジックを統合した単一ファイル構成（MVP）             |
| `logs/debug.log`         | PHP `error_log()` 出力が集約される実ログファイル。Git にはコミットしない（`.gitignore` 推奨） |
| `logs/.htaccess`         | `Deny from all` でブラウザ経由の直接アクセスを遮断                                |
| `readme.md`              | プラグイン概要・使い方・ライセンス等（日英併記）                                         |
| `LICENSE`                | All rights reserved を明示（再配布・商用利用禁止）                              |
