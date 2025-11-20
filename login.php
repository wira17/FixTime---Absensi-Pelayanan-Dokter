<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header('Location: redirect.php');
    exit;
}

$register_success = $_SESSION['register_success'] ?? false;
$register_nama = $_SESSION['register_nama'] ?? '';
unset($_SESSION['register_success'], $_SESSION['register_nama']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        
        // PENTING: Set semua session yang dibutuhkan
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['nama']     = $user['nama'];
        $_SESSION['role']     = $user['role'];
        $_SESSION['nik']      = $user['nik'];
        $_SESSION['email']    = $user['email'];
        
        // Regenerate session ID untuk keamanan lintas device
        session_regenerate_id(true);

        header("Location: redirect.php");  
        exit;

    } else {
        $error = "Email atau password salah.";
    }
}
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FixTime — Login</title>
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
    background: rgba(255, 255, 255, 0.12);
    animation: float 10s infinite ease-in-out;
}

body::before {
    width: 350px;
    height: 350px;
    top: -150px;
    right: -100px;
    animation-delay: 0s;
}

body::after {
    width: 250px;
    height: 250px;
    bottom: -80px;
    left: -80px;
    animation-delay: 3s;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(180deg); }
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
    filter: drop-shadow(0 0 25px rgba(255, 255, 255, 0.6));
    animation: heartbeat 2s infinite;
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    10%, 30% { transform: scale(1.1); }
    20%, 40% { transform: scale(1.05); }
}

.app-title {
    color: #fff;
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 4px 0;
    text-shadow: 0 2px 15px rgba(0,0,0,0.15);
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

/* Success Notification - Hijau Fresh */
.success-notification {
    background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
    color: white;
    border-radius: 18px;
    padding: 18px;
    margin-bottom: 20px;
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
    animation: bounceIn 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.success-notification::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
    animation: rotate 8s linear infinite;
}

@keyframes rotate {
    to { transform: rotate(360deg); }
}

@keyframes bounceIn {
    0% {
        opacity: 0;
        transform: scale(0.3);
    }
    50% {
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

.success-notification .content {
    position: relative;
    z-index: 1;
}

.success-notification .icon-check {
    font-size: 42px;
    margin-bottom: 8px;
    animation: checkmark 0.5s ease-out 0.3s both;
    display: inline-block;
}

@keyframes checkmark {
    0% {
        transform: scale(0) rotate(-45deg);
    }
    50% {
        transform: scale(1.2) rotate(10deg);
    }
    100% {
        transform: scale(1) rotate(0);
    }
}

.success-notification h4 {
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 6px;
}

.success-notification .welcome-text {
    font-size: 14px;
    font-weight: 600;
    margin: 8px 0;
    text-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.success-notification p {
    margin: 0;
    font-size: 11px;
    opacity: 0.95;
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

.form-control {
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

.form-control:focus {
    background: #fff;
    border-color: #4ade80;
    box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.15);
    outline: none;
}

.form-control:focus + i {
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
.link-register {
    display: inline-block;
    margin-top: 16px;
    color: #16a34a;
    font-weight: 500;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
}

.link-register::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #16a34a;
    transition: width 0.3s;
}

.link-register:hover::after {
    width: 100%;
}

.link-register:hover {
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
        font-size: 100px;
    }
    
    .app-title {
        font-size: 26px;
    }
    
    .card-app {
        padding: 20px;
    }
    
    .form-control {
        height: 46px;
        font-size: 13px;
    }
    
    .success-notification {
        padding: 16px;
    }
    
    .success-notification .icon-check {
        font-size: 38px;
    }
}

/* Loading Animation */
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
    <div class="app-subtitle">🌿 Absensi Pelayanan Dokter RS. Permata Hati</div>

    <!-- CARD -->
    <div class="card-app">

        <!-- SUCCESS NOTIFICATION -->
        <?php if ($register_success): ?>
        <div class="success-notification">
            <div class="content">
                <i class="bi bi-check-circle-fill icon-check"></i>
                <h4>🎉 Pendaftaran Berhasil!</h4>
                <p class="welcome-text">Selamat datang, <?= htmlspecialchars($register_nama) ?>!</p>
                <p style="margin-top: 8px;">Silahkan login untuk melanjutkan</p>
            </div>
        </div>
        <?php endif; ?>

       <div class="subtitle">Daftarkan akun Anda untuk mulai menggunakan sistem Absensi Pelayanan Dokter.</div>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" id="loginForm">
            <div class="input-group-custom">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
                <i class="bi bi-envelope-fill"></i>
            </div>

            <div class="input-group-custom">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <i class="bi bi-lock-fill"></i>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk Sekarang
            </button>
        </form>

       <a href="register.php" class="link-register">
    <i class="bi bi-person-plus me-1"></i>Belum punya akun? Daftar
</a>

<div class="text-center mt-2">
    <small style="color:#555; font-size:12px;">
        <i class="bi bi-whatsapp text-success"></i>
        Contact : 082177846209 / M. Wira,Sb. S. Kom
    </small>
</div>

    </div>
</div>

<script>
// Add loading animation on submit
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('.btn-primary');
    btn.classList.add('loading');
    btn.innerHTML = 'Memproses...';
});
</script>

</body>
</html>