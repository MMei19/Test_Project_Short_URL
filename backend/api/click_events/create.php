<?php

require_once __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo Connection initialized by bootstrap.php. */

requireMethod('POST');
$data = readJson();
$linkId = validateLinkId($data['link_id'] ?? null);
$pdo->beginTransaction();
$link = lockLink($pdo, $linkId);
if ($link === null) {
    transactionError($pdo, 'ไม่พบลิงก์', 404);
}
if (
    !in_array($link['status'], ['active', 'ใช้งาน'], true) ||
    ($link['expires_at'] !== null && $link['expires_at'] < gmdate('Y-m-d'))
) {
    transactionError($pdo, 'ลิงก์หมดอายุหรือใช้งานไม่ได้', 410);
}
$event = new Click_Events($pdo);
$event->link_id = $linkId;
$event->create();
syncClickStats($pdo, $linkId);
$row = $event->readById($event->click_id)->fetch();
$pdo->commit();

sendJson([
    'message' => 'บันทึกการคลิกสำเร็จ',
    'data' => $row,
], 201);