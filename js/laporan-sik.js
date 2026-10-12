/**
 * Laporan SIK RSUSISS - Controller & Client-Side Export Engine
 * Khusus Data Radiologi (Rawat Inap & Rawat Jalan)
 * Sesuai Dokumen PRD & Gambar Format Kolom Permintaan Radiologi:
 * - 11 Kolom Data:
 *   1. NO
 *   2. NAMA PASIEN
 *   3. PEMERIKSAAN
 *   4. TANGGAL PEMERIKSAAN
 *   5. WAKTU PEMERIKSAAN
 *   6. TANGGAL SELESAI PEMERIKSAAN
 *   7. WAKTU SELESAI PEMERIKSAAN
 *   8. TANGGAL EXPERTISE
 *   9. WAKTU EXPERTISE
 *   10. DOKTER PENGIRIM
 *   11. UNIT PERUJUK
 * - Filter Tanggal Mulai, Tanggal Akhir, Jenis Rawat (Ranap/Ralan), Kategori Pemeriksaan
 * - Client-side Export (Copy, CSV, Excel, PDF, Print)
 */

let dataTableInstance = null;
let currentReportData = [];

$(document).ready(function () {
    // Set default tanggal (awal bulan s/d hari ini) jika belum ada
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    
    const firstDayOfMonth = `${yyyy}-${mm}-01`;
    const todayFormatted = `${yyyy}-${mm}-${dd}`;

    if (!$('#tgl_mulai').val()) {
        $('#tgl_mulai').val(firstDayOfMonth);
    }
    if (!$('#tgl_akhir').val()) {
        $('#tgl_akhir').val(todayFormatted);
    }

    // Inisialisasi DataTables
    initDataTable();

    // Load data awal
    loadDataLaporan();

    // Event handler: Tombol Tampilkan Data
    $('#btn-filter').on('click', function (e) {
        e.preventDefault();
        loadDataLaporan();
    });

    // Event handler: Ganti Status Rawat lewat Select
    $('#status_rawat').on('change', function () {
        const val = $(this).val();
        updateStatusToggleButtons(val);
        loadDataLaporan();
    });

    // Event handler: Ganti Kategori & Unit Perujuk lewat Select
    $('#kategori, #unit_perujuk').on('change', function () {
        loadDataLaporan();
    });

    // Event handler: Klik Quick Toggle Tombol Status Rawat (Ranap/Ralan)
    $(document).on('click', '.status-toggle-btn', function (e) {
        e.preventDefault();
        const status = $(this).data('status');
        $('#status_rawat').val(status);
        updateStatusToggleButtons(status);
        loadDataLaporan();
    });

    // Event handler: Tombol Reset
    $('#btn-reset').on('click', function (e) {
        e.preventDefault();
        $('#tgl_mulai').val(firstDayOfMonth);
        $('#tgl_akhir').val(todayFormatted);
        $('#status_rawat').val('SEMUA');
        $('#kategori').val('SEMUA');
        $('#unit_perujuk').val('SEMUA');
        updateStatusToggleButtons('SEMUA');
        loadDataLaporan();
    });

    // Event handler: Quick Range Buttons
    $('.quick-range').on('click', function (e) {
        e.preventDefault();
        const range = $(this).data('range');
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        const todayStr = `${y}-${m}-${d}`;

        if (range === 'today') {
            $('#tgl_mulai').val(todayStr);
            $('#tgl_akhir').val(todayStr);
        } else if (range === '7days') {
            const past7 = new Date();
            past7.setDate(now.getDate() - 6);
            const pY = past7.getFullYear();
            const pM = String(past7.getMonth() + 1).padStart(2, '0');
            const pD = String(past7.getDate()).padStart(2, '0');
            $('#tgl_mulai').val(`${pY}-${pM}-${pD}`);
            $('#tgl_akhir').val(todayStr);
        } else if (range === 'month') {
            $('#tgl_mulai').val(`${y}-${m}-01`);
            $('#tgl_akhir').val(todayStr);
        }
        loadDataLaporan();
    });

    // Event handler: Klik Kategori di Sidebar
    $('.sidebar-filter-link').on('click', function (e) {
        e.preventDefault();
        const kat = $(this).data('kategori');
        if (kat) {
            $('#kategori').val(kat);
            loadDataLaporan();
            $('html, body').animate({
                scrollTop: $("#filterPanel").offset().top - 70
            }, 500);
        }
    });

    // Event handlers: Export Buttons (Client-Side)
    $('#btn-export-copy').on('click', exportCopy);
    $('#btn-export-csv').on('click', exportCSV);
    $('#btn-resume-report').on('click', showResumeReport);
    $('#btn-download-bulanan').on('click', function () {
        const tglMulai = $('#tgl_mulai').val() || '';
        const tglAkhir = $('#tgl_akhir').val() || '';
        if (tglMulai) {
            const parts = tglMulai.split('-');
            if (parts.length === 3) {
                $('#download_bulan').val(parseInt(parts[1], 10));
                $('#download_tahun').val(parts[0]);
            }
        }
        $('#preview_download_range').text(`${formatTanggalIndo(tglMulai)} s/d ${formatTanggalIndo(tglAkhir)}`);
        $('#download_opt_month').prop('checked', true);
        $('#section_download_month').show();
        $('#section_download_range').hide();
        $('#modalDownloadBulanan').modal('show');
    });

    $(document).on('change', 'input[name="download_periode_type"]', function () {
        const val = $(this).val();
        if (val === 'month') {
            $('#section_download_month').slideDown(200);
            $('#section_download_range').slideUp(200);
        } else {
            $('#section_download_month').slideUp(200);
            $('#section_download_range').slideDown(200);
        }
    });

    $('#btn-submit-download-bulanan').on('click', function () {
        const periodeType = $('input[name="download_periode_type"]:checked').val();
        let downloadUrl = 'api/export_bulanan.php';
        if (periodeType === 'month') {
            const bulan = $('#download_bulan').val();
            const tahun = $('#download_tahun').val();
            downloadUrl += `?bulan=${bulan}&tahun=${tahun}`;
        } else {
            const tglMulai = $('#tgl_mulai').val() || '';
            const tglAkhir = $('#tgl_akhir').val() || '';
            downloadUrl += `?tgl_mulai=${tglMulai}&tgl_akhir=${tglAkhir}`;
        }
        $('#modalDownloadBulanan').modal('hide');
        showToast('Menyiapkan file Excel 6 tabel dalam 1 sheet...', 'info');
        window.location.href = downloadUrl;
    });
    $('#btn-export-excel').on('click', exportExcel);
    $('#btn-export-pdf').on('click', exportPDF);
    $('#btn-export-print').on('click', exportPrint);
    $('#btn-print-resume').on('click', printResumeReport);

    // Event handler: Modal Test Koneksi DB
    $('#btn-test-db').on('click', testDatabaseConnection);
    checkDbStatusBadge();
    initUnitPerujukOptions();

    // Auto-adjust kolom saat ukuran layar berubah (Responsive Full Width)
    $(window).on('resize', function () {
        if (dataTableInstance) {
            dataTableInstance.columns.adjust();
        }
    });
});

