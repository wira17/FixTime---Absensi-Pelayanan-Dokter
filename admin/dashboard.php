<?php
// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Cek koneksi database
try {
    require '../koneksi.php';
} catch (Exception $e) {
    die("Error koneksi database: " . $e->getMessage());
}

// Cek session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Cek role admin
if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak! Hanya admin yang bisa mengakses halaman ini.");
}

date_default_timezone_set('Asia/Jakarta');

try {
    // Total Pengguna
    $totUser = $pdo->query("SELECT COUNT(*) FROM pengguna")->fetchColumn();

    // Total Absensi Hari Ini
    $tanggal = date("Y-m-d");
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi_kamera WHERE tanggal=?");
    $stmt->execute([$tanggal]);
    $jmlAbsensi = $stmt->fetchColumn();

    // Data Absensi 7 Hari Terakhir
    $minggu = $pdo->query("
        SELECT tanggal, COUNT(*) AS jml
        FROM absensi_kamera
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY tanggal
        ORDER BY tanggal ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Total Spesialis
    $totSpesialis = $pdo->query("SELECT COUNT(*) FROM master_spesialis")->fetchColumn();

    // Absensi Terbaru
    $recentAbsensi = $pdo->query("
        SELECT a.*, p.nama, p.email 
        FROM absensi_kamera a 
        JOIN pengguna p ON a.user_id = p.id 
        ORDER BY a.tanggal DESC, a.waktu_absen DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Statistik per Jenis Absensi
    $stmt_stat = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN jenis_absen = 'Masuk' THEN 1 END) as total_masuk,
            COUNT(CASE WHEN jenis_absen = 'Pulang' THEN 1 END) as total_pulang,
            COUNT(CASE WHEN jenis_absen = 'Jam Dinas' THEN 1 END) as total_dinas
        FROM absensi_kamera
        WHERE tanggal = ?
    ");
    $stmt_stat->execute([$tanggal]);
    $statJenis = $stmt_stat->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Admin - FixTime</title>

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
    padding: 30px;
}

/* HEADER */
.page-header {
    background: white;
    padding: 24px 28px;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1b5e20;
    margin-bottom: 4px;
}

.header-subtitle {
    color: #66bb6a;
    font-size: 14px;
}

.header-right {
    text-align: right;
}

.current-time {
    font-size: 24px;
    font-weight: 700;
    color: #2e7d32;
    margin-bottom: 2px;
}

.current-date {
    font-size: 13px;
    color: #66bb6a;
}

/* STAT CARDS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #66bb6a, #43a047);
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
}

.stat-icon-box {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    font-size: 28px;
}

.stat-icon-box.green {
    background: linear-gradient(135deg, #c8e6c9, #a5d6a7);
    color: #2e7d32;
}

.stat-icon-box.blue {
    background: linear-gradient(135deg, #bbdefb, #90caf9);
    color: #1976d2;
}

.stat-icon-box.orange {
    background: linear-gradient(135deg, #ffe0b2, #ffcc80);
    color: #f57c00;
}

.stat-icon-box.purple {
    background: linear-gradient(135deg, #e1bee7, #ce93d8);
    color: #7b1fa2;
}

.stat-icon-box.red {
    background: linear-gradient(135deg, #ffcdd2, #ef9a9a);
    color: #c62828;
}

.stat-icon-box.teal {
    background: linear-gradient(135deg, #b2dfdb, #80cbc4);
    color: #00796b;
}

.stat-label {
    font-size: 13px;
    color: #757575;
    font-weight: 500;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 32px;
    font-weight: 800;
    color: #1b5e20;
    margin-bottom: 4px;
}

.stat-change {
    font-size: 12px;
    color: #66bb6a;
    font-weight: 600;
}

/* CHART & TABLE SECTION */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-bottom: 30px;
}

.chart-card,
.table-card {
    background: white;
    padding: 28px;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.card-title {
    font-size: 18px;
    font-weight: 700;
    color: #1b5e20;
    display: flex;
    align-items: center;
}

.card-title i {
    margin-right: 10px;
    color: #66bb6a;
    font-size: 22px;
}

.badge-info {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

/* ACTIVITY LIST */
.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    display: flex;
    padding: 14px 0;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: #f9fafb;
    margin: 0 -10px;
    padding: 14px 10px;
    border-radius: 10px;
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #c8e6c9, #a5d6a7);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;
}

.activity-icon i {
    color: #2e7d32;
    font-size: 18px;
}

.activity-content {
    flex-grow: 1;
}

.activity-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 3px;
}

.activity-time {
    font-size: 12px;
    color: #9ca3af;
}

/* FULL TABLE */
.full-table-card {
    background: white;
    padding: 28px;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead th {
    background: #f9fafb;
    padding: 14px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
}

.data-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
    color: #6b7280;
}

.data-table tbody tr:hover {
    background: #f9fafb;
}

.badge-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-status.success {
    background: #d1fae5;
    color: #065f46;
}

