<?php


session_start();
require 'koneksi.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}



$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$jenis_absen = 'Visite';


// Ambil setting lokasi
$stmt_lokasi = $pdo->query("SELECT * FROM setting_lokasi LIMIT 1");
$setting_lokasi = $stmt_lokasi->fetch();

// Cek absensi hari ini
$stmt = $pdo->prepare("SELECT * FROM absensi_kamera WHERE user_id = ? AND tanggal = ? AND jenis_absen = ?");
$stmt->execute([$user_id, $today, $jenis_absen]);
$existing = $stmt->fetch();



?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $page_title ?> - MY-Visite</title>

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
}

.header-icon {
    font-size: 48px;
    margin-bottom: 10px;
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
    text-decoration: none;
}

.btn-back:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateX(-3px);
    color: white;
}

/* CAMERA CARD */
.camera-card {
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 20px;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
}

.section-title i {
    color: #22c55e;
    margin-right: 8px;
    font-size: 22px;
}

#video, #canvas {
    width: 100%;
    max-width: 480px;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 16px;
    display: block;
    background: #1f2937;
}

#canvas {
    display: none;
}

.camera-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 16px;
}

.btn-capture {
    background: linear-gradient(135deg, #4ade80, #22c55e);
    color: white;
    border: none;
    padding: 14px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s;
    cursor: pointer;
}

.btn-capture:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
}

.btn-capture:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-retake {
    background: #f3f4f6;
    color: #6b7280;
    border: none;
    padding: 14px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s;
    cursor: pointer;
    display: none;
}

.btn-retake:hover {
    background: #e5e7eb;
    color: #4b5563;
}

/* LOCATION STATUS */
.location-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    margin-bottom: 20px;
}

.location-status {
    display: flex;
    align-items: center;
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 12px;
    font-size: 14px;
    font-weight: 600;
}

