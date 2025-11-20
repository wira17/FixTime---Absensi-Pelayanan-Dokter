<?php
session_start();

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'user') {
    echo "Akses ditolak! Halaman ini hanya untuk user.";
    exit;
}

require 'koneksi.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Ambil data absensi kamera hari ini
$stmt_kamera = $pdo->prepare("
    SELECT * FROM absensi_kamera 
    WHERE user_id = ? AND tanggal = ? 
    ORDER BY waktu_absen ASC
");
$stmt_kamera->execute([$user_id, $today]);
$absensi_hari_ini = $stmt_kamera->fetchAll();

// Hitung statistik bulan ini per jenis
$bulan_ini = date('Y-m');
$jenis_list = ['Poliklinik', 'Visite', 'Jam Dinas', 'Operasi'];
$stats_per_jenis = [];

foreach ($jenis_list as $jenis) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM absensi_kamera 
        WHERE user_id = ? AND tanggal LIKE ? AND jenis_absen = ?
    ");
    $stmt->execute([$user_id, "$bulan_ini%", $jenis]);
    $result = $stmt->fetch();
    $stats_per_jenis[$jenis] = $result['total'] ?? 0;
}

// Total absensi bulan ini
$total_hadir = array_sum($stats_per_jenis);

// Ambil riwayat absensi (semua jenis)
$stmt_riwayat = $pdo->prepare("
    SELECT * FROM absensi_kamera 
    WHERE user_id = ? 
    ORDER BY tanggal DESC, waktu_absen DESC 
    LIMIT 15
");
$stmt_riwayat->execute([$user_id]);
$riwayat = $stmt_riwayat->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Dashboard FixTime</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/dashboard.css">

</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header-app">
        <div class="header-content">
            <div class="header-greeting">👋 Selamat Datang, Assalamualaikum Wr, Wb</div>
            <div class="header-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
            <div class="header-subtitle">🌿 FixTime • Absensi Pelayanan Medis</div>
            <a href="logout.php" class="btn-logout">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>

    <!-- JAM & TANGGAL -->
    <div class="clock-card">
        <div class="clock-icon">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="clock-time" id="clock">00:00:00</div>
        <div class="clock-date" id="date">Loading...</div>
    </div>

    <!-- STATISTIK -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            <div class="stat-value"><?= $total_hadir ?></div>
            <div class="stat-label">Total Absensi Bulan Ini</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-clock-fill"></i>
            </div>
            <div class="stat-value"><?= count($absensi_hari_ini) ?></div>
            <div class="stat-label">Absensi Hari Ini</div>
        </div>
    </div>

    <!-- MENU GRID -->
    <div class="menu-grid">
        <a href="absen_poli.php" class="menu-item">
            <div class="menu-icon-box">
                <i class="bi bi-hospital-fill"></i>
            </div>
            <div class="menu-label">Poliklinik</div>
        </a>

        <a href="absen_visite.php" class="menu-item">
            <div class="menu-icon-box">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <div class="menu-label">Visite</div>
        </a>

        <a href="absen_dinas.php" class="menu-item">
            <div class="menu-icon-box">
                <i class="bi bi-building-fill"></i>
            </div>
            <div class="menu-label">Jam Dinas</div>
        </a>

        <a href="absen_operasi.php" class="menu-item">
            <div class="menu-icon-box">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div class="menu-label">Operasi</div>
        </a>

        <a href="riwayat.php" class="menu-item">
            <div class="menu-icon-box">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="menu-label">Riwayat</div>
        </a>

        <a href="#.php" class="menu-item">
            <div class="menu-icon-box">
                <i class="bi bi-gear-fill"></i>
            </div>
            <div class="menu-label">Pengaturan</div>
        </a>
    </div>

    <!-- ABSENSI HARI INI -->
    <div class="absensi-today-card">
        <div class="section-title">
            <i class="bi bi-camera-fill"></i>
            Absensi Hari Ini
        </div>

        <?php if (count($absensi_hari_ini) > 0): ?>
            <?php foreach ($absensi_hari_ini as $abs): ?>
                <?php
                    // Icon class berdasarkan jenis
                    $icon_class = '';
                    $icon = '';
                    if ($abs['jenis_absen'] == 'Poliklinik') {
                        $icon_class = 'poli';
                        $icon = 'bi-hospital-fill';
                    } elseif ($abs['jenis_absen'] == 'Visite') {
                        $icon_class = 'visite';
                        $icon = 'bi-person-badge-fill';
                    } elseif ($abs['jenis_absen'] == 'Jam Dinas') {
                        $icon_class = 'dinas';
                        $icon = 'bi-building-fill';
                    } elseif ($abs['jenis_absen'] == 'Operasi') {
                        $icon_class = 'operasi';
                        $icon = 'bi-heart-pulse-fill';
                    }
                ?>
                <div class="absensi-item">
                    <div class="absensi-icon <?= $icon_class ?>">
                        <i class="bi <?= $icon ?>"></i>
                    </div>
                    <div class="absensi-info">
                        <div class="absensi-jenis"><?= htmlspecialchars($abs['jenis_absen']) ?></div>
                        <div class="absensi-waktu">
                            <i class="bi bi-clock me-1"></i><?= substr($abs['waktu_absen'], 0, 5) ?> WIB
                        </div>
                    </div>
                    <div class="absensi-status">
                        <i class="bi bi-check-circle-fill me-1"></i>Hadir
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-camera"></i>
                <p>Belum ada absensi hari ini</p>
                <a href="absen_poli.php" class="btn-absen-now">
                    <i class="bi bi-camera-fill me-2"></i>Mulai Absen
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- RIWAYAT ABSENSI -->
    <div class="riwayat-card">
        <div class="section-title">
            <i class="bi bi-clock-history"></i>
            Riwayat Absensi
        </div>
        
        <?php if (count($riwayat) > 0): ?>
            <table class="table-riwayat">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat as $r): ?>
                        <?php
                            // Badge class berdasarkan jenis
                            $badge_class = '';
                            if ($r['jenis_absen'] == 'Poliklinik') $badge_class = 'poli';
                            elseif ($r['jenis_absen'] == 'Visite') $badge_class = 'visite';
                            elseif ($r['jenis_absen'] == 'Jam Dinas') $badge_class = 'dinas';
                            elseif ($r['jenis_absen'] == 'Operasi') $badge_class = 'operasi';
                        ?>
                        <tr>
                            <td><?= date('d/m', strtotime($r['tanggal'])) ?></td>
                            <td>
                                <span class="badge-jenis <?= $badge_class ?>">
                                    <?= htmlspecialchars($r['jenis_absen']) ?>
                                </span>
                            </td>
                            <td><?= substr($r['waktu_absen'], 0, 5) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 20px; color: #9ca3af;">
                Belum ada riwayat absensi
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- FOOTER NAVIGATION -->
<div class="footer-nav">
    <a href="dashboard.php" class="footer-btn active">
        <i class="bi bi-house-fill"></i>
        <span>Home</span>
    </a>

    <a href="dashboard.php" class="footer-btn">
        <i class="bi bi-camera-fill"></i>
        <span>Absensi</span>
    </a>

    <a href="riwayat.php" class="footer-btn">
        <i class="bi bi-clock-history"></i>
        <span>Riwayat</span>
    </a>

    <a href="profil.php" class="footer-btn">
        <i class="bi bi-person-circle"></i>
        <span>Profil</span>
    </a>
</div>

<script>
// JAM & TANGGAL
function updateClock() {
    const now = new Date();
    
    const hari = ["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];
    const bulan = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agt","Sep","Okt","Nov","Des"];
    
    const namaHari = hari[now.getDay()];
    const tanggal = now.getDate();
    const namaBulan = bulan[now.getMonth()];
    const tahun = now.getFullYear();
    
    const jam = String(now.getHours()).padStart(2, '0');
    const menit = String(now.getMinutes()).padStart(2, '0');
    const detik = String(now.getSeconds()).padStart(2, '0');
    
    document.getElementById('clock').textContent = `${jam}:${menit}:${detik}`;
    document.getElementById('date').textContent = `${namaHari}, ${tanggal} ${namaBulan} ${tahun}`;
}

updateClock();
setInterval(updateClock, 1000);
</script>

</body>
</html>