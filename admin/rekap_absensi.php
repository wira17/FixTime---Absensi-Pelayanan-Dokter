<?php
session_start();
require '../koneksi.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    echo "Akses ditolak! Hanya admin.";
    exit;
}

// Filter
$filter_dokter = $_GET['dokter'] ?? 'all';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');
$filter_jenis = $_GET['jenis'] ?? 'all';

// Get list dokter untuk dropdown
$stmt_dokter = $pdo->query("
    SELECT DISTINCT p.id, p.nama, p.email 
    FROM pengguna p
    INNER JOIN absensi_kamera ak ON p.id = ak.user_id
    WHERE p.role = 'user'
    ORDER BY p.nama ASC
");
$list_dokter = $stmt_dokter->fetchAll(PDO::FETCH_ASSOC);

// Query rekap dengan filter
$sql = "
    SELECT 
        p.id as user_id,
        p.nama,
        p.email,
        ak.jenis_absen,
        ak.tanggal,
        ak.waktu_absen,
        ak.foto,
        ak.keterangan,
        ak.created_at
    FROM absensi_kamera ak
    INNER JOIN pengguna p ON ak.user_id = p.id
    WHERE DATE_FORMAT(ak.tanggal, '%Y-%m') = ?
";

$params = [$filter_bulan];

if ($filter_dokter !== 'all') {
    $sql .= " AND p.id = ?";
    $params[] = $filter_dokter;
}

if ($filter_jenis !== 'all') {
    $sql .= " AND ak.jenis_absen = ?";
    $params[] = $filter_jenis;
}

$sql .= " ORDER BY ak.tanggal DESC, ak.waktu_absen DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data_absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik Ringkasan
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT user_id) as total_dokter,
        COUNT(*) as total_absensi,
        COUNT(DISTINCT tanggal) as total_hari,
        COUNT(CASE WHEN jenis_absen = 'Poliklinik' THEN 1 END) as total_poli,
        COUNT(CASE WHEN jenis_absen = 'Visite' THEN 1 END) as total_visite,
        COUNT(CASE WHEN jenis_absen = 'Jam Dinas' THEN 1 END) as total_dinas,
        COUNT(CASE WHEN jenis_absen = 'Operasi' THEN 1 END) as total_operasi
    FROM absensi_kamera ak
    INNER JOIN pengguna p ON ak.user_id = p.id
    WHERE DATE_FORMAT(ak.tanggal, '%Y-%m') = ?
    " . ($filter_dokter !== 'all' ? " AND p.id = ?" : "")
);

$stats_params = [$filter_bulan];
if ($filter_dokter !== 'all') {
    $stats_params[] = $filter_dokter;
}
$stmt_stats->execute($stats_params);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Rekap per dokter
$stmt_per_dokter = $pdo->prepare("
    SELECT 
        p.nama,
        p.email,
        COUNT(*) as total,
        COUNT(CASE WHEN ak.jenis_absen = 'Poliklinik' THEN 1 END) as poli,
        COUNT(CASE WHEN ak.jenis_absen = 'Visite' THEN 1 END) as visite,
        COUNT(CASE WHEN ak.jenis_absen = 'Jam Dinas' THEN 1 END) as dinas,
        COUNT(CASE WHEN ak.jenis_absen = 'Operasi' THEN 1 END) as operasi
    FROM absensi_kamera ak
    INNER JOIN pengguna p ON ak.user_id = p.id
    WHERE DATE_FORMAT(ak.tanggal, '%Y-%m') = ?
    " . ($filter_dokter !== 'all' ? " AND p.id = ?" : "") . "
    GROUP BY p.id, p.nama, p.email
    ORDER BY total DESC
");
$stmt_per_dokter->execute($stats_params);
$rekap_dokter = $stmt_per_dokter->fetchAll(PDO::FETCH_ASSOC);

// Format bulan untuk display
$bulan_display = date('F Y', strtotime($filter_bulan . '-01'));
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rekap Absensi - FixTime Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 50%, #a5d6a7 100%);
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    padding-bottom: 60px;
}

