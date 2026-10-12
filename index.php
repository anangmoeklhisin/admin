<?php
/**
 * WebApps Ekstraktor & Laporan Data SIK
 * RSUSISS - Split Server Architecture
 * PHP 8.2+ Native
 */
require_once __DIR__ . '/config/database.php';
$dbStatus = Database::getStatus();
?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="description" content="WebApps Ekstraktor & Laporan Data SIK - RSUSISS">
    <meta name="author" content="Data RSUSISS">

    <title>Portal Laporan SIK - Data RSUSISS</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- DataTables Bootstrap 4 CSS -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
        /* Font Roboto untuk seluruh elemen halaman */
        body, 
        button, 
        input, 
        select, 
        textarea, 
        h1, h2, h3, h4, h5, h6, 
        .h1, .h2, .h3, .h4, .h5, .h6, 
        p, span, a, label, small, strong, b,
        .btn, 
        .form-control, 
        .nav-link, 
        .table, 
        .table th,
        .table td,
        .sidebar, 
        .modal,
        .card,
        .card-header,
        .card-body,
        .badge,
        .dropdown-menu,
        .dropdown-item,
        .custom-select,
        .custom-control-label,
        .dataTables_wrapper,
        .dataTables_info,
        .dataTables_paginate,
        .dataTables_length,
        .dataTables_filter {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* PERLINDUNGAN UTAMA FONT AWESOME AGAR SEMUA ICON & GLYPH TAMPIL SEMPURNA */
        .fa, .fas, .far, .fal, .fad,
        .fa::before, .fas::before, .far::before, .fal::before, .fad::before,
        .fa::after, .fas::after, .far::after, .fal::after, .fad::after {
            font-family: 'Font Awesome 5 Free' !important;
        }
        .fa, .fas,
        .fa::before, .fas::before,
        .fa::after, .fas::after {
            font-weight: 900 !important;
        }
        .far, .far::before, .far::after {
            font-weight: 400 !important;
        }
        .fab,
        .fab::before,
        .fab::after {
            font-family: 'Font Awesome 5 Brands' !important;
            font-weight: 400 !important;
        }

        /* Icon panah bawaan SB Admin 2 (Sidebar Toggle & Accordion Collapse) */
        .sidebar .nav-item .nav-link[data-toggle="collapse"]::after,
        #sidebarToggle::after,
        .sidebar #sidebarToggle::after,
        .sidebar.toggled #sidebarToggle::after {
            font-family: 'Font Awesome 5 Free' !important;
            font-weight: 900 !important;
        }

        html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        #content-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
            flex: 1 1 auto;
            background-color: #f8f9fc;
        }

        #content {
            flex: 1 0 auto;
            width: 100%;
        }

        .container-fluid {
            width: 100%;
            max-width: 100%;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        /* Footer selalu menempel rapi di bagian bawah */
        footer.sticky-footer {
            margin-top: auto !important;
            width: 100%;
            background-color: #ffffff;
            border-top: 1px solid #e3e6f0;
            padding: 1.25rem 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Tampilan Tabel Fit 100% Layar Tanpa Geser Kanan-Kiri */
        .table-laporan {
            width: 100% !important;
            table-layout: fixed;
            word-wrap: break-word;
            word-break: break-word;
            margin-bottom: 0 !important;
        }

        .filter-card {
            border-left: 4px solid #4e73df;
            background: #ffffff;
            transition: all 0.2s ease-in-out;
        }
        .filter-card:hover {
            box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.08) !important;
        }
        .export-btn-group {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
        }
        .export-btn-group .btn {
            font-size: 0.83rem;
            font-weight: 600;
            padding: 0.42rem 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1.2;
            border-radius: 0.35rem;
            margin: 0;
            white-space: nowrap;
            transition: all 0.15s ease-in-out;
        }
        .export-btn-group .btn i {
            font-size: 0.88rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.45rem;
            vertical-align: middle;
            position: relative;
            top: -0.5px;
        }
        .export-btn-group .btn span {
            display: inline-block;
            line-height: 1;
            vertical-align: middle;
        }
        .export-btn-group .btn:hover {
            transform: translateY(-1px);
        }
        .badge-kategori {
            font-size: 80%;
            font-weight: 700;
        }
        .table-laporan th {
            font-size: 0.67rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            background-color: #f8f9fc;
            vertical-align: middle !important;
            padding: 8px 13px 8px 3px !important;
            text-align: center;
            line-height: 1.2;
            white-space: normal !important;
            word-break: normal;
        }
        .table-laporan td {
            font-size: 0.74rem !important;
            vertical-align: middle !important;
            padding: 6px 4px !important;
            line-height: 1.25;
            word-break: break-word;
        }
        .table-laporan td.col-date,
        .table-laporan td.col-time {
            font-size: 0.72rem !important;
            white-space: nowrap !important;
            text-align: center;
            padding: 6px 2px !important;
            letter-spacing: -0.2px;
        }
        /* Kompresi padding DataTables sort arrow agar hemat lebar kolom */
        table.dataTable thead>tr>th.sorting,
        table.dataTable thead>tr>th.sorting_asc,
        table.dataTable thead>tr>th.sorting_desc {
            padding-right: 14px !important;
        }
        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_desc:before,
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:after {
            right: 2px !important;
            font-size: 0.65rem !important;
            bottom: 50% !important;
            margin-bottom: -0.45rem;
        }
        .loading-spinner-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.75);
            display: none;
            z-index: 10;
            align-items: center;
            justify-content: center;
            border-radius: 0.35rem;
        }
        .quick-range {
            font-size: 0.75rem;
            padding: 0.2rem 0.55rem;
            cursor: pointer;
        }
    </style>

