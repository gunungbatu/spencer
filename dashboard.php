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
$password_admin = isset($config['admin_pin']) ? $config['admin_pin'] : 'Spencer123'; 

// 2. LOGIC AUTH
if (isset($_GET['logout'])) { session_destroy(); header("Location: dashboard.php"); exit; }
if (isset($_POST['login'])) {
    if ($_POST['password'] === $password_admin) { $_SESSION['loggedin'] = true; } 
    else { $error = "PIN Salah!"; }
}
if (!isset($_SESSION['loggedin'])) {
    // FORM LOGIN
    echo '<body style="background:#f4f4f4; display:flex; height:100vh; justify-content:center; align-items:center; font-family:sans-serif;">
          <form method="post" style="background:white; padding:40px; border-radius:8px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.1); border-top:5px solid #1B4D3E;">
          <h2 style="color:#1B4D3E; margin-top:0;">SPENCER ADMIN</h2>
          <input type="password" name="password" placeholder="Masukkan PIN" style="padding:10px; width:100%; margin:10px 0; border:1px solid #ddd;" required>
          <button type="submit" name="login" style="padding:10px 20px; background:#1B4D3E; color:white; border:none; width:100%; cursor:pointer;">LOGIN</button>
          '.(isset($error)?"<p style='color:red'>$error</p>":"").'</form></body>';
    exit;
}

// 3. FUNGSI SCAN FOLDER ASSETS (Untuk Media Library)
function getServerImages($dir) {
    $images = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $file)) {
                $images[] = $dir . $file;
            }
        }
    }
    return $images;
}
$server_images = getServerImages($upload_dir);

// 4. SAVE KONTEN WEBSITE (LOGIKA MEDIA MANAGER)
$current_data = json_decode(file_get_contents($json_file), true);
if (!$current_data) $current_data = [];

if (isset($_POST['save_content'])) {
    // A. Simpan dari Input Text (Link Manual/Browse) DULU
    foreach ($current_data as $key => $val) {
        if (isset($_POST[$key])) {
            $current_data[$key] = $_POST[$key];
        }
    }
    
    // B. Simpan dari Upload File (KEMUDIAN - Prioritas lebih tinggi)
    foreach ($_FILES as $key => $file) {
        // Cek apakah ada file yang diupload dengan sukses
        if ($file['name'] && $file['error'] === 0) {
            $target_file = $upload_dir . basename($file['name']);
            // Cek tipe file agar aman (optional, tapi disarankan)
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            if(in_array($fileType, ['jpg','jpeg','png','gif','webp','avif','mp4'])) {
                 if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    $current_data[$key] = $target_file; // Timpa path dengan file baru
                }
            }
        }
    }
    
    file_put_contents($json_file, json_encode($current_data, JSON_PRETTY_PRINT));
    $msg = "Konten Website Berhasil Diupdate!";
}

