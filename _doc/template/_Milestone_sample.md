# {{プロジェクト名}} — 実装マイルストーン

**重要**
- フェーズは機能種別で分けること
- 各フェーズ冒頭に「目的・完了条件」を記載すること
- **実装予定関数・クラス名を必ず明記**（形式: `fn: ldl_xxx`, `class: Ldl_Xxx`）
- 役割が重複しないようタスクを棲み分けること
- ユーザーから明確に変更指示がない限り、MVPとしてまず動くものを作るための計画にすること

## 🎯 プロジェクト概要
{{プロジェクトの説明}}

## 📚 関連資料
**実際の資料に合わせて書き換え**
- **要件定義**: `_doc/_Requirements.md`
- **技術スタック**: `_doc/_TechStack.md`
- **関数・API仕様**: `_doc/_FunctionsAndAPI.md`
- **ディレクトリ構成**: `_doc/_Structure.md`
- **テストワークフロー**: `_doc/_TestWorkflow.md`
- **プラグイン説明**: `localize-debug-log/readme.md`

---

**以降、記載内容サンプル**

## 📋 Phase 1: テスト基盤セットアップ・開発環境構築
**ブランチ：feature/phase1-test-setup**
> **TDD原則**: テストファーストで基盤構築。機能実装前にテスト環境を完全に動作させ、Phase 2以降で安全にRed-Green-Refactorサイクルを回せる状態を作る。
> **完了条件**: PHPUnitが正常実行され、最初のダミーテストがGreenになること。プラグイン基本構造が WordPress で認識されること。
- [ ] **テスト環境優先構築**
  - [ ] PHPUnit + WP_Mock 環境セットアップ **(dev/composer.json, dev/phpunit.xml)**
  - [ ] 最初のダミーテスト作成・Green確認 **(class: DummyTest)**
  - [ ] npm run test スクリプト動作確認
- [ ] **最小プラグイン構造**
  - [ ] WordPress プラグインヘッダー作成 **(localize-debug-log.php)**
  - [ ] セキュリティ基盤（ABSPATH チェック）
  - [ ] プラグイン有効化・無効化の動作確認

**実装順序（TDD）**: ダミーテスト → プラグイン骨格 → テスト環境でのプラグイン読み込み確認

---

## 🔧 Phase 2: コア機能実装（ログ制御・処理）
**ブランチ：feature/phase2-core-logic**
> **TDD原則**: 各関数をTest → 実装 → リファクタリングの順で作成。UI依存なしでテスト可能な純粋関数を中心に構成。
> **完了条件**: 全コア関数のユニットテストがGreen、実際のログファイル操作が正常動作すること。
- [ ] **ログ出力先制御機能**
  - [ ] Red: `ldl_set_log_destination()` の期待動作テスト作成
  - [ ] Green: `ini_set('log_errors_max_len', 0)` + カスタムパス実装 **(fn: ldl_set_log_destination)**
  - [ ] Refactor: エラーハンドリングとパス正規化の整理
- [ ] **タイムゾーン変換機能**
  - [ ] Red: `ldl_convert_utc_to_local()` のUTC→現地時間変換テスト **(fn: ldl_convert_utc_to_local)**
  - [ ] Green: WordPress `get_option('timezone_string')` + DateTime実装
  - [ ] Refactor: 入力検証とフォーマット統一
- [ ] **ログ解析・整形機能**  
  - [ ] Red: `ldl_parse_log_line()` のタイムスタンプ抽出テスト **(fn: ldl_parse_log_line)**
  - [ ] Green: 正規表現による解析とタイムゾーン変換適用実装
  - [ ] Refactor: パフォーマンス最適化とメモリ効率化

**実装順序（TDD）**: 関数ごとにRed→Green→Refactor、依存関係の少ない順（タイムゾーン→解析→出力先制御）

---

## 🖥️ Phase 3: UI実装（管理画面）
**ブランチ：feature/phase3-admin-ui**  
> **TDD原則**: UI コンポーネントを関数単位で分割し、HTML出力・フック登録・権限チェックをそれぞれテスト可能にする。
> **完了条件**: 管理画面でログ表示・削除が正常動作、権限チェックが機能し、UIテストがGreenになること。
- [ ] **管理メニュー追加**
  - [ ] Red: `ldl_add_admin_menu()` のフック登録テスト **(fn: ldl_add_admin_menu)**
  - [ ] Green: `add_options_page()` + `current_user_can('manage_options')` 実装
  - [ ] Refactor: メニュー構造の最適化
- [ ] **ログ表示画面**
  - [ ] Red: `ldl_render_log_page()` のHTML出力テスト（textarea、ボタン要素） **(fn: ldl_render_log_page)**
  - [ ] Green: ログファイル読み込み + `esc_textarea()` での安全表示実装  
  - [ ] Refactor: 大容量ファイル対応とエラー表示の改善
- [ ] **削除機能**
  - [ ] Red: `ldl_handle_log_delete()` のPOST処理・nonce検証テスト **(fn: ldl_handle_log_delete)**
  - [ ] Green: `unlink()` + 削除後の空ファイル再生成実装
  - [ ] Refactor: フィードバック表示の統一

**実装順序（TDD）**: メニュー追加→表示→削除、各段階でUIテスト含め完全Green確認

---

