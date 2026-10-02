<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin)) {header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {http_response_code(204);exit;}
function sendJson(array $data, int $status = 200)
{http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);exit;}
function requireMethod(string $method): void
{if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {header("Allow: $method, OPTIONS");
    sendJson(['message' => "ต้องเรียกด้วย $method"], 405);}}
function readJson(): array
{try { $data = json_decode(file_get_contents('php://input') ?: '{}', true, 512, JSON_THROW_ON_ERROR);} catch (JsonException $e) {sendJson(['message' => 'JSON ไม่ถูกต้อง'], 400);}if (! is_array($data)) {
    sendJson(['message' => 'ข้อมูลไม่ถูกต้อง'], 400);
}
    return $data;}
function validateLinkId($v): string
{if (! is_string($v) || ! preg_match('/^L[0-9]{4,}$/', $v)) {
    throw new InvalidArgumentException('link_id ไม่ถูกต้อง');
}
    return $v;}
function validateClickId($v): string
{if (! is_string($v) || ! preg_match('/^C[0-9]{5,}$/', $v)) {
    throw new InvalidArgumentException('click_id ไม่ถูกต้อง');
}
    return $v;}
function baseUrl(): string
{$scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') . '/';}
function lockLink(PDO $pdo, string $id): ?array
{$s = $pdo->prepare('SELECT link_id,short_code,short_url,original_url,clicks,status,expires_at,created_at,last_clicked_at FROM links_URL WHERE link_id=?');
    $s->execute([$id]);return $s->fetch() ?: null;}
function syncClickStats(PDO $pdo, string $id): void
{$s = $pdo->prepare('UPDATE links_URL SET clicks=(SELECT COUNT(*) FROM click_events WHERE link_id=?),last_clicked_at=(SELECT MAX(clicked_at) FROM click_events WHERE link_id=?) WHERE link_id=?');
    $s->execute([$id, $id, $id]);}
function transactionError(PDO $pdo, string $message, int $status)
{if ($pdo->inTransaction()) {
    $pdo->rollBack();
}

    sendJson(['message' => $message], $status);}
set_exception_handler(function (Throwable $e): void {global $pdo;if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
}
    error_log('[' . gmdate('c') . '] ' . (string) $e . PHP_EOL, 3, __DIR__ . '/../config/app-error.log');if ($e instanceof InvalidArgumentException) {
        sendJson(['message' => $e->getMessage()], 400);
    }
    if ($e instanceof PDOException && in_array((string) $e->getCode(), ['23000', '19'], true)) {
        sendJson(['message' => 'รหัสลิงก์หรือนามแฝงซ้ำ กรุณาลองใหม่'], 409);
    }
    sendJson(['message' => 'ระบบฐานข้อมูลเกิดข้อผิดพลาด'], 500);});
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modals/Links_URL.php';
require_once __DIR__ . '/../modals/Click_Events.php';
$redirectUrl = baseUrl();