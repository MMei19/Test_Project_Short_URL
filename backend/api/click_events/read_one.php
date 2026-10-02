<?php
require_once __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo Connection initialized by bootstrap.php. */

requireMethod('GET');
$clickId = validateClickId($_GET['click_id'] ?? null);
$event = new Click_Events($pdo);
$row = $event->readById($clickId)->fetch();
if ($row === false) {
    sendJson(['message' => 'ไม่พบประวัติการคลิก'], 404);
}
requireOwnedLink($pdo, $row['link_id']);
sendJson(['data' => $row]);