.location-status.checking {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.location-status.valid {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.location-status.invalid {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.location-status i {
    margin-right: 10px;
    font-size: 20px;
}

.location-info {
    font-size: 13px;
    color: #6b7280;
    margin-top: 8px;
    line-height: 1.6;
}

.location-info strong {
    color: #1f2937;
}

/* FORM */
.form-group {
    margin-bottom: 16px;
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
    border-radius: 12px;
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
    padding: 16px 20px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s;
    cursor: pointer;
    margin-top: 20px;
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(34, 197, 94, 0.5);
}

.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ALERT */
.alert-box {
    padding: 16px 20px;
    border-radius: 14px;
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

.alert-box i {
    margin-right: 10px;
    font-size: 20px;
}

/* LOADING */
.loading {
    pointer-events: none;
    opacity: 0.6;
    position: relative;
}

.loading::after {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    top: 50%;
    left: 50%;
    margin-left: -9px;
    margin-top: -9px;
    border: 2px solid #fff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
</head>

<body>

<div class="container">
    
    <!-- HEADER -->
    <div class="header-card" style="position: relative;">
        <a href="dashboard.php" class="btn-back">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="header-icon">
            <i class="bi <?= $icon_class ?>"></i>
        </div>
        <div class="header-title"><?= $page_title ?></div>
        <div class="header-subtitle">📸 Ambil foto untuk verifikasi kehadiran</div>
    </div>

    <?php if ($existing): ?>
        <!-- SUDAH ABSEN -->
        <div class="alert-box alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <div>
                <strong>Anda sudah absen hari ini!</strong><br>
                Waktu: <?= $existing['waktu_absen'] ?> WIB
            </div>
        </div>

        <div class="camera-card">
            <div class="section-title">
                <i class="bi bi-image-fill"></i>
                Foto Absensi Anda
            </div>
            <img src="<?= htmlspecialchars($existing['foto']) ?>" style="width: 100%; border-radius: 16px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
            
            <div style="margin-top: 20px; padding: 16px; background: #f9fafb; border-radius: 12px;">
                <p style="margin: 0; font-size: 14px; color: #6b7280;"><strong>Keterangan:</strong></p>
                <p style="margin: 8px 0 0 0; font-size: 14px; color: #1f2937;">
                    <?= htmlspecialchars($existing['keterangan'] ?? '-') ?>
                </p>
            </div>
        </div>

    <?php else: ?>
        <!-- LOCATION CHECK -->
        <div class="location-card" id="locationCard">
            <div class="section-title">
                <i class="bi bi-geo-alt-fill"></i>
                Validasi Lokasi
            </div>
            
            <div id="locationStatus" class="location-status checking">
                <i class="bi bi-hourglass-split"></i>
                <span>Sedang memeriksa lokasi Anda...</span>
            </div>
            
            <div class="location-info">
                <strong>📍 Lokasi Absensi:</strong> <?= $setting_lokasi ? htmlspecialchars($setting_lokasi['nama_lokasi']) : 'Belum diatur' ?><br>
                <strong>📏 Radius:</strong> <?= $setting_lokasi ? $setting_lokasi['radius'] : '0' ?> meter<br>
                <strong>💡 Tip:</strong> Pastikan GPS Anda aktif untuk validasi lokasi
            </div>
        </div>

        <!-- FORM ABSENSI -->
        <div class="camera-card">
            <div class="section-title">
                <i class="bi bi-camera-fill"></i>
                Ambil Foto Selfie
            </div>

            <div id="cameraContainer">
                <video id="video" autoplay playsinline></video>
                <canvas id="canvas"></canvas>
            </div>

            <div class="camera-controls">
                <button id="btnCapture" class="btn-capture" disabled>
                    <i class="bi bi-camera-fill me-2"></i>Ambil Foto
                </button>
                <button id="btnRetake" class="btn-retake">
                    <i class="bi bi-arrow-clockwise me-2"></i>Foto Ulang
                </button>
            </div>
        </div>

        <div class="camera-card">
            <form id="formAbsen" method="POST" action="proses_absen_kamera.php">
                <input type="hidden" name="jenis_absen" value="<?= $jenis_absen ?>">
                <input type="hidden" name="foto_data" id="fotoData">
                <input type="hidden" name="latitude" id="userLatitude">
                <input type="hidden" name="longitude" id="userLongitude">

                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-chat-text-fill me-1"></i>
                        Keterangan (Opsional)
                    </label>
                    <textarea 
                        name="keterangan" 
                        class="form-control-custom" 
                        rows="3" 
                        placeholder="Tulis keterangan jika ada..."
                    ></textarea>
                </div>

                <button type="submit" id="btnSubmit" class="btn-submit" disabled>
                    <i class="bi bi-check-circle-fill me-2"></i>Submit Absensi
                </button>
            </form>
        </div>
    <?php endif; ?>

</div>

<script>
<?php if (!$existing): ?>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const btnCapture = document.getElementById('btnCapture');
const btnRetake = document.getElementById('btnRetake');
const btnSubmit = document.getElementById('btnSubmit');
const fotoData = document.getElementById('fotoData');
const locationStatus = document.getElementById('locationStatus');
const userLatitude = document.getElementById('userLatitude');
const userLongitude = document.getElementById('userLongitude');

let stream = null;
let locationValid = false;
let photoTaken = false;

// Setting lokasi dari PHP
const settingLokasi = {
    latitude: <?= $setting_lokasi ? $setting_lokasi['latitude'] : '0' ?>,
    longitude: <?= $setting_lokasi ? $setting_lokasi['longitude'] : '0' ?>,
    radius: <?= $setting_lokasi ? $setting_lokasi['radius'] : '100' ?>,
    nama: '<?= $setting_lokasi ? addslashes($setting_lokasi['nama_lokasi']) : 'Belum diatur' ?>'
};

// Fungsi menghitung jarak (Haversine Formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3;
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c;
}

// Validasi lokasi
function checkLocation() {
    if (!navigator.geolocation) {
        locationStatus.className = 'location-status invalid';
        locationStatus.innerHTML = '<i class="bi bi-x-circle-fill"></i><span>Browser tidak mendukung GPS</span>';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const userLat = position.coords.latitude;
            const userLon = position.coords.longitude;
            
            userLatitude.value = userLat;
            userLongitude.value = userLon;
            
            const distance = calculateDistance(
                userLat, userLon,
                settingLokasi.latitude,
                settingLokasi.longitude
            );
            
            if (distance <= settingLokasi.radius) {
                locationValid = true;
                locationStatus.className = 'location-status valid';
                locationStatus.innerHTML = '<i class="bi bi-check-circle-fill"></i><span>✅ Lokasi valid! Jarak: ' + distance.toFixed(0) + ' meter</span>';
                btnCapture.disabled = false;
            } else {
                locationValid = false;
                locationStatus.className = 'location-status invalid';
                locationStatus.innerHTML = '<i class="bi bi-x-circle-fill"></i><span>❌ Anda di luar radius! Jarak: ' + distance.toFixed(0) + ' meter (Max: ' + settingLokasi.radius + 'm)</span>';
                btnCapture.disabled = true;
            }
        },
        function(error) {
            locationStatus.className = 'location-status invalid';
            let errorMsg = '';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg = '❌ Akses lokasi ditolak. Mohon izinkan akses GPS.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg = '❌ Informasi lokasi tidak tersedia.';
                    break;
                case error.TIMEOUT:
                    errorMsg = '❌ Timeout mendapatkan lokasi.';
                    break;
                default:
                    errorMsg = '❌ Error mendapatkan lokasi.';
            }
            locationStatus.innerHTML = '<i class="bi bi-x-circle-fill"></i><span>' + errorMsg + '</span>';
            btnCapture.disabled = true;
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

// Start Camera
async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            } 
        });
        video.srcObject = stream;
    } catch (error) {
        alert('Gagal mengakses kamera: ' + error.message);
    }
}

