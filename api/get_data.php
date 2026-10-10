<?php
/**
 * API Ekstraktor Data Radiologi SIK RSUSISS
 * Sesuai Dokumen PRD & Kueri Navicat Pengguna:
 * - Menampilkan 11 Kolom Data Radiologi (Ranap & Ralan)
 * - Filter Tanggal Permintaan (tgl_mulai & tgl_akhir)
 * - Filter Jenis Rawat (status = 'ranap' / 'ralan' / 'SEMUA')
 * - Filter Jenis Pemeriksaan Radiologi
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';

// Ambil parameter filter dari request GET / POST
$tglMulai = filter_input(INPUT_GET, 'tgl_mulai', FILTER_SANITIZE_SPECIAL_CHARS) 
         ?: filter_input(INPUT_POST, 'tgl_mulai', FILTER_SANITIZE_SPECIAL_CHARS) 
         ?: date('Y-m-01');

$tglAkhir = filter_input(INPUT_GET, 'tgl_akhir', FILTER_SANITIZE_SPECIAL_CHARS) 
         ?: filter_input(INPUT_POST, 'tgl_akhir', FILTER_SANITIZE_SPECIAL_CHARS) 
         ?: date('Y-m-d');

$statusRawat = filter_input(INPUT_GET, 'status_rawat', FILTER_SANITIZE_SPECIAL_CHARS) 
            ?: filter_input(INPUT_POST, 'status_rawat', FILTER_SANITIZE_SPECIAL_CHARS) 
            ?: 'SEMUA'; // 'SEMUA', 'ralan', 'ranap'

$kategori = filter_input(INPUT_GET, 'kategori', FILTER_SANITIZE_SPECIAL_CHARS) 
         ?: filter_input(INPUT_POST, 'kategori', FILTER_SANITIZE_SPECIAL_CHARS) 
         ?: 'SEMUA';

// Validasi format tanggal YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglMulai)) {
    $tglMulai = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) {
    $tglAkhir = date('Y-m-d');
}

$dbStatus = Database::getStatus();
$pdo = Database::getConnection();

$dataResults = [];
$isMock = false;

if ($pdo !== null) {
    try {
        // Query persis sesuai Navicat permintaan_radiologi RSUSISS
        $sql = "
            SELECT 
                a.noorder AS no_permintaan, 
                a.no_rawat, 
                CONCAT(c.no_rkm_medis, ' ', c.nm_pasien) AS pasien,
                jpr.nm_perawatan AS pemeriksaan, 
                a.tgl_permintaan AS tgl_pemeriksaan,
                a.jam_permintaan AS jam_pemeriksaan,
                IFNULL(a.tgl_sampel, '') AS tgl_selesai,
                IFNULL(a.jam_sampel, '') AS jam_selesai,
                IFNULL(a.tgl_hasil, '') AS tgl_expertise,
                IFNULL(a.jam_hasil, '') AS jam_expertise,
                IFNULL(d.nm_dokter, '-') AS dokter_perujuk,
                IFNULL(e.nm_poli, IF(a.status = 'ranap', 'Rawat Inap', 'Poli Registrasi')) AS unit_perujuk,
                a.informasi_tambahan,
                a.diagnosa_klinis,
                IFNULL(f.png_jawab, '-') AS jenis_bayar,
                a.status AS status_rawat
            FROM permintaan_radiologi a
            INNER JOIN permintaan_pemeriksaan_radiologi ppr ON a.noorder = ppr.noorder
            INNER JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
            INNER JOIN reg_periksa b ON a.no_rawat = b.no_rawat
            INNER JOIN pasien c ON b.no_rkm_medis = c.no_rkm_medis
            LEFT JOIN dokter d ON a.dokter_perujuk = d.kd_dokter
            LEFT JOIN poliklinik e ON b.kd_poli = e.kd_poli
            LEFT JOIN penjab f ON b.kd_pj = f.kd_pj
            WHERE a.tgl_permintaan BETWEEN :tgl_mulai AND :tgl_akhir
        ";

        $params = [
            ':tgl_mulai' => $tglMulai,
            ':tgl_akhir' => $tglAkhir
        ];

        // Filter Rawat Inap / Rawat Jalan
        if ($statusRawat === 'ranap') {
            $sql .= " AND a.status = 'ranap' ";
        } elseif ($statusRawat === 'ralan') {
            $sql .= " AND a.status = 'ralan' ";
        }

        // Filter modalitas / tindakan radiologi jika dipilih spesifik
        if ($kategori !== 'SEMUA' && $kategori !== 'RADIOLOGI') {
            $sql .= " AND jpr.nm_perawatan LIKE :pemeriksaan ";
            $params[':pemeriksaan'] = '%' . $kategori . '%';
        }

        $sql .= " ORDER BY a.tgl_permintaan DESC, a.jam_permintaan DESC LIMIT 3000";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $no = 1;
        foreach ($rows as $r) {
            $dataResults[] = [
                'no'                         => $no++,
                'no_permintaan'              => $r['no_permintaan'],
                'no_rawat'                   => $r['no_rawat'],
                'nm_pasien'                  => $r['pasien'],
                'pemeriksaan'                => $r['pemeriksaan'],
                'tgl_pemeriksaan'            => $r['tgl_pemeriksaan'],
                'jam_pemeriksaan'            => $r['jam_pemeriksaan'],
                'tgl_selesai_pemeriksaan'    => $r['tgl_selesai'] ?: '-',
                'waktu_selesai_pemeriksaan'  => $r['jam_selesai'] ?: '-',
                'tgl_expertise'              => $r['tgl_expertise'] ?: '-',
                'waktu_expertise'            => $r['jam_expertise'] ?: '-',
                'dokter_pengirim'            => $r['dokter_perujuk'] ?: '-',
                'unit_perujuk'               => $r['unit_perujuk'] ?: '-',
                'status_rawat'               => $r['status_rawat'],
                'jenis_bayar'                => $r['jenis_bayar'],
                'diagnosa_klinis'            => $r['diagnosa_klinis'] ?? ''
            ];
        }
    } catch (Exception $e) {
        $isMock = true;
        $dataResults = generateMockData($tglMulai, $tglAkhir, $statusRawat, $kategori);
    }
} else {
    $isMock = true;
    $dataResults = generateMockData($tglMulai, $tglAkhir, $statusRawat, $kategori);
}

echo json_encode([
    'status'     => 'success',
    'is_mock'    => $isMock,
    'message'    => $isMock 
        ? 'Data simulasi aktif (Server DB 192.168.1.2 belum terhubung di intranet saat ini)' 
        : 'Data berhasil ditarik secara real-time dari database SIK (192.168.1.2)',
    'db_info'    => $dbStatus,
    'filter'     => [
        'tgl_mulai'    => $tglMulai,
        'tgl_akhir'    => $tglAkhir,
        'status_rawat' => $statusRawat,
        'kategori'     => $kategori
    ],
    'total'      => count($dataResults),
    'data'       => $dataResults
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

/**
 * Generator data simulasi SIK RSUSISS untuk pengujian dan demo tanpa database online
 */
