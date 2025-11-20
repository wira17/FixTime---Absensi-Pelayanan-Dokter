<?php
session_start();
require '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    echo "Akses ditolak! Hanya admin.";
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM pengguna WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: pengguna.php?success=delete');
    exit;
}

// Get all users with spesialis info
$stmt = $pdo->query("
    SELECT p.*, ms.nama_spesialis 
    FROM pengguna p 
    LEFT JOIN master_spesialis ms ON p.spesialis_id = ms.id 
    ORDER BY p.id DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count statistics
$totalUsers = count($users);
$adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$userCount = count(array_filter($users, fn($u) => $u['role'] === 'user'));
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Pengguna - FixTime</title>

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
}

.header-title {
    font-size: 28px;
    font-weight: 700;
    color: #1b5e20;
    margin-bottom: 4px;
}

.header-subtitle {
    color: #66bb6a;
    font-size: 14px;
}

/* STATS MINI */
.mini-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 30px;
}

.mini-stat-card {
    background: white;
    padding: 20px;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
}

.mini-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-right: 16px;
}

.mini-stat-icon.green {
    background: linear-gradient(135deg, #c8e6c9, #a5d6a7);
    color: #2e7d32;
}

.mini-stat-icon.blue {
    background: linear-gradient(135deg, #bbdefb, #90caf9);
    color: #1976d2;
}

.mini-stat-icon.orange {
    background: linear-gradient(135deg, #ffe0b2, #ffcc80);
    color: #f57c00;
}

.mini-stat-content .label {
    font-size: 12px;
    color: #757575;
    margin-bottom: 4px;
}

.mini-stat-content .value {
    font-size: 24px;
    font-weight: 700;
    color: #1b5e20;
}

/* TABLE CARD */
.table-card {
    background: white;
    padding: 28px;
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.card-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.card-title {
    font-size: 20px;
    font-weight: 700;
    color: #1b5e20;
}

.btn-add {
    background: linear-gradient(135deg, #4ade80, #22c55e);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
    color: white;
}

.btn-add i {
    margin-right: 8px;
}

/* SEARCH BOX */
.search-box {
    margin-bottom: 20px;
}

.search-input {
    width: 100%;
    max-width: 400px;
    padding: 12px 16px 12px 44px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: #4ade80;
    box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.1);
}

.search-wrapper {
    position: relative;
}

.search-wrapper i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 18px;
}

/* TABLE */
.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.data-table thead th {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #2e7d32;
    padding: 14px 16px;
    font-weight: 600;
    font-size: 13px;
    text-align: left;
    border: none;
}

.data-table thead th:first-child {
    border-radius: 10px 0 0 0;
}

.data-table thead th:last-child {
    border-radius: 0 10px 0 0;
}

.data-table tbody td {
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
    color: #4b5563;
    font-size: 14px;
}

.data-table tbody tr:hover {
    background: #f9fafb;
}

.badge-role {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
}

.badge-role.admin {
    background: #fce7f3;
    color: #be185d;
}

.badge-role.user {
    background: #dbeafe;
    color: #1e40af;
}

.btn-action {
    padding: 8px 12px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-right: 6px;
    text-decoration: none;
    display: inline-block;
}

.btn-edit {
    background: #dbeafe;
    color: #1e40af;
}

.btn-edit:hover {
    background: #bfdbfe;
    transform: translateY(-2px);
}

.btn-delete {
    background: #fee2e2;
    color: #dc2626;
}

.btn-delete:hover {
    background: #fecaca;
    transform: translateY(-2px);
}

/* ALERT */
.alert-custom {
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    animation: slideDown 0.4s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-custom i {
    margin-right: 10px;
    font-size: 18px;
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

.empty-state h4 {
    font-size: 18px;
    margin-bottom: 8px;
}

/* FOOTER */
.footer {
    position: fixed;
    bottom: 0;
    left: 260px;
    right: 0;
    background: white;
    padding: 16px 30px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
    font-size: 13px;
    color: #6b7280;
    z-index: 999;
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
        <div class="header-title">👥 Kelola Pengguna</div>
        <div class="header-subtitle">Manajemen data pengguna sistem FixTime</div>
    </div>

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert-custom alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php if ($_GET['success'] === 'delete'): ?>
                Data pengguna berhasil dihapus!
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- MINI STATS -->
    <div class="mini-stats">
        <div class="mini-stat-card">
            <div class="mini-stat-icon green">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="mini-stat-content">
                <div class="label">Total Pengguna</div>
                <div class="value"><?= $totalUsers ?></div>
            </div>
        </div>

        <div class="mini-stat-card">
            <div class="mini-stat-icon blue">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <div class="mini-stat-content">
                <div class="label">User / Dokter</div>
                <div class="value"><?= $userCount ?></div>
            </div>
        </div>

        <div class="mini-stat-card">
            <div class="mini-stat-icon orange">
                <i class="bi bi-shield-fill"></i>
            </div>
            <div class="mini-stat-content">
                <div class="label">Administrator</div>
                <div class="value"><?= $adminCount ?></div>
            </div>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="table-card">
        <div class="card-header-custom">
            <div class="card-title">
                <i class="bi bi-table me-2"></i>Data Pengguna
            </div>
           
        </div>

        <!-- SEARCH -->
        <div class="search-box">
            <div class="search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Cari nama, email, atau NIK...">
            </div>
        </div>

        <!-- TABLE -->
        <table class="data-table" id="userTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIK/NIP</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Spesialis</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $index => $user): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($user['nik']) ?></td>
                        <td><strong><?= htmlspecialchars($user['nama']) ?></strong></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= $user['nama_spesialis'] ? htmlspecialchars($user['nama_spesialis']) : '-' ?></td>
                        <td>
                            <span class="badge-role <?= $user['role'] ?>">
                                <?= strtoupper($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit_pengguna.php?id=<?= $user['id'] ?>" class="btn-action btn-edit">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <a href="pengguna.php?delete=<?= $user['id'] ?>" 
                               class="btn-action btn-delete" 
                               onclick="return confirm('Yakin ingin menghapus pengguna ini?')">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <h4>Belum Ada Data</h4>
                                <p>Belum ada pengguna terdaftar dalam sistem</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- FOOTER -->
<div class="footer">
    © <?= date("Y") ?> FixTime • Kelola Pengguna
</div>

<script>
// SEARCH FUNCTIONALITY
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const table = document.getElementById('userTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        
        if (text.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
});

// AUTO HIDE ALERT
setTimeout(() => {
    const alert = document.querySelector('.alert-custom');
    if (alert) {
        alert.style.animation = 'slideDown 0.4s ease-out reverse';
        setTimeout(() => alert.remove(), 400);
    }
}, 3000);
</script>

</body>
</html>