<?php
require_once __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo Connection initialized by bootstrap.php. */

requireMethod('GET');
$event = new Click_Events($pdo);
if (isset($_GET['link_id'])) {
    
    $linkId = validateLinkId($_GET['link_id']);
    requireOwnedLink($pdo, $linkId);
    $stmt = $event->readByLinkId($linkId);
    
} else {
    $stmt = $pdo->prepare('SELECT c.click_id, c.link_id, c.clicked_at FROM click_events c JOIN links_URL l ON l.link_id = c.link_id WHERE l.owner_hash = ? ORDER BY c.clicked_at DESC');
    $stmt->execute([browserOwnerHash()]);

}

sendJson([

    'data' => $stmt->fetchAll(),

]);
