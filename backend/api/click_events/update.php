<?php
require_once __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo Connection initialized by bootstrap.php. */

requireMethod('PUT');
$data = readJson();
$clickId = validateClickId($data['click_id'] ?? null);

$hasChanges =

    array_key_exists('link_id', $data) ||

    array_key_exists('clicked_at', $data);

if (!$hasChanges) {

    throw new InvalidArgumentException(

        'ต้องส่ง link_id หรือ clicked_at'

    );

}

if (

    array_key_exists('clicked_at', $data) &&

    !is_string($data['clicked_at'])

) {

    throw new InvalidArgumentException(
        'clicked_at ต้องเป็นข้อความวันเวลา'
    );

}

$event = new Click_Events($pdo);

$initial = $event->readById($clickId)->fetch();

if ($initial === false) {
    sendJson(['message' => 'ไม่พบประวัติการคลิก'], 404);
}

$oldLinkId = $initial['link_id'];
$newLinkId = array_key_exists('link_id', $data)

    ? validateLinkId($data['link_id'])
    : $oldLinkId;

$pdo->beginTransaction();

$linkIds = array_unique([$oldLinkId, $newLinkId]);
sort($linkIds, SORT_STRING);

foreach ($linkIds as $id) {

    if (lockLink($pdo, $id) === null) {

        transactionError($pdo, 'ไม่พบลิงก์ที่เกี่ยวข้อง', 404);

    }

}

$stmt = $pdo->prepare("

    SELECT click_id, link_id, clicked_at

    FROM click_events

    WHERE click_id = ?

    FOR UPDATE

");

$stmt->execute([$clickId]);
$current = $stmt->fetch();
if ($current === false) {

    transactionError($pdo, 'ไม่พบประวัติการคลิก', 404);

}

if ($current['link_id'] !== $oldLinkId) {
    transactionError(
        $pdo,
        'ข้อมูลถูกแก้ไขระหว่างดำเนินการ กรุณาโหลดใหม่',
        409
    );

}

$event->click_id = $clickId;

$event->link_id = $newLinkId;

$event->clicked_at = array_key_exists('clicked_at', $data)

    ? $data['clicked_at']

    : $current['clicked_at'];

$event->update();

foreach ($linkIds as $id) {

    syncClickStats($pdo, $id);

}

$row = $event->readById($clickId)->fetch();

$pdo->commit();

sendJson([

    'message' => 'แก้ไขประวัติการคลิกสำเร็จ',

    'data' => $row,

]);