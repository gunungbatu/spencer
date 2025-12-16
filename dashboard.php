<?php
session_start();

// --- KONFIGURASI ---
$json_file = 'data.json';
$review_file = 'reviews.json';
$config_file = 'config.json';
$upload_dir = 'assets/';

// 1. LOAD CONFIG & PASSWORD
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
}
// Default password jika config belum ada
$password_admin = isset($config['admin_pin']) ? $config['admin_pin'] : 'Spencer123'; 

// 2. LOGIC LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: dashboard.php");
    exit;
}

// 3. LOGIC LOGIN
if (isset($_POST['login'])) {
    if ($_POST['password'] === $password_admin) {
        $_SESSION['loggedin'] = true;
    } else {
        $error = "PIN Salah!";
    }
}

// JIKA BELUM LOGIN, TAMPILKAN HALAMAN LOGIN
if (!isset($_SESSION['loggedin'])) {
?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login Dashboard</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Montserrat', sans-serif; display: flex; height: 100vh; justify-content: center; align-items: center; background: #f4f4f4; margin: 0; }
            .box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; width: 300px; border-top: 5px solid #1B4D3E; }
            input { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; text-align: center; }
            button { width: 100%; padding: 12px; background: #1B4D3E; color: white; border: none; font-weight: bold; cursor: pointer; border-radius: 4px; }
            button:hover { background: #143d30; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2 style="color:#1B4D3E; margin-top:0;">SPENCER ADMIN</h2>
            <form method="post">
                <input type="password" name="password" placeholder="Masukkan PIN" required>
                <button type="submit" name="login">LOGIN</button>
                <?php if(isset($error)) echo "<p style='color:red; font-size:0.9rem;'>$error</p>"; ?>
            </form>
        </div>
    </body>
    </html>
<?php
    exit;
}

// --- BAGIAN BACKEND SAVE DATA ---

// A. SAVE KONTEN WEBSITE (TEXT & GAMBAR)
$current_data = json_decode(file_get_contents($json_file), true);
if (!$current_data) $current_data = [];

if (isset($_POST['save_content'])) {
    // 1. Simpan Text Input
    foreach ($current_data as $key => $val) {
        if (isset($_POST[$key])) {
            $current_data[$key] = $_POST[$key];
        }
    }
    // 2. Simpan Upload File (Gambar/Video)
    foreach ($_FILES as $key => $file) {
        if ($file['name'] && (strpos($key, 'img_') === 0 || strpos($key, 'hero_video') !== false)) {
            $target_file = $upload_dir . basename($file['name']);
            // Upload file
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $current_data[$key] = $target_file;
            }
        }
    }
    // Tulis ke JSON
    file_put_contents($json_file, json_encode($current_data, JSON_PRETTY_PRINT));
    $msg = "Konten Website Berhasil Diupdate!";
}

// B. SAVE REVIEW MANAGER
$reviews = json_decode(file_get_contents($review_file), true);
if (!$reviews) $reviews = [];

if (isset($_POST['save_reviews'])) {
    $new_reviews_list = [];
    
    // 1. Proses Data Existing (Edit/Hapus/Show)
    if(isset($_POST['rev_id'])) {
        foreach($_POST['rev_id'] as $index => $id) {
            // Cek apakah dicentang Hapus? Jika YA, jangan dimasukkan ke list baru
            if (!isset($_POST['del_' . $id])) {
                $new_reviews_list[] = [
                    "id" => $id,
                    "name" => $_POST['rev_name'][$index],
                    "rating" => $_POST['rev_rating'][$index],
                    "comment" => $_POST['rev_comment'][$index],
                    "source" => $_POST['rev_source'][$index],
                    "date" => $_POST['rev_date'][$index],
                    "visible" => isset($_POST['rev_vis'][$index]) ? true : false
                ];
            }
        }
    }
    
    // 2. Proses Review Baru (Manual Input)
    if (!empty($_POST['new_name']) && !empty($_POST['new_comment'])) {
        $new_reviews_list[] = [
            "id" => uniqid("rev_"),
            "name" => $_POST['new_name'],
            "rating" => $_POST['new_rating'],
            "comment" => $_POST['new_comment'],
            "source" => $_POST['new_source'],
            "date" => date("Y-m-d"),
            "visible" => true // Manual input langsung tampil
        ];
    }

    file_put_contents($review_file, json_encode($new_reviews_list, JSON_PRETTY_PRINT));
    
    // Redirect refresh agar form bersih
    header("Location: dashboard.php?msg=Review Saved");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spencer Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #1B4D3E; --gold: #C5A059; --bg: #f8f9fa; --white: #ffffff; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg); color: #333; margin: 0; padding-bottom: 50px; }
        
        /* HEADER */
        .navbar { background: var(--white); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: var(--primary); }
        .logout-btn { color: #dc3545; text-decoration: none; font-weight: 600; border: 1px solid #dc3545; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; }
        
        /* CONTAINER */
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .alert { background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        
        /* CARD STYLE */
        .card { background: var(--white); border-radius: 10px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #eee; }
        .card-header { font-weight: 700; color: var(--primary); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; text-transform: uppercase; letter-spacing: 1px; }
        
        /* FORM ELEMENTS */
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.85rem; color: #666; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 0.95rem; box-sizing: border-box; }
        textarea { resize: vertical; min-height: 80px; }
        
        /* IMAGE UPLOAD */
        .img-box { border: 2px dashed #ddd; padding: 15px; text-align: center; border-radius: 6px; background: #fafafa; position: relative; }
        .img-preview { max-height: 100px; margin-top: 10px; display: block; margin-left: auto; margin-right: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        /* BUTTONS */
        .btn-save { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-save:hover { background: #143d30; }

        /* TOGGLE SWITCH CSS (Untuk Review) */
        /* Checkbox default disembunyikan jika pakai custom switch, tapi ini versi simple biar aman di semua browser */
        input[type="checkbox"] { transform: scale(1.3); cursor: pointer; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand"><i class="fas fa-edit"></i> SPENCER EDITOR</div>
        <a href="?logout=true" class="logout-btn">Logout</a>
    </nav>

    <div class="container">
        
        <?php if(isset($msg) || isset($_GET['msg'])) echo "<div class='alert'><i class='fas fa-check-circle'></i> Perubahan berhasil disimpan!</div>"; ?>

        <form method="post" enctype="multipart/form-data">
            <?php 
            // Grouping Logic: Kelompokkan key berdasarkan prefix (hero_, room_, social_)
            $grouped = [];
            foreach ($current_data as $key => $val) {
                $parts = explode('_', $key);
                $prefix = $parts[0];
                // Khusus img_hero masuk ke group hero
                if($prefix == 'img' && isset($parts[1]) && $parts[1] == 'hero') $prefix = 'hero'; 
                $grouped[$prefix][] = ['key' => $key, 'val' => $val];
            }

            foreach ($grouped as $groupName => $items): 
            ?>
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-layer-group"></i> Bagian: <?php echo strtoupper($groupName); ?></span>
                    </div>
                    
                    <?php foreach ($items as $item): 
                        $key = $item['key'];
                        $val = $item['val'];
                        $label = ucwords(str_replace(['_', $groupName], [' ', ''], $key));
                    ?>
                        <div style="margin-bottom: 20px;">
                            <label>
                                <?php echo $label; ?> <small style="color:#ccc; font-weight:normal;">(ID: <?php echo $key; ?>)</small>
                            </label>

                            <?php if ($key == 'hero_type'): ?>
                                <select name="<?php echo $key; ?>">
                                    <option value="video" <?php echo ($val == 'video') ? 'selected' : ''; ?>>Video Background</option>
                                    <option value="slider" <?php echo ($val == 'slider') ? 'selected' : ''; ?>>Image Slider</option>
                                </select>

                            <?php elseif (strpos($key, 'img_') === 0 || strpos($key, 'video') !== false): ?>
                                <div class="img-box">
                                    <input type="file" name="<?php echo $key; ?>">
                                    <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $val; ?>">
                                    <small style="color:#888;">File saat ini: <?php echo basename($val); ?></small>
                                    <?php if($val): ?>
                                        <?php if(pathinfo($val, PATHINFO_EXTENSION) == 'mp4'): ?>
                                            <video src="<?php echo $val; ?>" style="height:80px; margin-top:5px; display:block; margin:5px auto;"></video>
                                        <?php else: ?>
                                            <img src="<?php echo $val; ?>?t=<?php echo time(); ?>" class="img-preview">
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                            <?php elseif (strlen($val) > 50 || strpos($key, 'desc') !== false || strpos($key, 'comment') !== false): ?>
                                <textarea name="<?php echo $key; ?>"><?php echo $val; ?></textarea>

                            <?php else: ?>
                                <input type="text" name="<?php echo $key; ?>" value="<?php echo $val; ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" name="save_content" class="btn-save" style="margin-bottom: 50px;">SIMPAN KONTEN WEBSITE</button>
        </form>


        <form method="post">
            <div class="card" style="border-top:5px solid var(--gold);">
                <div class="card-header">
                    <span><i class="fas fa-star"></i> REVIEW MANAGER</span>
                    <button type="submit" name="save_reviews" class="btn-save" style="width:auto; padding:8px 20px; font-size:0.8rem; background:var(--gold);">SIMPAN REVIEW</button>
                </div>
                
                <div style="display:flex; flex-direction:column; gap:15px;">
                <?php if(empty($reviews)): ?>
                    <p style="text-align:center; color:#999;">Belum ada review.</p>
                <?php else: ?>
                    <?php foreach($reviews as $i=>$r): ?>
                        <div style="background:#f8f9fa; border:1px solid #e9ecef; padding:15px; border-radius:8px; display:flex; gap:20px; align-items:flex-start; <?php if(isset($r['visible']) && $r['visible']) echo 'border-left:5px solid #1B4D3E;'; ?>">
                            
                            <input type="hidden" name="rev_id[]" value="<?php echo $r['id']; ?>">
                            <input type="hidden" name="rev_date[]" value="<?php echo $r['date']; ?>">
                            <input type="hidden" name="rev_source[]" value="<?php echo $r['source']; ?>">

                            <div style="flex:1;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                                    <input type="text" name="rev_name[]" value="<?php echo $r['name']; ?>" style="font-weight:bold; color:#1B4D3E; width:60%; border:none; background:transparent; font-size:1rem;" placeholder="Nama Tamu">
                                    <span style="font-size:0.7rem; background:#ddd; padding:2px 8px; border-radius:4px; color:#555;"><?php echo strtoupper($r['source']); ?></span>
                                </div>
                                
                                <div style="margin-bottom:10px;">
                                    <select name="rev_rating[]" style="width:120px; padding:5px; font-size:0.85rem;">
                                        <option value="5" <?php echo ($r['rating']==5)?'selected':''; ?>>⭐⭐⭐⭐⭐</option>
                                        <option value="4" <?php echo ($r['rating']==4)?'selected':''; ?>>⭐⭐⭐⭐</option>
                                        <option value="3" <?php echo ($r['rating']==3)?'selected':''; ?>>⭐⭐⭐</option>
                                    </select>
                                </div>

                                <textarea name="rev_comment[]" style="width:100%; height:60px; font-size:0.9rem; padding:8px; border:1px solid #ccc; border-radius:4px;"><?php echo $r['comment']; ?></textarea>
                            </div>

                            <div style="width:140px; display:flex; flex-direction:column; gap:10px; border-left:1px solid #ddd; padding-left:15px;">
                                
                                <label style="cursor:pointer; display:flex; align-items:center; gap:8px; font-weight:bold; font-size:0.8rem; color:#1B4D3E;">
                                    <input type="checkbox" name="rev_vis[<?php echo $i; ?>]" value="1" <?php if(isset($r['visible']) && $r['visible']) echo 'checked'; ?> style="width:18px; height:18px; accent-color:#1B4D3E;">
                                    TAMPILKAN
                                </label>

                                <label style="cursor:pointer; display:flex; align-items:center; gap:8px; font-size:0.8rem; color:#dc3545; margin-top:5px;">
                                    <input type="checkbox" name="del_<?php echo $r['id']; ?>" style="width:18px; height:18px; accent-color:#dc3545;">
                                    Hapus
                                </label>

                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
                
                <div style="margin-top:30px; padding-top:20px; border-top:2px dashed #ddd; background:#fff;">
                    <h4 style="color:var(--gold); margin-top:0;">+ Tambah Review Manual (Sumber: Google Maps)</h4>
                    <div style="display:grid; grid-template-columns: 1fr 2fr 1fr; gap:10px; margin-bottom:10px;">
                        <select name="new_source" style="padding:10px;"><option value="google">Google Maps</option><option value="website">Manual Input</option></select>
                        <input type="text" name="new_name" placeholder="Nama Tamu" style="padding:10px;">
                        <select name="new_rating" style="padding:10px;"><option value="5">⭐⭐⭐⭐⭐</option><option value="4">⭐⭐⭐⭐</option></select>
                    </div>
                    <textarea name="new_comment" placeholder="Paste komentar tamu dari Google Maps disini..." style="width:100%; height:60px; padding:10px;"></textarea>
                </div>
            </div>
        </form>

    </div>
</body>
</html>
