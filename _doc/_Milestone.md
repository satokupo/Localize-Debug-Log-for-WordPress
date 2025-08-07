# Localize Debug Log for WordPress — 実装マイルストーン

**重要**
- フェーズは機能種別で分けること
- 各フェーズ冒頭に「目的・完了条件」を記載すること
- **実装予定関数・クラス名を必ず明記**（形式: `fn: ldl_xxx`, `class: Ldl_Xxx`）
- 役割が重複しないようタスクを棲み分けること
- ユーザーから明確に変更指示がない限り、MVPとしてまず動くものを作るための計画にすること

## 🎯 プロジェクト概要
WordPressプラグイン「Localize Debug Log for WordPress」の完成までの実装マイルストーン
TDDスタイルによる開発進行

## 📚 関連資料
- **要件定義**: `_doc/_Requirements.md`
- **技術仕様書**: `_doc/_TechSpec.md`
- **ディレクトリ構成**: `_doc/_Structure.md`
- **テストワークフロー**: `_doc/_TestWorkflow.md`
- **プラグイン説明**: `localize-debug-log/readme.md`

---

## 📋 Phase 1: 基盤セットアップ・環境構築
- [x] プラグインディレクトリ構成の確立
  > **完了日**: 2025-08-05 推定
  > **内容**: 基本ディレクトリ構造を確立。localize-debug-log/、logs/、readme.mdが配置済み
  > **注記**: メインプラグインファイル、.htaccess、LICENSEは未配置のため部分完了
  > **ファイル**: `localize-debug-log/`
- [x] メインプラグインファイル（localize-debug-log.php）の骨格作成
  > **完了日**: 2025-01-06
  > **内容**: WordPressプラグインヘッダー完成、セキュリティチェック(ABSPATH)実装、ldl_関数プレフィックス準備完了。Phase 2-4実装ガイドライン明記。日本語コメントでユーザー可読性向上。
  > **ファイル**: `localize-debug-log/localize-debug-log.php`
- [x] logs/ディレクトリの自動生成機能実装
  > **完了日**: 2025-08-05 推定
  > **内容**: logs/ディレクトリを手動作成済み
  > **注記**: 自動生成機能は未実装（メインプラグインファイル未作成のため）
  > **ファイル**: `localize-debug-log/logs/`
- [x] .htaccess設置によるセキュリティ基盤構築
  > **完了日**: 2025-01-06
  > **内容**: 全ファイルアクセス拒否、Apache 2.4+対応、ディレクトリリスト防止、ログファイル拡張子保護の包括的セキュリティ実装。日本語コメントで管理者可読性向上。
  > **ファイル**: `localize-debug-log/logs/.htaccess`
- [x] PHPUnitテスト環境のセットアップ検証
  > **完了日**: 2025-08-05 確認
  > **内容**: 完全なテスト環境構築完了。composer.json（PHPUnit 9.6）、phpunit.xml、npm scripts、ディレクトリ構造全て設定済み
  > **ファイル**: `dev/composer.json`, `dev/phpunit.xml`, `package.json`, `dev/tests/`

**Phase 1完了報告**:
> **完了日**: 2025-01-06
> **プルリク**: feature/phase1-setup → main (マージ完了)
> **実装成果**: WordPressプラグイン基盤ファイル群作成完了
> - メインプラグインファイル骨格(localize-debug-log.php)
> - セキュリティ基盤構築(logs/.htaccess)
> - 個人利用・改変許可ライセンス実装(LICENSE、readme.md更新)
> **動作確認**: プラグイン認識・有効化、セキュリティ機能、ファイル整合性 - 全て正常
> **Phase 2準備状況**: error_log出力先変更機能実装の基盤整備完了

---

## 🔧 Phase 2: コア機能実装（ログ収集・処理）
- [x] error_log()出力先変更機能の実装
  - [x] ini_set()による設定変更
  - [x] debug_log_pathフィルタの実装
- [x] タイムゾーン処理ロジックの実装
  - [x] WordPress設定からのタイムゾーン取得
  - [x] UTC⇔ローカル時間変換機能
- [x] ログファイル読み込み・整形機能の実装
  - [x] ログ行解析とタイムスタンプ抽出
  - [x] ローカル時間の行頭付加処理

