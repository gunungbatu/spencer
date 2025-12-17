<?php
session_start();

// --- CONFIG ---
$json_file = 'data.json';
$config_file = 'config.json';
$upload_dir = 'assets/';

// --- BACKUP DATA ---
if (isset($_GET['backup_data'])) {
    if (!class_exists('ZipArchive')) die("Error: ZipArchive not supported.");
    $zip_file = 'backup_spencer_' . date('Y-m-d_H-i') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        if(file_exists('data.json')) $zip->addFile('data.json');
        if(file_exists('reviews.json')) $zip->addFile('reviews.json');
        if(file_exists('config.json')) $zip->addFile('config.json');
        if(is_dir('assets')) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('assets'), RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $path = $file->getRealPath();
                    $rel = 'assets/' . substr($path, strlen(realpath('assets')) + 1);
                    $zip->addFile($path, $rel);
                }
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.basename($zip_file).'"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);
        unlink($zip_file);
        exit;
    }
}

// 1. AUTH
$config = [];
if (file_exists($config_file)) $config = json_decode(file_get_contents($config_file), true);
$password_admin = isset($config['admin_pin']) ? $config['admin_pin'] : 'Spencer123'; 

if (isset($_GET['logout'])) { session_destroy(); header("Location: dashboard.php"); exit; }
if (isset($_POST['login'])) {
    if ($_POST['password'] === $password_admin) $_SESSION['loggedin'] = true; 
    else $error = "PIN Salah!";
}
if (!isset($_SESSION['loggedin'])) {
    echo '<body style="background:#f4f4f4; display:flex; height:100vh; justify-content:center; align-items:center; font-family:sans-serif;">
          <form method="post" style="background:white; padding:40px; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.1); border-top:5px solid #1B4D3E;">
          <h2 style="color:#1B4D3E; margin:0 0 20px;">SPENCER ADMIN</h2>
          <input type="password" name="password" placeholder="Masukkan PIN" style="padding:10px; width:100%; border:1px solid #ddd;" required>
          <button type="submit" name="login" style="margin-top:10px; padding:10px 20px; background:#1B4D3E; color:white; border:none; width:100%; cursor:pointer;">LOGIN</button>
          '.(isset($error)?"<p style='color:red'>$error</p>":"").'</form></body>';
    exit;
}

// 2. SERVER IMAGES (Scan Foto & Video untuk Modal)
function getServerImages($dir) {
    $images = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            // Support Image & Video Extensions
            if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp|avif|mp4|webm|mov)$/i', $file)) {
                $images[] = $dir . $file;
            }
        }
    }
    return $images;
}
$server_images = getServerImages($upload_dir);

// 3. SAVE DATA
$current_data = json_decode(file_get_contents($json_file), true);
if (!$current_data) $current_data = [];

