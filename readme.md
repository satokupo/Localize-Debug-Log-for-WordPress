## Localize Debug Log for WordPress — Repository

このリポジトリは、WordPress の `error_log()` をローカル時刻で表示する管理者向けプラグインの開発用プロジェクトです。
実運用のプラグイン本体は `localize-debug-log/` 配下にあります。

### ディレクトリ構成
- `localize-debug-log/` プラグイン本体（`readme.md`, `localize-debug-log.php`, `logs/.htaccess`）
- `dev/` テスト環境（PHPUnit, Composer, npm scripts）
- `_doc/` 要件定義・技術仕様・マイルストーン・作業計画

### 動作要件
- WordPress 5.1 以上
- PHP 7.4 以上（推奨 8.2）
- 詳細は `localize-debug-log/readme.md` を参照

### 開発・テスト
- 全テスト実行:
```sh
npm run test
```
- アーカイブ含む全テスト:
```sh
npm run test-all
```
- テスト詳細: `dev/tests/_README.md`

### リリース手順（ステージング/本番）
1. `localize-debug-log/` を ZIP 化
2. WordPress 管理画面 → プラグイン → 新規追加 → プラグインのアップロード で導入
- 含めるファイル: `localize-debug-log.php`, `readme.md`, `LICENSE`, `logs/.htaccess`
- 含めない: `dev/`, `_doc/`, `package.json`, `vendor/`

### ドキュメント
- 技術仕様書: `_doc/_TechSpec.md`
- 構成図: `_doc/_Structure.md`
- マイルストーン: `_doc/_Milestone.md`
- フェーズ6の計画/結果: `_doc/branch-docs/plan/2025-08-11_3_フェーズ６スコープ.md`

### プラグイン利用者向け README
- 実際のプラグインの使い方・特徴は `localize-debug-log/readme.md` に記載

### ライセンス
- 個人利用に限り使用・改変可。再配布・商用利用は禁止
- 詳細は `localize-debug-log/LICENSE`


