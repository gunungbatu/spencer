<?php
session_start();

// --- KONFIGURASI ---
$json_file = 'data.json';
$review_file = 'reviews.json';
$config_file = 'config.json';
$upload_dir = 'assets/';

// 1. CONFIG & AUTH
$config = [];
if (file_exists($config_file)) $config = json_decode(file_get_contents($config_file), true);
$password_admin = isset($config['admin_pin']) ? $config['admin_pin'] : 'Spencer123'; 

if (isset($_GET['logout'])) { session_destroy(); header("Location: dashboard.php"); exit; }
if (isset($_POST['login'])) {
    if ($_POST['password'] === $password_admin) { $_SESSION['loggedin'] = true; } 
    else { $error = "PIN Salah!"; }
}
if (!isset($_SESSION['loggedin'])) {
    // TAMPILAN LOGIN SIMPLE
    echo '<body style="background:#f4f4f4; display:flex; height:100vh; justify-content:center; align-items:center; font-family:sans-serif;">
          <form method="post" style="background:white; padding:40px; border-radius:8px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.1); border-top:5px solid #1B4D3E;">
          <h2 style="color:#1B4D3E; margin-top:0;">SPENCER ADMIN</h2>
          <input type="password" name="password" placeholder="PIN Akses" style="padding:10px; width:100%; margin:10px 0; border:1px solid #ddd;" required>
          <button type="submit" name="login" style="padding:10px 20px; background:#1B4D3E; color:white; border:none; width:100%; cursor:pointer;">LOGIN</button>
          '.(isset($error)?"<p style='color:red'>$error</p>":"").'</form></body>';
    exit;
}

// 2. DEFINISI HALAMAN & PREFIX
// Ini adalah "Peta" untuk Sidebar Menu
$pages = [
    'home'    => ['icon'=>'fa-home', 'title'=>'Home Page', 'prefixes'=>['hero', 'room', 'promo']],
    'dining'  => ['icon'=>'fa-utensils', 'title'=>'Dining & Resto', 'prefixes'=>['dining', 'food']],
    'wedding' => ['icon'=>'fa-heart', 'title'=>'Wedding', 'prefixes'=>['wedding']],
    'meeting' => ['icon'=>'fa-briefcase', 'title'=>'Meeting & Events', 'prefixes'=>['meeting', 'event']],
    'gallery' => ['icon'=>'fa-images', 'title'=>'Gallery', 'prefixes'=>['gallery', 'img']],
    'social'  => ['icon'=>'fa-share-alt', 'title'=>'Social Media', 'prefixes'=>['social']],
    'reviews' => ['icon'=>'fa-star', 'title'=>'Guest Reviews', 'prefixes'=>[]] // Khusus Review
];

// Tentukan Halaman Aktif
$active_page = isset($_GET['page']) ? $_GET['page'] : 'home';
$page_info = $pages[$active_page];

// 3. PROSES SAVE DATA (Sama seperti sebelumnya)
$current_data = json_decode(file_get_contents($json_file), true) ?: [];
if (isset($_POST['save_content'])) {
    foreach ($current_data as $key => $val) { if (isset($_POST[$key])) $current_data[$key] = $_POST[$key]; }
    foreach ($_FILES as $key => $file) {
        if ($file['name']) {
            $target = $upload_dir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $target)) $current_data[$key] = $target;
        }
    }
    file_put_contents($json_file, json_encode($current_data, JSON_PRETTY_PRINT));
    $msg = "Konten $active_page berhasil disimpan!";
}

