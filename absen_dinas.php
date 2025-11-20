<?php
session_start();
require 'koneksi.php';

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$jenis_absen = 'Jam Dinas';

// Ambil setting lokasi
$stmt_lokasi = $pdo->query("SELECT * FROM setting_lokasi LIMIT 1");
$setting_lokasi = $stmt_lokasi->fetch();

// Cek absensi hari ini untuk jenis ini
$stmt = $pdo->prepare("SELECT * FROM absensi_kamera WHERE user_id = ? AND tanggal = ? AND jenis_absen = ?");
$stmt->execute([$user_id, $today, $jenis_absen]);
$existing = $stmt->fetch();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Absensi Jam Dinas - MY-Visite</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/absen_dinas.css">


</head>

<body>

<div class="container">
    
    <!-- HEADER -->
    <div class="header-card" style="position: relative;">
        <a href="dashboard.php" class="btn-back">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="header-icon">
            <i class="bi bi-building-fill"></i>
        </div>
        <div class="header-title">Absensi Jam Dinas</div>
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
                <button id="btnCapture" class="btn-capture">
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

// Fungsi menghitung jarak antara dua koordinat (Haversine Formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Radius bumi dalam meter
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c; // Jarak dalam meter
}

// Validasi lokasi user
function checkLocation() {
    if (!navigator.geolocation) {
        locationStatus.className = 'location-status invalid';
        locationStatus.innerHTML = '<i class="bi bi-x-circle-fill"></i><span>Browser Anda tidak mendukung GPS</span>';
        btnCapture.disabled = true;
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const userLat = position.coords.latitude;
            const userLon = position.coords.longitude;
            
            // Simpan koordinat user
            userLatitude.value = userLat;
            userLongitude.value = userLon;
            
            // Hitung jarak
            const distance = calculateDistance(
                userLat, 
                userLon, 
                settingLokasi.latitude, 
                settingLokasi.longitude
            );
            
            console.log('Jarak dari lokasi: ' + distance.toFixed(2) + ' meter');
            
            // Validasi jarak
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
        alert('Lokasi Anda tidak valid! Pastikan Anda berada di area yang ditentukan.');
        return;
    }
    
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0);
    
    const photoData = canvas.toDataURL('image/jpeg', 0.8);
    fotoData.value = photoData;
    
    // Hide video, show canvas
    video.style.display = 'none';
    canvas.style.display = 'block';
    
    // Toggle buttons
    btnCapture.style.display = 'none';
    btnRetake.style.display = 'block';
    photoTaken = true;
    
    // Enable submit if location is valid
    if (locationValid && photoTaken) {
        btnSubmit.disabled = false;
    }
    
    // Stop camera
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

// Check location on load
checkLocation();

// Start camera on load
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