<?php
require_once __DIR__ . '/../bootstrap.php';
secure_headers();
$config = inquiry_config();
$expected = (string)$config['admin_token'];
$provided = (string)($_SERVER['HTTP_X_ADMIN_TOKEN'] ?? ($_GET['token'] ?? ''));
if (strlen($expected) < 32 || str_starts_with($expected, 'CHANGE-') || !hash_equals($expected, $provided)) {
    header('WWW-Authenticate: Bearer realm="Inquiry records"'); http_response_code(401); header('Content-Type: text/plain; charset=utf-8'); exit('認証に失敗しました。');
}
try { $items = inquiry_db()->query('SELECT reference,name,email,subject,message,status,created_at,mail_error FROM inquiries ORDER BY id DESC LIMIT 500')->fetchAll(); }
catch (Throwable $e) { http_response_code(500); exit('データベースを開けません。'); }
function esc(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: no-store');
?>
<!doctype html><html lang="ja"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>問い合わせ記録</title><style>body{font:14px system-ui,sans-serif;background:#f5f7f3;color:#28463f;margin:0;padding:24px}main{max-width:1000px;margin:auto}h1{font-size:23px}.item{background:white;border:1px solid #dfe7de;padding:18px;margin:14px 0;overflow-wrap:anywhere}.meta{color:#64776d;font-size:12px}.status{font-weight:bold;color:#9a6041}pre{white-space:pre-wrap;font:inherit}</style><main><h1>問い合わせ記録</h1><p>新しい順に最大500件を表示しています。メール状態: pending は通知失敗または未設定です。</p><?php foreach ($items as $item): ?><article class="item"><p class="meta"><?=esc($item['created_at'])?> · 受付番号 <?=esc($item['reference'])?> · <span class="status"><?=esc($item['status'])?></span></p><h2><?=esc($item['subject'])?></h2><p><?=esc($item['name'])?> · <a href="mailto:<?=esc($item['email'])?>"><?=esc($item['email'])?></a></p><pre><?=esc($item['message'])?></pre><?php if ($item['mail_error']): ?><p class="meta">メール記録: <?=esc($item['mail_error'])?></p><?php endif ?></article><?php endforeach ?><?php if (!$items): ?><p>保存されたお問い合わせはありません。</p><?php endif ?></main></html>
