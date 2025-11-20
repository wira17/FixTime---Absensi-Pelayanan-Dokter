<?php
/**
 * SETUP FOLDER UPLOADS
 * Jalankan file ini sekali untuk membuat folder uploads
 * Akses: http://localhost/project/setup_uploads.php
 */

$base_dir = __DIR__;
$upload_dir = $base_dir . '/uploads/absensi/';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Setup Folder Uploads</title>";
echo "<style>
    body { font-family: Arial; padding: 40px; background: #f5f5f5; }
    .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #22c55e; }
    .success { color: #059669; background: #d1fae5; padding: 15px; border-radius: 8px; margin: 10px 0; }
    .error { color: #dc2626; background: #fee2e2; padding: 15px; border-radius: 8px; margin: 10px 0; }
    .info { color: #1e40af; background: #dbeafe; padding: 15px; border-radius: 8px; margin: 10px 0; }
    code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    .btn { display: inline-block; padding: 10px 20px; background: #22c55e; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    .btn:hover { background: #16a34a; }
</style>";
echo "</head><body>";
echo "<div class='container'>";
echo "<h1>🔧 Setup Folder Uploads</h1>";

// Cek apakah folder sudah ada
if (file_exists($upload_dir)) {
    echo "<div class='info'>✅ Folder <code>uploads/absensi/</code> sudah ada!</div>";
    
    // Cek permission
    if (is_writable($upload_dir)) {
        echo "<div class='success'>✅ Folder dapat ditulis (writable)</div>";
    } else {
        echo "<div class='error'>❌ Folder tidak dapat ditulis!</div>";
        echo "<div class='info'>Jalankan command: <code>chmod 777 uploads/absensi/</code></div>";
    }
    
} else {
    // Buat folder
    echo "<div class='info'>📁 Membuat folder <code>uploads/absensi/</code>...</div>";
    
    if (mkdir($upload_dir, 0777, true)) {
        chmod($upload_dir, 0777); // Set permission eksplisit
        echo "<div class='success'>✅ Folder berhasil dibuat!</div>";
        
        // Cek lagi permission
        if (is_writable($upload_dir)) {
            echo "<div class='success'>✅ Folder dapat ditulis (writable)</div>";
        } else {
            echo "<div class='error'>❌ Folder dibuat tapi tidak dapat ditulis!</div>";
            echo "<div class='info'>Jalankan command: <code>chmod 777 uploads/absensi/</code></div>";
        }
    } else {
        echo "<div class='error'>❌ Gagal membuat folder!</div>";
        echo "<div class='info'>Buat folder manual dengan langkah berikut:</div>";
        echo "<ol>";
        echo "<li>Buat folder <code>uploads</code> di root project</li>";
        echo "<li>Buat folder <code>absensi</code> di dalam folder uploads</li>";
        echo "<li>Set permission 777: <code>chmod 777 uploads/absensi/</code></li>";
        echo "</ol>";
    }
}

// Test write file
echo "<hr>";
echo "<h3>🧪 Test Menulis File</h3>";

$test_file = $upload_dir . 'test_' . time() . '.txt';
$test_content = 'Test write permission - ' . date('Y-m-d H:i:s');

if (@file_put_contents($test_file, $test_content)) {
    echo "<div class='success'>✅ Berhasil menulis file test!</div>";
    echo "<div class='info'>File: <code>" . basename($test_file) . "</code></div>";
    
    // Hapus file test
    @unlink($test_file);
    echo "<div class='info'>File test sudah dihapus.</div>";
} else {
    echo "<div class='error'>❌ Gagal menulis file test!</div>";
    echo "<div class='info'>Kemungkinan penyebab:</div>";
    echo "<ul>";
    echo "<li>Permission folder salah (harus 777)</li>";
    echo "<li>PHP tidak punya akses write</li>";
    echo "<li>Disk penuh</li>";
    echo "<li>SELinux blocking (jika di Linux)</li>";
    echo "</ul>";
}

// Info path
echo "<hr>";
echo "<h3>📋 Informasi Path</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><td><strong>Base Directory</strong></td><td><code>" . htmlspecialchars($base_dir) . "</code></td></tr>";
echo "<tr><td><strong>Upload Directory</strong></td><td><code>" . htmlspecialchars($upload_dir) . "</code></td></tr>";
echo "<tr><td><strong>Folder Exists?</strong></td><td>" . (file_exists($upload_dir) ? '✅ Ya' : '❌ Tidak') . "</td></tr>";
echo "<tr><td><strong>Writable?</strong></td><td>" . (is_writable($upload_dir) ? '✅ Ya' : '❌ Tidak') . "</td></tr>";
echo "<tr><td><strong>Permission</strong></td><td><code>" . (file_exists($upload_dir) ? substr(sprintf('%o', fileperms($upload_dir)), -4) : '-') . "</code></td></tr>";
echo "</table>";

// Command untuk cPanel/FTP
echo "<hr>";
echo "<h3>💡 Jika Menggunakan cPanel/FTP</h3>";
echo "<div class='info'>";
echo "<ol>";
echo "<li>Login ke cPanel → File Manager</li>";
echo "<li>Buat folder <code>uploads</code> di public_html/project</li>";
echo "<li>Buat folder <code>absensi</code> di dalam uploads</li>";
echo "<li>Klik kanan folder absensi → Change Permissions</li>";
echo "<li>Set ke <strong>777</strong> (atau centang semua checkbox)</li>";
echo "</ol>";
echo "</div>";

echo "<a href='dashboard.php' class='btn'>← Kembali ke Dashboard</a>";
echo "</div>";
echo "</body></html>";
?>