# Localize Debug Log for WordPress

WordPress のタイムゾーン設定に基づいて、PHP の `error_log()` 出力を収集し、ローカル時間付きで表示する管理用プラグインです。

This plugin collects PHP `error_log()` output and displays it with local timestamps based on your WordPress timezone setting.

---

## 🔍 概要 / Overview

このプラグインは、WordPress サイト上で発生した PHP エラーを専用のログファイルに記録し、管理画面から確認できるようにする開発者向けツールです。ログはそのまま保存され、表示時にのみローカル時間が行頭に追加されます。

This plugin is intended as a developer utility to capture and view PHP errors triggered by `error_log()`. The log content remains unaltered; local time is added only during display.

---

## 📦 特徴 / Features

- `logs/debug.log` に `error_log()` 出力を集約
- 表示時に WordPress のタイムゾーンに基づいたローカル時刻を行頭に追加
  例：`JST 2025/08/04 11:00:00 | UTC [2025-08-04 02:00:00] Error: Something happened`
- 管理画面にコピペしやすい `<textarea>` で表示
- 「設定」メニュー下と管理バー（上部バー）にアクセスリンクを追加
- 管理者のみアクセス可能、ログの削除も可能（確認プロンプトあり）
- wp-config.php の編集不要
- `.htaccess` によるログファイルへの外部アクセス遮断

- Aggregates `error_log()` output into `logs/debug.log`
- Prepends local time (based on WordPress timezone) during display only
- Example:
  `JST 2025/08/04 11:00:00 | UTC [2025-08-04 02:00:00] Error: Something happened`
- Log is shown as `<textarea readonly>` in admin screen for easy copying
- Adds link under “Settings” and in the admin top bar
- Accessible to administrators only; includes delete button with confirmation
- No need to edit wp-config.php
- Log file access is blocked by `.htaccess`

---

## 🛠 使い方 / Usage

1. このリポジトリを `wp-content/plugins/localize-debug-log/` に配置
2. WordPress 管理画面から有効化
3. `error_log()` によるログが `logs/debug.log` に記録されます
4. 管理画面の「設定 → Localize Debug Log」または上部バーからアクセス
5. 表示されたログにローカル時刻が付加されます（UTC 時刻も維持）
6. ログの削除ボタンでログファイルを初期化できます（要確認）

1. Place this plugin in `wp-content/plugins/localize-debug-log/`
2. Activate from the WordPress admin
3. Logs from `error_log()` will be written to `logs/debug.log`
4. Access the log via `Settings → Localize Debug Log` or from the admin top bar
5. Logs will show prepended local time (UTC remains visible)
6. Use the delete button to clear the log (confirmation required)

---

## 📁 ディレクトリ構成 / Directory Structure

```
localize-debug-log/
├── localize-debug-log.php         # メインプラグインファイル / Main plugin file
├── logs/
│   ├── debug.log                  # ログ出力先 / Log file
│   └── .htaccess                  # 外部アクセス遮断 / Blocks direct access
└── readme.md                      # このファイル / This file
```

---

## ⚙️ 動作環境 / Requirements

- WordPress 5.1 以上 / WordPress 5.1 or later
- PHP 7.4 以上 / PHP 7.4 or later
  （最適動作: WordPress 6.8.2 / PHP 8.2）
  (Optimized for WordPress 6.8.2 / PHP 8.2)

---

## 🔐 セキュリティ / Security

- ログファイルはプラグイン内 `logs/` ディレクトリに保存
- `.htaccess` により外部からのアクセスを遮断
- 管理者権限のみ閲覧・削除が可能
- CSRF対策（nonce）と削除前の確認プロンプトを実装済み

- Logs are stored in the internal `logs/` directory
- `.htaccess` prevents external access to the log file
- Only administrators can view or delete the log
- CSRF protection and confirmation prompt are implemented

---

## 🚫 ライセンスと使用条件 / License & Usage Terms

このプラグインの著作権は作成者に帰属し、**個人利用に限り自由に使用・改変できます**。ただし、**無断での再配布、商用利用（販売）は禁止されています**。
技術力開示を目的として公開しており、OSSとしての再利用を意図したものではありません。
WordPress公式ディレクトリへの登録も予定していません。

All rights reserved by the author.
**Free for personal use and modification only.** However, redistribution or commercial use (sales) is strictly prohibited.
This plugin is published solely for showcasing development capability.
It is **not intended as an open-source contribution**.

---

## 👤 作者 / Author

satokupo