if (isset($_POST['save_content'])) {
    // A. Normal Fields
    foreach ($current_data as $key => $val) {
        if (isset($_POST[$key]) && !is_array($_POST[$key])) {
            $current_data[$key] = $_POST[$key];
        }
    }
    // B. File Uploads (Support Single File Logic)
    foreach ($_FILES as $key => $file) {
        if (!is_array($file['name']) && $file['name'] && $file['error'] === 0) {
            $target = $upload_dir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $target)) $current_data[$key] = $target;
        }
    }
    // C. Gallery Items (Unlimited Array Logic)
    if(isset($_POST['gallery_items_src'])) {
        $new_items = [];
        $srcs = $_POST['gallery_items_src'];
        $tags = $_POST['gallery_items_tag'];
        
        for($i=0; $i<count($srcs); $i++) {
            $uploaded_src = $srcs[$i];
            if(isset($_FILES['gallery_file']['name'][$i]) && $_FILES['gallery_file']['error'][$i] === 0) {
                $fname = basename($_FILES['gallery_file']['name'][$i]);
                if(move_uploaded_file($_FILES['gallery_file']['tmp_name'][$i], $upload_dir . $fname)) {
                    $uploaded_src = $upload_dir . $fname;
                }
            }
            if($uploaded_src) $new_items[] = ["src" => $uploaded_src, "tag" => $tags[$i]];
        }
        $current_data['gallery_items'] = $new_items;
    }

    file_put_contents($json_file, json_encode($current_data, JSON_PRETTY_PRINT));
    $msg = "Perubahan Berhasil Disimpan!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spencer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; margin:0; display:flex; height:100vh; background:#f4f4f4; overflow:hidden; }
        .sidebar { width:250px; background:#1B4D3E; color:#fff; display:flex; flex-direction:column; flex-shrink:0; }
        .brand { padding:25px; font-weight:bold; font-size:1.2rem; border-bottom:1px solid rgba(255,255,255,0.1); }
        .menu { flex:1; padding:10px 0; overflow-y:auto; }
        .menu a { display:block; padding:15px 25px; color:rgba(255,255,255,0.7); text-decoration:none; transition:0.3s; border-left:4px solid transparent; }
        .menu a:hover, .menu a.active { background:rgba(0,0,0,0.2); color:#C5A059; border-left-color:#C5A059; }
        .main { flex:1; padding:30px; overflow-y:auto; position:relative; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .card { background:#fff; padding:25px; margin-bottom:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        label { display:block; font-weight:600; margin-bottom:8px; font-size:0.85rem; color:#555; text-transform:uppercase; }
        input[type="text"], textarea, select { width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; margin-bottom:15px; box-sizing:border-box; }
        .btn { padding:12px 30px; background:#C5A059; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold; }
        
        /* Media Preview & Control */
        .media-box { background:#f9f9f9; padding:15px; border:1px solid #eee; border-radius:6px; margin-bottom:15px; }
        .media-prev { width:100%; height:150px; object-fit:cover; border-radius:4px; margin-bottom:10px; background:#ddd; }
        .row-item { display:flex; gap:10px; align-items:center; background:#fff; padding:10px; margin-bottom:10px; border:1px solid #eee; }
        .row-item .media-prev { width:60px; height:60px; margin:0; }
        
        /* Modal Library */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; }
        .modal-content { background:#fff; width:80%; height:80%; margin:5% auto; padding:20px; overflow:auto; border-radius:8px; }
        .gal-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:15px; }
        .gal-item { cursor:pointer; border:2px solid transparent; position: relative; }
        .gal-item:hover { border-color:#C5A059; }
        .gal-item img, .gal-item video { width:100%; height:100px; object-fit:cover; display:block; }
        .vid-badge { position:absolute; top:5px; right:5px; background:rgba(0,0,0,0.6); color:#fff; padding:2px 5px; font-size:0.6rem; border-radius:3px; }
    </style>
</head>
<body>
    <?php 
    $page = isset($_GET['page']) ? $_GET['page'] : 'home';
    $menus = [
        'home'=>['Home Page', ['hero','home_intro']],
        'rooms'=>['Rooms', ['room_deluxe','room_superior','room_executive']],
        'facilities'=>['Facilities', ['home_facil','facil_rooftop','facil_dinner']],
        'dining'=>['Dining', ['dining']],
        'meeting'=>['Meeting', ['meeting']],
        'wedding'=>['Wedding', ['wedding']],
        'gallery'=>['Gallery Manager', []],
        'social'=>['Social Media', ['social']]
    ];
    ?>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-hotel"></i> SPENCER ADMIN</div>
        <div class="menu">
            <?php foreach($menus as $k=>$v): ?>
                <a href="?page=<?php echo $k; ?>" class="<?php echo $page==$k?'active':''; ?>">
                    <i class="fas fa-chevron-right" style="font-size:0.7rem; margin-right:10px;"></i> <?php echo $v[0]; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div style="padding:20px; border-top:1px solid rgba(255,255,255,0.1);">
            <a href="?backup_data=true" style="color:#FFD700; text-decoration:none; display:block; margin-bottom:10px;"><i class="fas fa-download"></i> Backup Data</a>
            <a href="?logout=true" style="color:#ff6b6b; text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="header">
            <h2 style="margin:0; text-transform:uppercase; color:#1B4D3E;"><?php echo $menus[$page][0]; ?></h2>
            <a href="index.html" target="_blank" style="color:#1B4D3E; text-decoration:none; font-weight:bold;">Lihat Website <i class="fas fa-external-link-alt"></i></a>
        </div>

        <?php if(isset($msg)) echo "<div style='background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:4px;'>$msg</div>"; ?>

        <form method="post" enctype="multipart/form-data">
            
            <?php if($page == 'gallery'): ?>
                <div class="card">
                    <h3>Gallery Header</h3>
                    <label>Judul Halaman</label><input type="text" name="gallery_title" value="<?php echo $current_data['gallery_title']??''; ?>">
                    <label>Sub Judul</label><input type="text" name="gallery_subtitle" value="<?php echo $current_data['gallery_subtitle']??''; ?>">
                    <label>Background Header</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="img_gallery_hero" id="hero_img" value="<?php echo $current_data['img_gallery_hero']??''; ?>">
                        <button type="button" class="btn" style="padding:5px 15px;" onclick="openMediaModal('hero_img')">Pilih</button>
                    </div>
                </div>

                <div class="card">
                    <h3>Photo Management</h3>
                    <label>Kategori (Pisahkan koma)</label>
                    <input type="text" name="gallery_tags" value="<?php echo $current_data['gallery_tags']??'All, Rooms, Dining, MICE'; ?>">
                    
                    <div id="gallery-rows" style="margin-top:20px;">
                        <?php 
                        $items = isset($current_data['gallery_items']) ? $current_data['gallery_items'] : [];
                        foreach($items as $i => $item): 
                        ?>
                        <div class="row-item">
                            <img src="<?php echo $item['src']; ?>" class="media-prev" id="prev_<?php echo $i; ?>">
                            <div style="flex:1;">
                                <input type="text" name="gallery_items_src[]" id="input_<?php echo $i; ?>" value="<?php echo $item['src']; ?>" placeholder="Path Gambar">
                                <input type="file" name="gallery_file[]">
                            </div>
                            <div style="width:150px;">
                                <select name="gallery_items_tag[]">
                                    <?php 
                                    $tags = explode(',', $current_data['gallery_tags']);
                                    foreach($tags as $t) {
                                        $t = trim($t);
                                        $sel = ($t == $item['tag']) ? 'selected' : '';
                                        echo "<option value='$t' $sel>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="button" onclick="openMediaModal('input_<?php echo $i; ?>', 'prev_<?php echo $i; ?>')" style="background:#eee; border:1px solid #ccc; padding:5px 10px; cursor:pointer;">Browse</button>
                            <button type="button" onclick="this.parentElement.remove()" style="background:#d9534f; color:white; border:none; padding:5px 10px; cursor:pointer;">Hapus</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addGalleryRow()" class="btn" style="background:#1B4D3E; margin-top:10px;">+ Tambah Foto</button>
                </div>

            <?php else: ?>
                <?php 
                $prefixes = $menus[$page][1];
                $groups = [];
                $has_content = false;

                // Grouping Logic
                foreach ($current_data as $key => $val) {
                    $match = false;
                    foreach($prefixes as $p) { if($key === $p || strpos($key, $p.'_') === 0) $match = true; }
                    if($match && !is_array($val)) {
                        $parts = explode('_', $key);
                        $grp = $parts[0];
                        if($grp == 'room' && isset($parts[1])) $grp = 'Room '.$parts[1];
                        if($grp == 'facil' && isset($parts[1])) $grp = 'Facility '.$parts[1];
                        if($grp == 'img') $grp = 'Images & Media';
                        if($grp == 'hero') $grp = 'Hero Section';
                        
                        $groups[$grp][] = ['k'=>$key, 'v'=>$val];
                        $has_content = true;
                    }
                }

                if(!$has_content): echo "<div class='card'><p>Belum ada data untuk halaman ini.</p></div>"; 
                else:
                    foreach($groups as $grpName => $fields): ?>
                        <div class="card">
                            <h3 style="color:#1B4D3E; border-bottom:1px solid #eee; padding-bottom:10px; margin-top:0; text-transform:uppercase;">
                                <?php echo $grpName; ?>
                            </h3>
                            <?php foreach($fields as $f): 
                                $k = $f['k']; $v = $f['v'];
                                $label = ucwords(str_replace(['_', 'img', 'room', 'facil'], [' ', '', '', ''], $k));
                                // Deteksi Media (Gambar/Video) untuk menampilkan Browse & Upload
                                $is_media = (strpos($k, 'img')===0 || strpos($k, 'video')!==false || strpos($k, 'slide')!==false);
                            ?>
                                <label><?php echo $label; ?></label>
                                
                                <?php if($is_media): ?>
                                    <div class="media-box">
                                        <?php if(preg_match('/\.(mp4|webm)$/i', $v)): ?>
                                            <video src="<?php echo $v; ?>" class="media-prev" controls></video>
                                        <?php else: ?>
                                            <img src="<?php echo $v; ?>" id="prev_<?php echo $k; ?>" class="media-prev">
                                        <?php endif; ?>
                                        
                                        <div style="display:flex; gap:10px;">
                                            <input type="text" name="<?php echo $k; ?>" id="<?php echo $k; ?>" value="<?php echo $v; ?>" style="margin:0;">
                                            <button type="button" class="btn" style="padding:5px 15px; background:#eee; color:#333; border:1px solid #ccc;" onclick="openMediaModal('<?php echo $k; ?>', 'prev_<?php echo $k; ?>')">Browse Server</button>
                                        </div>
                                        
                                        <div style="margin-top:5px; font-size:0.8rem; color:#666;">
                                            Atau Upload File Baru: <input type="file" name="<?php echo $k; ?>">
                                        </div>
                                    </div>

                                <?php elseif(strpos($k, 'desc')!==false): ?>
                                    <textarea name="<?php echo $k; ?>" rows="3"><?php echo $v; ?></textarea>
                                <?php else: ?>
                                    <input type="text" name="<?php echo $k; ?>" value="<?php echo $v; ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; 
                endif; ?>
            <?php endif; ?>

            <div style="height:80px;"></div> <div style="position:fixed; bottom:0; right:0; width:100%; background:#fff; padding:15px; box-shadow:0 -2px 10px rgba(0,0,0,0.1); text-align:right;">
                <button type="submit" name="save_content" class="btn">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>

    <div id="mediaModal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="margin:0;">Media Library (Server)</h3>
                <span onclick="document.getElementById('mediaModal').style.display='none'" style="font-size:2rem; cursor:pointer;">&times;</span>
            </div>
            <div class="gal-grid">
                <?php foreach($server_images as $img): ?>
                    <div class="gal-item" onclick="selectImage('<?php echo $img; ?>')">
                        <?php if(preg_match('/\.(mp4|webm|mov)$/i', $img)): ?>
                            <video src="<?php echo $img; ?>"></video>
                            <span class="vid-badge">VIDEO</span>
                        <?php else: ?>
                            <img src="<?php echo $img; ?>" loading="lazy">
                        <?php endif; ?>
                        <div style="font-size:0.7rem; padding:5px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;"><?php echo basename($img); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        let targetInput = '';
        let targetPrev = '';

        function openMediaModal(inputId, prevId = null) {
            targetInput = inputId;
            targetPrev = prevId;
            document.getElementById('mediaModal').style.display = 'block';
        }

        function selectImage(path) {
            document.getElementById(targetInput).value = path;
            if(targetPrev && document.getElementById(targetPrev)) {
                // Cek apakah video atau gambar untuk update preview
                if(path.match(/\.(mp4|webm)$/i)) {
                    // Jika video, replace img tag dengan video tag (advanced) atau biarkan text update saja
                    // Untuk simplifikasi, kita update text input saja
                } else {
                    document.getElementById(targetPrev).src = path;
                }
            }
            document.getElementById('mediaModal').style.display = 'none';
        }

        function addGalleryRow() {
            const id = Date.now();
            const tagsVal = document.querySelector('input[name="gallery_tags"]').value;
            const tags = tagsVal ? tagsVal.split(',') : ['All'];
            let opts = '';
            tags.forEach(t => opts += `<option value="${t.trim()}">${t.trim()}</option>`);

            const html = `
            <div class="row-item">
                <div class="media-prev" style="background:#ddd;"></div>
                <div style="flex:1;">
                    <input type="text" name="gallery_items_src[]" id="new_${id}" placeholder="Path Gambar">
                    <input type="file" name="gallery_file[]">
                </div>
                <div style="width:150px;">
                    <select name="gallery_items_tag[]">${opts}</select>
                </div>
                <button type="button" onclick="openMediaModal('new_${id}')" style="background:#eee; padding:5px;">Browse</button>
                <button type="button" onclick="this.parentElement.remove()" style="background:#d9534f; color:white; padding:5px;">Hapus</button>
            </div>`;
            document.getElementById('gallery-rows').insertAdjacentHTML('beforeend', html);
        }
    </script>
</body>
</html>