// Capture Photo
btnCapture.addEventListener('click', function() {
    if (!locationValid) {
        alert('Lokasi Anda tidak valid!');
        return;
    }
    
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0);
    
    const photoData = canvas.toDataURL('image/jpeg', 0.8);
    fotoData.value = photoData;
    
    video.style.display = 'none';
    canvas.style.display = 'block';
    
    btnCapture.style.display = 'none';
    btnRetake.style.display = 'block';
    photoTaken = true;
    
    if (locationValid && photoTaken) {
        btnSubmit.disabled = false;
    }
    
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
});

// Retake Photo
btnRetake.addEventListener('click', function() {
    video.style.display = 'block';
    canvas.style.display = 'none';
    btnCapture.style.display = 'block';
    btnRetake.style.display = 'none';
    btnSubmit.disabled = true;
    fotoData.value = '';
    photoTaken = false;
    
    startCamera();
});

// Submit Form
document.getElementById('formAbsen').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!fotoData.value) {
        alert('Harap ambil foto terlebih dahulu!');
        return;
    }
    
    if (!locationValid) {
        alert('Lokasi Anda tidak valid!');
        return;
    }
    
    if (!userLatitude.value || !userLongitude.value) {
        alert('Koordinat GPS tidak ditemukan!');
        return;
    }
    
    btnSubmit.classList.add('loading');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = 'Mengirim...';
    
    const formData = new FormData(this);
    
    fetch('proses_absen_kamera.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Absensi berhasil disimpan!');
            window.location.reload();
        } else {
            alert('❌ ' + data.message);
            btnSubmit.classList.remove('loading');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Submit Absensi';
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
        btnSubmit.classList.remove('loading');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Submit Absensi';
    });
});

// Check location and start camera on load
checkLocation();
startCamera();

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
});
<?php endif; ?>
</script>

</body>
</html>