/**
 * Sinkronisasi visual tombol status toggle
 */
function updateStatusToggleButtons(status) {
    $('.status-toggle-btn').removeClass('active btn-primary btn-info btn-success').addClass('btn-outline-secondary');
    const $target = $(`.status-toggle-btn[data-status="${status}"]`);
    $target.addClass('active').removeClass('btn-outline-secondary');
    if (status === 'SEMUA') $target.addClass('btn-primary');
    else if (status === 'ralan') $target.addClass('btn-info');
    else if (status === 'ranap') $target.addClass('btn-success');
}

/**
 * Inisialisasi DataTables dengan 11 Kolom Sesuai Gambar 1
 */
function initDataTable() {
    dataTableInstance = $('#tabelLaporan').DataTable({
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Cari pasien, pemeriksaan, dokter...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "<div class='text-center py-4'><i class='fas fa-inbox fa-3x text-gray-300 mb-2'></i><p class='text-gray-500 mb-0'>Tidak ada data radiologi yang sesuai dengan kriteria filter.</p></div>",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Awal",
                last: "Akhir",
                next: "<i class='fas fa-chevron-right'></i>",
                previous: "<i class='fas fa-chevron-left'></i>"
            }
        },
        autoWidth: false,
        responsive: false,
        order: [[3, 'desc'], [4, 'desc']], // Urutkan berdasarkan tanggal & jam pemeriksaan
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        columns: [
            { data: 'no', className: 'text-center font-weight-bold', width: '3%' },
            { 
                data: 'nm_pasien', 
                width: '13%',
                render: function(data) {
                    if (!data) return '-';
                    const parts = data.trim().split(' ');
                    if (parts.length > 1 && /^[0-9]+$/.test(parts[0])) {
                        const noRm = parts[0];
                        const nama = parts.slice(1).join(' ');
                        return `<div class="font-weight-bold text-gray-800" style="line-height:1.2;">
                                    <div class="text-secondary small font-weight-normal" style="font-size:0.68rem; letter-spacing:0.3px;">${escapeHtml(noRm)}</div>
                                    <div>${escapeHtml(nama)}</div>
                                </div>`;
                    }
                    return `<span class="font-weight-bold text-gray-800" style="line-height:1.2;">${escapeHtml(data)}</span>`;
                }
            },
            { 
                data: 'pemeriksaan', 
                width: '13.5%',
                render: function(data) {
                    return `<span class="font-weight-bold text-primary" style="line-height:1.2; display:block;">${escapeHtml(data)}</span>`;
                }
            },
            { data: 'tgl_pemeriksaan', className: 'text-center col-date', width: '8%' },
            { data: 'jam_pemeriksaan', className: 'text-center col-time', width: '6.5%' },
            { data: 'tgl_selesai_pemeriksaan', className: 'text-center col-date', width: '8%' },
            { data: 'waktu_selesai_pemeriksaan', className: 'text-center col-time', width: '6.5%' },
            { data: 'tgl_expertise', className: 'text-center col-date', width: '8%' },
            { data: 'waktu_expertise', className: 'text-center col-time', width: '6.5%' },
            { 
                data: 'dokter_pengirim', 
                width: '13.5%',
                render: function(data) {
                    return `<span class="text-gray-800" style="line-height:1.2; display:block;">${escapeHtml(data)}</span>`;
                }
            },
            { 
                data: 'unit_perujuk', 
                width: '13.5%',
                render: function(data, type, row) {
                    let badge = '';
                    if (row.status_rawat === 'ranap') {
                        badge = ' <span class="badge badge-success px-1" title="Rawat Inap" style="font-size:0.65rem;">Ranap</span>';
                    } else if (row.status_rawat === 'ralan') {
                        badge = ' <span class="badge badge-info px-1" title="Rawat Jalan" style="font-size:0.65rem;">Ralan</span>';
                    }
                    return `<span style="line-height:1.2; display:block;">${escapeHtml(data)}${badge}</span>`;
                }
            }
        ]
    });
}