function generateMockData(string $tglMulai, string $tglAkhir, string $statusRawat, string $kategori): array {
    $pasienList = [
        ['001920', 'Siti Rahmawati'],
        ['001921', 'Budi Santoso'],
        ['001922', 'Ahmad Fauzi'],
        ['001923', 'Dewi Lestari'],
        ['001924', 'Eko Prasetyo'],
        ['001925', 'Sri Wahyuni'],
        ['001926', 'Hendra Gunawan'],
        ['001927', 'Nur Hidayah'],
        ['001928', 'Rian Hidayat'],
        ['001929', 'Tri Mulyani'],
        ['001930', 'Bambang Supriyanto'],
        ['001931', 'Kartika Sari'],
        ['001932', 'Muhammad Ilham'],
        ['001933', 'Ratna Juwita'],
        ['001934', 'Agus Setiawan'],
        ['001935', 'Lia Anggraini'],
        ['001936', 'Dedi Kurniawan'],
        ['001937', 'Yuliana Putri'],
        ['001938', 'Fajar Pratama'],
        ['001939', 'Mega Wati'],
        ['001940', 'Irfan Hakim'],
        ['001941', 'Anisa Rahma'],
        ['001942', 'Rahmat Hidayat'],
        ['001943', 'Fitri Handayani'],
        ['001944', 'Wawan Gunawan']
    ];

    $dokterPengirimList = [
        'dr. Anita Rahayu, Sp.PD',
        'dr. Bagus Triyanto, Sp.B',
        'dr. Cahyo Nugroho, Sp.A',
        'dr. Diana Permata, Sp.OG',
        'dr. Hendra Setiawan, Sp.S',
        'dr. Maya Kusuma, Sp.P',
        'dr. Rizky Pratama, Sp.OT'
    ];

    $unitRalan = [
        'Poli Penyakit Dalam',
        'Poli Bedah Umum',
        'Poli Saraf',
        'Poli Kebidanan & Kandungan',
        'Poli Paru',
        'Poli Ortopedi',
        'IGD (Gawat Darurat)'
    ];

    $unitRanap = [
        'Bangsal Melati (Rawat Inap)',
        'Bangsal Teratai (Rawat Inap)',
        'Bangsal Dahlia',
        'Ruang ICU / HCU',
        'Paviliun VIP Cempaka',
        'Bangsal Anggrek'
    ];

    $radiologiProcedures = [
        'Thorax' => [
            'Thorax Foto AP/PA',
            'Thorax Foto 2 Posisi (AP/Lat)',
            'Thorax Foto Top Lordotik'
        ],
        'USG' => [
            'USG Abdomen Upper-Lower',
            'USG Kebidanan (Fetomaternal)',
            'USG Urologi (Ginjal-Buli)',
            'USG Thyroid / Colli',
            'USG Mammae Dextra/Sinistra'
        ],
        'CT-Scan' => [
            'CT-Scan Kepala Non-Kontras',
            'CT-Scan Kepala Kontras',
            'CT-Scan Abdomen 3 Fase',
            'CT-Scan Sinus Paranasal (SPN)'
        ],
        'Extremitas' => [
            'Foto Extremitas Atas (Antebrachii/Humerus)',
            'Foto Extremitas Bawah (Femur/Cruris)',
            'Foto Genu AP/Lat',
            'Foto Manus / Pedis AP/Oblique'
        ],
        'Panoramic' => [
            'Panoramic Gigi & Cephalometri',
            'Panoramic Dental Orthodontic'
        ],
        'Lumbosacral' => [
            'Lumbosacral AP/Lateral',
            'Vertebra Cervical AP/Lat/Oblique',
            'Vertebra Thoracolumbal AP/Lat'
        ]
    ];

    $allTindakan = [];
    foreach ($radiologiProcedures as $modalityList) {
        foreach ($modalityList as $tind) {
            $allTindakan[] = $tind;
        }
    }

    $results = [];
    $startTs = strtotime($tglMulai);
    $endTs = strtotime($tglAkhir);

    if ($endTs < $startTs) {
        $temp = $startTs;
        $startTs = $endTs;
        $endTs = $temp;
    }

    $daysDiff = max(1, min(60, (int)(($endTs - $startTs) / 86400) + 1));
    $totalRows = min(150, max(20, $daysDiff * 5));

    $counter = 1;
    for ($i = 0; $i < $totalRows; $i++) {
        // Tentukan status rawat (ranap / ralan)
        if ($statusRawat === 'ranap') {
            $currStatus = 'ranap';
        } elseif ($statusRawat === 'ralan') {
            $currStatus = 'ralan';
        } else {
            // Campuran jika 'SEMUA'
            $currStatus = ($i % 2 === 0) ? 'ralan' : 'ranap';
        }

        $pIndex = $i % count($pasienList);
        $pasien = $pasienList[$pIndex];

        // Pilih pemeriksaan sesuai kategori filter
        if (isset($radiologiProcedures[$kategori])) {
            $tindakanList = $radiologiProcedures[$kategori];
            $tindakan = $tindakanList[$i % count($tindakanList)];
        } else {
            $tindakan = $allTindakan[$i % count($allTindakan)];
        }

        // Acak tanggal permintaan di antara rentang filter
        $randomDayOffset = ($totalRows > 1) ? (int)(($i / $totalRows) * ($endTs - $startTs)) : 0;
        $tglPermintaan = date('Y-m-d', $startTs + $randomDayOffset);
        
        $jam = 8 + ($i % 10);
        $menit = ($i * 7) % 60;
        $detik = ($i * 13) % 60;
        $jamPermintaan = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);

        // Waktu selesai pemeriksaan (+10 s/d 25 menit)
        $menitSelesai = ($menit + 15 + ($i % 10)) % 60;
        $jamSelesaiHour = $jam + (int)(($menit + 15 + ($i % 10)) / 60);
        $jamSelesai = sprintf('%02d:%02d:%02d', $jamSelesaiHour, $menitSelesai, ($detik + 20) % 60);

        // Waktu expertise (+15 s/d 30 menit setelah selesai)
        $menitExp = ($menitSelesai + 20) % 60;
        $jamExpHour = $jamSelesaiHour + (int)(($menitSelesai + 20) / 60);
        $jamExpertise = sprintf('%02d:%02d:%02d', $jamExpHour, $menitExp, ($detik + 40) % 60);

        $dokter = $dokterPengirimList[$i % count($dokterPengirimList)];
        $unit = ($currStatus === 'ranap') 
            ? $unitRanap[$i % count($unitRanap)]
            : $unitRalan[$i % count($unitRalan)];

        $noOrder = 'PR' . str_replace('-', '', $tglPermintaan) . sprintf('%04d', $i + 1);
        $noRawat = str_replace('-', '/', $tglPermintaan) . '/' . sprintf('%06d', $i + 1);

        $results[] = [
            'no'                         => $counter++,
            'no_permintaan'              => $noOrder,
            'no_rawat'                   => $noRawat,
            'nm_pasien'                  => $pasien[0] . ' ' . $pasien[1],
            'pemeriksaan'                => $tindakan,
            'tgl_pemeriksaan'            => $tglPermintaan,
            'jam_pemeriksaan'            => $jamPermintaan,
            'tgl_selesai_pemeriksaan'    => $tglPermintaan,
            'waktu_selesai_pemeriksaan'  => $jamSelesai,
            'tgl_expertise'              => $tglPermintaan,
            'waktu_expertise'            => $jamExpertise,
            'dokter_pengirim'            => $dokter,
            'unit_perujuk'               => $unit,
            'status_rawat'               => $currStatus,
            'jenis_bayar'                => ($i % 3 === 0) ? 'Umum / Mandiri' : 'BPJS Kesehatan',
            'diagnosa_klinis'            => 'Pemeriksaan Radiologi Penunjang'
        ];
    }

    // Urutkan berdasarkan tanggal permintaan terbaru
    usort($results, function($a, $b) {
        $cmp = strcmp($b['tgl_pemeriksaan'], $a['tgl_pemeriksaan']);
        if ($cmp !== 0) return $cmp;
        return strcmp($b['jam_pemeriksaan'], $a['jam_pemeriksaan']);
    });

    for ($j = 0; $j < count($results); $j++) {
        $results[$j]['no'] = $j + 1;
    }

    return $results;
}
