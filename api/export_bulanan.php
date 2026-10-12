<?php
/**
 * API Endpoint: Export Laporan Bulanan Radiologi (Excel 6 Tabel - 1 Sheet)
 * Format sesuai template contoh_laporan.xlsx RSUSISS
 */

require_once __DIR__ . '/../config/database.php';

// Class SimpleZip murni PHP (tanpa ketergantungan ekstensi eksternal)
class SimpleZip {
    private array $files = [];
    
    public function addFile(string $path, string $content): void {
        $path = str_replace('\\', '/', $path);
        $compressed = gzdeflate($content);
        $crc = crc32($content);
        $this->files[] = [
            'name'     => $path,
            'data'     => $compressed,
            'uncSize'  => strlen($content),
            'compSize' => strlen($compressed),
            'crc'      => $crc
        ];
    }
    
    public function getZip(): string {
        $data = '';
        $dir = '';
        $offset = 0;
        
        foreach ($this->files as $f) {
            $nameLen = strlen($f['name']);
            // Local file header
            $header = "\x50\x4b\x03\x04";
            $header .= "\x14\x00"; // version needed (2.0)
            $header .= "\x00\x00"; // flags
            $header .= "\x08\x00"; // deflate compression
            $header .= "\x00\x00\x00\x00"; // time/date
            $header .= pack('V', $f['crc']);
            $header .= pack('V', $f['compSize']);
            $header .= pack('V', $f['uncSize']);
            $header .= pack('v', $nameLen);
            $header .= pack('v', 0); // extra field len
            $header .= $f['name'];
            
            $data .= $header . $f['data'];
            
            // Central directory entry
            $cd = "\x50\x4b\x01\x02";
            $cd .= "\x14\x00"; // version made by
            $cd .= "\x14\x00"; // version needed
            $cd .= "\x00\x00"; // flags
            $cd .= "\x08\x00"; // compression method
            $cd .= "\x00\x00\x00\x00"; // time/date
            $cd .= pack('V', $f['crc']);
            $cd .= pack('V', $f['compSize']);
            $cd .= pack('V', $f['uncSize']);
            $cd .= pack('v', $nameLen);
            $cd .= pack('v', 0); // extra len
            $cd .= pack('v', 0); // comment len
            $cd .= pack('v', 0); // disk start
            $cd .= pack('v', 0); // internal attr
            $cd .= pack('V', 32); // external attr
            $cd .= pack('V', $offset); // offset
            $cd .= $f['name'];
            
            $dir .= $cd;
            $offset = strlen($data);
        }
        
        // End of central directory
        $eocd = "\x50\x4b\x05\x06";
        $eocd .= "\x00\x00";
        $eocd .= "\x00\x00";
        $eocd .= pack('v', count($this->files));
        $eocd .= pack('v', count($this->files));
        $eocd .= pack('V', strlen($dir));
        $eocd .= pack('V', strlen($data));
        $eocd .= pack('v', 0);
        
        return $data . $dir . $eocd;
    }
}

function dateToExcel($dateStr): string|int {
    if (!$dateStr || $dateStr === '0000-00-00' || $dateStr === '-') return '';
    $t = strtotime($dateStr);
    if (!$t) return '';
    return (int)(($t - strtotime('1899-12-30')) / 86400) + 1;
}

function timeToExcel($timeStr): string|float {
    if (!$timeStr || $timeStr === '00:00:00' || $timeStr === '-') return '';
    $parts = explode(':', $timeStr);
    if (count($parts) < 2) return '';
    $h = (int)$parts[0];
    $m = (int)$parts[1];
    $s = isset($parts[2]) ? (int)$parts[2] : 0;
    return ($h * 3600 + $m * 60 + $s) / 86400;
}

function escapeXml($str): string {
    return htmlspecialchars((string)$str, ENT_XML1, 'UTF-8');
}

$namaBulanIndo = [
    1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
    5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
    9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
];

