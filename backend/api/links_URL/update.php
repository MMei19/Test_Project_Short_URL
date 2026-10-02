<?php
require_once __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo Connection initialized by bootstrap.php. */
/** @var string $redirectUrl URL prefix initialized by bootstrap.php. */

requireMethod('PUT');

$data = readJson();

$linkId = validateLinkId($data['link_id'] ?? null);
requireOwnedLink($pdo, $linkId);

$editableFields = ['original_url', 'status', 'expires_at'];

$hasChanges = false;

foreach ($editableFields as $field) {

    if (array_key_exists($field, $data)) {

        $hasChanges = true;



        if (

            !is_string($data[$field]) &&

            !($field === 'expires_at' && $data[$field] === null)

        ) {

            throw new InvalidArgumentException(

                "$field มีชนิดข้อมูลไม่ถูกต้อง"

            );

        }

    }

}

if (!$hasChanges) {

    throw new InvalidArgumentException(

        'ต้องส่ง original_url, status หรือ expires_at'

    );

}

$pdo->beginTransaction();

$existing = lockLink($pdo, $linkId);

if ($existing === null) {

    transactionError($pdo, 'ไม่พบลิงก์', 404);

}

$link = new Links_URL($pdo, $redirectUrl);

$link->link_id = $linkId;

$link->original_url = array_key_exists('original_url', $data)

    ? $data['original_url']

    : $existing['original_url'];

$link->status = array_key_exists('status', $data)

    ? $data['status']

    : $existing['status'];

$link->expires_at = array_key_exists('expires_at', $data)

    ? $data['expires_at']

    : $existing['expires_at'];

$link->update();

$row = $link->readById($linkId)->fetch();

$pdo->commit();

sendJson([

    'message' => 'แก้ไขลิงก์สำเร็จ',

    'data' => $link->withShortUrl($row),

]);