</head>

<body id="page-top" class="sidebar-toggled">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion toggled" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-hospital-alt"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Data RSUSISS</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard Laporan -->
            <!-- <li class="nav-item active">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard Laporan</span></a>
            </li> -->

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
           <!-- <div class="sidebar-heading">
                Laporan Unit & Poli
            </div> -->

            <!-- Nav Item - Radiologi Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseRadiologi"
                    aria-expanded="false" aria-controls="collapseRadiologi">
                    <i class="fas fa-fw fa-x-ray"></i>
                    <span>Radiologi</span>
                </a>
                <div id="collapseRadiologi" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item sidebar-filter-link quick-range" href="#" data-range="month" data-kategori="RADIOLOGI">Laporan Bulanan</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3" title="Perluas / Perkecil Sidebar">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Title Info (Intranet) -->
                    <div class="d-none d-md-flex align-items-center text-gray-700 font-weight-bold ml-2">
                        <span>Portal Data Rumah Sakit Saras Ibnu Sina Sukowati</span>
                    </div>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto align-items-center">

                        

                        <!-- Nav Item - User Information -->
                        <!-- <li class="nav-item dropdown no-arrow ml-2">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small font-weight-bold">Staf RSUSISS</span>
                                <img class="img-profile rounded-circle" src="img/undraw_profile.svg" alt="User">
                            </a>
                          
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#settingModal">
                                    <i class="fas fa-server fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Status Koneksi Database
                                </a>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#guideModal">
                                    <i class="fas fa-book fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Panduan Ekspor Laporan
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" onclick="location.reload();">
                                    <i class="fas fa-sync-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Muat Ulang Halaman
                                </a>
                            </div>
                        </li> -->

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">


                    <!-- ============================================================== -->
                    <!-- SECTION 1: FILTER PANEL (Card Atas - Sesuai PRD Section 3.1 & 6) -->
                    <!-- ============================================================== -->
                    <div class="card shadow mb-4 filter-card" id="filterPanel">
                        <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between bg-white border-bottom">
                            <h6 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">
                                <i class="fas fa-filter mr-1"></i> Parameter Pencarian & Filter Radiologi
                            </h6>
                            <!-- Quick Toggle Status Rawat (Ranap & Ralan) -->
                          
                        </div>
                        <div class="card-body">
                            <form id="formFilter" onsubmit="return false;">
                                <div class="form-row align-items-end">
                                    <!-- Input: Tanggal Mulai (F1.01) -->
                                    <div class="form-group col-xl-2 col-lg-2 col-md-4 col-sm-6 mb-2">
                                        <label for="tgl_mulai" class="small font-weight-bold text-gray-700">
                                            <i class="far fa-calendar-alt text-primary mr-1"></i> Tanggal Mulai
                                        </label>
                                        <input type="date" class="form-control form-control-sm" id="tgl_mulai" name="tgl_mulai" required>
                                    </div>

                                    <!-- Input: Tanggal Akhir (F1.01) -->
                                    <div class="form-group col-xl-2 col-lg-2 col-md-4 col-sm-6 mb-2">
                                        <label for="tgl_akhir" class="small font-weight-bold text-gray-700">
                                            <i class="far fa-calendar-check text-primary mr-1"></i> Tanggal Akhir
                                        </label>
                                        <input type="date" class="form-control form-control-sm" id="tgl_akhir" name="tgl_akhir" required>
                                    </div>

                                    <!-- Select: Status / Jenis Rawat (Ranap / Ralan) -->
                                    <div class="form-group col-xl-2 col-lg-2 col-md-4 col-sm-6 mb-2">
                                        <label for="status_rawat" class="small font-weight-bold text-gray-700">
                                            <i class="fas fa-hospital-user text-primary mr-1"></i> Jenis Rawat
                                        </label>
                                        <select class="form-control form-control-sm" id="status_rawat" name="status_rawat">
                                            <option value="SEMUA" selected>Semua Jenis Rawat (Ranap & Ralan)</option>
                                            <option value="ralan">Rawat Jalan (Ralan)</option>
                                            <option value="ranap">Rawat Inap (Ranap)</option>
                                        </select>
                                    </div>

                                    <!-- Select: Pemeriksaan Radiologi (F1.01) -->
                                    <div class="form-group col-xl-2 col-lg-2 col-md-4 col-sm-6 mb-2">
                                        <label for="kategori" class="small font-weight-bold text-gray-700">
                                            <i class="fas fa-x-ray text-primary mr-1"></i> Pemeriksaan Radiologi
                                        </label>
                                        <select class="form-control form-control-sm" id="kategori" name="kategori">
                                            <option value="SEMUA" selected>Semua Pemeriksaan Radiologi</option>
                                            <option value="Thorax">Thorax Foto (Rontgen Dada)</option>
                                            <option value="USG">USG (Abdomen / Kandungan)</option>
                                            <option value="msct">MSCT</option>
                                        </select>
                                    </div>

                                    <!-- Select: Unit Perujuk -->
                                    <div class="form-group col-xl-2 col-lg-2 col-md-4 col-sm-6 mb-2">
                                        <label for="unit_perujuk" class="small font-weight-bold text-gray-700">
                                            <i class="fas fa-clinic-medical text-primary mr-1"></i> Unit Perujuk
                                        </label>
                                        <select class="form-control form-control-sm" id="unit_perujuk" name="unit_perujuk" onchange="loadDataLaporan();">
                                            <option value="SEMUA" selected>Semua Unit Perujuk</option>
                                            <option value="IGD">IGD</option>
                                            <option value="Radiologi">Radiologi</option>
                                            <option value="Klinik Bedah Orthopedi">Klinik Bedah Orthopedi</option>
                                            <option value="Klinik Penyakit Dalam">Klinik Penyakit Dalam</option>
                                            <option value="Klinik Saraf">Klinik Saraf</option>
                                            <option value="Klinik Bedah umum">Klinik Bedah umum</option>
                                            <option value="Klinik Anak">Klinik Anak</option>
                                            <option value="Klinik Jantung">Klinik Jantung</option>
                                            <option value="Klinik Bedah Umum (Sore)">Klinik Bedah Umum (Sore)</option>
                                            <option value="Klinik Mata">Klinik Mata</option>
                                            <option value="Klinik Obsgyn">Klinik Obsgyn</option>
                                            <option value="Klinik Umum">Klinik Umum</option>
                                            <option value="Kinik Jiwa">Kinik Jiwa</option>
                                            <option value="Klinik MCU">Klinik MCU</option>
                                            <option value="Fisioterapi">Fisioterapi</option>
                                            <option value="Laboratorium">Laboratorium</option>
                                            <option value="Rawat Inap">Rawat Inap</option>
                                        </select>
                                    </div>

                                    <!-- Tombol: Tampilkan Data (F1.02) -->
                                    <div class="form-group col-xl-2 col-lg-2 col-md-4 col-sm-6 mb-2">
                                        <div class="btn-group w-100" role="group">
                                            <button type="button" class="btn btn-primary btn-sm font-weight-bold" id="btn-filter" onclick="loadDataLaporan();" title="Tampilkan Data">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>



                    <!-- ============================================================== -->
                    <!-- SECTION 2: DATA PANEL (Card Bawah - Sesuai PRD Section 3.2, 3.3 & 6) -->
                    <!-- ============================================================== -->
                    <div class="card shadow mb-4 position-relative">
                        <!-- Loading Overlay -->
                        <div class="loading-spinner-overlay" id="loading-overlay">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="sr-only">Memuat Data...</span>
                                </div>
                                <h6 class="font-weight-bold text-gray-700 mt-2">Menarik data dari database SIK...</h6>
                            </div>
                        </div>

                        <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between bg-white border-bottom">
                            <h6 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">
                                <i class="fas fa-table mr-1"></i> 
                            </h6>

                            <div class="export-btn-group">
                                <button type="button" class="btn btn-info shadow-sm" id="btn-download-bulanan" title="Download Laporan Bulanan">
                                    <i class="fas fa-file-download"></i><span>Download Laporan</span>
                                </button>
                                <button type="button" class="btn btn-primary shadow-sm" id="btn-resume-report" title="Lihat Resume & Rekapitulasi Laporan">
                                    <i class="fas fa-file-invoice"></i><span>Resume Report</span>
                                </button>
                                <button type="button" class="btn btn-success shadow-sm" id="btn-export-excel" title="Unduh file format Microsoft Excel">
                                    <i class="fas fa-file-excel"></i><span>Excel (.xlsx)</span>
                                </button>
                                <button type="button" class="btn btn-danger shadow-sm" id="btn-export-pdf" title="Cetak / Unduh dokumen PDF">
                                    <i class="fas fa-file-pdf"></i><span>PDF</span>
                                </button>
                                <button type="button" class="btn btn-dark shadow-sm" id="btn-export-print" title="Cetak laporan langsung">
                                    <i class="fas fa-print"></i><span>Print</span>
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-2 p-md-3">
                            <!-- Tabel Data Viewer (F2.01 - F2.04: Tabel Dinamis, Live Search, Sorting, Pagination) -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped table-laporan" id="tabelLaporan" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 3%;">NO</th>
                                            <th style="width: 13%;">NAMA PASIEN</th>
                                            <th style="width: 13.5%;">PEMERIKSAAN</th>
                                            <th class="text-center" style="width: 8%;">TANGGAL<br>PEMERIKSAAN</th>
                                            <th class="text-center" style="width: 6.5%;">WAKTU<br>PEMERIKSAAN</th>
                                            <th class="text-center" style="width: 8%;">TANGGAL SELESAI<br>PEMERIKSAAN</th>
                                            <th class="text-center" style="width: 6.5%;">WAKTU SELESAI<br>PEMERIKSAAN</th>
                                            <th class="text-center" style="width: 8%;">TANGGAL<br>EXPERTISE</th>
                                            <th class="text-center" style="width: 6.5%;">WAKTU<br>EXPERTISE</th>
                                            <th style="width: 13.5%;">DOKTER PENGIRIM</th>
                                            <th style="width: 13.5%;">UNIT PERUJUK</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data diisi secara dinamis melalui DataTables AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white shadow-sm border-top">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>&copy; ANANG MUKLHISIN 2026</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- ============================================================== -->
    <!-- MODAL: RESUME & REKAPITULASI LAPORAN RADIOLOGI                 -->
    <!-- ============================================================== -->
    <div class="modal fade" id="resumeReportModal" tabindex="-1" role="dialog" aria-labelledby="resumeReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold" id="resumeReportModalLabel">
                        <i class="fas fa-file-invoice mr-2"></i> Resume &amp; Rekapitulasi Laporan Radiologi
                    </h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <!-- Info Header Periode & Filter -->
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center bg-light p-3 rounded mb-3 border">
                        <div>
                            <h6 class="font-weight-bold text-gray-800 mb-1" id="resume-title-periode">Periode: -</h6>
                            <span class="badge badge-primary px-2 py-1" id="resume-badge-status">Semua Status Rawat</span>
                            <span class="badge badge-info px-2 py-1" id="resume-badge-kategori">Semua Pemeriksaan</span>
                            <span class="badge badge-secondary px-2 py-1" id="resume-badge-unit">Semua Unit Perujuk</span>
                        </div>
                        <div class="mt-2 mt-md-0 text-md-right">
                            <span class="text-muted small">Waktu Resume Dibuat:</span>
                            <div class="font-weight-bold small text-gray-700" id="resume-timestamp">-</div>
                        </div>
                    </div>

                    <!-- Mini KPI Summary Cards -->
                    <div class="row mb-3">
                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="card border-left-primary shadow-sm h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pemeriksaan</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="resume-total-items">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="card border-left-info shadow-sm h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Rawat Jalan (Ralan)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="resume-total-ralan">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="card border-left-success shadow-sm h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Rawat Inap (Ranap)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="resume-total-ranap">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="card border-left-warning shadow-sm h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Variasi Tindakan</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="resume-total-variasi">0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nav Tabs untuk Detail Rekap -->
                    <ul class="nav nav-pills mb-3" id="resumeTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-bold" id="tab-tindakan-tab" data-toggle="pill" href="#tab-tindakan" role="tab">
                                <i class="fas fa-x-ray mr-1"></i> Per Jenis Pemeriksaan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-unit-tab" data-toggle="pill" href="#tab-unit" role="tab">
                                <i class="fas fa-clinic-medical mr-1"></i> Per Unit Perujuk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-dokter-tab" data-toggle="pill" href="#tab-dokter" role="tab">
                                <i class="fas fa-user-md mr-1"></i> Per Dokter Pengirim
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content border rounded p-3 bg-white" id="resumeTabContent" style="max-height: 400px; overflow-y: auto;">
                        <!-- Tab 1: Tindakan -->
                        <div class="tab-pane fade show active" id="tab-tindakan" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-bordered table-hover mb-0" id="table-resume-tindakan">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" style="width: 50px;">NO</th>
                                            <th>NAMA PEMERIKSAAN / TINDAKAN</th>
                                            <th class="text-center" style="width: 120px;">JUMLAH</th>
                                            <th class="text-center" style="width: 160px;">PERSENTASE</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-resume-tindakan"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 2: Unit Perujuk -->
                        <div class="tab-pane fade" id="tab-unit" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-bordered table-hover mb-0" id="table-resume-unit">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" style="width: 50px;">NO</th>
                                            <th>UNIT / POLI PERUJUK</th>
                                            <th class="text-center" style="width: 120px;">JUMLAH</th>
                                            <th class="text-center" style="width: 160px;">PERSENTASE</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-resume-unit"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 3: Dokter Pengirim -->
                        <div class="tab-pane fade" id="tab-dokter" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-bordered table-hover mb-0" id="table-resume-dokter">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" style="width: 50px;">NO</th>
                                            <th>DOKTER PENGIRIM</th>
                                            <th class="text-center" style="width: 120px;">JUMLAH</th>
                                            <th class="text-center" style="width: 160px;">PERSENTASE</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-resume-dokter"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Tutup
                    </button>
                    <button type="button" class="btn btn-dark shadow-sm" id="btn-print-resume">
                        <i class="fas fa-print mr-1"></i> Cetak Resume
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: DOWNLOAD LAPORAN BULANAN (EXCEL 6 TABEL - 1 SHEET)      -->
    <!-- ============================================================== -->
    <div class="modal fade" id="modalDownloadBulanan" tabindex="-1" role="dialog" aria-labelledby="modalDownloadBulananLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold" id="modalDownloadBulananLabel">
                        <i class="fas fa-file-download mr-2"></i> Download Laporan Bulanan Radiologi (1 Sheet, 6 Tabel)
                    </h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-3">
                        <div class="mr-3">
                            <i class="fas fa-file-excel fa-2x text-primary"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold">Format Standar 6 Tabel Bertingkat (1 Lembar Kerja / 1 Sheet)</div>
                            <small class="text-muted">File Excel (.xlsx) akan otomatis menyusun 6 tabel pemeriksaan radiologi secara berurutan sesuai format resmi contoh laporan RSUSISS.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="font-weight-bold text-gray-700 small mb-2 d-block">
                            <i class="fas fa-filter mr-1 text-info"></i> Opsi Periode Laporan:
                        </label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="download_opt_month" name="download_periode_type" class="custom-control-input" value="month" checked>
                            <label class="custom-control-label small font-weight-bold" for="download_opt_month">Pilih Bulan &amp; Tahun (1 Bulan Penuh)</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="download_opt_range" name="download_periode_type" class="custom-control-input" value="range">
                            <label class="custom-control-label small font-weight-bold" for="download_opt_range">Gunakan Rentang Tanggal Filter Halaman</label>
                        </div>
                    </div>

                    <div id="section_download_month" class="row">
                        <!-- Pilihan: Pilih Bulan & Tahun -->
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-gray-700 small">
                                <i class="far fa-calendar-alt mr-1 text-info"></i> Pilih Bulan:
                            </label>
                            <select class="form-control" id="download_bulan">
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-gray-700 small">
                                <i class="fas fa-calendar mr-1 text-info"></i> Pilih Tahun:
                            </label>
                            <select class="form-control" id="download_tahun">
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                                <option value="2027">2027</option>
                            </select>
                        </div>
                    </div>

                    <div id="section_download_range" class="alert alert-info border-0 p-2 small mb-3" style="display: none;">
                        <i class="fas fa-info-circle mr-1"></i> File Excel akan diekspor sesuai tanggal filter aktif: <strong id="preview_download_range"></strong>
                    </div>

                    <!-- Ringkasan Isi 6 Tabel -->
                    <div class="card border-0 bg-light rounded mt-2">
                        <div class="card-body p-3">
                            <h6 class="font-weight-bold text-gray-800 small mb-2">
                                <i class="fas fa-list-ol mr-1 text-info"></i> Rincian 6 Tabel yang Diekspor ke Dalam Sheet:
                            </h6>
                            <ol class="small text-muted mb-0 pl-3">
                                <li class="mb-1"><strong>Tabel 1:</strong> Rontgen Tanpa Kontras Rawat Jalan (Tanpa USG &amp; MSCT)</li>
                                <li class="mb-1"><strong>Tabel 2:</strong> Rontgen Tanpa Kontras Rawat Inap (Tanpa USG &amp; MSCT)</li>
                                <li class="mb-1"><strong>Tabel 3:</strong> Pemeriksaan USG Rawat Jalan</li>
                                <li class="mb-1"><strong>Tabel 4:</strong> Pemeriksaan USG Rawat Inap</li>
                                <li class="mb-1"><strong>Tabel 5:</strong> Rontgen Tanpa Kontras Rujukan Dari Luar RS (Unit Radiologi)</li>
                                <li><strong>Tabel 6:</strong> Pasien Radiologi Rujukan Ke Luar RS (MSCT)</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="button" class="btn btn-success shadow-sm" id="btn-submit-download-bulanan">
                        <i class="fas fa-file-excel mr-1"></i> Unduh File Excel (.xlsx)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: KONEKSI DATABASE (Setting / Info Split Server)          -->
    <!-- ============================================================== -->
    <div class="modal fade" id="settingModal" tabindex="-1" role="dialog" aria-labelledby="settingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold" id="settingModalLabel">
                        <i class="fas fa-server mr-2"></i> Konfigurasi Split Server & Koneksi Database SIK
                    </h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Aplikasi ini menggunakan arsitektur <em>split server</em> lintas sistem operasi (Windows untuk Web Server, Linux untuk Database MySQL SIK) sesuai dokumen spesifikasi <strong>PRD v1.2</strong>.
                    </p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 bg-light h-100">
                                <h6 class="font-weight-bold text-primary mb-2">
                                    <i class="fab fa-windows mr-1"></i> Web Server (Aplikasi)
                                </h6>
                                <ul class="list-unstyled small mb-0">
                                    <li><strong>IP Address:</strong> <code>192.168.1.36</code></li>
                                    <li><strong>Sistem Operasi:</strong> Windows</li>
                                    <li><strong>Environment:</strong> XAMPP (Apache)</li>
                                    <li><strong>Versi PHP:</strong> PHP 8.2 (PDO MySQL)</li>
                                    <li><strong>Peran:</strong> Antarmuka, Filter, dan Client-side Exporter</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 bg-light h-100">
                                <h6 class="font-weight-bold text-success mb-2">
                                    <i class="fas fa-database mr-1"></i> Database Server (SIK)
                                </h6>
                                <ul class="list-unstyled small mb-0">
                                    <li><strong>IP Address:</strong> <code>192.168.1.2</code></li>
                                    <li><strong>Port MySQL:</strong> <code>3306</code></li>
                                    <li><strong>Nama Database:</strong> <code>sik</code></li>
                                    <li><strong>User Akun (NF.01):</strong> <code>report_viewer</code> (Read-Only)</li>
                                    <li><strong>Akses Privileges:</strong> <code>GRANT SELECT ON sik.*</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Uji Koneksi Live -->
                    <div class="card mt-2 border-left-info shadow-sm">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="font-weight-bold text-gray-800 mb-1">Uji Status Koneksi Port 3306</h6>
                                    <small class="text-muted">Kirimkan request ping PDO ke <code>192.168.1.2:3306/sik</code></small>
                                </div>
                                <button type="button" class="btn btn-info btn-sm font-weight-bold" id="btn-test-db">
                                    <i class="fas fa-sync-alt mr-1"></i> Uji Koneksi Sekarang
                                </button>
                            </div>
                            <div id="db-test-result"></div>
                        </div>
                    </div>

                    <div class="alert alert-secondary small mt-3 mb-0">
                        <strong><i class="fas fa-shield-alt text-primary mr-1"></i> Keamanan Sesuai PRD (NF.01):</strong><br>
                        Koneksi dari Web Server tidak menggunakan user <code>root</code>. Query dijamin 100% <em>Read-Only (SELECT)</em> tanpa adanya operasi manipulasi data (Create, Update, Delete).
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: PANDUAN PENGGUNAAN & EKSPOR LAPORAN                     -->
    <!-- ============================================================== -->
    <div class="modal fade" id="guideModal" tabindex="-1" role="dialog" aria-labelledby="guideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold" id="guideModalLabel">
                        <i class="fas fa-book mr-2"></i> Panduan Ekstraksi &amp; Ekspor Laporan SIK
                    </h5>
                    <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6 class="font-weight-bold text-gray-800 mb-2">1. Cara Melakukan Filter Data (F1.01)</h6>
                    <p class="small text-muted mb-3">
                        Pilih <strong>Tanggal Mulai</strong> dan <strong>Tanggal Akhir</strong> yang diinginkan, kemudian tentukan <strong>Kategori/Unit</strong> (misalnya: Radiologi, Rawat Jalan, dsb). Klik tombol <strong>"Tampilkan Data"</strong> untuk mengirim query ke database server.
                    </p>

                    <h6 class="font-weight-bold text-gray-800 mb-2">2. Fitur Pencarian Cepat &amp; Pengurutan (F2.02 &amp; F2.03)</h6>
                    <p class="small text-muted mb-3">
                        Ketik nama pasien, nomor rawat, nama dokter, atau tindakan pada kolom <em>"Cari data instan..."</em> di atas tabel. Data akan tersaring secara otomatis seketika. Klik judul kolom mana pun untuk mengurutkan (A-Z atau tanggal terbaru).
                    </p>

                    <h6 class="font-weight-bold text-gray-800 mb-2">3. Opsi Ekspor Laporan (F3.01 - F3.03 &amp; NF.02)</h6>
                    <div class="row small mb-2">
                        <div class="col-md-6 mb-2">
                            <span class="badge badge-secondary px-2 py-1"><i class="fas fa-copy"></i> Copy:</span> Salin seluruh data yang tampil untuk langsung ditempel (*paste*) ke Microsoft Excel.
                        </div>
                        <div class="col-md-6 mb-2">
                            <span class="badge badge-info px-2 py-1"><i class="fas fa-file-csv"></i> CSV:</span> Unduh data dalam format CSV standar (UTF-8 BOM).
                        </div>
                        <div class="col-md-6 mb-2">
                            <span class="badge badge-success px-2 py-1"><i class="fas fa-file-excel"></i> Excel:</span> Unduh file lembar kerja Excel (.xlsx/.xls) dengan kop dan tabel rapi.
                        </div>
                        <div class="col-md-6 mb-2">
                            <span class="badge badge-danger px-2 py-1"><i class="fas fa-file-pdf"></i> PDF:</span> Buka jendela pratinjau dokumen cetak A4 landscape dengan kop surat resmi.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins (DataTables) -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom script untuk Laporan SIK -->
    <script src="js/laporan-sik.js?v=<?= time() ?>"></script>

</body>

</html>
