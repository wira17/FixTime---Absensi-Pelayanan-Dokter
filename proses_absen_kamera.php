<?php
session_start();
require 'koneksi.php';

// Set timezone ke WIB (Indonesia Barat)
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

// Error logging untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output JSON

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$jenis_absen = $_POST['jenis_absen'] ?? '';
$keterangan = trim($_POST['keterangan'] ?? '');
$foto_data = $_POST['foto_data'] ?? '';
$latitude = $_POST['latitude'] ?? '';
$longitude = $_POST['longitude'] ?? '';

if (empty($jenis_absen) || empty($foto_data)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

// Validasi jenis absen
if (!in_array($jenis_absen, ['Operasi','Poliklinik', 'Visite', 'Jam Dinas'])) {
    echo json_encode(['success' => false, 'message' => 'Jenis absensi tidak valid']);
    exit;
}

// Jika absen pulang, cek apakah sudah absen masuk
if ($jenis_absen === 'Pulang') {
    $today = date('Y-m-d');
    $stmt_cek = $pdo->prepare("SELECT id FROM absensi_kamera WHERE user_id = ? AND tanggal = ? AND jenis_absen = 'Masuk'");
    $stmt_cek->execute([$user_id, $today]);
    
    if (!$stmt_cek->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Anda belum absen masuk hari ini. Silakan absen masuk terlebih dahulu.']);
        exit;
    }
}

// Validasi koordinat GPS
if (empty($latitude) || empty($longitude)) {
    echo json_encode(['success' => false, 'message' => 'Koordinat GPS tidak ditemukan']);
    exit;
}

// Ambil setting lokasi untuk validasi server-side
$stmt_lokasi = $pdo->query("SELECT * FROM setting_lokasi LIMIT 1");
$setting_lokasi = $stmt_lokasi->fetch();

if ($setting_lokasi) {
    // Hitung jarak menggunakan Haversine Formula
    $lat1 = floatval($latitude);
    $lon1 = floatval($longitude);
    $lat2 = floatval($setting_lokasi['latitude']);
    $lon2 = floatval($setting_lokasi['longitude']);
    $radius = floatval($setting_lokasi['radius']);
    
    $earthRadius = 6371000; // meter
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    // Validasi jarak
    if ($distance > $radius) {
        echo json_encode([
            'success' => false, 
            'message' => 'Anda berada di luar radius lokasi. Jarak: ' . round($distance) . ' meter (Max: ' . $radius . ' meter)'
        ]);
        exit;
    }
}

// Decode base64 image
$foto_data = str_replace('data:image/jpeg;base64,', '', $foto_data);
$foto_data = str_replace(' ', '+', $foto_data);
$foto_decoded = base64_decode($foto_data);

if ($foto_decoded === false) {
    echo json_encode(['success' => false, 'message' => 'Format foto tidak valid']);
    exit;
}

// Create uploads directory if not exists - gunakan path absolut
$base_dir = __DIR__; // Current directory
$upload_dir = $base_dir . '/uploads/absensi/';

// Buat folder jika belum ada
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Gagal membuat folder uploads']);
        exit;
    }
    chmod($upload_dir, 0777); // Set permission eksplisit
}

// Cek apakah folder writable
if (!is_writable($upload_dir)) {
    echo json_encode(['success' => false, 'message' => 'Folder uploads tidak dapat ditulis. Cek permission folder.']);
    exit;
}

// Generate unique filename
$filename = 'absen_' . $user_id . '_' . date('Ymd_His') . '_' . uniqid() . '.jpg';
$filepath_full = $upload_dir . $filename; // Full path untuk save
$filepath_db = 'uploads/absensi/' . $filename; // Relative path untuk database

// Save image
$save_result = @file_put_contents($filepath_full, $foto_decoded);

if ($save_result === false) {
    $error_msg = error_get_last();
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal menyimpan foto',
        'debug' => [
            'upload_dir' => $upload_dir,
            'filepath' => $filepath_full,
            'writable' => is_writable($upload_dir),
            'exists' => file_exists($upload_dir),
            'error' => $error_msg['message'] ?? 'Unknown error'
        ]
    ]);
    exit;
}

// Verify file was created
if (!file_exists($filepath_full)) {
    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan setelah disimpan']);
    exit;
}

// Set file permission
chmod($filepath_full, 0644);

// Save to database
try {
    $today = date('Y-m-d');
    $waktu = date('H:i:s');
    
    // Check if already exists
    $stmt = $pdo->prepare("SELECT id FROM absensi_kamera WHERE user_id = ? AND tanggal = ? AND jenis_absen = ?");
    $stmt->execute([$user_id, $today, $jenis_absen]);
    
    if ($stmt->fetch()) {
        // Delete uploaded file
        @unlink($filepath_full);
        echo json_encode(['success' => false, 'message' => 'Anda sudah absen ' . $jenis_absen . ' hari ini']);
        exit;
    }
    
    // Insert new record - gunakan relative path
    $stmt = $pdo->prepare("
        INSERT INTO absensi_kamera 
        (user_id, jenis_absen, tanggal, waktu_absen, foto, keterangan, latitude, longitude) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$user_id, $jenis_absen, $today, $waktu, $filepath_db, $keterangan, $latitude, $longitude]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Absensi ' . $jenis_absen . ' berhasil disimpan',
        'data' => [
            'jenis_absen' => $jenis_absen,
            'waktu' => $waktu,
            'foto' => $filepath_db
        ]
    ]);
    
} catch (PDOException $e) {
    // Delete uploaded file on error
    if (file_exists($filepath_full)) {
        @unlink($filepath_full);
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>