## 🔐 Phase 4: セキュリティ強化
**ブランチ：feature/phase4-security**
> **TDD原則**: セキュリティ要件を先にテストケースで定義し、攻撃パターンに対する防御をテストで検証してから実装。
> **完了条件**: セキュリティテスト（CSRF、パス検証、権限制御）が全てGreen、ペネトレーションテスト相当の検証完了。
- [ ] **CSRF対策**
  - [ ] Red: `ldl_verify_nonce()` の正規・不正nonce検証テスト **(fn: ldl_verify_nonce)**
  - [ ] Green: `wp_nonce_field()` + `check_admin_referer()` 実装
  - [ ] Refactor: 共通化と例外処理の整備
- [ ] **パス・ファイルアクセス制御**
  - [ ] Red: `ldl_validate_file_access()` のディレクトリトラバーサル攻撃防御テスト **(fn: ldl_validate_file_access)**
  - [ ] Green: `realpath()` + 許可ディレクトリ範囲チェック実装
  - [ ] Refactor: セキュリティログ出力とアクセス記録
- [ ] **権限チェック統合**
  - [ ] Red: 各UI関数での権限不足時の挙動テスト（404/403応答）
  - [ ] Green: 全UI関数に `current_user_can()` ガード追加
  - [ ] Refactor: 共通権限チェック関数の抽出

**実装順序（TDD）**: 攻撃パターン列挙→防御テスト作成→実装→ペネトレーション的検証

---

## 📦 Phase 5: リリース準備・最終化
**ブランチ：feature/phase5-release-preparation**
> **TDD原則**: Phase 1-4で構築した機能・テストの総合検証と、リリース可能状態への仕上げ。新規機能追加は行わない。
> **完了条件**: 全Phase完了テストがarchive移動済み、ドキュメント整合済み、ステージング環境での動作確認完了。
- [ ] **テスト資産の最終整理**
  - [ ] Phase 1テスト群のarchive移動（基盤セットアップ関連テスト）
  - [ ] Phase 2テスト群のarchive移動（コア機能テスト）  
  - [ ] Phase 3テスト群のarchive移動（UI機能テスト）
  - [ ] Phase 4テスト群のarchive移動（セキュリティテスト）
  - [ ] `npm run test-all`で全archive含めた総合テスト実行・Green確認
- [ ] **ドキュメント最終整合**
  - [ ] 技術スタック・関数仕様・READMEの内容一致確認
  - [ ] マイルストーン完了状況の最終記録
- [ ] **本番相当環境での最終検証**  
  - [ ] ステージング環境でのフルシナリオテスト実施
  - [ ] パフォーマンス・セキュリティの最終確認
  - [ ] ユーザー受け入れテスト報告受領

**実装順序（TDD）**: Phase1-4完了テストの段階的archive移動→ドキュメント整合→最終検証の順でリリース準備

---

## 🎯 Phase 6: 最終品質保証（必要に応じて）
**ブランチ：feature/phase6-quality-assurance**
> **TDD原則**: 品質チェックをテスト化し、ドキュメント整合性もテストで自動検証。コード変更は最小限にとどめる。
> **完了条件**: 全品質チェックテストGreen、ステージング環境テスト完了報告受領、リリース判定基準全クリア。
- [ ] **コード品質自動検証**
  - [ ] Red: `ldl_` プレフィックス統一・WordPress規約準拠の自動チェックテスト **(fn: ldl_run_quality_checks)**
  - [ ] Green: 既存コードの検証、必要最小限の修正
  - [ ] Refactor: 品質チェック結果の可視化
- [ ] **ドキュメント整合性検証**
  - [ ] Red: 技術仕様書・README・コメントの関数名・バージョン情報一致テスト
  - [ ] Green: 差分解消とドキュメント最終更新
  - [ ] Refactor: ドキュメント生成の半自動化
- [ ] **最終ステージングテスト**
  - [ ] Red: 本番環境相当での全機能動作確認テストスイート
  - [ ] Green: ユーザーステージング環境でのテスト実施・報告受領
  - [ ] Refactor: リリース判定チェックリストの自動化

**実装順序（TDD）**: 品質チェックの自動化→ドキュメント検証→ステージング→リリースゲートの順で最終検証

---

## 🎉 完成・リリース
- [ ] **全テストスイート最終実行**（Phase 1-6の全テストがGreen）
- [ ] **リリース判定基準全クリア**（パフォーマンス、セキュリティ、互換性、品質）
- [ ] **ユーザー最終承認取得**（ステージング環境での動作確認報告）  
- [ ] **プロジェクト完成宣言**

---

## 📊 TDDマイルストーン運用ガイド

**各Phaseでのテスト戦略**:
- **Phase 1-2**: 単体テスト中心（純粋関数、入出力明確）
- **Phase 3-4**: UI・セキュリティのモック/スタブテスト  
- **Phase 5**: 統合・エッジケース・パフォーマンステスト
- **Phase 6**: 品質・ドキュメント・リリース基準の自動検証

**Red-Green-Refactor サイクル厳守**:
1. **Red**: 期待動作のテストを書き、失敗することを確認
2. **Green**: テストが通る最小限の実装
3. **Refactor**: テストを維持しながらコード品質向上

**テスト品質管理**:
- 各Phase完了時点でテスト実行時間・成功率・カバレッジを記録
- リグレッション防止のため、過去Phaseのテストは常にGreenを維持
- テストコード自体も本実装と同等の品質基準を適用