/* SIDEBAR */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 260px;
    background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%);
    padding: 24px 0;
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-brand {
    padding: 0 24px 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 20px;
}

.brand-title {
    color: white;
    font-size: 22px;
    font-weight: 800;
    display: flex;
    align-items: center;
    text-decoration: none;
}

.brand-title i {
    font-size: 28px;
    margin-right: 12px;
    color: #81c784;
}

.brand-subtitle {
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
    margin-top: 4px;
}

.sidebar-menu {
    list-style: none;
    padding: 0 12px;
}

.menu-item {
    margin-bottom: 6px;
}

.menu-link {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s;
    font-size: 14px;
    font-weight: 500;
}

.menu-link:hover,
.menu-link.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    transform: translateX(4px);
}

.menu-link i {
    font-size: 20px;
    margin-right: 12px;
    width: 24px;
}

.menu-link.logout {
    color: #ffcdd2;
}

.menu-link.logout:hover {
    background: rgba(244, 67, 54, 0.2);
    color: #ff5252;
}

/* MAIN CONTENT */
.main-content {
    margin-left: 260px;
    padding: 24px;
}

/* HEADER */
.page-header {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #1b5e20;
    margin-bottom: 4px;
}

.page-subtitle {
    color: #6b7280;
    font-size: 14px;
}

/* FILTER CARD */
.filter-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.filter-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
}

