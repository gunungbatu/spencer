<?php
session_start();
$config_file = 'config.json';
$data = json_decode(file_get_contents($config_file), true);

// 1. LOGIN SYSTEM
if (isset($_POST['login'])) {
    // Default password jika file json belum ada/rusak: 'admin'
    $pass_check = isset($data['super_password']) ? $data['super_password'] : 'admin';
    
    if ($_POST['password'] === $pass_check) {
        $_SESSION['super_logged'] = true;
    } else {
        $error = "Password Super Admin Salah!";
    }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: super_admin.php"); exit; }

if (!isset($_SESSION['super_logged'])) {
?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Super Admin Login</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body { background: #111; color: #0f0; font-family: monospace; display: flex; height: 100vh; justify-content: center; align-items: center; }
            .box { border: 1px solid #0f0; padding: 40px; text-align: center; }
            input { background: #000; border: 1px solid #0f0; color: #0f0; padding: 10px; margin-top: 10px; text-align: center; }
            button { background: #0f0; color: #000; border: none; padding: 10px 20px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>SYSTEM CONFIG</h2>
            <form method="post">
                <input type="password" name="password" placeholder="Access Key" required><br>
                <button type="submit" name="login">ENTER SYSTEM</button>
                <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
            </form>
        </div>
    </body>
    </html>
<?php exit; } 

// 2. SAVE CONFIG
if (isset($_POST['save'])) {
    foreach ($_POST as $key => $val) {
        if($key != 'save') $data[$key] = $val;
    }
    file_put_contents($config_file, json_encode($data, JSON_PRETTY_PRINT));
    $msg = "Konfigurasi System Berhasil Disimpan!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Super Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background: #f0f2f5; padding: 40px; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top: 5px solid #d63031; }
        h2 { margin-top: 0; color: #d63031; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.9rem; color: #555; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-family: monospace; }
        .hint { font-size: 0.8rem; color: #888; margin-top: 5px; }
        .btn-save { background: #d63031; color: white; border: none; padding: 15px 30px; font-size: 1rem; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; }
        .btn-save:hover { background: #c0392b; }
        .alert { background: #dff9fb; color: #130f40; padding: 15px; margin-bottom: 20px; border-left: 5px solid #22a6b3; }
        .logout { float: right; text-decoration: none; color: #d63031; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <a href="?logout=true" class="logout">Logout</a>
        <h2>⚙️ DEVELOPER CONFIG</h2>
        
        <?php if(isset($msg)) echo "<div class='alert'>$msg</div>"; ?>

        <form method="post">
            
            <div class="form-group">
                <label>🌐 Google Apps Script URL (Web App)</label>
                <input type="text" name="api_url" value="<?php echo $data['api_url'] ?? ''; ?>">
                <div class="hint">URL dari Deploy > Web App > Copy URL (exec)</div>
            </div>

            <div class="form-group">
                <label>📞 WhatsApp Hotel (Format 62...)</label>
                <input type="text" name="hotel_wa" value="<?php echo $data['hotel_wa'] ?? ''; ?>">
                <div class="hint">Nomor tujuan notifikasi booking</div>
            </div>

            <div class="form-group">
                <label>📧 Email Notifikasi</label>
                <input type="text" name="hotel_email" value="<?php echo $data['hotel_email'] ?? ''; ?>">
                <div class="hint">Email yang muncul di voucher</div>
            </div>

            <div class="form-group">
                <label>📍 Google Maps Link</label>
                <input type="text" name="hotel_maps_link" value="<?php echo $data['hotel_maps_link'] ?? ''; ?>">
                <div class="hint">Link untuk tombol "Get Direction"</div>
            </div>

            <hr style="margin: 30px 0; border: 0; border-top: 1px dashed #ccc;">

            <div class="form-group">
                <label>🔐 PIN Admin PMS (Stok & Harga)</label>
                <input type="text" name="admin_pin" value="<?php echo $data['admin_pin'] ?? ''; ?>">
                <div class="hint">PIN untuk membuka halaman admin.html</div>
            </div>

            <div class="form-group">
                <label>🔑 Password Super Admin (Halaman Ini)</label>
                <input type="text" name="super_password" value="<?php echo $data['super_password'] ?? ''; ?>">
                <div class="hint">Ganti password untuk masuk ke halaman ini</div>
            </div>

            <button type="submit" name="save" class="btn-save">UPDATE SYSTEM CONFIG</button>
        </form>
    </div>
</body>
</html>
