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

// Handle Update/Insert
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lokasi = $_POST['nama_lokasi'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $radius = $_POST['radius'];
    $alamat = $_POST['alamat'];
    $keterangan = $_POST['keterangan'];
    
    // Check if location already exists
    $check = $pdo->query("SELECT id FROM setting_lokasi LIMIT 1");
    
    if ($check->rowCount() > 0) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE setting_lokasi SET 
            nama_lokasi = ?, 
            latitude = ?, 
            longitude = ?, 
            radius = ?, 
            alamat = ?, 
            keterangan = ?,
            updated_at = NOW()
            WHERE id = (SELECT id FROM setting_lokasi LIMIT 1)
        ");
        $stmt->execute([$nama_lokasi, $latitude, $longitude, $radius, $alamat, $keterangan]);
        $success = 'update';
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO setting_lokasi 
            (nama_lokasi, latitude, longitude, radius, alamat, keterangan, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$nama_lokasi, $latitude, $longitude, $radius, $alamat, $keterangan]);
        $success = 'save';
    }
    
    header('Location: setting_lokasi.php?success=' . $success);
    exit;
}

// Get current location settings
$stmt = $pdo->query("SELECT * FROM setting_lokasi LIMIT 1");
$lokasi = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setting Lokasi Absensi - FixTime</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Leaflet CSS for Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

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

/* FORM CARD */
.form-card {
    background: white;
    padding: 32px;
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 24px;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1b5e20;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 10px;
    font-size: 22px;
}

.form-label {
    font-weight: 600;
    color: #2e7d32;
    font-size: 14px;
    margin-bottom: 8px;
}

.form-control, .form-select {
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
    border-color: #4ade80;
    box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.1);
}

/* MAP */
#map {
    height: 400px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* INFO BOX */
.info-box {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border-left: 4px solid #2196f3;
}

.info-box i {
    color: #1976d2;
    font-size: 20px;
    margin-right: 10px;
}

.info-box-text {
    color: #0d47a1;
    font-size: 13px;
    line-height: 1.6;
}

