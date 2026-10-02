<?php
final class Database
{
    public function getConnection()
    {
        $localConfigPath = __DIR__ . '/database.local.php';
        $localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
        if (!is_array($localConfig)) {
            throw new RuntimeException('Invalid local database configuration');
        }
        $isProduction = isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'short-url.lnw.mn') !== false;
        $dsn          = getenv('DB_DSN') ?: ($isProduction
                ? 'mysql:host=localhost;dbname=shorturlln_short_url;charset=utf8mb4'
                : 'sqlite:' . __DIR__ . '/../data/short_url.sqlite');
        $username = getenv('DB_USERNAME') ?: ($isProduction ? 'shorturlln_short_url' : null);
        $password = getenv('DB_PASSWORD') ?: ($localConfig['password'] ?? null);
        if (strpos($dsn, 'sqlite:') === 0) {
            $directory = dirname(substr($dsn, 7));
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

        }
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            self::ensureSchema($pdo);
            self::ensureOwnerColumn($pdo);
        } else {
            self::migrateMysqlSchema($pdo);
            self::ensureOwnerColumn($pdo);
            if ($isProduction) {
                $pdo->exec("UPDATE links_URL SET short_url = CONCAT('https://short-url.lnw.mn/', short_code) WHERE short_url LIKE '%/api/redirect.php?code=%'");
            }

        }
        return $pdo;
    }
    private static function migrateMysqlSchema(PDO $pdo)
    {
        $columns = $pdo->query('SHOW COLUMNS FROM links_URL')->fetchAll(PDO::FETCH_COLUMN);
        if (! in_array('expries_at', $columns, true)) {
            return;
        }

        $pdo->exec("ALTER TABLE links_URL MODIFY short_code VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL, MODIFY short_url TEXT CHARACTER SET utf8mb4 NOT NULL, MODIFY original_url TEXT CHARACTER SET utf8mb4 NOT NULL, MODIFY clicks INT NOT NULL DEFAULT 0, MODIFY status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL DEFAULT 'active'");
        $pdo->exec("UPDATE links_URL SET status='active'");
        $pdo->exec("ALTER TABLE links_URL MODIFY status ENUM('active','inactive') CHARACTER SET utf8mb4 NOT NULL DEFAULT 'active', CHANGE expries_at expires_at DATE NULL, MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, MODIFY last_clicked_at DATETIME NULL");
        $pdo->exec("ALTER TABLE click_events MODIFY clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }
    private static function ensureSchema(PDO $pdo)
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $id     = $driver === 'mysql' ? 'INTEGER PRIMARY KEY AUTO_INCREMENT' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $pdo->exec("CREATE TABLE IF NOT EXISTS links_URL (id $id, link_id VARCHAR(5) NOT NULL UNIQUE, short_code VARCHAR(32) NOT NULL UNIQUE, short_url TEXT NOT NULL, original_url TEXT NOT NULL, clicks INTEGER NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'active', expires_at DATE NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, last_clicked_at DATETIME NULL, owner_hash CHAR(64) NULL)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS click_events (id $id, click_id VARCHAR(6) NOT NULL UNIQUE, link_id VARCHAR(5) NOT NULL, clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (link_id) REFERENCES links_URL(link_id) ON DELETE CASCADE)");
        try { $pdo->exec('CREATE INDEX idx_click_events_link_id ON click_events(link_id)');} catch (PDOException $e) {}
    }

    private static function ensureOwnerColumn(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $columns = $pdo->query('PRAGMA table_info(links_URL)')->fetchAll();
            $names = array_column($columns, 'name');
        } else {
            $names = $pdo->query('SHOW COLUMNS FROM links_URL')->fetchAll(PDO::FETCH_COLUMN);
        }
        if (!in_array('owner_hash', $names, true)) {
            $pdo->exec('ALTER TABLE links_URL ADD COLUMN owner_hash CHAR(64) NULL');
        }
    }
}
$pdo = (new Database())->getConnection();
