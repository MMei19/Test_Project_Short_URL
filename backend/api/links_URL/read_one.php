<?php

require_once __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo Connection initialized by bootstrap.php. */
/** @var string $redirectUrl URL prefix initialized by bootstrap.php. */

requireMethod('GET');

$linkId = validateLinkId($_GET['link_id'] ?? null);

$link = new Links_URL($pdo, $redirectUrl);

$row = $link->readById($linkId)->fetch();

if ($row === false) {

    sendJson(['message' => 'ไม่พบลิงก์'], 404);

}

sendJson(['data' => $link->withShortUrl($row)]);