<?php
session_start();
require 'koneksi.php';

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Filter
$filter_jenis = $_GET['jenis'] ?? 'all';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

// Query dengan filter
$sql = "SELECT * FROM absensi_kamera WHERE user_id = ?";
$params = [$user_id];

if ($filter_jenis !== 'all') {
    $sql .= " AND jenis_absen = ?";
    $params[] = $filter_jenis;
}

if (!empty($filter_bulan)) {
    $sql .= " AND DATE_FORMAT(tanggal, '%Y-%m') = ?";
    $params[] = $filter_bulan;
}

$sql .= " ORDER BY tanggal DESC, waktu_absen DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$riwayat = $stmt->fetchAll();

// Statistik
$stmt_stats = $pdo->prepare("
    SELECT 
        jenis_absen,
        COUNT(*) as total
    FROM absensi_kamera 
    WHERE user_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?
    GROUP BY jenis_absen
");
$stmt_stats->execute([$user_id, $filter_bulan]);
$stats = $stmt_stats->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat Absensi - MY-Visite</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #a8e6cf 0%, #dcedc8 100%);
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    padding: 15px;
    padding-bottom: 90px;
}

.container {
    max-width: 600px;
    margin: 0 auto;
}

/* HEADER */
.header-card {
    background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
    padding: 20px;
    border-radius: 20px;
    color: white;
    box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
    margin-bottom: 20px;
    text-align: center;
    position: relative;
}

.btn-back {
    position: absolute;
    top: 20px;
    left: 20px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-back:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateX(-3px);
}

.header-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 4px;
}

.header-subtitle {
    font-size: 13px;
    opacity: 0.9;
}

/* FILTER CARD */
.filter-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.filter-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 12px;
}

.filter-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.form-select-custom {
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 13px;
    transition: all 0.3s;
}

.form-select-custom:focus {
    outline: none;
    border-color: #4ade80;
    box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.1);
}

/* STATS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.stat-box {
    background: white;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    text-align: center;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 20px;
}

.stat-icon.poli {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
}

.stat-icon.visite {
    background: linear-gradient(135deg, #fce7f3, #fbcfe8);
    color: #be185d;
}

.stat-icon.dinas {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #d97706;
}

.stat-icon.operasi {
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #4f46e5;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 11px;
    color: #6b7280;
}

/* ABSENSI LIST */
.absensi-list-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
}

.section-title i {
    color: #22c55e;
    margin-right: 8px;
    font-size: 20px;
}

.absensi-item {
    border: 2px solid #f3f4f6;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.3s;
}

.absensi-item:hover {
    border-color: #4ade80;
    box-shadow: 0 4px 12px rgba(74, 222, 128, 0.15);
}

.absensi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.absensi-jenis {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

.absensi-tanggal {
    font-size: 12px;
    color: #6b7280;
}

.absensi-photo {
    width: 100%;
    border-radius: 12px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.3s;
}

.absensi-photo:hover {
    transform: scale(1.02);
}

.absensi-info {
    background: #f9fafb;
    padding: 10px;
    border-radius: 10px;
    font-size: 12px;
    color: #4b5563;
}

.absensi-info i {
    color: #22c55e;
    margin-right: 6px;
}

.badge-jenis {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
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
    max-width: 100%;
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
}

/* FOOTER */
.footer-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 75px;
    background: white;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-around;
    align-items: center;
    box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.08);
    z-index: 999;
}

.footer-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #6b7280;
    transition: all 0.3s;
    padding: 8px 16px;
    border-radius: 12px;
}

.footer-btn.active {
    color: #22c55e;
    background: #f0fdf4;
}

.footer-btn i {
    font-size: 24px;
    margin-bottom: 4px;
}

.footer-btn span {
    font-size: 11px;
    font-weight: 500;
}
</style>
</head>

<body>

