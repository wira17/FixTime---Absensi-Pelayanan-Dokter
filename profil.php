<?php
session_start();

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'koneksi.php';

$user_id = $_SESSION['user_id'];

// Ambil data pengguna beserta nama spesialis (LEFT JOIN)
$stmt = $pdo->prepare("
    SELECT p.*, s.nama_spesialis 
    FROM pengguna p 
    LEFT JOIN master_spesialis s ON p.spesialis_id = s.id 
    WHERE p.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Data pengguna tidak ditemukan!";
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Profil FixTime</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
<div class="container">

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

    <!-- PROFIL CARD -->
    <div class="absensi-today-card">
        <div class="section-title">
            <i class="bi bi-person-circle"></i>
            Informasi Profil
        </div>

        <div class="absensi-item" style="flex-direction: column; align-items: flex-start; padding: 20px;">
            <div class="absensi-info" style="width: 100%;">
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Nama</div>
                    <div class="col-8"><?= htmlspecialchars($user['nama']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">NIK</div>
                    <div class="col-8"><?= htmlspecialchars($user['nik'] ?? '-') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Email</div>
                    <div class="col-8"><?= htmlspecialchars($user['email']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Spesialis</div>
                    <div class="col-8"><?= htmlspecialchars($user['nama_spesialis'] ?? '-') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Tanggal Daftar</div>
                    <div class="col-8"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- FOOTER NAVIGATION -->
<div class="footer-nav">
    <a href="dashboard.php" class="footer-btn">
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

    <a href="profil.php" class="footer-btn active">
        <i class="bi bi-person-circle"></i>
        <span>Profil</span>
    </a>
</div>

</body>
</html>