// Ambil parameter tanggal atau bulan/tahun
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$tglMulai = $_GET['tgl_mulai'] ?? '';
$tglAkhir = $_GET['tgl_akhir'] ?? '';

if ($bulan >= 1 && $bulan <= 12 && $tahun >= 2000) {
    $tglMulai = sprintf('%04d-%02d-01', $tahun, $bulan);
    $tglAkhir = date('Y-m-t', strtotime($tglMulai));
    $bulanText = $namaBulanIndo[$bulan] . ' ' . $tahun;
} elseif ($tglMulai && $tglAkhir) {
    $bNum = (int)date('n', strtotime($tglMulai));
    $tNum = (int)date('Y', strtotime($tglMulai));
    $bulanText = ($namaBulanIndo[$bNum] ?? '') . ' ' . $tNum;
} else {
    // Default: bulan berjalan
    $bNum = (int)date('n');
    $tNum = (int)date('Y');
    $tglMulai = sprintf('%04d-%02d-01', $tNum, $bNum);
    $tglAkhir = date('Y-m-t', strtotime($tglMulai));
    $bulanText = ($namaBulanIndo[$bNum] ?? '') . ' ' . $tNum;
}

$pdo = Database::getConnection();
if ($pdo === null) {
    die("Error: Koneksi ke database SIK gagal. Periksa konfigurasi di config/database.php");
}

$sql = "
    SELECT 
        a.noorder,
        a.no_rawat,
        b.kd_poli,
        CONCAT(c.no_rkm_medis, ' ', c.nm_pasien) AS nama_pasien,
        jpr.nm_perawatan AS pemeriksaan,
        a.tgl_permintaan AS tgl_pemeriksaan,
        a.jam_permintaan AS waktu_pemeriksaan,
        IFNULL(a.tgl_sampel, '') AS tgl_selesai_pemeriksaan,
        IFNULL(a.jam_sampel, '') AS waktu_selesai_pemeriksaan,
        IFNULL(a.tgl_hasil, '') AS tgl_expertise,
        IFNULL(a.jam_hasil, '') AS waktu_expertise,
        IFNULL(d.nm_dokter, '-') AS dokter_pengirim,
        CASE 
            WHEN a.status = 'ranap' THEN IFNULL(bng.nm_bangsal, IFNULL(e.nm_poli, 'Rawat Inap'))
            ELSE IFNULL(e.nm_poli, 'Poli Registrasi')
        END AS unit_perujuk,
        a.status AS status_rawat
    FROM permintaan_radiologi a
    INNER JOIN permintaan_pemeriksaan_radiologi ppr ON a.noorder = ppr.noorder
    INNER JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
    INNER JOIN reg_periksa b ON a.no_rawat = b.no_rawat
    INNER JOIN pasien c ON b.no_rkm_medis = c.no_rkm_medis
    LEFT JOIN dokter d ON a.dokter_perujuk = d.kd_dokter
    LEFT JOIN poliklinik e ON b.kd_poli = e.kd_poli
    LEFT JOIN (
        SELECT ki1.no_rawat, bng.nm_bangsal
        FROM kamar_inap ki1
        INNER JOIN kamar km ON ki1.kd_kamar = km.kd_kamar
        INNER JOIN bangsal bng ON km.kd_bangsal = bng.kd_bangsal
        INNER JOIN (
            SELECT no_rawat, MAX(CONCAT(tgl_masuk, ' ', jam_masuk)) AS max_masuk
            FROM kamar_inap
            GROUP BY no_rawat
        ) ki2 ON ki1.no_rawat = ki2.no_rawat AND CONCAT(ki1.tgl_masuk, ' ', ki1.jam_masuk) = ki2.max_masuk
        GROUP BY ki1.no_rawat
    ) bng ON a.no_rawat = bng.no_rawat
    WHERE a.tgl_permintaan BETWEEN :tgl_mulai AND :tgl_akhir
    ORDER BY a.tgl_permintaan ASC, a.jam_permintaan ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':tgl_mulai' => $tglMulai, ':tgl_akhir' => $tglAkhir]);
$allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Definisi 6 Tabel Sesuai Permintaan
$tables = [
    1 => [
        'title' => "PEMERIKSAAN FOTO RONTGEN TANPA KONTRAS RAWAT JALAN  UNIT RADIOLOGI BULAN $bulanText",
        'rows'  => []
    ],
    2 => [
        'title' => "PASIEN RADIOLOGI RONTGEN TANPA KONTRAS RAWAT INAP BULAN $bulanText",
        'rows'  => []
    ],
    3 => [
        'title' => "PASIEN RADIOLOGI USG RAWAT JALAN BULAN $bulanText",
        'rows'  => []
    ],
    4 => [
        'title' => "PASIEN RADIOLOGI USG RAWAT INAP BULAN $bulanText",
        'rows'  => []
    ],
    5 => [
        'title' => "PASIEN RADIOLOGI RONTGEN TANPA KONTRAS DARI LUAR RS BULAN $bulanText",
        'rows'  => []
    ],
    6 => [
        'title' => "PASIEN RADIOLOGI KE LUAR RS $bulanText ( MSCT )",
        'rows'  => []
    ]
];

foreach ($allRows as $r) {
    $p = strtolower($r['pemeriksaan']);
    $isUsg = (strpos($p, 'usg') !== false);
    $isMsct = (strpos($p, 'msct') !== false);
    $isNonKontras = (!$isUsg && !$isMsct);
    $isRalan = ($r['status_rawat'] === 'ralan');
    $isRanap = ($r['status_rawat'] === 'ranap');
    $isLuarRs = (stripos($r['unit_perujuk'], 'radiologi') !== false || $r['kd_poli'] === '82');

    // Tabel 1: Rontgen Tanpa Kontras Ralan
    if ($isNonKontras && $isRalan) {
        $tables[1]['rows'][] = $r;
    }

    // Tabel 2: Rontgen Tanpa Kontras Ranap
    if ($isNonKontras && $isRanap) {
        $tables[2]['rows'][] = $r;
    }

    // Tabel 3: USG Ralan
    if ($isUsg && $isRalan) {
        $tables[3]['rows'][] = $r;
    }

    // Tabel 4: USG Ranap
    if ($isUsg && $isRanap) {
        $tables[4]['rows'][] = $r;
    }

    // Tabel 5: Rontgen Tanpa Kontras Dari Luar RS (unit perujuk Radiologi)
    if ($isNonKontras && $isLuarRs) {
        $tables[5]['rows'][] = $r;
    }

    // Tabel 6: MSCT (gabungan Ranap & Ralan)
    if ($isMsct) {
        $tables[6]['rows'][] = $r;
    }
}

// Bangun sheet1.xml (1 Sheet, 6 Tabel Bertingkat Sesuai Contoh File)
$sheetRowsXml = '';
$currRow = 1;