/**
 * Menarik data dari API backend berdasarkan filter
 */
function loadDataLaporan() {
    const tglMulai = $('#tgl_mulai').val();
    const tglAkhir = $('#tgl_akhir').val();
    const statusRawat = $('#status_rawat').val() || 'SEMUA';
    const kategori = $('#kategori').val() || 'SEMUA';
    const unitPerujuk = $('#unit_perujuk').val() || 'SEMUA';

    // Tampilkan state loading
    const $btnFilter = $('#btn-filter');
    const originalText = $btnFilter.html();
    $btnFilter.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memuat...');

    $('#loading-overlay').show();

    $.ajax({
        url: 'api/get_data.php',
        type: 'GET',
        cache: false,
        dataType: 'json',
        data: {
            tgl_mulai: tglMulai,
            tgl_akhir: tglAkhir,
            status_rawat: statusRawat,
            kategori: kategori,
            unit_perujuk: unitPerujuk
        },
        success: function (response) {
            if (response.status === 'success') {
                currentReportData = response.data || [];
                
                // Update tabel DataTables
                dataTableInstance.clear();
                dataTableInstance.rows.add(currentReportData);
                dataTableInstance.draw();

                // Update KPI Cards
                updateKpiCards(response);
            } else {
                showToast('Gagal memuat data: ' + (response.message || 'Terjadi kesalahan'), 'danger');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX Error:', error);
            showToast('Gagal terhubung ke endpoint API server.', 'danger');
        },
        complete: function () {
            $btnFilter.prop('disabled', false).html(originalText);
            $('#loading-overlay').hide();
        }
    });
}

/**
 * Update statistik KPI Cards di bagian atas
 */
function updateKpiCards(response) {
    const total = response.total || 0;
    const statusVal = $('#status_rawat').val() || 'SEMUA';
    let statusText = 'Semua (Ranap & Ralan)';
    if (statusVal === 'ralan') statusText = 'Rawat Jalan (Ralan)';
    if (statusVal === 'ranap') statusText = 'Rawat Inap (Ranap)';

    const tglMulai = response.filter.tgl_mulai;
    const tglAkhir = response.filter.tgl_akhir;

    $('#kpi-total-data').text(total.toLocaleString('id-ID'));
    $('#kpi-kategori').text(statusText);
    $('#kpi-periode').text(`${formatTanggalIndo(tglMulai)} s/d ${formatTanggalIndo(tglAkhir)}`);
    
    if (response.is_mock) {
        $('#kpi-db-status').html('<span class="text-warning font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Mode Simulasi</span>');
        $('#kpi-db-sub').text('Server 192.168.1.2 Offline');
    } else {
        $('#kpi-db-status').html('<span class="text-success font-weight-bold"><i class="fas fa-check-circle"></i> Terhubung SIK</span>');
        $('#kpi-db-sub').text('192.168.1.2 (Read-Only)');
    }
}

/**
 * Cek status database live untuk badge di topbar
 */
function checkDbStatusBadge() {
    $.getJSON('api/status_db.php', function(data) {
        const $badge = $('#topbar-db-badge');
        if (data.connected) {
            $badge.html('<span class="badge badge-success px-2 py-1"><i class="fas fa-database mr-1"></i> DB SIK: Online (192.168.1.2)</span>');
        } else {
            $badge.html('<span class="badge badge-warning px-2 py-1" title="Server 192.168.1.2 belum terjangkau (Simulasi aktif)"><i class="fas fa-plug mr-1"></i> DB: Simulasi Offline</span>');
        }
    }).fail(function() {
        $('#topbar-db-badge').html('<span class="badge badge-secondary px-2 py-1"><i class="fas fa-question-circle mr-1"></i> DB Status: Unknown</span>');
    });
}

/**
 * Test koneksi database dari modal setting
 */
function testDatabaseConnection() {
    const $btn = $('#btn-test-db');
    const $result = $('#db-test-result');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menguji Port 3306...');
    $result.html('<div class="text-muted"><i class="fas fa-circle-notch fa-spin mr-1"></i> Menghubungi 192.168.1.2:3306...</div>');

    $.getJSON('api/status_db.php', function (res) {
        if (res.connected) {
            $result.html(`
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-check-circle mr-1"></i> <strong>Koneksi Berhasil!</strong><br>
                    Tersambung ke Database <code>${res.database}</code> di Host <code>${res.host}:${res.port}</code> dengan User <code>${res.user}</code> (Read-Only).
                </div>
            `);
        } else {
            $result.html(`
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-exclamation-circle mr-1"></i> <strong>Koneksi Belum Terhubung:</strong><br>
                    <small class="d-block mb-1 text-danger font-weight-bold">${escapeHtml(res.error || 'Server tidak merespons')}</small>
                    <small class="text-muted">Pastikan server database Linux (192.168.1.2) telah menyalakan MySQL service, membuka port 3306 di firewall untuk IP 192.168.1.36, dan user <code>report_viewer</code> telah dibuat.</small>
                </div>
            `);
        }
        checkDbStatusBadge();
    }).fail(function () {
        $result.html(`
            <div class="alert alert-danger mt-3 mb-0">
                <i class="fas fa-times-circle mr-1"></i> Gagal menghubungi API status server.
            </div>
        `);
    }).always(function () {
        $btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Uji Ulang Koneksi');
    });
}

/* ==========================================================================
   MODUL EKSPOR LAPORAN (CLIENT-SIDE EXPORT Sesuai F3.01 - F3.03 & NF.02)
   11 Kolom Sesuai Gambar 1:
   NO | NAMA PASIEN | PEMERIKSAAN | TANGGAL PEMERIKSAAN | WAKTU PEMERIKSAAN |
   TANGGAL SELESAI PEMERIKSAAN | WAKTU SELESAI PEMERIKSAAN | TANGGAL EXPERTISE |
   WAKTU EXPERTISE | DOKTER PENGIRIM | UNIT PERUJUK
   ========================================================================== */

function getExportableData() {
    const filteredRows = dataTableInstance.rows({ search: 'applied' }).data().toArray();
    return filteredRows.length > 0 ? filteredRows : currentReportData;
}

/**
 * F3.02: Copy ke Clipboard
 */
function exportCopy() {
    const data = getExportableData();
    if (!data || data.length === 0) {
        showToast('Tidak ada data untuk disalin.', 'warning');
        return;
    }

    const headers = [
        'NO', 
        'NAMA PASIEN', 
        'PEMERIKSAAN', 
        'TANGGAL PEMERIKSAAN', 
        'WAKTU PEMERIKSAAN', 
        'TANGGAL SELESAI PEMERIKSAAN', 
        'WAKTU SELESAI PEMERIKSAAN', 
        'TANGGAL EXPERTISE', 
        'WAKTU EXPERTISE', 
        'DOKTER PENGIRIM', 
        'UNIT PERUJUK'
    ];
    let text = headers.join('\t') + '\n';

    data.forEach((row, idx) => {
        const line = [
            idx + 1,
            row.nm_pasien,
            row.pemeriksaan,
            row.tgl_pemeriksaan,
            row.jam_pemeriksaan,
            row.tgl_selesai_pemeriksaan,
            row.waktu_selesai_pemeriksaan,
            row.tgl_expertise,
            row.waktu_expertise,
            row.dokter_pengirim,
            row.unit_perujuk
        ];
        text += line.join('\t') + '\n';
    });

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function () {
            showToast(`Berhasil menyalin ${data.length} baris data ke clipboard!`, 'success');
        }).catch(function () {
            fallbackCopyText(text, data.length);
        });
    } else {
        fallbackCopyText(text, data.length);
    }
}

