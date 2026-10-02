<?php

require_once __DIR__ . '/../bootstrap.php';/** @var PDO $pdo Connection initialized by bootstrap.php. */

requireMethod('GET');

$event = new Click_Events($pdo);

if (isset($_GET['link_id'])) {
    $linkId = validateLinkId($_GET['link_id']);

    $stmt = $event->readByLinkId($linkId);
} else {
    $stmt = $event->read();
}

sendJson([
    'data' => $stmt->fetchAll(),
]);