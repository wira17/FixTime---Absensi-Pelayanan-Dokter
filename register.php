<?php
session_start();
require 'koneksi.php';

// Ambil data spesialis untuk dropdown
$spesialisList = $pdo->query("SELECT * FROM master_spesialis ORDER BY nama_spesialis ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = trim($_POST['nik']);
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $spesialis = $_POST['spesialis'] ?? '';

    if (!$nik || !$nama || !$email || !$pass || !$spesialis) {
        $error = "Lengkapi semua field.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO pengguna (nik,nama,email,password,role,spesialis_id) VALUES (?,?,?,?, 'user', ?)");
        try {
            $stmt->execute([$nik, $nama, $email, $hash, $spesialis]);

            $_SESSION['register_success'] = true;
            $_SESSION['register_nama'] = $nama;

            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $error = "Email atau NIK sudah terdaftar.";
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FixTime — Register</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    background: linear-gradient(135deg, #a8e6cf 0%, #56c596 50%, #2ecc71 100%);
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 15px;
    position: relative;
    overflow-x: hidden;
}

/* Animated Background Circles */
body::before,
body::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    animation: float 8s infinite ease-in-out;
}

body::before {
    width: 300px;
    height: 300px;
    top: -100px;
    right: -100px;
    animation-delay: 0s;
}

body::after {
    width: 200px;
    height: 200px;
    bottom: -50px;
    left: -50px;
    animation-delay: 2s;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

.wrapper {
    width: 100%;
    max-width: 420px;
    text-align: center;
    position: relative;
    z-index: 10;
}

/* Header with Glow Effect */
.logo-container {
    position: relative;
    display: inline-block;
    margin-bottom: 8px;
}

.logo-icon {
    font-size: 52px;
    color: #fff;
    filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.6));
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.app-title {
    color: #fff;
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 4px 0;
    text-shadow: 0 2px 10px rgba(0,0,0,0.15);
    letter-spacing: 0.5px;
}

.app-subtitle {
    color: rgba(255, 255, 255, 0.95);
    font-size: 12px;
    margin-bottom: 18px;
    font-weight: 400;
}

/* Glassmorphism Card */
.card-app {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 24px;
    padding: 24px;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.1),
        0 0 0 1px rgba(255, 255, 255, 0.3);
    animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.subtitle {
    color: #6b7280;
    font-size: 12px;
    margin-bottom: 18px;
    line-height: 1.5;
    font-weight: 400;
}

/* Modern Input Fields */
.input-group-custom {
    position: relative;
    margin-bottom: 14px;
}

.input-group-custom i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 16px;
    transition: all 0.3s;
    z-index: 1;
}

.form-control, .form-select {
    height: 48px;
    border-radius: 14px;
    padding-left: 46px;
    padding-right: 16px;
    background: #f0fdf4;
    border: 2px solid #d1fae5;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 400;
}

.form-control:focus, .form-select:focus {
    background: #fff;
    border-color: #4ade80;
    box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.15);
    outline: none;
}

.form-control:focus + i,
.form-select:focus + i {
    color: #2ecc71;
}

/* Gradient Button - Hijau */
.btn-primary {
    background: linear-gradient(135deg, #4ade80 0%, #2ecc71 50%, #22c55e 100%);
    border: none;
    height: 48px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 600;
    width: 100%;
    color: white;
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
    transition: all 0.3s ease;
    margin-top: 8px;
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(46, 204, 113, 0.5);
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Link Styling */
.link-login {
    display: inline-block;
    margin-top: 16px;
    color: #16a34a;
    font-weight: 500;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
}

.link-login::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #16a34a;
    transition: width 0.3s;
}

.link-login:hover::after {
    width: 100%;
}

.link-login:hover {
    color: #15803d;
}

/* Alert Styling */
.alert-danger {
    background: linear-gradient(135deg, #fb7185 0%, #f43f5e 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    padding: 12px;
    margin-bottom: 16px;
    box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3);
}

/* Mobile Optimization */
@media (max-width: 480px) {
    body {
        padding: 12px;
    }
    
    .logo-icon {
        font-size: 46px;
    }
    
    .app-title {
        font-size: 26px;
    }
    
    .card-app {
        padding: 20px;
    }
    
    .form-control, .form-select {
        height: 46px;
        font-size: 13px;
    }
}

/* Loading Animation for Button */
.btn-primary.loading {
    pointer-events: none;
    opacity: 0.7;
}

.btn-primary.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
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

<div class="wrapper">
    <!-- HEADER -->
    <div class="logo-container">
        <i class="bi bi-heart-pulse-fill logo-icon"></i>
    </div>
    <div class="app-title">FixTime</div>
    <div class="app-subtitle">🌿 Absensi Pelayanan Dokter</div>

    <!-- CARD -->
    <div class="card-app">
        <div class="subtitle">Daftarkan akun Anda untuk mulai menggunakan sistem Absensi Pelayanan Dokter.</div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger text-center">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" id="registerForm">
            <div class="input-group-custom">
                <input type="text" class="form-control" name="nik" placeholder="NIK / NIP" required>
                <i class="bi bi-credit-card-2-front-fill"></i>
            </div>

            <div class="input-group-custom">
                <input type="text" class="form-control" name="nama" placeholder="Nama Lengkap" required>
                <i class="bi bi-person-fill"></i>
            </div>

            <div class="input-group-custom">
                <input type="email" class="form-control" name="email" placeholder="Email" required>
                <i class="bi bi-envelope-fill"></i>
            </div>

            <div class="input-group-custom">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
                <i class="bi bi-lock-fill"></i>
            </div>

            <div class="input-group-custom">
                <select name="spesialis" class="form-select" required>
                    <option value="">-- Pilih Spesialis --</option>
                    <?php foreach($spesialisList as $s): ?>
                        <option value="<?= htmlspecialchars($s['id']) ?>"><?= htmlspecialchars($s['nama_spesialis']) ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="bi bi-card-list"></i>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
            </button>
        </form>

        <a href="login.php" class="link-login">
            <i class="bi bi-box-arrow-in-right me-1"></i>Sudah punya akun? Login
        </a>
    </div>
</div>

<script>
// Add loading animation on submit
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('.btn-primary');
    btn.classList.add('loading');
    btn.innerHTML = 'Mendaftar...';
});
</script>

</body>
</html>