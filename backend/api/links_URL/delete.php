<?php

require_once __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo Connection initialized by bootstrap.php. */
/** @var string $redirectUrl URL prefix initialized by bootstrap.php. */

requireMethod('DELETE');

$data = readJson();

$linkId = validateLinkId($data['link_id'] ?? null);
requireOwnedLink($pdo, $linkId);

$pdo->beginTransaction();

if (lockLink($pdo, $linkId) === null) {

    transactionError($pdo, 'ไม่พบลิงก์', 404);

}

$events = new Click_Events($pdo);

$links = new Links_URL($pdo, $redirectUrl);

$events->deleteByLinkId($linkId);

if (!$links->deleteByLinkId($linkId)) {

    throw new RuntimeException('ลบลิงก์ไม่สำเร็จ');

}

$pdo->commit();

sendJson([

    'message' => 'ลบลิงก์และประวัติการคลิกสำเร็จ',

]);
