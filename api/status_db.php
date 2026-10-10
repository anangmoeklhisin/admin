<?php
/**
 * API Status Koneksi Server Database SIK
 * Sesuai Dokumen PRD:
 * - Menampilkan status split server (192.168.1.36 -> 192.168.1.2)
 * - Cek ketersediaan port 3306 & database sik
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$status = Database::getStatus();
echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