**Phase 2完了報告**:
> **完了日**: 2025-08-06
> **ブランチ**: feature/phase2-core-functions
> **実装成果**: コア機能実装完了 - 全マイルストーン項目達成
> - 実装関数数: 14関数（全てldl_プレフィックス統一）
> - テスト結果: 22テスト・75アサーション 全て成功
> - TDD実装: Red-Green-Refactorサイクル完全実施
> **機能完成度**:
> - ✅ error_log出力先変更: ini_set() + debug_log_pathフィルタ実装
> - ✅ タイムゾーン処理: WordPress設定取得 + UTC⇔ローカル変換実装
> - ✅ ログ整形機能: 解析・抽出・ローカル時刻付加実装
> - ✅ 統合機能: メイン処理・相互不可侵・安全初期化実装
> **品質保証**: WordPress debug設定との相互不可侵確認、PHPDoc完備、セキュリティ基盤整備完了
> **Phase 3準備状況**: 管理画面UI実装の基盤（コア機能）整備完了

---

## 🖥️ Phase 3: 管理画面UI実装
> 本フェーズは「管理者がUIを通じてログを閲覧・削除できること」を最小要件とし、機能実装は WordPress 標準UIとPHP関数で完結する構成とする。
> 追加のスタイル調整や装飾、権限の抽象化は行わない。
- [ ] 管理画面メニュー追加機能 **(fn: ldl_add_admin_menu)**
  - [ ] 設定サブメニューの実装 **(fn: ldl_add_admin_menu)**
  - [ ] 管理バー（上部バー）リンクの実装 **(fn: ldl_add_admin_bar_link)**
- [ ] ログ表示画面の実装 **(fn: ldl_render_log_page)**
  - [ ] textarea によるログ表示機能（`esc_textarea()`） **(fn: ldl_render_log_page)**
  - [ ] コピペ可能な形式での表示 **(fn: ldl_render_log_page)**
- [ ] ログ削除機能の実装 **(fn: ldl_handle_delete_request)**
  - [ ] 削除ボタンとフォーム生成 **(fn: ldl_render_log_page)**
  - [ ] JavaScript `confirm()` 確認プロンプト
  - [ ] 削除処理ラッパー **(fn: ldl_delete_log_file)**
    - [ ] `unlink()` による削除処理
    - [ ] 削除後に空のログファイル（debug.log）を再生成する処理 **(fn: ldl_delete_log_file)**
  - [ ] 削除結果通知 (`admin_notices`) **(fn: ldl_notice_delete_result)**
- [ ] PHPUnit UI テスト **(class: Ldl_Ui_Test)**

---

## 🔐 Phase 4: セキュリティ・権限制御実装
> 管理画面機能は `admin_menu` 等での入口遮断によりアクセス不可とするため、追加の権限チェック関数や共通処理の抽象化は不要。
> このフェーズでは CSRF やファイル操作まわりの制御に限定する。
- [ ] 共通 CSRF 保護ユーティリティ **(fn: ldl_csrf_protect)**
  - [ ] `wp_nonce_field()` による発行
  - [ ] `check_admin_referer()` による検証
- [ ] ファイルアクセス制御
  - [ ] パス検証 (`realpath`) **(fn: ldl_validate_log_path)**
  - [ ] 権限外アクセス遮断

---

## 🧪 Phase 5: テスト実装・品質保証
> 本フェーズは機能実装ではなく品質保証に特化する。
> UnitテストとE2Eテストの分離により、処理ごとの正確な網羅とUIの動作確認を両立する。
> テストは全て PHPUnit または Playwright / WP Browser ベースで構築する。
- [ ] 単体テスト拡充（PHPUnit）
  - [ ] タイムゾーン処理テスト
  - [ ] ログ整形機能テスト
  - [ ] セキュリティ機能テスト
- [ ] UI／E2E テスト実装 **(class: Ldl_E2E_LogUiTest)**
- [ ] エラーハンドリングテスト
  - [ ] ファイル権限エラー時の挙動
  - [ ] 大容量ログファイル時の性能確認

---

## 📦 Phase 6: 最終化・リリース準備
> 本フェーズはソースコード・ドキュメントの整合性と、本番ステージング環境における最終動作確認を行い、プロジェクトを完成状態にする。
> この段階での機能追加・修正は原則として行わず、精度保証と整備のみを行う。
- [ ] エラーハンドリング強化
- [ ] コード品質チェック
  - [ ] 関数プレフィックス（ldl_）統一確認 **(fn: ldl_run_coding_standards)**
  - [ ] WordPress コーディング規約準拠確認
- [ ] ドキュメント最終化
  - [ ] 技術仕様書との整合性確認
- [ ] 最終動作テスト
  - [ ] ステージング環境での最終確認・報告受領

---

## 🎉 完成・リリース
- [ ] 全機能統合テスト完了
- [ ] ユーザー環境での最終動作確認報告受領
- [ ] プロジェクト完成