/* COORDINATE BOX */
.coordinate-box {
    background: linear-gradient(135deg, #f3e5f5, #e1bee7);
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.coordinate-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.coordinate-label {
    font-weight: 600;
    color: #6a1b9a;
    font-size: 13px;
}

.coordinate-value {
    font-weight: 700;
    color: #4a148c;
    font-size: 14px;
    font-family: 'Courier New', monospace;
}

/* BUTTONS */
.btn-group-custom {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn-save {
    background: linear-gradient(135deg, #4ade80, #22c55e);
    color: white;
    border: none;
    padding: 14px 32px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}

.btn-location {
    background: linear-gradient(135deg, #60a5fa, #3b82f6);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-location:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

.btn-reset {
    background: linear-gradient(135deg, #f87171, #ef4444);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-reset:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
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
@media (max-width: 768px) {
    .sidebar {
        width: 0;
        padding: 0;
    }
    
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
    
    .footer {
        left: 0;
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
        <div class="header-title">📍 Setting Lokasi Absensi</div>
        <div class="header-subtitle">Kelola koordinat lokasi untuk sistem absensi</div>
    </div>

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert-custom alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php if ($_GET['success'] === 'save'): ?>
                Setting lokasi berhasil disimpan!
            <?php elseif ($_GET['success'] === 'update'): ?>
                Setting lokasi berhasil diperbarui!
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- FORM CARD -->
    <div class="form-card">
        <div class="section-title">
            <i class="bi bi-pin-map-fill"></i>
            Pengaturan Lokasi
        </div>

        <!-- INFO BOX -->
        <div class="info-box">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle-fill"></i>
                <div class="info-box-text">
                    <strong>Petunjuk:</strong><br>
                    • Klik pada peta untuk menentukan lokasi<br>
                    • Atau gunakan tombol "Gunakan Lokasi Saat Ini" untuk deteksi otomatis<br>
                    • Atur radius untuk menentukan jarak maksimal absensi<br>
                    • Koordinat akan otomatis terisi saat klik peta
                </div>
            </div>
        </div>

        <form method="POST" id="lokasiForm">
            <div class="row mb-4">
                <div class="col-md-12">
                    <!-- MAP -->
                    <div id="map"></div>
                    
                    <!-- MAP CONTROLS -->
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn-location" onclick="getCurrentLocation()">
                            <i class="bi bi-crosshair"></i>
                            Gunakan Lokasi Saat Ini
                        </button>
                        <button type="button" class="btn-reset" onclick="resetMap()">
                            <i class="bi bi-arrow-clockwise"></i>
                            Reset Peta
                        </button>
                    </div>
                </div>
            </div>

            <!-- COORDINATE DISPLAY -->
            <div class="coordinate-box">
                <div class="coordinate-item">
                    <span class="coordinate-label">📍 Latitude:</span>
                    <span class="coordinate-value" id="displayLat">
                        <?= $lokasi ? $lokasi['latitude'] : '-' ?>
                    </span>
                </div>
                <div class="coordinate-item">
                    <span class="coordinate-label">📍 Longitude:</span>
                    <span class="coordinate-value" id="displayLng">
                        <?= $lokasi ? $lokasi['longitude'] : '-' ?>
                    </span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lokasi" class="form-control" 
                           value="<?= $lokasi ? htmlspecialchars($lokasi['nama_lokasi']) : '' ?>" 
                           placeholder="Contoh: RSUD Panglima Sebaya" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Radius (meter) <span class="text-danger">*</span></label>
                    <input type="number" name="radius" class="form-control" 
                           value="<?= $lokasi ? $lokasi['radius'] : '100' ?>" 
                           placeholder="Contoh: 100" min="10" max="1000" required>
                    <small class="text-muted">Jarak maksimal absensi dari titik pusat</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Latitude <span class="text-danger">*</span></label>
                    <input type="text" name="latitude" id="latitude" class="form-control" 
                           value="<?= $lokasi ? $lokasi['latitude'] : '' ?>" 
                           placeholder="-1.2345678" readonly required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Longitude <span class="text-danger">*</span></label>
                    <input type="text" name="longitude" id="longitude" class="form-control" 
                           value="<?= $lokasi ? $lokasi['longitude'] : '' ?>" 
                           placeholder="116.1234567" readonly required>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="alamat" class="form-control" rows="2" 
                              placeholder="Masukkan alamat lengkap lokasi"><?= $lokasi ? htmlspecialchars($lokasi['alamat']) : '' ?></textarea>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2" 
                              placeholder="Keterangan tambahan (opsional)"><?= $lokasi ? htmlspecialchars($lokasi['keterangan']) : '' ?></textarea>
                </div>
            </div>

            <div class="btn-group-custom">
                <button type="submit" class="btn-save">
                    <i class="bi bi-save-fill"></i>
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>

</div>

<!-- FOOTER -->
<div class="footer">
    © <?= date("Y") ?> FixTime • Setting Lokasi Absensi
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Initialize map
let map;
let marker;
let circle;

// Default coordinates (Indonesia center)
const defaultLat = <?= $lokasi ? $lokasi['latitude'] : '-0.7893' ?>;
const defaultLng = <?= $lokasi ? $lokasi['longitude'] : '113.9213' ?>;
const defaultRadius = <?= $lokasi ? $lokasi['radius'] : '100' ?>;

// Initialize map
map = L.map('map').setView([defaultLat, defaultLng], <?= $lokasi ? '17' : '5' ?>);

// Add tile layer
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
}).addTo(map);

// Add marker if location exists
<?php if ($lokasi): ?>
marker = L.marker([defaultLat, defaultLng]).addTo(map);
circle = L.circle([defaultLat, defaultLng], {
    color: '#22c55e',
    fillColor: '#4ade80',
    fillOpacity: 0.3,
    radius: defaultRadius
}).addTo(map);
<?php endif; ?>

// Click on map to set location
map.on('click', function(e) {
    const lat = e.latlng.lat;
    const lng = e.latlng.lng;
    
    setLocation(lat, lng);
});

function setLocation(lat, lng) {
    // Remove old marker and circle
    if (marker) {
        map.removeLayer(marker);
    }
    if (circle) {
        map.removeLayer(circle);
    }
    
    // Add new marker
    marker = L.marker([lat, lng]).addTo(map);
    
    // Add circle
    const radius = parseInt(document.querySelector('input[name="radius"]').value) || 100;
    circle = L.circle([lat, lng], {
        color: '#22c55e',
        fillColor: '#4ade80',
        fillOpacity: 0.3,
        radius: radius
    }).addTo(map);
    
    // Update form fields
    document.getElementById('latitude').value = lat.toFixed(7);
    document.getElementById('longitude').value = lng.toFixed(7);
    document.getElementById('displayLat').textContent = lat.toFixed(7);
    document.getElementById('displayLng').textContent = lng.toFixed(7);
    
    // Center map on new location
    map.setView([lat, lng], 17);
}

// Get current location
function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                setLocation(lat, lng);
            },
            function(error) {
                alert('Tidak dapat mengakses lokasi Anda. Pastikan GPS aktif dan izinkan akses lokasi.');
            }
        );
    } else {
        alert('Browser Anda tidak mendukung geolocation.');
    }
}

// Reset map
function resetMap() {
    if (confirm('Yakin ingin reset peta ke lokasi default?')) {
        map.setView([-0.7893, 113.9213], 5);
        if (marker) map.removeLayer(marker);
        if (circle) map.removeLayer(circle);
        document.getElementById('latitude').value = '';
        document.getElementById('longitude').value = '';
        document.getElementById('displayLat').textContent = '-';
        document.getElementById('displayLng').textContent = '-';
    }
}

// Update circle radius on input change
document.querySelector('input[name="radius"]').addEventListener('input', function() {
    if (circle && marker) {
        map.removeLayer(circle);
        const lat = parseFloat(document.getElementById('latitude').value);
        const lng = parseFloat(document.getElementById('longitude').value);
        circle = L.circle([lat, lng], {
            color: '#22c55e',
            fillColor: '#4ade80',
            fillOpacity: 0.3,
            radius: parseInt(this.value)
        }).addTo(map);
    }
});

// Form validation
document.getElementById('lokasiForm').addEventListener('submit', function(e) {
    const lat = document.getElementById('latitude').value;
    const lng = document.getElementById('longitude').value;
    
    if (!lat || !lng) {
        e.preventDefault();
        alert('Silakan klik pada peta untuk menentukan lokasi terlebih dahulu!');
        return false;
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