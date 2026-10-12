<?php
/**
 * API Endpoint: Ambil Daftar Unit Perujuk Radiologi SIK
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();
$units = [];

if ($pdo !== null) {
    try {
        $sql = "
            SELECT DISTINCT IFNULL(e.nm_poli, IF(a.status = 'ranap', 'Rawat Inap', 'Poli Registrasi')) AS unit_perujuk
            FROM permintaan_radiologi a
            INNER JOIN reg_periksa b ON a.no_rawat = b.no_rawat
            LEFT JOIN poliklinik e ON b.kd_poli = e.kd_poli
            WHERE IFNULL(e.nm_poli, '') != ''
            ORDER BY unit_perujuk ASC
        ";
        $stmt = $pdo->query($sql);
        $units = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $units = [];
    }
}

// Fallback jika database offline atau kosong
if (empty($units)) {
    $units = [
        "Fisioterapi",
        "IGD",
        "Kinik Jiwa",
        "Klinik Anak",
        "Klinik Bedah Orthopedi",
        "Klinik Bedah umum",
        "Klinik Bedah Umum (Sore)",
        "Klinik Jantung",
        "Klinik Mata",
        "Klinik MCU",
        "Klinik Obsgyn",
        "Klinik Penyakit Dalam",
        "Klinik Saraf",
        "Klinik Umum",
        "Laboratorium",
        "Radiologi",
        "Rawat Inap"
    ];
}

echo json_encode([
    'status' => 'success',
    'total'  => count($units),
    'data'   => $units
]);