foreach ($tables as $tNum => $tData) {
    $tTitle = escapeXml($tData['title']);
    
    // Baris Judul Tabel
    $sheetRowsXml .= '<row r="' . $currRow . '" spans="1:12">';
    $sheetRowsXml .= '<c r="B' . $currRow . '" s="31"/>';
    $sheetRowsXml .= '<c r="C' . $currRow . '" s="3" t="inlineStr"><is><t>' . $tTitle . '</t></is></c>';
    for ($col = 'D'; $col <= 'L'; $col++) {
        $sheetRowsXml .= '<c r="' . $col . $currRow . '" s="21"/>';
    }
    $sheetRowsXml .= '</row>';
    $currRow++;

    // Baris Header Kolom Tabel (B: NO s/d L: UNIT PERUJUK)
    $sheetRowsXml .= '<row r="' . $currRow . '" spans="1:12">';
    $sheetRowsXml .= '<c r="B' . $currRow . '" s="6" t="inlineStr"><is><t>NO</t></is></c>';
    $sheetRowsXml .= '<c r="C' . $currRow . '" s="7" t="inlineStr"><is><t>NAMA PASIEN</t></is></c>';
    $sheetRowsXml .= '<c r="D' . $currRow . '" s="8" t="inlineStr"><is><t>PEMERIKSAN</t></is></c>';
    $sheetRowsXml .= '<c r="E' . $currRow . '" s="8" t="inlineStr"><is><t>TANGGAL PEMERIKSAAN</t></is></c>';
    $sheetRowsXml .= '<c r="F' . $currRow . '" s="8" t="inlineStr"><is><t>WAKTU PEMERIKSAAN</t></is></c>';
    $sheetRowsXml .= '<c r="G' . $currRow . '" s="8" t="inlineStr"><is><t>TANGGAL SELESI PEMERIKSAAN</t></is></c>';
    $sheetRowsXml .= '<c r="H' . $currRow . '" s="8" t="inlineStr"><is><t>WAKTIU SELESAI PEMERIKSAAN</t></is></c>';
    $sheetRowsXml .= '<c r="I' . $currRow . '" s="8" t="inlineStr"><is><t>TANGGAL EXPERTISE</t></is></c>';
    $sheetRowsXml .= '<c r="J' . $currRow . '" s="8" t="inlineStr"><is><t>WAKTU EXPERTISE</t></is></c>';
    $sheetRowsXml .= '<c r="K' . $currRow . '" s="6" t="inlineStr"><is><t>DOKTER PENGIRIM</t></is></c>';
    $sheetRowsXml .= '<c r="L' . $currRow . '" s="6" t="inlineStr"><is><t>UNIT PERUJUK</t></is></c>';
    $sheetRowsXml .= '</row>';
    $currRow++;

    // Baris-baris Data Pasien
    $no = 1;
    foreach ($tData['rows'] as $row) {
        $sheetRowsXml .= '<row r="' . $currRow . '" spans="1:12">';
        $sheetRowsXml .= '<c r="A' . $currRow . '" s="9"/>';
        $sheetRowsXml .= '<c r="B' . $currRow . '" s="32"><v>' . $no . '</v></c>';
        $sheetRowsXml .= '<c r="C' . $currRow . '" s="33" t="inlineStr"><is><t>' . escapeXml($row['nama_pasien']) . '</t></is></c>';
        $sheetRowsXml .= '<c r="D' . $currRow . '" s="32" t="inlineStr"><is><t>' . escapeXml($row['pemeriksaan']) . '</t></is></c>';
        
        $dPem = dateToExcel($row['tgl_pemeriksaan']);
        $sheetRowsXml .= $dPem !== '' ? '<c r="E' . $currRow . '" s="34"><v>' . $dPem . '</v></c>' : '<c r="E' . $currRow . '" s="34"/>';
        
        $tPem = timeToExcel($row['waktu_pemeriksaan']);
        $sheetRowsXml .= $tPem !== '' ? '<c r="F' . $currRow . '" s="35"><v>' . $tPem . '</v></c>' : '<c r="F' . $currRow . '" s="35"/>';

        $dSel = dateToExcel($row['tgl_selesai_pemeriksaan']);
        $sheetRowsXml .= $dSel !== '' ? '<c r="G' . $currRow . '" s="34"><v>' . $dSel . '</v></c>' : '<c r="G' . $currRow . '" s="34"/>';

        $tSel = timeToExcel($row['waktu_selesai_pemeriksaan']);
        $sheetRowsXml .= $tSel !== '' ? '<c r="H' . $currRow . '" s="35"><v>' . $tSel . '</v></c>' : '<c r="H' . $currRow . '" s="35"/>';

        $dExp = dateToExcel($row['tgl_expertise']);
        $sheetRowsXml .= $dExp !== '' ? '<c r="I' . $currRow . '" s="34"><v>' . $dExp . '</v></c>' : '<c r="I' . $currRow . '" s="34"/>';

        $tExp = timeToExcel($row['waktu_expertise']);
        $sheetRowsXml .= $tExp !== '' ? '<c r="J' . $currRow . '" s="35"><v>' . $tExp . '</v></c>' : '<c r="J' . $currRow . '" s="35"/>';

        $sheetRowsXml .= '<c r="K' . $currRow . '" s="32" t="inlineStr"><is><t>' . escapeXml($row['dokter_pengirim']) . '</t></is></c>';
        $sheetRowsXml .= '<c r="L' . $currRow . '" s="32" t="inlineStr"><is><t>' . escapeXml($row['unit_perujuk']) . '</t></is></c>';
        $sheetRowsXml .= '</row>';
        $no++;
        $currRow++;
    }

    // Pemisah Antar Tabel (1 Baris Kosong)
    $sheetRowsXml .= '<row r="' . $currRow . '" spans="1:12"/>';
    $currRow++;
}