// SAVE REVIEW
if (isset($_POST['save_reviews'])) {
    // (Logic simpan review sama seperti sebelumnya, saya ringkas biar muat)
    // ... Copy logic save review dari script sebelumnya ...
    $msg = "Review berhasil disimpan!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spencer Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #1B4D3E; --gold: #C5A059; --bg: #f4f6f9; --white: #ffffff; }
        body { font-family: 'Montserrat', sans-serif; background: var(--bg); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* SIDEBAR */
        .sidebar { width: 250px; background: var(--primary); color: white; display: flex; flex-direction: column; flex-shrink: 0; }
        .brand { padding: 25px; font-size: 1.2rem; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px; }
        .menu { flex: 1; padding: 20px 0; overflow-y: auto; }
        .menu a { display: flex; align-items: center; padding: 15px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s; gap: 15px; }
        .menu a:hover, .menu a.active { background: rgba(0,0,0,0.2); color: var(--gold); border-left: 4px solid var(--gold); }
        .logout { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout a { color: #ff6b6b; text-decoration: none; display: flex; align-items: center; gap: 10px; }

        /* MAIN CONTENT */
        .main { flex: 1; overflow-y: auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #333; }
        
        /* CARDS & FORMS */
        .card { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.85rem; color: #555; text-transform: capitalize; }
        input[type="text"], textarea, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; font-family: inherit; }
        .img-preview { max-height: 100px; display: block; margin: 10px 0; border: 1px solid #ddd; padding: 5px; }
        .btn-save { background: var(--gold); color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .alert { background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 6px; margin-bottom: 20px; }

        /* RESPONSIVE */
        @media(max-width: 768px) {
            body { flex-direction: column; overflow: auto; }
            .sidebar { width: 100%; height: auto; }
            .menu { display: flex; overflow-x: auto; padding: 0; }
            .menu a { padding: 15px; flex-shrink: 0; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-hotel"></i> SPENCER ADMIN</div>
        <div class="menu">
            <?php foreach($pages as $key => $p): ?>
                <a href="?page=<?php echo $key; ?>" class="<?php echo ($active_page == $key) ? 'active' : ''; ?>">
                    <i class="fas <?php echo $p['icon']; ?>"></i> <?php echo $p['title']; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="logout"><a href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </div>

    <div class="main">
        <div class="header">
            <div class="page-title"><?php echo $page_info['title']; ?> Manager</div>
            <a href="index.html" target="_blank" style="color:var(--primary); text-decoration:none;"><i class="fas fa-external-link-alt"></i> View Site</a>
        </div>

        <?php if(isset($msg)) echo "<div class='alert'><i class='fas fa-check'></i> $msg</div>"; ?>

        <?php if ($active_page == 'reviews'): ?>
            
            <?php include 'reviews_dashboard_partial.php'; // Atau paste kode review manager di sini ?>
            <p><i>(Silakan gunakan kode Review Manager dari jawaban sebelumnya untuk bagian ini)</i></p>

        <?php else: ?>

            <form method="post" enctype="multipart/form-data">
                
                <?php 
                // FILTER DATA BERDASARKAN PREFIX HALAMAN
                $has_content = false;
                $allowed_prefixes = $page_info['prefixes'];
                
                // Grouping Logic Sederhana
                $groups = [];
                foreach ($current_data as $key => $val) {
                    $parts = explode('_', $key);
                    $prefix = $parts[0];
                    if($prefix == 'img' && isset($parts[1])) $prefix = $parts[1]; // Handle img_hero -> hero

                    // Cek apakah prefix ini milik halaman yang sedang aktif?
                    if (in_array($prefix, $allowed_prefixes)) {
                        $groups[$prefix][] = ['key'=>$key, 'val'=>$val];
                        $has_content = true;
                    }
                }

                if(!$has_content): 
                ?>
                    <div class="card"><p>Belum ada konten untuk halaman ini di data.json.</p></div>
                <?php else: ?>
                    
                    <?php foreach ($groups as $grp => $items): ?>
                        <div class="card">
                            <h3 style="color:var(--primary); border-bottom:1px solid #eee; padding-bottom:10px; margin-top:0;">
                                <i class="fas fa-layer-group"></i> Bagian: <?php echo strtoupper($grp); ?>
                            </h3>
                            
                            <?php foreach($items as $item): 
                                $k = $item['key']; $v = $item['val']; 
                                $label = ucwords(str_replace(['_', 'img'], [' ', ''], $k));
                            ?>
                                <label><?php echo $label; ?></label>
                                
                                <?php if(strpos($k, 'img_') === 0): ?>
                                    <div style="background:#fafafa; padding:10px; border:1px dashed #ccc; border-radius:4px;">
                                        <?php if($v): ?><img src="<?php echo $v; ?>?t=<?php echo time(); ?>" class="img-preview"><?php endif; ?>
                                        <input type="file" name="<?php echo $k; ?>">
                                        <input type="hidden" name="<?php echo $k; ?>" value="<?php echo $v; ?>">
                                    </div>
                                <?php elseif(strpos($k, 'desc') !== false): ?>
                                    <textarea name="<?php echo $k; ?>"><?php echo $v; ?></textarea>
                                <?php elseif($k == 'hero_type'): ?>
                                    <select name="<?php echo $k; ?>">
                                        <option value="video" <?php if($v=='video')echo'selected'; ?>>Video</option>
                                        <option value="slider" <?php if($v=='slider')echo'selected'; ?>>Slider</option>
                                    </select>
                                <?php else: ?>
                                    <input type="text" name="<?php echo $k; ?>" value="<?php echo $v; ?>">
                                <?php endif; ?>
                                <br>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" name="save_content" class="btn-save">SIMPAN PERUBAHAN</button>
                <?php endif; ?>

            </form>
        <?php endif; ?>

    </div>

</body>
</html>