.filter-title i {
    color: #2e7d32;
    margin-right: 8px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.form-select-custom, .form-control-custom {
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-select-custom:focus, .form-control-custom:focus {
    outline: none;
    border-color: #4ade80;
    box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.1);
}

.btn-filter {
    background: linear-gradient(135deg, #4ade80, #22c55e);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
}

.btn-export {
    background: white;
    color: #2e7d32;
    border: 2px solid #2e7d32;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-export:hover {
    background: #2e7d32;
    color: white;
}

/* STATS CARDS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 12px;
}

.stat-icon.blue {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
}

.stat-icon.green {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #059669;
}

.stat-icon.purple {
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #4f46e5;
}

.stat-icon.orange {
    background: linear-gradient(135deg, #fed7aa, #fdba74);
    color: #ea580c;
}

.stat-icon.pink {
    background: linear-gradient(135deg, #fce7f3, #fbcfe8);
    color: #be185d;
}

.stat-icon.yellow {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #d97706;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    color: #6b7280;
}

/* TABLE */
.table-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
}

.section-title i {
    color: #2e7d32;
    margin-right: 8px;
    font-size: 22px;
}

.table-responsive {
    overflow-x: auto;
}

.custom-table {
    width: 100%;
    font-size: 13px;
    border-collapse: separate;
    border-spacing: 0;
}

.custom-table th {
    background: #f0fdf4;
    color: #166534;
    padding: 12px;
    font-weight: 600;
    text-align: left;
    border-bottom: 2px solid #22c55e;
    white-space: nowrap;
}

.custom-table td {
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.custom-table tr:hover {
    background: #f9fafb;
}

.badge-jenis {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.badge-jenis.poli {
    background: #dbeafe;
    color: #1e40af;
}

.badge-jenis.visite {
    background: #fce7f3;
    color: #be185d;
}

.badge-jenis.dinas {
    background: #fef3c7;
    color: #d97706;
}

.badge-jenis.operasi {
    background: #e0e7ff;
    color: #4f46e5;
}

.foto-thumb {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
    cursor: pointer;
    transition: all 0.3s;
}

.foto-thumb:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* MODAL */
.modal-foto {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-foto.show {
    display: flex;
}

.modal-foto img {
    max-width: 90%;
    max-height: 90vh;
    border-radius: 12px;
}

.modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    background: white;
    color: #1f2937;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 20px;
    cursor: pointer;
    z-index: 10000;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <a href="dashboard.php" class="brand-title">
            <i class="bi bi-house-heart-fill"></i>
            <div>
                <div>FixTime</div>
                <div class="brand-subtitle">Admin Panel</div>
            </div>
        </a>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="dashboard.php" class="menu-link active">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="pengguna.php" class="menu-link">
                <i class="bi bi-people-fill"></i>
                <span>Kelola Pengguna</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="master_spesialis.php" class="menu-link">
                <i class="bi bi-card-list"></i>
                <span>Data Spesialis</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="setting_lokasi.php" class="menu-link">
                <i class="bi bi-geo-alt-fill"></i>
                <span>Setting Lokasi</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="rekap_absensi.php" class="menu-link">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Rekap Absensi</span>
            </a>
        </li>
       
        <li class="menu-item">
            <a href="#.php" class="menu-link">
                <i class="bi bi-gear-fill"></i>
                <span>Pengaturan</span>
            </a>
        </li>
        <li class="menu-item" style="margin-top: 20px;">
            <a href="../logout.php" class="menu-link logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    
    <!-- HEADER -->
    <div class="page-header">
        <div class="page-title">📊 Rekap Absensi</div>
        <div class="page-subtitle">
            Periode: <strong><?= $bulan_display ?></strong>
            <?php if ($filter_dokter !== 'all'): ?>
                <?php 
                    $dokter_selected = array_filter($list_dokter, fn($d) => $d['id'] == $filter_dokter);
                    $dokter_selected = reset($dokter_selected);
                ?>
                • Dokter: <strong><?= $dokter_selected['nama'] ?? '' ?></strong>
            <?php endif; ?>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-card">
        <div class="filter-title">
            <i class="bi bi-funnel-fill"></i>
            Filter Data
        </div>
        
        <form method="GET" action="">
            <div class="filter-grid">
                <div>
                    <label style="font-size: 12px; color: #6b7280; margin-bottom: 6px; display: block;">Bulan</label>
                    <input 
                        type="month" 
                        name="bulan" 
                        class="form-control-custom" 
                        value="<?= htmlspecialchars($filter_bulan) ?>"
                        style="width: 100%;"
                    >
                </div>
                
                <div>
                    <label style="font-size: 12px; color: #6b7280; margin-bottom: 6px; display: block;">Dokter</label>
                    <select name="dokter" class="form-select-custom" style="width: 100%;">
                        <option value="all">Semua Dokter</option>
                        <?php foreach ($list_dokter as $dok): ?>
                            <option value="<?= $dok['id'] ?>" <?= $filter_dokter == $dok['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dok['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="font-size: 12px; color: #6b7280; margin-bottom: 6px; display: block;">Jenis Absensi</label>
                    <select name="jenis" class="form-select-custom" style="width: 100%;">
                        <option value="all">Semua Jenis</option>
                        <option value="Poliklinik" <?= $filter_jenis == 'Poliklinik' ? 'selected' : '' ?>>Poliklinik</option>
                        <option value="Visite" <?= $filter_jenis == 'Visite' ? 'selected' : '' ?>>Visite</option>
                        <option value="Jam Dinas" <?= $filter_jenis == 'Jam Dinas' ? 'selected' : '' ?>>Jam Dinas</option>
                        <option value="Operasi" <?= $filter_jenis == 'Operasi' ? 'selected' : '' ?>>Operasi</option>
                    </select>
                </div>
                
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn-filter" style="width: 100%;">
                        <i class="bi bi-search me-2"></i>Filter
                    </button>
                </div>
            </div>
        </form>
        
        <div style="margin-top: 12px;">
            <a href="export_excel.php?bulan=<?= $filter_bulan ?>&dokter=<?= $filter_dokter ?>&jenis=<?= $filter_jenis ?>" class="btn-export">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            </a>
            <a href="export_pdf.php?bulan=<?= $filter_bulan ?>&dokter=<?= $filter_dokter ?>&jenis=<?= $filter_jenis ?>" class="btn-export" style="margin-left: 8px;">
                <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
            </a>
        </div>
    </div>

    <!-- STATISTIK RINGKASAN -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['total_dokter'] ?? 0 ?></div>
            <div class="stat-label">Dokter Aktif</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['total_absensi'] ?? 0 ?></div>
            <div class="stat-label">Total Absensi</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-hospital-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['total_poli'] ?? 0 ?></div>
            <div class="stat-label">Poliklinik</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon pink">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['total_visite'] ?? 0 ?></div>
            <div class="stat-label">Visite</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="bi bi-building-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['total_dinas'] ?? 0 ?></div>
            <div class="stat-label">Jam Dinas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['total_operasi'] ?? 0 ?></div>
            <div class="stat-label">Operasi</div>
        </div>
    </div>

    <!-- REKAP PER DOKTER -->
    <?php if ($filter_dokter === 'all' && count($rekap_dokter) > 0): ?>
    <div class="table-card">
        <div class="section-title">
            <i class="bi bi-person-lines-fill"></i>
            Rekap Per Dokter
        </div>
        
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Dokter</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Poli</th>
                        <th>Visite</th>
                        <th>Dinas</th>
                        <th>Operasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($rekap_dokter as $dok): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($dok['nama']) ?></strong></td>
                        <td><?= htmlspecialchars($dok['email']) ?></td>
                        <td><strong><?= $dok['total'] ?></strong></td>
                        <td><?= $dok['poli'] ?></td>
                        <td><?= $dok['visite'] ?></td>
                        <td><?= $dok['dinas'] ?></td>
                        <td><?= $dok['operasi'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- DETAIL ABSENSI -->
    <div class="table-card">
        <div class="section-title">
            <i class="bi bi-list-check"></i>
            Detail Absensi (<?= count($data_absensi) ?> record)
        </div>
        
        <?php if (count($data_absensi) > 0): ?>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama Dokter</th>
                        <th>Jenis</th>
                        <th>Waktu</th>
                        <th>Foto</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($data_absensi as $abs): 
                        // Badge class
                        $badge_class = '';
                        if ($abs['jenis_absen'] == 'Poliklinik') $badge_class = 'poli';
                        elseif ($abs['jenis_absen'] == 'Visite') $badge_class = 'visite';
                        elseif ($abs['jenis_absen'] == 'Jam Dinas') $badge_class = 'dinas';
                        elseif ($abs['jenis_absen'] == 'Operasi') $badge_class = 'operasi';
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d M Y', strtotime($abs['tanggal'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($abs['nama']) ?></strong><br>
                            <small style="color: #6b7280;"><?= htmlspecialchars($abs['email']) ?></small>
                        </td>
                        <td>
                            <span class="badge-jenis <?= $badge_class ?>">
                                <?= htmlspecialchars($abs['jenis_absen']) ?>
                            </span>
                        </td>
                        <td><?= $abs['waktu_absen'] ?></td>
                        <td>
                            <img 
                                src="../<?= htmlspecialchars($abs['foto']) ?>" 
                                class="foto-thumb" 
                                onclick="showModal('../<?= htmlspecialchars($abs['foto']) ?>')"
                                alt="Foto"
                            >
                        </td>
                        <td><?= htmlspecialchars($abs['keterangan'] ?: '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h4>Tidak Ada Data</h4>
            <p>Belum ada data absensi untuk filter yang dipilih</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL FOTO -->
<div class="modal-foto" id="modalFoto" onclick="hideModal()">
    <button class="modal-close" onclick="hideModal()">
        <i class="bi bi-x-lg"></i>
    </button>
    <img id="modalImage" src="" alt="Foto Absensi">
</div>

<script>
function showModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('modalFoto').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function hideModal() {
    document.getElementById('modalFoto').classList.remove('show');
    document.body.style.overflow = '';
}

// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideModal();
    }
});
</script>

</body>
</html>