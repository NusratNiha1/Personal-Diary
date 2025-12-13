<?php
require_once __DIR__ . '/../config/db.php';

function db(): PDO {
    return get_pdo();
}

function db_all(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_one(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_exec(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function db_last_id(): string {
    return db()->lastInsertId();
}

function db_tx(callable $fn, int $retries = 2) {
    $attempt = 0;
    do {
        try {
            $pdo = db();
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }
            $result = $fn($pdo);
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            return $result;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $sqlState = ($e instanceof PDOException && isset($e->errorInfo[0])) ? $e->errorInfo[0] : null;
            $driverCode = ($e instanceof PDOException && isset($e->errorInfo[1])) ? $e->errorInfo[1] : null;
            // MySQL deadlock (1213) or lock wait timeout (1205) or serialization failures
            $isRetryable = ($sqlState === '40001') || ($sqlState === 'HY000' && in_array((int)$driverCode, [1205,1213], true));
            if ($attempt < $retries && $isRetryable) {
                usleep((int) (100000 * pow(2, $attempt))); // 100ms, 200ms, ...
                $attempt++;
                continue;
            }
            // Log and rethrow
            error_log('[DB] Tx failed: ' . $e->getMessage());
            throw $e;
        }
    } while ($attempt <= $retries);
}

function db_ensure_user_profile_columns(): void {
    // Ensure extended columns exist on users table
    $cols = db_all("SELECT column_name FROM information_schema.COLUMNS WHERE table_schema = DATABASE() AND table_name = 'users'");
    $have = array_map(fn($r) => strtolower($r['column_name']), $cols);
    $alters = [];
    if (!in_array('full_name', $have, true)) {
        $alters[] = 'ADD COLUMN full_name VARCHAR(100) NULL AFTER username';
    }
    if (!in_array('date_of_birth', $have, true)) {
        $alters[] = 'ADD COLUMN date_of_birth DATE NULL AFTER full_name';
    }
    if (!in_array('profile_pic', $have, true)) {
        $alters[] = 'ADD COLUMN profile_pic VARCHAR(255) NULL AFTER date_of_birth';
    }
    if (!empty($alters)) {
        $sql = 'ALTER TABLE users ' . implode(', ', $alters);
        try { db_exec($sql); } catch (Throwable $e) { error_log('[DB] ALTER users failed: ' . $e->getMessage()); }
    }
}
