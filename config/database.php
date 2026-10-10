<?php
/**
 * Konfigurasi Koneksi Database SIK (Split Server Architecture)
 * Sesuai Dokumen PRD:
 * - Web Server: 192.168.1.36 (Windows XAMPP Apache, PHP 8.2+)
 * - Database Server: 192.168.1.2 (Linux/MySQL Database: sik)
 * - Keamanan (NF.01): Read-Only user (report_viewer)
 */

define('DB_HOST', '192.168.1.2');
define('DB_PORT', '3306');
define('DB_NAME', 'sik');
define('DB_USER', 'report_viewer');
define('DB_PASS', 'PasswordReport123!');
define('DB_CHARSET', 'utf8mb4');
define('DB_TIMEOUT', 3); // Timeout detik untuk koneksi remote intranet

class Database {
    private static ?PDO $instance = null;
    private static ?string $connectionError = null;
    private static bool $isMockMode = false;

    /**
     * Mendapatkan koneksi PDO
     */
    public static function getConnection(): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => DB_TIMEOUT,
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            self::$connectionError = null;
            self::$isMockMode = false;
            return self::$instance;
        } catch (PDOException $e) {
            self::$connectionError = $e->getMessage();
            self::$isMockMode = true; // Aktifkan mode simulasi saat server remote belum terjangkau
            return null;
        }
    }

    /**
     * Mendapatkan status koneksi database
     */
    public static function getStatus(): array {
        $conn = self::getConnection();
        return [
            'connected'  => ($conn !== null),
            'host'       => DB_HOST,
            'port'       => DB_PORT,
            'database'   => DB_NAME,
            'user'       => DB_USER,
            'is_mock'    => self::$isMockMode,
            'error'      => self::$connectionError,
            'mode_info'  => ($conn !== null) 
                ? 'Terhubung langsung ke Server Database SIK (192.168.1.2)' 
                : 'Server 192.168.1.2 belum terjangkau. Menggunakan data simulasi sistem RSUSISS untuk pengujian.',
        ];
    }
}