.badge-status.warning {
    background: #fef3c7;
    color: #92400e;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }
    
    .brand-subtitle,
    .menu-link span {
        display: none;
    }
    
    .main-content {
        margin-left: 70px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="header-left">
            <h1>👋 Selamat Datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>!</h1>
            <div class="header-subtitle">Kelola sistem absensi dengan mudah dan efisien</div>
        </div>
        <div class="header-right">
            <div class="current-time" id="currentTime">00:00:00</div>
            <div class="current-date" id="currentDate">Loading...</div>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-grid">
        <div class="stat-card" onclick="window.location.href='pengguna.php'">
            <div class="stat-icon-box green">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-label">Total Pengguna</div>
            <div class="stat-value"><?= $totUser ?></div>
            <div class="stat-change"><i class="bi bi-arrow-right"></i> Lihat Detail</div>
        </div>

        <div class="stat-card" onclick="window.location.href='rekap_absensi.php'">
            <div class="stat-icon-box blue">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div class="stat-label">Absensi Hari Ini</div>
            <div class="stat-value"><?= $jmlAbsensi ?></div>
            <div class="stat-change"><i class="bi bi-arrow-right"></i> Lihat Detail</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box teal">
                <i class="bi bi-box-arrow-in-right"></i>
            </div>
            <div class="stat-label">Absen Masuk</div>
            <div class="stat-value"><?= $statJenis['total_masuk'] ?></div>
            <div class="stat-change">Hari Ini</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box orange">
                <i class="bi bi-box-arrow-right"></i>
            </div>
            <div class="stat-label">Absen Pulang</div>
            <div class="stat-value"><?= $statJenis['total_pulang'] ?></div>
            <div class="stat-change">Hari Ini</div>
        </div>

        <div class="stat-card" onclick="window.location.href='master_spesialis.php'">
            <div class="stat-icon-box purple">
                <i class="bi bi-card-list"></i>
            </div>
            <div class="stat-label">Total Spesialis</div>
            <div class="stat-value"><?= $totSpesialis ?></div>
            <div class="stat-change"><i class="bi bi-arrow-right"></i> Lihat Detail</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box red">
                <i class="bi bi-clock-fill"></i>
            </div>
            <div class="stat-label">Jam Dinas</div>
            <div class="stat-value"><?= $statJenis['total_dinas'] ?></div>
            <div class="stat-change">Hari Ini</div>
        </div>
    </div>

    <!-- CHART & ACTIVITY -->
    <div class="content-grid">
        <!-- CHART -->
        <div class="chart-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="bi bi-graph-up"></i>
                    Grafik Absensi
                </div>
                <span class="badge-info">7 Hari Terakhir</span>
            </div>
            <canvas id="chartAbsensi" height="80"></canvas>
        </div>

        <!-- RECENT ACTIVITY -->
        <div class="table-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="bi bi-clock-history"></i>
                    Aktivitas Terbaru
                </div>
            </div>
            <ul class="activity-list">
                <?php if (count($recentAbsensi) > 0): ?>
                    <?php foreach ($recentAbsensi as $recent): ?>
                    <li class="activity-item">
                        <div class="activity-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?= htmlspecialchars($recent['nama']) ?></div>
                            <div class="activity-time">
                                <?= date('d/m/Y', strtotime($recent['tanggal'])) ?> • 
                                <?= date('H:i', strtotime($recent['waktu_absen'])) ?> WIB •
                                <?= htmlspecialchars($recent['jenis_absen']) ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="text-align: center; padding: 20px; color: #9ca3af;">
                        Belum ada aktivitas
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- FULL TABLE -->
    <div class="full-table-card">
        <div class="card-header">
            <div class="card-title">
                <i class="bi bi-table"></i>
                Data Absensi Terbaru
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Jenis Absen</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recentAbsensi) > 0): ?>
                    <?php foreach ($recentAbsensi as $recent): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($recent['nama']) ?></strong></td>
                        <td><?= htmlspecialchars($recent['email']) ?></td>
                        <td>
                            <?php
                            $badge_class = 'success';
                            if ($recent['jenis_absen'] === 'Pulang') $badge_class = 'warning';
                            ?>
                            <span class="badge-status <?= $badge_class ?>">
                                <?= htmlspecialchars($recent['jenis_absen']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($recent['tanggal'])) ?></td>
                        <td><?= date('H:i', strtotime($recent['waktu_absen'])) ?> WIB</td>
                        <td><span class="badge-status success">Hadir</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #9ca3af;">
                            Belum ada data absensi
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// CLOCK
function updateClock() {
    const now = new Date();
    
    const hari = ["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];
    const bulan = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    
    const jam = String(now.getHours()).padStart(2, '0');
    const menit = String(now.getMinutes()).padStart(2, '0');
    const detik = String(now.getSeconds()).padStart(2, '0');
    
    document.getElementById('currentTime').textContent = `${jam}:${menit}:${detik}`;
    document.getElementById('currentDate').textContent = `${hari[now.getDay()]}, ${now.getDate()} ${bulan[now.getMonth()]} ${now.getFullYear()}`;
}

updateClock();
setInterval(updateClock, 1000);

// CHART
const labels = <?= json_encode(array_map(function($item) {
    return date('d/m', strtotime($item['tanggal']));
}, $minggu)) ?>;

const dataVals = <?= json_encode(array_column($minggu, 'jml')) ?>;

const ctx = document.getElementById('chartAbsensi');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Jumlah Absensi',
            data: dataVals,
            borderColor: '#66bb6a',
            backgroundColor: 'rgba(102, 187, 106, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#43a047',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    color: '#6b7280'
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                ticks: {
                    color: '#6b7280'
                },
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

</body>
</html>