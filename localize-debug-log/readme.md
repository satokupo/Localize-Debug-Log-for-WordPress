# Localize Debug Log for WordPress

> **Current status / 現在の状態:** This is an older development and verification plugin that is no longer used or maintained. It remains public as a past code sample for application review. / 過去に作成した開発・検証用プラグインで、現在は使用・保守していません。応募審査のための旧コード例です。

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
- 「ツール」メニュー配下と管理バー（上部バー）にアクセスリンクを追加（フロント側でも表示、権限者のみ）
- 管理者のみアクセス可能、ログの削除も可能（確認プロンプトあり）
- wp-config.php の編集不要
- `.htaccess` によるログファイルへの外部アクセス遮断

追加（Phase 7）
- 強制キャプチャーモード（オプトイン）で WP_DEBUG=false 環境でもエラーを収集（既存ハンドラへ委譲）
- ログ表示順トグル（新しい→古い / 古い→新しい）
- 管理バーのアイコン表示を安定化（dashicons を確実に読み込み、疑似要素で歯車を表示）

- Aggregates `error_log()` output into `logs/debug.log`
- Prepends local time (based on WordPress timezone) during display only
- Example:
  `JST 2025/08/04 11:00:00 | UTC [2025-08-04 02:00:00] Error: Something happened`
- Log is shown as `<textarea readonly>` in admin screen for easy copying
- Adds link under “Tools” and in the admin top bar (also on front-end for logged-in admins)
- Accessible to administrators only; includes delete button with confirmation
- No need to edit wp-config.php
- Log file access is blocked by `.htaccess`

Added in Phase 7
- Opt-in Force Capture mode to collect errors even when WP_DEBUG=false (delegates to existing handlers)
- Log order toggle (newest→oldest / oldest→newest)
- Stabilized admin-bar icon display (ensure dashicons load and render gear via pseudo-element)

---

## 🛠 使い方 / Usage

1. このリポジトリを `wp-content/plugins/localize-debug-log/` に配置
2. WordPress 管理画面から有効化
3. `error_log()` によるログが `logs/debug.log` に記録されます
4. 管理画面の「ツール → Localize Debug Log」または上部バーからアクセス（ログイン中のフロントでも表示）
5. 表示されたログにローカル時刻が付加されます（UTC 時刻も維持）
6. ログの削除ボタンでログファイルを初期化できます（要確認）

1. Place this plugin in `wp-content/plugins/localize-debug-log/`
2. Activate from the WordPress admin
3. Logs from `error_log()` will be written to `logs/debug.log`
4. Access the log via `Tools → Localize Debug Log` or from the admin top bar (also visible on the front-end if logged in)
5. Logs will show prepended local time (UTC remains visible)
6. Use the delete button to clear the log (confirmation required)

### 設定 / Settings

- 強制キャプチャーモード: チェックを入れると、WP_DEBUG=false でも警告・注意・例外・致命的エラーを収集します（推奨: 開発・検証環境）。
- ログ表示順: `新しい → 古い`（既定）または `古い → 新しい` を選択できます。表示順のみが変わり、保存形式は変わりません。

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
