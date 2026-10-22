# Inquiry desk — お問い合わせフォーム

React 19 + TypeScript の問い合わせフォームと PHP + SQLite の受付 API です。React は静的ファイルへ事前ビルドするため、本番サーバーに Node.js は不要です。

## 日付別の構成

```
Inquiry-website/
├── README.md
└── 2026-10-22/
    ├── README.md            # この日の作業内容とファイル一覧
    ├── front/               # React / Vite 開発ソース
    ├── public/              # ビルド済み画面と PHP の公開入口
    │   ├── index.html       # npm run build で生成
    │   ├── assets/          # npm run build で生成
    │   ├── api/submit.php
    │   └── admin/index.php
    ├── php/                 # PHP 処理・非公開設定
    │   ├── api/submit.php   # 検証、CSRF、スパム対策、保存、メール通知
    │   ├── admin/index.php  # 認証付き問い合わせ一覧
    │   ├── bootstrap.php
    │   └── config.example.php
    └── storage/             # SQLite DB の自動作成先（公開領域外）
```

## 必要環境

- 開発: Node.js 20 以降、npm 10 以降。
- 実行: PHP 8.1 以降、Apache 2.4 など PHP 対応 Web サーバー。
- PHP 拡張: PDO、pdo_sqlite、mbstring、session。SQLite 3.24 以降を推奨（通常は PHP の pdo_sqlite に同梱）。
- メール: PHP `mail()` を利用できる MTA/ホスティング設定。SMTP のみの環境では `mail()` が利用できないことがあります。
- 本番サイトは HTTPS にしてください。

## 開発環境での起動

```powershell
cd C:\laragon\www\Inquiry-website\2026-10-22\front
npm install
npm run dev
```

Vite 開発サーバーは PHP を処理しません。送信/API を含めて試す場合は `npm run build` 後、`2026-10-22/public/` の内容を Apache のドキュメントルートとして開き、隣に `php/` と `storage/` を置いてください。設定例をコピーして `php/config.local.php` を作成します。

## React のビルド

```powershell
cd C:\laragon\www\Inquiry-website\2026-10-22\front
npm install
npm run build
```

生成物は `2026-10-22/public/` です。Vite の相対パス設定によりサブディレクトリにも配置できます。Node.js はこのビルド時だけ必要です。

## レンタルサーバーへの配置

1. 上記の手順でビルドします。
2. `2026-10-22/public/` の**中身**（`index.html`, `assets/`, `api/`, `admin/`, `.htaccess`）を `public_html/` へアップロードします。
3. `2026-10-22/php/` の中身を `public_html/` の隣へ配置します。`2026-10-22/storage/` もその隣に置きます。

   ```
   account/
   ├── public_html/       # 2026-10-22/public の中身
   ├── php/               # 2026-10-22/php の中身
   │   ├── api/submit.php
   │   ├── admin/index.php
   │   ├── bootstrap.php
   │   ├── config.example.php
   │   └── config.local.php
   └── storage/           # 自動作成の inquiries.sqlite
   ```

   公開入口は `../` をたどって隣の `php/` を読み込みます。サーバーの配置が異なる場合は `public_html/api/submit.php` と `public_html/admin/index.php` の `require_once` パスを調整してください。
4. `php/config.example.php` を `php/config.local.php` に複製して設定します。DB の既定位置は PHP フォルダと同階層の `storage/inquiries.sqlite` です。
5. `storage/` を PHP 実行ユーザーが書き込めるようにします。Linux の一般的な目安はディレクトリ `700`（必要なら `750`）、DB `600`（必要なら `660`）。共有サーバーの指定を優先し、公開ディレクトリ内へ DB や設定を置かないでください。SQLite はファイルと親ディレクトリ双方への書き込みが必要です。
6. HTTPS のサイトで送信を確認します。管理画面は `/admin/` です。`X-Admin-Token` ヘッダーでトークンを渡すのが推奨です。簡易確認用の `?token=...` はブラウザー履歴やサーバーログに残る可能性があります。

## PHP / SQLite 設定

PHP 8.1 以上と `PDO`, `pdo_sqlite`, `mbstring`, `session` を有効にします。ホスティング会社の PHP 拡張設定を使ってください。初回の送信時に DB とテーブルを作成します。`public/` の `.htaccess` が利用できないサーバーでは、Web サーバー設定で公開領域外の PHP/DB ファイルへの HTTP アクセスを拒否してください。

## メール・サーバー設定

`php/config.local.php` の値、または同名の環境変数を設定します。

- `INQUIRY_MAIL_TO`: 通知先メールアドレス
- `INQUIRY_FROM_EMAIL`: 自サイトのドメインに属する送信元
- `INQUIRY_FROM_NAME`: 表示名
- `INQUIRY_DB_PATH`: SQLite の絶対パス
- `INQUIRY_ADMIN_TOKEN`: 管理画面用の32文字以上の長いランダム文字列
- `INQUIRY_TIMEZONE`: 日時（既定 `Asia/Tokyo`）

初期設定の宛先・送信元・管理トークンはプレースホルダーです。本番前に必ず変更してください。メール送信に失敗しても内容は先に SQLite に保存され、管理画面で `mail_pending` として確認できます。メール設定は MTA またはホスティング会社の案内に合わせてください。

## セキュリティ上の注意

CSRF セッション、SameSite/HttpOnly cookie、サーバー側の必須・形式・長さ検証、ハニーポット、送信頻度制限、PDO プリペアドステートメントを使用します。設定と SQLite は公開領域外に配置し、強い管理トークンと HTTPS を使ってください。バックアップも非公開領域に保管し、個人情報の保存期間と削除手順を決めてください。サーバーログのアクセス権・保管期間も管理してください。

## よくあるエラー

- 画面や JS/CSS が 404: `public/` の**中身**を公開ディレクトリ直下に置き、再ビルドしてください。
- API が 404: `public/api/submit.php` があることを確認してください。
- API が 500: `pdo_sqlite` と `mbstring` を有効にし、Web サーバーのエラーログを確認してください。
- DB に保存できない / `database is locked`: `storage/` と DB の所有者・書き込み権限を確認してください。
- メールが届かない: `mail()`/MTA、送信元ドメイン、SPF/DKIM、迷惑メールフォルダーを確認してください。通知失敗レコードは管理画面に残ります。
- 管理画面が 401: `INQUIRY_ADMIN_TOKEN` と `X-Admin-Token` が一致し、独自の32文字以上の値か確認してください。
- CSRF 期限切れ: ページを再読み込みして入力し直してください。
- 日本語の文字化け: `mbstring` と UTF-8 設定を確認してください。

日ごとの作業記録と主要ファイルは `2026-10-22/README.md` に記載しています。