<div class="container">
    
    <!-- HEADER -->
    <div class="header-card">
        <a href="dashboard.php" class="btn-back">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="header-title">📋 Riwayat Absensi</div>
        <div class="header-subtitle">Lihat detail absensi Anda</div>
    </div>

    <!-- FILTER -->
    <div class="filter-card">
        <div class="filter-title">
            <i class="bi bi-funnel-fill me-2"></i>Filter Data
        </div>
        <form method="GET" action="">
            <div class="filter-group">
                <select name="jenis" class="form-select-custom" onchange="this.form.submit()">
                    <option value="all" <?= $filter_jenis === 'all' ? 'selected' : '' ?>>Semua Jenis</option>
                    <option value="Poliklinik" <?= $filter_jenis === 'Poliklinik' ? 'selected' : '' ?>>Poliklinik</option>
                    <option value="Visite" <?= $filter_jenis === 'Visite' ? 'selected' : '' ?>>Visite</option>
                    <option value="Jam Dinas" <?= $filter_jenis === 'Jam Dinas' ? 'selected' : '' ?>>Jam Dinas</option>
                    <option value="Operasi" <?= $filter_jenis === 'Operasi' ? 'selected' : '' ?>>Operasi</option>
                </select>

                <input 
                    type="month" 
                    name="bulan" 
                    class="form-select-custom" 
                    value="<?= htmlspecialchars($filter_bulan) ?>"
                    onchange="this.form.submit()"
                >
            </div>
        </form>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-icon poli">
                <i class="bi bi-hospital-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['Poliklinik'] ?? 0 ?></div>
            <div class="stat-label">Poliklinik</div>
        </div>

        <div class="stat-box">
            <div class="stat-icon visite">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['Visite'] ?? 0 ?></div>
            <div class="stat-label">Visite</div>
        </div>

        <div class="stat-box">
            <div class="stat-icon dinas">
                <i class="bi bi-building-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['Jam Dinas'] ?? 0 ?></div>
            <div class="stat-label">Jam Dinas</div>
        </div>

        <div class="stat-box">
            <div class="stat-icon operasi">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div class="stat-value"><?= $stats['Operasi'] ?? 0 ?></div>
            <div class="stat-label">Operasi</div>
        </div>
    </div>

    <!-- LIST ABSENSI -->
    <div class="absensi-list-card">
        <div class="section-title">
            <i class="bi bi-list-check"></i>
            Daftar Absensi
        </div>

        <?php if (count($riwayat) > 0): ?>
            <?php foreach ($riwayat as $item): ?>
                <?php
                    // Badge class
                    $badge_class = '';
                    if ($item['jenis_absen'] == 'Poliklinik') $badge_class = 'poli';
                    elseif ($item['jenis_absen'] == 'Visite') $badge_class = 'visite';
                    elseif ($item['jenis_absen'] == 'Jam Dinas') $badge_class = 'dinas';
                    elseif ($item['jenis_absen'] == 'Operasi') $badge_class = 'operasi';
                ?>
                <div class="absensi-item">
                    <div class="absensi-header">
                        <div>
                            <span class="badge-jenis <?= $badge_class ?>">
                                <?= htmlspecialchars($item['jenis_absen']) ?>
                            </span>
                        </div>
                        <div class="absensi-tanggal">
                            <?= date('d M Y', strtotime($item['tanggal'])) ?>
                        </div>
                    </div>

                    <img 
                        src="<?= htmlspecialchars($item['foto']) ?>" 
                        class="absensi-photo" 
                        onclick="showModal('<?= htmlspecialchars($item['foto']) ?>')"
                        alt="Foto Absensi"
                    >

                    <div class="absensi-info">
                        <div>
                            <i class="bi bi-clock-fill"></i>
                            <strong>Waktu:</strong> <?= $item['waktu_absen'] ?> WIB
                        </div>
                        <?php if (!empty($item['keterangan'])): ?>
                        <div style="margin-top: 6px;">
                            <i class="bi bi-chat-text-fill"></i>
                            <strong>Keterangan:</strong> <?= htmlspecialchars($item['keterangan']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>Tidak Ada Data</h4>
                <p>Belum ada riwayat absensi untuk filter ini</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- FOOTER -->
<div class="footer-nav">
    <a href="dashboard.php" class="footer-btn">
        <i class="bi bi-house-fill"></i>
        <span>Home</span>
    </a>

    <a href="dashboard.php" class="footer-btn">
        <i class="bi bi-camera-fill"></i>
        <span>Absensi</span>
    </a>

    <a href="riwayat.php" class="footer-btn active">
        <i class="bi bi-clock-history"></i>
        <span>Riwayat</span>
    </a>

    <a href="profil.php" class="footer-btn">
        <i class="bi bi-person-circle"></i>
        <span>Profil</span>
    </a>
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