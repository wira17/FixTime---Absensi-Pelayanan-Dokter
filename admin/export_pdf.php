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

// Statistik
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_absensi,
        COUNT(CASE WHEN jenis_absen = 'Poliklinik' THEN 1 END) as total_poli,
        COUNT(CASE WHEN jenis_absen = 'Visite' THEN 1 END) as total_visite,
        COUNT(CASE WHEN jenis_absen = 'Jam Dinas' THEN 1 END) as total_dinas,
        COUNT(CASE WHEN jenis_absen = 'Operasi' THEN 1 END) as total_operasi
    FROM absensi_kamera ak
    WHERE DATE_FORMAT(ak.tanggal, '%Y-%m') = ?
    " . ($filter_dokter !== 'all' ? " AND ak.user_id = ?" : "")
);
$stats_params = [$filter_bulan];
if ($filter_dokter !== 'all') {
    $stats_params[] = $filter_dokter;
}
$stmt_stats->execute($stats_params);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Format bulan
$bulan_display = date('F Y', strtotime($filter_bulan . '-01'));

// Set header untuk PDF
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"Rekap_Absensi_" . $filter_bulan . ".pdf\"");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi</title>
    <style>
        @page {
            margin: 20mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #2e7d32;
            padding-bottom: 10px;
        }
        
        .header h1 {
            margin: 0;
            color: #2e7d32;
            font-size: 18px;
        }
        
        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        
        .info-box {
            background: #f0fdf4;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #22c55e;
        }
        
        .info-box p {
            margin: 3px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .stat-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px;
            text-align: center;
            border-radius: 4px;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #2e7d32;
        }
        
        .stat-label {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th {
            background: #2e7d32;
            color: white;
            padding: 8px;
            font-size: 10px;
            text-align: left;
            border: 1px solid #1b5e20;
        }
        
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        
        tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-poli { background: #dbeafe; color: #1e40af; }
        .badge-visite { background: #fce7f3; color: #be185d; }
        .badge-dinas { background: #fef3c7; color: #d97706; }
        .badge-operasi { background: #e0e7ff; color: #4f46e5; }
        
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <h1>🏥 REKAP ABSENSI MY-VISITE</h1>
        <p>Sistem Absensi Pelayanan Medis</p>
    </div>
    
    <!-- INFO -->
    <div class="info-box">
        <p><strong>Periode:</strong> <?= $bulan_display ?></p>
        <p><strong>Tanggal Export:</strong> <?= date('d F Y H:i:s') ?> WIB</p>
        <p><strong>Total Data:</strong> <?= count($data) ?> record</p>
        <?php if ($filter_dokter !== 'all'): ?>
            <?php
                $stmt_dok = $pdo->prepare("SELECT nama FROM pengguna WHERE id = ?");
                $stmt_dok->execute([$filter_dokter]);
                $dok_name = $stmt_dok->fetchColumn();
            ?>
            <p><strong>Filter Dokter:</strong> <?= htmlspecialchars($dok_name) ?></p>
        <?php endif; ?>
        <?php if ($filter_jenis !== 'all'): ?>
            <p><strong>Filter Jenis:</strong> <?= htmlspecialchars($filter_jenis) ?></p>
        <?php endif; ?>
    </div>
    
    <!-- STATISTIK -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-value"><?= $stats['total_absensi'] ?></div>
            <div class="stat-label">Total Absensi</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $stats['total_poli'] ?></div>
            <div class="stat-label">Poliklinik</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $stats['total_visite'] ?></div>
            <div class="stat-label">Visite</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $stats['total_dinas'] ?></div>
            <div class="stat-label">Jam Dinas</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $stats['total_operasi'] ?></div>
            <div class="stat-label">Operasi</div>
        </div>
    </div>
    
    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Tanggal</th>
                <th width="20%">Nama Dokter</th>
                <th width="18%">Email</th>
                <th width="15%">Jenis</th>
                <th width="12%">Waktu</th>
                <th width="18%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($data) > 0): ?>
                <?php 
                $no = 1; 
                foreach ($data as $row): 
                    $badge_class = '';
                    if ($row['jenis_absen'] == 'Poliklinik') $badge_class = 'poli';
                    elseif ($row['jenis_absen'] == 'Visite') $badge_class = 'visite';
                    elseif ($row['jenis_absen'] == 'Jam Dinas') $badge_class = 'dinas';
                    elseif ($row['jenis_absen'] == 'Operasi') $badge_class = 'operasi';
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <span class="badge badge-<?= $badge_class ?>">
                            <?= htmlspecialchars($row['jenis_absen']) ?>
                        </span>
                    </td>
                    <td><?= $row['waktu_absen'] ?></td>
                    <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="no-data">Tidak ada data untuk ditampilkan</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- FOOTER -->
    <div class="footer">
        <p><em>Dokumen ini dihasilkan secara otomatis oleh sistem MY-Visite</em></p>
        <p>Dicetak oleh: <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></p>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>