// Konstruksi dokumen XML Worksheet lengkap
$fullSheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<dimension ref="A1:L' . ($currRow - 1) . '"/>
<sheetViews>
<sheetView tabSelected="1" workbookViewId="0"/>
</sheetViews>
<sheetFormatPr defaultRowHeight="15"/>
<cols>
<col min="1" max="1" width="9.109375" customWidth="1"/>
<col min="2" max="2" width="9.109375" customWidth="1"/>
<col min="3" max="3" width="40.33203125" customWidth="1"/>
<col min="4" max="4" width="43.88671875" customWidth="1"/>
<col min="5" max="5" width="17.5546875" customWidth="1"/>
<col min="6" max="6" width="29.33203125" customWidth="1"/>
<col min="7" max="7" width="24.88671875" customWidth="1"/>
<col min="8" max="8" width="20.6640625" customWidth="1"/>
<col min="9" max="9" width="23.44140625" customWidth="1"/>
<col min="10" max="10" width="12.109375" customWidth="1"/>
<col min="11" max="11" width="37" customWidth="1"/>
<col min="12" max="12" width="20.88671875" customWidth="1"/>
<col min="13" max="16384" width="9.109375"/>
</cols>
<sheetData>
' . $sheetRowsXml . '
</sheetData>
<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>
</worksheet>';

// Kemas file menjadi format .XLSX standar
$templateDir = __DIR__ . '/xlsx_template';
$zip = new SimpleZip();
$zip->addFile('[Content_Types].xml', file_get_contents($templateDir . '/[Content_Types].xml'));
$zip->addFile('_rels/.rels', file_get_contents($templateDir . '/_rels/.rels'));
$zip->addFile('xl/workbook.xml', file_get_contents($templateDir . '/xl/workbook.xml'));
$zip->addFile('xl/_rels/workbook.xml.rels', file_get_contents($templateDir . '/xl/_rels/workbook.xml.rels'));
$zip->addFile('xl/styles.xml', file_get_contents($templateDir . '/xl/styles.xml'));
$zip->addFile('xl/theme/theme1.xml', file_get_contents($templateDir . '/xl/theme/theme1.xml'));
$zip->addFile('docProps/core.xml', file_get_contents($templateDir . '/docProps/core.xml'));
$zip->addFile('docProps/app.xml', file_get_contents($templateDir . '/docProps/app.xml'));
$zip->addFile('xl/worksheets/sheet1.xml', $fullSheetXml);

$xlsxContent = $zip->getZip();
$safeFilename = 'Laporan_Bulanan_Radiologi_' . preg_replace('/[^A-Za-z0-9_]/', '_', $bulanText) . '.xlsx';

// Kirim header HTTP download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
header('Content-Length: ' . strlen($xlsxContent));
header('Cache-Control: max-age=0, must-revalidate');
header('Pragma: public');

echo $xlsxContent;
exit;
