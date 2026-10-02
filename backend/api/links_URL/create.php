<?php

require_once __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo Connection initialized by bootstrap.php. */
/** @var string $redirectUrl URL prefix initialized by bootstrap.php. */

requireMethod('POST');

$data = readJson();

$originalUrl = $data['original_url'] ?? null;

$shortCode = $data['short_code'] ?? null;

$expiry = $data['expiry'] ?? 'never';

if (!is_string($originalUrl)) {

    throw new InvalidArgumentException(

        'ต้องระบุ original_url'

    );

}

if ($shortCode !== null && !is_string($shortCode)) {

    throw new InvalidArgumentException(

        'short_code ต้องเป็นข้อความหรือ null'

    );

}

if (

    !is_string($expiry) ||

    !in_array($expiry, ['never', '7', '30', '365'], true)

) {

    throw new InvalidArgumentException(

        'expiry ต้องเป็น never, 7, 30 หรือ 365'

    );

}

$link = new Links_URL($pdo, $redirectUrl);

$link->original_url = $originalUrl;

$link->short_code = $shortCode;

$link->status = 'active';
$link->owner_hash = browserOwnerHash();



$link->expires_at = $expiry === 'never'

    ? '9999-12-31'

    : gmdate('Y-m-d', time() + (int) $expiry * 86400);

$link->create();

$row = $link->readById($link->link_id)->fetch();

sendJson([

    'message' => 'สร้างลิงก์สำเร็จ',

    'data' => $link->withShortUrl($row),

], 201);
