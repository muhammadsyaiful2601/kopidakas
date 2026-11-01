<?php
declare(strict_types=1);

function ensureSettingsTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB"
    );
}

function getSystemSetting(PDO $pdo, string $key, ?string $default = null): ?string
{
    ensureSettingsTable($pdo);
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function saveSystemSetting(PDO $pdo, string $key, string $value): void
{
    ensureSettingsTable($pdo);
    $stmt = $pdo->prepare(
        "INSERT INTO system_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->execute([$key, $value]);
}
