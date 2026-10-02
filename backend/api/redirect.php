<?php
require_once __DIR__ . '/bootstrap.php';
requireMethod('GET');
/** @var PDO $pdo Connection initialized by bootstrap.php. */
$code = trim((string) ($_GET['code'] ?? ''));if ($code === '') {
    sendJson(['message' => 'ไม่พบรหัสลิงก์'], 400);
}

$s = $pdo->prepare('SELECT link_id,short_code,short_url,original_url,clicks,status,expires_at,created_at,last_clicked_at FROM links_URL WHERE short_code=?');
$s->execute([$code]);
$link = $s->fetch();if (! $link) {
    sendJson(['message' => 'ไม่พบลิงก์'], 404);
}

if (! in_array($link['status'], ['active', 'ใช้งาน'], true) || ($link['expires_at'] && $link['expires_at'] < gmdate('Y-m-d'))) {
    sendJson(['message' => 'ลิงก์นี้หมดอายุหรือถูกปิดใช้งาน'], 410);
}

$pdo->beginTransaction();
$event          = new Click_Events($pdo);
$event->link_id = $link['link_id'];
$event->create();
syncClickStats($pdo, $link['link_id']);
$pdo->commit();
header('Content-Type: text/html; charset=utf-8');
header('Location: ' . $link['original_url'], true, 302);exit;