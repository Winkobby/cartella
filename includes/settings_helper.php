<?php
// Simple settings helper: creates a `settings` table and provides get/set helpers
class SettingsHelper
{
    public static function init(PDO $pdo)
    {
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(191) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'string',
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $pdo->exec($sql);
    }

    public static function get(PDO $pdo, string $key, $default = null)
    {
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return $default;
        $val = $row['setting_value'];
        // attempt JSON decode for arrays/objects
        $decoded = json_decode($val, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $val;
    }

    public static function set(PDO $pdo, string $key, $value, string $type = 'string', string $description = '')
    {
        if (is_array($value) || is_object($value)) {
            $store = json_encode($value);
        } else {
            $store = (string)$value;
        }

        // upsert
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type, description)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), description = VALUES(description)");

        return $stmt->execute([$key, $store, $type, $description]);
    }

    public static function getAll(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $val = $r['setting_value'];
            $decoded = json_decode($val, true);
            $out[$r['setting_key']] = json_last_error() === JSON_ERROR_NONE ? $decoded : $val;
        }
        return $out;
    }
}

?>
