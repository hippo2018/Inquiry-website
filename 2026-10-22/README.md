# 2026-10-22 — 問い合わせフォーム

## 作業内容

- React + TypeScript で日本語のレスポンシブ問い合わせフォームを作成。
- 必須項目とメール形式の検証、確認画面、完了/エラー表示を追加。
- PHP 側に CSRF、ハニーポット、頻度制限、入力検証を実装。
- SQLite へ先に保存し、通知メール失敗後も記録を管理画面で確認できるようにした。
- React のビルド先を `public/` に設定し、サーバー上で Node.js 不要の配置を可能にした。

## 主要ファイル

- `front/src/App.tsx` — 入力・確認・送信画面
- `front/src/App.css`, `front/src/index.css` — レスポンシブデザイン
- `front/vite.config.ts` — 静的ファイル出力先
- `public/api/submit.php`, `public/admin/index.php` — Web 公開側の入口
- `php/api/submit.php` — 検証、保存、メール通知
- `php/admin/index.php` — 認証付き問い合わせ一覧
- `php/bootstrap.php` — SQLite 接続とテーブル作成
- `php/config.example.php` — 設定例
- `storage/` — SQLite DB 自動作成先

## 確認状況

`npm run build` が成功し、`public/index.html` と圧縮済み JS/CSS を生成。PHP の主要ファイル5件は `php -l` で構文エラーなし。

## 配置

`public/` の中身を `public_html/` へ、`php/` と `storage/` は公開領域の隣へ配置します。権限と設定はルート `README.md` を参照してください。

