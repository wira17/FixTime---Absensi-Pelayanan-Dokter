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

$success = '';
$error = '';

// Handle Add
if (isset($_POST['add'])) {
    $nama = trim($_POST['nama_spesialis']);
    
    if (!empty($nama)) {
        $stmt = $pdo->prepare("INSERT INTO master_spesialis (nama_spesialis) VALUES (?)");
        try {
            $stmt->execute([$nama]);
            $success = 'add';
        } catch (PDOException $e) {
            $error = 'Gagal menambah data: ' . $e->getMessage();
        }
    } else {
        $error = 'Nama spesialis tidak boleh kosong!';
    }
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = trim($_POST['nama_spesialis']);
    
    if (!empty($nama)) {
        $stmt = $pdo->prepare("UPDATE master_spesialis SET nama_spesialis = ? WHERE id = ?");
        try {
            $stmt->execute([$nama, $id]);
            $success = 'edit';
        } catch (PDOException $e) {
            $error = 'Gagal mengupdate data: ' . $e->getMessage();
        }
    } else {
        $error = 'Nama spesialis tidak boleh kosong!';
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM master_spesialis WHERE id = ?");
    try {
        $stmt->execute([$id]);
        $success = 'delete';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus data: ' . $e->getMessage();
    }
}

// Get all spesialis
$stmt = $pdo->query("SELECT * FROM master_spesialis ORDER BY nama_spesialis ASC");
$spesialisList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total
$totalSpesialis = count($spesialisList);

// Get edit data if exists
$editData = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM master_spesialis WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Master Spesialis - FixTime</title>

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

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-custom i {
    margin-right: 10px;
    font-size: 18px;
}

/* CONTENT GRID */
.content-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 24px;
}

/* FORM CARD */
.form-card {
    background: white;
    padding: 28px;
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    height: fit-content;
}

.card-title {
    font-size: 18px;
    font-weight: 700;
    color: #1b5e20;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.card-title i {
    margin-right: 10px;
    color: #66bb6a;
    font-size: 22px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    display: block;
}

.form-control-custom {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control-custom:focus {
    outline: none;
    border-color: #4ade80;
    box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.1);
}

.btn-submit {
    width: 100%;
    background: linear-gradient(135deg, #4ade80, #22c55e);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    cursor: pointer;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
}

.btn-cancel {
    width: 100%;
    background: #f3f4f6;
    color: #6b7280;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    margin-top: 8px;
    cursor: pointer;
    text-decoration: none;
    display: block;
    text-align: center;
    transition: all 0.3s;
}

.btn-cancel:hover {
    background: #e5e7eb;
    color: #4b5563;
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

.badge-count {
    background: linear-gradient(135deg, #c8e6c9, #a5d6a7);
    color: #2e7d32;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
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

/* RESPONSIVE */
@media (max-width: 1200px) {
    .content-grid {
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
        <div class="header-title">📋 Master Spesialis</div>
        <div class="header-subtitle">Kelola data spesialis dokter dalam sistem</div>
    </div>

    <!-- SUCCESS MESSAGE -->
    <?php if ($success): ?>
        <div class="alert-custom alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php
                if ($success === 'add') echo 'Data spesialis berhasil ditambahkan!';
                elseif ($success === 'edit') echo 'Data spesialis berhasil diupdate!';
                elseif ($success === 'delete') echo 'Data spesialis berhasil dihapus!';
            ?>
        </div>
    <?php endif; ?>

    <!-- ERROR MESSAGE -->
    <?php if ($error): ?>
        <div class="alert-custom alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- CONTENT GRID -->
    <div class="content-grid">
        
        <!-- FORM CARD -->
        <div class="form-card">
            <div class="card-title">
                <i class="bi bi-plus-circle-fill"></i>
                <?= $editData ? 'Edit' : 'Tambah' ?> Spesialis
            </div>

            <form method="POST">
                <?php if ($editData): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-card-text me-1"></i>
                        Nama Spesialis
                    </label>
                    <input 
                        type="text" 
                        name="nama_spesialis" 
                        class="form-control-custom" 
                        placeholder="Contoh: Spesialis Jantung" 
                        value="<?= $editData ? htmlspecialchars($editData['nama_spesialis']) : '' ?>"
                        required
                        autofocus
                    >
                </div>

                <button type="submit" name="<?= $editData ? 'edit' : 'add' ?>" class="btn-submit">
                    <i class="bi bi-<?= $editData ? 'check' : 'plus' ?>-circle-fill me-2"></i>
                    <?= $editData ? 'Update Data' : 'Tambah Data' ?>
                </button>

                <?php if ($editData): ?>
                    <a href="master_spesialis.php" class="btn-cancel">
                        <i class="bi bi-x-circle me-2"></i>Batal Edit
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- TABLE CARD -->
        <div class="table-card">
            <div class="card-header-custom">
                <div class="card-title">
                    <i class="bi bi-table"></i>
                    Daftar Spesialis
                </div>
                <span class="badge-count">
                    Total: <?= $totalSpesialis ?>
                </span>
            </div>

            <!-- TABLE -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Spesialis</th>
                        <th style="width: 180px;">Tanggal Dibuat</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($spesialisList) > 0): ?>
                        <?php foreach ($spesialisList as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($item['nama_spesialis']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                            <td>
                                <a href="master_spesialis.php?edit=<?= $item['id'] ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="master_spesialis.php?delete=<?= $item['id'] ?>" 
                                   class="btn-action btn-delete" 
                                   title="Hapus"
                                   onclick="return confirm('Yakin ingin menghapus spesialis: <?= htmlspecialchars($item['nama_spesialis']) ?>?')">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h4>Belum Ada Data</h4>
                                    <p>Mulai tambahkan spesialis menggunakan form di sebelah kiri</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- FOOTER -->
<div class="footer">
    © <?= date("Y") ?> FixTime • Master Spesialis
</div>

<script>
// AUTO HIDE ALERT
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert-custom');
    alerts.forEach(alert => {
        alert.style.animation = 'slideDown 0.4s ease-out reverse';
        setTimeout(() => alert.remove(), 400);
    });
}, 4000);

// Auto focus on input when edit mode
<?php if ($editData): ?>
    document.querySelector('input[name="nama_spesialis"]').select();
<?php endif; ?>
</script>

</body>
</html>