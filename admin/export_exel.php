<?php
session_start();
require '../koneksi.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Access denied');
}

// Filter
$filter_dokter = $_GET['dokter'] ?? 'all';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');
$filter_jenis = $_GET['jenis'] ?? 'all';

// Query data
$sql = "
    SELECT 
        p.nama,
        p.email,
        ak.jenis_absen,
        ak.tanggal,
        ak.waktu_absen,
        ak.keterangan
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
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set header untuk download Excel
$filename = "Rekap_Absensi_" . $filter_bulan . "_" . date('YmdHis') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Format bulan
$bulan_display = date('F Y', strtotime($filter_bulan . '-01'));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .info {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">REKAP ABSENSI MY-VISITE</div>
    <div class="info">
        <strong>Periode:</strong> <?= $bulan_display ?><br>
        <strong>Tanggal Export:</strong> <?= date('d F Y H:i:s') ?> WIB<br>
        <strong>Total Data:</strong> <?= count($data) ?> record
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Dokter</th>
                <th>Email</th>
                <th>Jenis Absensi</th>
                <th>Waktu Absensi</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($data) > 0): ?>
                <?php $no = 1; foreach ($data as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['jenis_absen']) ?></td>
                    <td><?= $row['waktu_absen'] ?></td>
                    <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <br><br>
    <div style="margin-top: 30px;">
        <p><em>Dokumen ini dihasilkan secara otomatis oleh sistem MY-Visite</em></p>
    </div>
</body>
</html>