function fallbackCopyText(text, count) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        showToast(`Berhasil menyalin ${count} baris data ke clipboard!`, 'success');
    } catch (err) {
        showToast('Gagal menyalin data secara otomatis.', 'danger');
    }
    document.body.removeChild(textArea);
}

/**
 * F3.02: Download CSV
 */
function exportCSV() {
    const data = getExportableData();
    if (!data || data.length === 0) {
        showToast('Tidak ada data untuk diunduh.', 'warning');
        return;
    }

    const headers = [
        'NO', 
        'NAMA PASIEN', 
        'PEMERIKSAAN', 
        'TANGGAL PEMERIKSAAN', 
        'WAKTU PEMERIKSAAN', 
        'TANGGAL SELESAI PEMERIKSAAN', 
        'WAKTU SELESAI PEMERIKSAAN', 
        'TANGGAL EXPERTISE', 
        'WAKTU EXPERTISE', 
        'DOKTER PENGIRIM', 
        'UNIT PERUJUK'
    ];
    let csvContent = '\uFEFF';
    csvContent += headers.map(h => `"${h}"`).join(',') + '\r\n';

    data.forEach((row, idx) => {
        const fields = [
            idx + 1,
            row.nm_pasien,
            row.pemeriksaan,
            row.tgl_pemeriksaan,
            row.jam_pemeriksaan,
            row.tgl_selesai_pemeriksaan,
            row.waktu_selesai_pemeriksaan,
            row.tgl_expertise,
            row.waktu_expertise,
            row.dokter_pengirim,
            row.unit_perujuk
        ];
        const line = fields.map(val => `"${String(val).replace(/"/g, '""')}"`).join(',');
        csvContent += line + '\r\n';
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const filename = generateExportFilename('csv');
    triggerDownloadBlob(blob, filename);
    showToast(`File CSV berhasil diunduh (${data.length} data)`, 'success');
}

/**
 * F3.02: Download Excel (.xlsx / .xls)
 */
function exportExcel() {
    const data = getExportableData();
    if (!data || data.length === 0) {
        showToast('Tidak ada data untuk diunduh.', 'warning');
        return;
    }

    const statusVal = $('#status_rawat').val() || 'SEMUA';
    let statusText = 'Semua (Ranap & Ralan)';
    if (statusVal === 'ralan') statusText = 'Rawat Jalan (Ralan)';
    if (statusVal === 'ranap') statusText = 'Rawat Inap (Ranap)';

    const tglMulai = $('#tgl_mulai').val();
    const tglAkhir = $('#tgl_akhir').val();

    let excelContent = `<?xml version="1.0" encoding="utf-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#000000"/>
  </Style>
  <Style ss:ID="Title">
   <Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1" ss:Color="#1f4e78"/>
  </Style>
  <Style ss:ID="Subtitle">
   <Font ss:FontName="Calibri" ss:Size="11" ss:Italic="1" ss:Color="#595959"/>
  </Style>
  <Style ss:ID="Header">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#4E73DF" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="Cell">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
   </Borders>
  </Style>
  <Style ss:ID="CellCenter">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/>
   </Borders>
  </Style>
 </Styles>
 <Worksheet ss:Name="Laporan_Radiologi">
  <Table>
   <Column ss:Width="30"/>
   <Column ss:Width="160"/>
   <Column ss:Width="170"/>
   <Column ss:Width="100"/>
   <Column ss:Width="80"/>
   <Column ss:Width="110"/>
   <Column ss:Width="80"/>
   <Column ss:Width="95"/>
   <Column ss:Width="80"/>
   <Column ss:Width="150"/>
   <Column ss:Width="140"/>
   <Row>
    <Cell ss:StyleID="Title"><Data ss:Type="String">LAPORAN DATA RADIOLOGI - RSUSISS</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="Subtitle"><Data ss:Type="String">Jenis Rawat: ${escapeXml(statusText)} | Periode: ${tglMulai} s/d ${tglAkhir} | Total: ${data.length} Data</Data></Cell>
   </Row>
   <Row></Row>
   <Row>
    <Cell ss:StyleID="Header"><Data ss:Type="String">NO</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">NAMA PASIEN</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">PEMERIKSAAN</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">TANGGAL PEMERIKSAAN</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">WAKTU PEMERIKSAAN</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">TANGGAL SELESAI PEMERIKSAAN</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">WAKTU SELESAI PEMERIKSAAN</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">TANGGAL EXPERTISE</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">WAKTU EXPERTISE</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">DOKTER PENGIRIM</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">UNIT PERUJUK</Data></Cell>
   </Row>`;

    data.forEach((row, idx) => {
        excelContent += `
   <Row>
    <Cell ss:StyleID="CellCenter"><Data ss:Type="Number">${idx + 1}</Data></Cell>
    <Cell ss:StyleID="Cell"><Data ss:Type="String">${escapeXml(row.nm_pasien)}</Data></Cell>
    <Cell ss:StyleID="Cell"><Data ss:Type="String">${escapeXml(row.pemeriksaan)}</Data></Cell>
    <Cell ss:StyleID="CellCenter"><Data ss:Type="String">${escapeXml(row.tgl_pemeriksaan)}</Data></Cell>
    <Cell ss:StyleID="CellCenter"><Data ss:Type="String">${escapeXml(row.jam_pemeriksaan)}</Data></Cell>
    <Cell ss:StyleID="CellCenter"><Data ss:Type="String">${escapeXml(row.tgl_selesai_pemeriksaan)}</Data></Cell>
    <Cell ss:StyleID="CellCenter"><Data ss:Type="String">${escapeXml(row.waktu_selesai_pemeriksaan)}</Data></Cell>
    <Cell ss:StyleID="CellCenter"><Data ss:Type="String">${escapeXml(row.tgl_expertise)}</Data></Cell>
    <Cell ss:StyleID="CellCenter"><Data ss:Type="String">${escapeXml(row.waktu_expertise)}</Data></Cell>
    <Cell ss:StyleID="Cell"><Data ss:Type="String">${escapeXml(row.dokter_pengirim)}</Data></Cell>
    <Cell ss:StyleID="Cell"><Data ss:Type="String">${escapeXml(row.unit_perujuk)}</Data></Cell>
   </Row>`;
    });

    excelContent += `
  </Table>
 </Worksheet>
</Workbook>`;

    const blob = new Blob([excelContent], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const filename = generateExportFilename('xls');
    triggerDownloadBlob(blob, filename);
    showToast(`File Excel berhasil diunduh (${data.length} data)`, 'success');
}

/**
 * F3.02: Download PDF
 */
function exportPDF() {
    exportPrint(true);
}

/**
 * F3.02: Print Laporan
 */
function exportPrint(isPdfMode = false) {
    const data = getExportableData();
    if (!data || data.length === 0) {
        showToast('Tidak ada data untuk dicetak.', 'warning');
        return;
    }

    const statusVal = $('#status_rawat').val() || 'SEMUA';
    let statusText = 'Semua (Ranap & Ralan)';
    if (statusVal === 'ralan') statusText = 'Rawat Jalan (Ralan)';
    if (statusVal === 'ranap') statusText = 'Rawat Inap (Ranap)';

    const tglMulai = formatTanggalIndo($('#tgl_mulai').val());
    const tglAkhir = formatTanggalIndo($('#tgl_akhir').val());
    const printTime = new Date().toLocaleString('id-ID');

    let rowsHtml = '';
    data.forEach((row, idx) => {
        rowsHtml += `
            <tr>
                <td style="text-align:center;">${idx + 1}</td>
                <td><strong>${escapeHtml(row.nm_pasien)}</strong></td>
                <td>${escapeHtml(row.pemeriksaan)}</td>
                <td style="text-align:center;">${escapeHtml(row.tgl_pemeriksaan)}</td>
                <td style="text-align:center;">${escapeHtml(row.jam_pemeriksaan)}</td>
                <td style="text-align:center;">${escapeHtml(row.tgl_selesai_pemeriksaan)}</td>
                <td style="text-align:center;">${escapeHtml(row.waktu_selesai_pemeriksaan)}</td>
                <td style="text-align:center;">${escapeHtml(row.tgl_expertise)}</td>
                <td style="text-align:center;">${escapeHtml(row.waktu_expertise)}</td>
                <td>${escapeHtml(row.dokter_pengirim)}</td>
                <td>${escapeHtml(row.unit_perujuk)}</td>
            </tr>
        `;
    });

    const printWindow = window.open('', '_blank');
    const titleText = isPdfMode ? 'Simpan PDF - Laporan Data Radiologi' : 'Cetak Laporan Data Radiologi';

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${titleText}</title>
            <style>
                @page { size: A4 landscape; margin: 10mm 8mm 12mm 8mm; }
                body { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; color: #222; margin: 0; padding: 10px; }
                .kop { border-bottom: 2px solid #222; padding-bottom: 8px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; }
                .kop-text h2 { margin: 0 0 3px 0; font-size: 15pt; color: #1f4e78; }
                .kop-text h4 { margin: 0 0 3px 0; font-size: 10.5pt; color: #444; }
                .kop-text p { margin: 0; font-size: 8pt; color: #666; }
                .meta-table { width: 100%; margin-bottom: 10px; font-size: 8.5pt; }
                .meta-table td { padding: 2px 0; }
                table.data { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 8pt; }
                table.data th, table.data td { border: 1px solid #777; padding: 4px 5px; }
                table.data th { background-color: #e9ecef; font-weight: bold; text-align: center; }
                table.data tr:nth-child(even) { background-color: #fbfbfb; }
                .footer { margin-top: 20px; display: flex; justify-content: space-between; font-size: 8.5pt; }
                .ttd-box { text-align: center; width: 220px; }
                .ttd-space { height: 50px; }
                @media print {
                    .no-print { display: none !important; }
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="no-print" style="background:#e3f2fd; padding:10px 15px; margin-bottom:12px; border-radius:5px; display:flex; justify-content:space-between; align-items:center;">
                <span><strong>Petunjuk:</strong> Gunakan opsi <em>"Save as PDF"</em> di jendela printer untuk mengunduh dokumen sebagai file PDF.</span>
                <button onclick="window.print()" style="background:#007bff; color:#fff; border:none; padding:7px 15px; border-radius:4px; cursor:pointer; font-weight:bold;">
                    ${isPdfMode ? 'Cetak / Simpan PDF Sekarang' : 'Cetak Sekarang'}
                </button>
            </div>

            <div class="kop">
                <div class="kop-text">
                    <h2>RSU SARAS IBNU SINA SUKOWATI (RSUSISS)</h2>
                    <h4>UNIT RADIOLOGI &amp; DIAGNOSTIK</h4>
                    <p>Sistem Informasi Klinik (SIK) | Data Ekstraktor Radiologi Intranet</p>
                </div>
                <div style="text-align:right; font-size:8pt; color:#666;">
                    Waktu Cetak: ${printTime}
                </div>
            </div>

            <table class="meta-table">
                <tr>
                    <td style="width: 15%;"><strong>Jenis Layanan:</strong></td>
                    <td style="width: 35%;">${escapeHtml(statusText)}</td>
                    <td style="width: 15%;"><strong>Periode Data:</strong></td>
                    <td style="width: 35%;">${tglMulai} s/d ${tglAkhir}</td>
                </tr>
                <tr>
                    <td><strong>Total Pasien:</strong></td>
                    <td>${data.length} Pemeriksaan</td>
                    <td><strong>Spesialis Radiologi:</strong></td>
                    <td>dr. H. Anang Moekhlisin, Sp.Rad</td>
                </tr>
            </table>

            <table class="data">
                <thead>
                    <tr>
                        <th style="width:3%;">NO</th>
                        <th>NAMA PASIEN</th>
                        <th>PEMERIKSAAN</th>
                        <th>TANGGAL PEMERIKSAAN</th>
                        <th>WAKTU PEMERIKSAAN</th>
                        <th>TANGGAL SELESAI PEMERIKSAAN</th>
                        <th>WAKTU SELESAI PEMERIKSAAN</th>
                        <th>TANGGAL EXPERTISE</th>
                        <th>WAKTU EXPERTISE</th>
                        <th>DOKTER PENGIRIM</th>
                        <th>UNIT PERUJUK</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>

            <div class="footer">
                <div>
                    <small>* Dokumen ini digenerate secara otomatis dari WebApps Laporan SIK RSUSISS.</small>
                </div>
                <div class="ttd-box">
                    <p style="margin:0 0 5px 0;">Penanggung Jawab Radiologi,</p>
                    <div class="ttd-space"></div>
                    <p style="margin:0; font-weight:bold; text-decoration:underline;">dr. H. Anang Moekhlisin, Sp.Rad</p>
                    <small>Dokter Spesialis Radiologi</small>
                </div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
}

/**
 * Triggers download of a Blob
 */
function triggerDownloadBlob(blob, filename) {
    if (window.navigator && window.navigator.msSaveOrOpenBlob) {
        window.navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
}

/**
 * Format nama file ekspor
 */
function generateExportFilename(extension) {
    const statusVal = ($('#status_rawat').val() || 'semua').toLowerCase();
    const tglMulai = $('#tgl_mulai').val() || 'mulai';
    const tglAkhir = $('#tgl_akhir').val() || 'akhir';
    return `Laporan_Radiologi_${statusVal}_${tglMulai}_sd_${tglAkhir}.${extension}`;
}

/**
 * Helper notifikasi toast
 */
function showToast(message, type = 'info') {
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    else if (type === 'warning') icon = 'exclamation-triangle';
    else if (type === 'danger') icon = 'times-circle';

    const $toast = $(`
        <div class="alert alert-${type} alert-dismissible shadow-lg fade show" 
             style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px;" role="alert">
            <i class="fas fa-${icon} mr-2"></i> ${escapeHtml(message)}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `);

    $('body').append($toast);
    setTimeout(function () {
        $toast.fadeOut(400, function () {
            $(this).remove();
        });
    }, 3500);
}

function formatTanggalIndo(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function escapeXml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&apos;");
}

/**
 * Menampilkan Modal Resume & Rekapitulasi Laporan
 */
function showResumeReport() {
    if (!currentReportData || currentReportData.length === 0) {
        showToast('Tidak ada data laporan untuk diresume. Silakan klik Tampilkan data terlebih dahulu.', 'warning');
        return;
    }

    const tglMulai = $('#tgl_mulai').val();
    const tglAkhir = $('#tgl_akhir').val();
    const statusVal = $('#status_rawat').val() || 'SEMUA';
    const kategoriVal = $('#kategori option:selected').text();
    const unitVal = $('#unit_perujuk option:selected').text();
    let statusText = 'Semua Status (Ranap & Ralan)';
    if (statusVal === 'ralan') statusText = 'Rawat Jalan (Ralan)';
    if (statusVal === 'ranap') statusText = 'Rawat Inap (Ranap)';

    $('#resume-title-periode').text(`Periode: ${formatTanggalIndo(tglMulai)} s/d ${formatTanggalIndo(tglAkhir)}`);
    $('#resume-badge-status').text(statusText);
    $('#resume-badge-kategori').text(kategoriVal);
    $('#resume-badge-unit').text(unitVal);
    $('#resume-timestamp').text(new Date().toLocaleString('id-ID'));

    // Agregasi Data
    let totalItems = currentReportData.length;
    let totalRalan = 0;
    let totalRanap = 0;

    let groupTindakan = {};
    let groupUnit = {};
    let groupDokter = {};

    currentReportData.forEach(row => {
        // Status rawat
        if (row.status_rawat === 'ranap') totalRanap++;
        else totalRalan++;

        // Tindakan
        const t = row.pemeriksaan || 'Lain-lain';
        groupTindakan[t] = (groupTindakan[t] || 0) + 1;

        // Unit perujuk
        const u = row.unit_perujuk || 'Lain-lain';
        groupUnit[u] = (groupUnit[u] || 0) + 1;

        // Dokter
        const d = row.dokter_pengirim || '-';
        groupDokter[d] = (groupDokter[d] || 0) + 1;
    });

    $('#resume-total-items').text(totalItems.toLocaleString('id-ID'));
    $('#resume-total-ralan').text(totalRalan.toLocaleString('id-ID'));
    $('#resume-total-ranap').text(totalRanap.toLocaleString('id-ID'));
    $('#resume-total-variasi').text(Object.keys(groupTindakan).length + ' Tindakan');

    // Helper render tabel rekap
    function renderRekapTable(groupObj, tbodyId) {
        const sorted = Object.entries(groupObj).sort((a, b) => b[1] - a[1]);
        let html = '';
        sorted.forEach(([name, count], idx) => {
            const pct = ((count / totalItems) * 100).toFixed(1);
            html += `
                <tr>
                    <td class="text-center font-weight-bold text-muted">${idx + 1}</td>
                    <td class="font-weight-bold text-gray-800">${escapeHtml(name)}</td>
                    <td class="text-center font-weight-bold text-primary">${count.toLocaleString('id-ID')}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="mr-2 small font-weight-bold text-muted" style="min-width: 42px;">${pct}%</span>
                            <div class="progress flex-grow-1" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: ${pct}%;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });
        $(`#${tbodyId}`).html(html);
    }

    renderRekapTable(groupTindakan, 'tbody-resume-tindakan');
    renderRekapTable(groupUnit, 'tbody-resume-unit');
    renderRekapTable(groupDokter, 'tbody-resume-dokter');

    $('#resumeReportModal').modal('show');
}

/**
 * Cetak Dokumen Resume Report
 */
function printResumeReport() {
    if (!currentReportData || currentReportData.length === 0) return;

    const tglMulai = formatTanggalIndo($('#tgl_mulai').val());
    const tglAkhir = formatTanggalIndo($('#tgl_akhir').val());
    const statusVal = $('#status_rawat').val() || 'SEMUA';
    const kategoriVal = $('#kategori option:selected').text();
    let statusText = 'Semua Status (Ranap & Ralan)';
    if (statusVal === 'ralan') statusText = 'Rawat Jalan (Ralan)';
    if (statusVal === 'ranap') statusText = 'Rawat Inap (Ranap)';

    let totalItems = currentReportData.length;
    let totalRalan = 0;
    let totalRanap = 0;
    let groupTindakan = {};
    let groupUnit = {};

    currentReportData.forEach(row => {
        if (row.status_rawat === 'ranap') totalRanap++;
        else totalRalan++;

        const t = row.pemeriksaan || 'Lain-lain';
        groupTindakan[t] = (groupTindakan[t] || 0) + 1;

        const u = row.unit_perujuk || 'Lain-lain';
        groupUnit[u] = (groupUnit[u] || 0) + 1;
    });

    const sortedTindakan = Object.entries(groupTindakan).sort((a, b) => b[1] - a[1]);
    const sortedUnit = Object.entries(groupUnit).sort((a, b) => b[1] - a[1]);

    let tindakanRows = '';
    sortedTindakan.forEach(([name, count], idx) => {
        const pct = ((count / totalItems) * 100).toFixed(1);
        tindakanRows += `<tr><td style="text-align:center;">${idx + 1}</td><td>${escapeHtml(name)}</td><td style="text-align:center; font-weight:bold;">${count}</td><td style="text-align:center;">${pct}%</td></tr>`;
    });

    let unitRows = '';
    sortedUnit.forEach(([name, count], idx) => {
        const pct = ((count / totalItems) * 100).toFixed(1);
        unitRows += `<tr><td style="text-align:center;">${idx + 1}</td><td>${escapeHtml(name)}</td><td style="text-align:center; font-weight:bold;">${count}</td><td style="text-align:center;">${pct}%</td></tr>`;
    });

    const printWin = window.open('', '_blank');
    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Resume & Rekapitulasi Laporan Radiologi - RSUSISS</title>
            <style>
                @page { size: A4 portrait; margin: 15mm; }
                body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; margin: 0; }
                .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px; }
                .header h2 { margin: 0 0 4px 0; font-size: 15pt; }
                .header h3 { margin: 0 0 4px 0; font-size: 12pt; font-weight: normal; }
                .header p { margin: 0; font-size: 9pt; color: #555; }
                .meta-table { width: 100%; margin-bottom: 15px; font-size: 10pt; }
                .meta-table td { padding: 3px 0; }
                .summary-box { display: flex; justify-content: space-between; margin-bottom: 20px; border: 1px solid #ccc; padding: 10px; background: #f9f9f9; }
                .summary-item { text-align: center; flex: 1; }
                .summary-item .num { font-size: 16pt; font-weight: bold; color: #0275d8; }
                .summary-item .lbl { font-size: 9pt; color: #666; text-transform: uppercase; }
                table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10pt; }
                table.data th, table.data td { border: 1px solid #888; padding: 6px 8px; }
                table.data th { background: #e9ecef; }
                .section-title { font-size: 11pt; font-weight: bold; margin: 15px 0 6px 0; border-left: 4px solid #0275d8; padding-left: 8px; }
                .footer { margin-top: 30px; display: flex; justify-content: space-between; font-size: 10pt; }
                .ttd-box { text-align: center; width: 220px; }
                .ttd-space { height: 60px; }
            </style>
        </head>
        <body onload="window.print(); window.close();">
            <div class="header">
                <h2>RUMAH SAKIT SARAS IBNU SINA SUKOWATI</h2>
                <h3>RESUME &amp; REKAPITULASI PELAYANAN RADIOLOGI</h3>
                <p>Jl. Raya Sukowati No. 123, Sragen | Telp: (0271) 891234</p>
            </div>
            <table class="meta-table">
                <tr>
                    <td style="width:18%;"><strong>Periode Laporan</strong></td>
                    <td style="width:2%;">:</td>
                    <td>${tglMulai} s/d ${tglAkhir}</td>
                    <td style="width:18%;"><strong>Filter Pemeriksaan</strong></td>
                    <td style="width:2%;">:</td>
                    <td>${escapeHtml(kategoriVal)}</td>
                </tr>
                <tr>
                    <td><strong>Jenis Rawat</strong></td>
                    <td>:</td>
                    <td>${statusText}</td>
                    <td><strong>Waktu Cetak</strong></td>
                    <td>:</td>
                    <td>${new Date().toLocaleString('id-ID')}</td>
                </tr>
            </table>

            <div class="summary-box">
                <div class="summary-item">
                    <div class="num">${totalItems}</div>
                    <div class="lbl">Total Pemeriksaan</div>
                </div>
                <div class="summary-item">
                    <div class="num">${totalRalan}</div>
                    <div class="lbl">Rawat Jalan (Ralan)</div>
                </div>
                <div class="summary-item">
                    <div class="num">${totalRanap}</div>
                    <div class="lbl">Rawat Inap (Ranap)</div>
                </div>
                <div class="summary-item">
                    <div class="num">${sortedTindakan.length}</div>
                    <div class="lbl">Variasi Tindakan</div>
                </div>
            </div>

            <div class="section-title">A. Rekapitulasi per Jenis Tindakan / Pemeriksaan</div>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:30px; text-align:center;">No</th>
                        <th>Nama Pemeriksaan</th>
                        <th style="width:80px; text-align:center;">Jumlah</th>
                        <th style="width:80px; text-align:center;">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    ${tindakanRows}
                </tbody>
            </table>

            <div class="section-title">B. Rekapitulasi per Unit Perujuk</div>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:30px; text-align:center;">No</th>
                        <th>Unit / Ruang Perujuk</th>
                        <th style="width:80px; text-align:center;">Jumlah</th>
                        <th style="width:80px; text-align:center;">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    ${unitRows}
                </tbody>
            </table>

            <div class="footer">
                <div><small>* Dicetak secara otomatis oleh sistem reporting SIK</small></div>
                <div class="ttd-box">
                    <p style="margin:0 0 5px 0;">Penanggung Jawab Radiologi,</p>
                    <div class="ttd-space"></div>
                    <p style="margin:0; font-weight:bold; text-decoration:underline;">dr. H. Anang Moekhlisin, Sp.Rad</p>
                    <small>Dokter Spesialis Radiologi</small>
                </div>
            </div>
        </body>
        </html>
    `);
    printWin.document.close();
}

/**
 * Mengambil daftar unik unit perujuk dari database dan menambahkan ke dropdown jika belum ada
 */
function initUnitPerujukOptions() {
    $.getJSON('api/get_units.php', function (res) {
        if (res && res.status === 'success' && Array.isArray(res.data) && res.data.length > 0) {
            const $select = $('#unit_perujuk');
            const existing = [];
            $select.find('option').each(function () {
                existing.push($(this).val());
            });
            res.data.forEach(function (u) {
                if (u && !existing.includes(u)) {
                    $select.append(`<option value="${escapeHtml(u)}">${escapeHtml(u)}</option>`);
                }
            });
        }
    });
}
