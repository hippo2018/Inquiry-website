<?php
require_once __DIR__ . '/../bootstrap.php';
secure_headers();
session_name('inquiry_session');
session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Strict', 'path' => '/']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && ($_GET['action'] ?? '') === 'token') {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    json_response(200, ['csrf' => $_SESSION['csrf']]);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: GET, POST'); json_response(405, ['ok'=>false,'message'=>'許可されていない操作です。']); }
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) json_response(400, ['ok'=>false,'message'=>'入力内容を読み取れませんでした。']);
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf'] ?? '');
if (empty($_SESSION['csrf']) || !is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) json_response(403, ['ok'=>false,'message'=>'ページの有効期限が切れました。再読み込みしてお試しください。']);
if (!empty($input['website'])) json_response(200, ['ok'=>true,'message'=>'お問い合わせを受け付けました。']);
$config = inquiry_config();
$window = (int)($config['rate_window_seconds'] ?? 3600);
$now = time(); $_SESSION['submissions'] = array_values(array_filter($_SESSION['submissions'] ?? [], fn($t) => $t > $now - $window));
if (count($_SESSION['submissions']) >= (int)$config['rate_limit']) json_response(429, ['ok'=>false,'message'=>'送信回数が上限に達しました。時間をおいてお試しください。']);
$name = trim((string)($input['name'] ?? '')); $email = trim((string)($input['email'] ?? '')); $subject = trim((string)($input['subject'] ?? '')); $message = trim((string)($input['message'] ?? ''));
$errors = [];
if ($name === '' || mb_strlen($name) > 100) $errors[] = 'お名前は1〜100文字で入力してください。';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || preg_match('/[\r\n]/', $email)) $errors[] = '有効なメールアドレスを入力してください。';
if ($subject === '' || mb_strlen($subject) > 200 || preg_match('/[\r\n]/', $subject)) $errors[] = '件名は1〜200文字で入力してください。';
if ($message === '' || mb_strlen($message) > 10000) $errors[] = 'お問い合わせ内容は1〜10,000文字で入力してください。';
if ($errors) json_response(422, ['ok'=>false,'message'=>implode(' ', $errors)]);
$reference = strtoupper(bin2hex(random_bytes(5))); $created = date('Y-m-d H:i:s');
try {
    $stmt = inquiry_db()->prepare('INSERT INTO inquiries (reference,name,email,subject,message,status,created_at) VALUES (:reference,:name,:email,:subject,:message,"mail_pending",:created)');
    $stmt->execute([':reference'=>$reference,':name'=>$name,':email'=>$email,':subject'=>$subject,':message'=>$message,':created'=>$created]);
} catch (Throwable $e) { error_log('Inquiry database save failed: ' . $e->getMessage()); json_response(500, ['ok'=>false,'message'=>'保存に失敗しました。時間をおいて再度お試しください。']); }
$_SESSION['submissions'][] = $now;
$to = (string)$config['recipient']; $from = (string)$config['from_email']; $mailOk = false; $mailError = null;
if (filter_var($to, FILTER_VALIDATE_EMAIL) && filter_var($from, FILTER_VALIDATE_EMAIL)) {
    $encodedSubject = mb_encode_mimeheader('[お問い合わせ] ' . $subject, 'UTF-8');
    $body = "受付番号: {$reference}\n日時: {$created}\nお名前: {$name}\nメールアドレス: {$email}\n件名: {$subject}\n\n{$message}\n";
    $headers = 'From: ' . mb_encode_mimeheader((string)$config['from_name'], 'UTF-8') . " <{$from}>\r\n" . "Reply-To: {$email}\r\n" . "Content-Type: text/plain; charset=UTF-8\r\n";
    try { $mailOk = mail($to, $encodedSubject, $body, $headers); } catch (Throwable $e) { $mailError = $e->getMessage(); }
} else { $mailError = 'Mail recipient or sender is not configured'; }
$status = $mailOk ? 'sent' : 'mail_pending'; $detail = $mailOk ? null : ($mailError ?: 'mail() returned false');
try { $update = inquiry_db()->prepare('UPDATE inquiries SET status=:status,mail_error=:error WHERE reference=:reference'); $update->execute([':status'=>$status,':error'=>$detail,':reference'=>$reference]); } catch (Throwable $e) { error_log('Inquiry status update failed: ' . $e->getMessage()); }
$_SESSION['csrf'] = bin2hex(random_bytes(32));
if (!$mailOk) error_log('Inquiry mail delivery failed for reference ' . $reference . ': ' . $detail);
json_response(200, ['ok'=>true,'message'=>$mailOk ? 'お問い合わせを受け付けました。' : 'お問い合わせを保存しました。メール通知の送信に失敗しましたが、内容は保存されています。','reference'=>$reference,'mail_sent'=>$mailOk]);