// 5. SAVE REVIEW (Sama seperti sebelumnya)
$reviews = json_decode(file_get_contents($review_file), true);
if (!$reviews) $reviews = [];
if (isset($_POST['save_reviews'])) {
    $new_reviews_list = []; if(isset($_POST['rev_id'])) { foreach($_POST['rev_id'] as $index => $id) { if (!isset($_POST['del_' . $id])) { $new_reviews_list[] = ["id" => $id, "name" => $_POST['rev_name'][$index], "rating" => $_POST['rev_rating'][$index], "comment" => $_POST['rev_comment'][$index], "source" => $_POST['rev_source'][$index], "date" => $_POST['rev_date'][$index], "visible" => isset($_POST['rev_vis'][$index]) ? true : false]; } } }
    if (!empty($_POST['new_name']) && !empty($_POST['new_comment'])) { $new_reviews_list[] = ["id" => uniqid("rev_"), "name" => $_POST['new_name'], "rating" => $_POST['new_rating'], "comment" => $_POST['new_comment'], "source" => $_POST['new_source'], "date" => date("Y-m-d"), "visible" => true]; }
    file_put_contents($review_file, json_encode($new_reviews_list, JSON_PRETTY_PRINT)); header("Location: dashboard.php?msg=Review Saved"); exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spencer Dashboard Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* STYLES SAMA SEPERTI SEBELUMNYA */
        :root { --primary: #1B4D3E; --gold: #C5A059; --bg: #f8f9fa; --white: #ffffff; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 250px; background: var(--primary); color: white; display: flex; flex-direction: column; flex-shrink: 0; }
        .brand { padding: 25px; font-size: 1.2rem; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu { flex: 1; padding: 20px 0; overflow-y: auto; }
        .menu a { display: block; padding: 15px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s; border-left: 4px solid transparent; }
        .menu a:hover, .menu a.active { background: rgba(0,0,0,0.2); color: var(--gold); border-left-color: var(--gold); }
        .logout { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .main { flex: 1; overflow-y: auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; align-items: center; }
        .card { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.85rem; color: #555; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; box-sizing: border-box; font-family: inherit; }
        .btn-save { background: var(--gold); color: white; border: none; padding: 12px 30px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .media-control { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
        .media-preview { width: 100%; height: 150px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; background: #fafafa; margin-bottom: 10px; cursor: pointer; }
        .btn-browse { background: #eee; border: 1px solid #ddd; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; white-space: nowrap; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 900px; border-radius: 8px; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; }
        .gallery-item { border: 2px solid transparent; cursor: pointer; border-radius: 4px; overflow: hidden; position: relative; }
        .gallery-item img { width: 100%; height: 100px; object-fit: cover; display: block; }
        .gallery-item:hover { border-color: var(--gold); }
        .gallery-name { font-size: 0.7rem; text-align: center; padding: 5px; background: #fafafa; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        @media(max-width: 768px) { body { flex-direction: column; } .sidebar { width: 100%; height: auto; } .menu { display: flex; overflow-x: auto; padding: 10px; } .menu a { padding: 10px 15px; white-space: nowrap; border-left: none; border-bottom: 3px solid transparent; } .menu a.active { border-bottom-color: var(--gold); } }
    </style>
</head>
<body>

    <?php 
    // DAFTAR MENU DASHBOARD
    $pages = [
        'home'=>['icon'=>'fa-home','title'=>'Home (Hero & Intro)','prefixes'=>['hero','home_intro']],
        'rooms'=>['icon'=>'fa-bed','title'=>'Home (Rooms)','prefixes'=>['room_deluxe','room_superior','room_executive','img_room']], // PERHATIKAN PREFIX INI
        'facilities'=>['icon'=>'fa-concierge-bell','title'=>'Home (Facilities)','prefixes'=>['home_facil','facil_rooftop','facil_dinner','img_wedding_venue','wedding_title','wedding_desc','img_meeting_hero','meeting_title','meeting_desc','meeting_subtitle','wedding_subtitle']],
        'dining'=>['icon'=>'fa-utensils','title'=>'Dining Page','prefixes'=>['dining_subtitle','dining_title','dining_rooftop','dining_botanica','dining_candle','img_dining']],
        'wedding'=>['icon'=>'fa-heart','title'=>'Wedding Page','prefixes'=>['wedding_intro','wedding_venue','wedding_spec','img_wedding_gal','wedding_form']],
        'meeting'=>['icon'=>'fa-briefcase','title'=>'Meeting Page','prefixes'=>['meeting_ballroom','meeting_func','meeting_pkg']],
        'gallery'=>['icon'=>'fa-images','title'=>'Gallery Page','prefixes'=>['gallery','img_gallery']],
        'social'=>['icon'=>'fa-share-alt','title'=>'Social & Header','prefixes'=>['social','header_btn']],
        'reviews'=>['icon'=>'fa-star','title'=>'Guest Reviews','prefixes'=>[]]
    ];
    $active_page = isset($_GET['page']) ? $_GET['page'] : 'home';
    $page_info = $pages[$active_page];
    ?>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-hotel"></i> SPENCER ADMIN</div>
        <div class="menu">
            <?php foreach($pages as $key => $p): ?>
                <a href="?page=<?php echo $key; ?>" class="<?php echo ($active_page == $key) ? 'active' : ''; ?>">
                    <i class="fas <?php echo $p['icon']; ?>"></i> <?php echo $p['title']; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="logout"><a href="?logout=true" style="color:#ff6b6b; text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </div>

    <div class="main">
        <div class="header">
            <h2 style="margin:0;"><?php echo $page_info['title']; ?></h2>
            <a href="index.html" target="_blank" style="color:var(--primary); text-decoration:none; font-weight:600;"><i class="fas fa-external-link-alt"></i> Lihat Website</a>
        </div>

        <?php if(isset($msg) || isset($_GET['msg'])) echo "<div style='background:#d1e7dd; color:#0f5132; padding:15px; border-radius:6px; margin-bottom:20px;'><i class='fas fa-check'></i> Perubahan berhasil disimpan!</div>"; ?>

        <?php if ($active_page == 'reviews'): ?>
            <form method="post">
                <div class="card"><p>Silakan gunakan kode Review Manager dari file sebelumnya.</p><button class="btn-save">SIMPAN</button></div>
            </form>

        <?php else: ?>
            <form method="post" enctype="multipart/form-data">
                <?php 
                $allowed_prefixes = $page_info['prefixes'];
                $has_content = false;
                $groups = [];
                foreach ($current_data as $key => $val) {
                    $show = false;
                    foreach($allowed_prefixes as $ap) {
                        if ($key === $ap) { $show = true; break; }
                        if (strpos($key, $ap . '_') === 0) { $show = true; break; }
                    }

                    if ($show) {
                        $parts = explode('_', $key);
                        $prefix = $parts[0]; 
                        // LOGIC GROUPING ROOMS DIPERBAIKI
                        if($prefix == 'room' && isset($parts[1])) $prefix = 'room_' . $parts[1]; 
                        
                        if($prefix == 'img' && isset($parts[1])) $prefix = $parts[1];
                        if($prefix == 'facil' && isset($parts[1])) $prefix = 'facilities_' . $parts[1];
                        if($key == 'wedding_title' || $key == 'wedding_desc' || $key == 'img_wedding_venue') $prefix = 'wedding_teaser';
                        if($key == 'meeting_title' || $key == 'meeting_desc' || $key == 'img_meeting_hero') $prefix = 'meeting_teaser';
                        
                        $groups[$prefix][] = ['key'=>$key, 'val'=>$val];
                        $has_content = true;
                    }
                }

                if(!$has_content): ?>
                    <div class="card"><p>Belum ada data untuk halaman ini di data.json.</p></div>
                <?php else: 
                    foreach ($groups as $grp => $items): ?>
                        <div class="card">
                            <h3 style="color:var(--primary); border-bottom:1px solid #eee; padding-bottom:10px; margin-top:0; text-transform:uppercase;">
                                <i class="fas fa-layer-group"></i> <?php echo str_replace('_', ' ', $grp); ?>
                            </h3>
                            <?php foreach($items as $item): 
                                $k = $item['key']; $v = $item['val']; 
                                $label = ucwords(str_replace(['_', 'img', 'facil', 'room'], [' ', '', '', ''], $k));
                            ?>
                                <label><?php echo $label; ?></label>
                                
                                <?php if($k == 'hero_type'): ?>
                                    <select name="<?php echo $k; ?>"><option value="video" <?php if($v=='video')echo'selected'; ?>>Video Background</option><option value="slider" <?php if($v=='slider')echo'selected'; ?>>Image Slider</option></select>

                                <?php elseif(strpos($k, 'social_') === 0 || strpos($k, 'header_') === 0 || strpos($k, '_link') !== false): ?>
                                    <input type="text" name="<?php echo $k; ?>" value="<?php echo $v; ?>">

                                <?php elseif(strpos($k, 'img_') === 0 || strpos($k, 'video') !== false || strpos($k, 'img_room_') === 0): ?>
                                    <div style="background:#f9f9f9; padding:15px; border:1px solid #eee; border-radius:6px;">
                                        <?php if(pathinfo($v, PATHINFO_EXTENSION) == 'mp4'): ?>
                                            <video src="<?php echo $v; ?>" style="height:150px; width:100%; object-fit:cover; border-radius:4px; margin-bottom:10px;"></video>
                                        <?php else: ?>
                                            <img src="<?php echo $v; ?>?t=<?php echo time(); ?>" id="preview_<?php echo $k; ?>" class="media-preview" onclick="openMediaModal('<?php echo $k; ?>')">
                                        <?php endif; ?>
                                        <div class="media-control">
                                            <input type="text" name="<?php echo $k; ?>" id="input_<?php echo $k; ?>" value="<?php echo $v; ?>" placeholder="Link file / Pilih dari server..." style="margin-bottom:0; flex:1;">
                                            <button type="button" class="btn-browse" onclick="openMediaModal('<?php echo $k; ?>')"><i class="fas fa-folder-open"></i> Pilih</button>
                                        </div>
                                        <div style="font-size:0.8rem; color:#666; margin-top:5px;">Atau Upload Baru: <input type="file" name="<?php echo $k; ?>" style="font-size:0.8rem;"></div>
                                    </div>

                                <?php elseif(strpos($k, 'desc') !== false || strlen($v) > 50): ?>
                                    <textarea name="<?php echo $k; ?>"><?php echo $v; ?></textarea>

                                <?php else: ?>
                                    <input type="text" name="<?php echo $k; ?>" value="<?php echo $v; ?>">
                                <?php endif; ?>
                                <br>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="save_content" class="btn-save" style="margin-bottom:50px;">SIMPAN PERUBAHAN</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <div id="mediaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0;">Media Library (Server Hosting)</h3>
                <span class="close" onclick="closeMediaModal()">&times;</span>
            </div>
            <div class="gallery-grid">
                <?php foreach($server_images as $img): ?>
                    <div class="gallery-item" onclick="selectImage('<?php echo $img; ?>')">
                        <img src="<?php echo $img; ?>" loading="lazy">
                        <div class="gallery-name"><?php echo basename($img); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        let currentInputId = '';
        function openMediaModal(key) { currentInputId = key; document.getElementById('mediaModal').style.display = 'block'; }
        function closeMediaModal() { document.getElementById('mediaModal').style.display = 'none'; }
        function selectImage(path) {
            document.getElementById('input_' + currentInputId).value = path;
            const previewImg = document.getElementById('preview_' + currentInputId);
            if(previewImg) previewImg.src = path;
            closeMediaModal();
        }
        window.onclick = function(event) { if (event.target == document.getElementById('mediaModal')) closeMediaModal(); }
    </script>
</body>
</html>
