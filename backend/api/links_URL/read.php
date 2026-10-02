<?php

require_once __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo Connection initialized by bootstrap.php. */
/** @var string $redirectUrl URL prefix initialized by bootstrap.php. */

requireMethod('GET');

$link = new Links_URL($pdo, $redirectUrl);

$rows = array_map([$link, 'withShortUrl'], $link->readForOwner(browserOwnerHash())->fetchAll());

sendJson(['data' => $rows]);
