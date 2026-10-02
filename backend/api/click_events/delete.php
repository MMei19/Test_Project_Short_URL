<?php

require_once __DIR__ . '/../bootstrap.php';/** @var PDO $pdo Connection initialized by bootstrap.php. */

requireMethod('DELETE');

$data = readJson();
$clickId = validateClickId($data['click_id'] ?? null);

$event = new Click_Events($pdo);

$initial = $event->readById($clickId)->fetch();

if ($initial === false) {
    sendJson(['message' => 'ไม่พบประวัติการคลิก'], 404);
}

$linkId = $initial['link_id'];

$pdo->beginTransaction();

if (lockLink($pdo, $linkId) === null) {
    transactionError($pdo, 'ไม่พบลิงก์', 404);
}

$stmt = $pdo->prepare("
    SELECT link_id
    FROM click_events
    WHERE click_id = ?
    FOR UPDATE
");

$stmt->execute([$clickId]);
$current = $stmt->fetch();

if ($current === false) {
    transactionError($pdo, 'ไม่พบประวัติการคลิก', 404);
}

if ($current['link_id'] !== $linkId) {
    transactionError(
        $pdo,
        'ข้อมูลถูกแก้ไขระหว่างดำเนินการ กรุณาโหลดใหม่',
        409
    );
}

// Model เดิมยังไม่มี deleteById จึงใช้ prepared statement ที่นี่
$stmt = $pdo->prepare("
    DELETE FROM click_events
    WHERE click_id = ?
");

$stmt->execute([$clickId]);

syncClickStats($pdo, $linkId);

$pdo->commit();

sendJson([
    'message' => 'ลบประวัติการคลิกสำเร็จ',
]);