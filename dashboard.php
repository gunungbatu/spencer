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

// 2. SERVER IMAGES
function getServerImages($dir) {
    $images = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
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
    // A. Handle Normal Fields
    foreach ($current_data as $key => $val) {
        if (isset($_POST[$key]) && !is_array($_POST[$key])) {
            $current_data[$key] = $_POST[$key];
        }
    }
    // B. Handle File Uploads (Normal)
    foreach ($_FILES as $key => $file) {
        if (!is_array($file['name']) && $file['name'] && $file['error'] === 0) {
            $target = $upload_dir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $target)) $current_data[$key] = $target;
        }
    }
    // C. Handle Special Gallery Items (Array)
    if(isset($_POST['gallery_items_src'])) {
        $new_items = [];
        $srcs = $_POST['gallery_items_src'];
        $tags = $_POST['gallery_items_tag'];
        
        for($i=0; $i<count($srcs); $i++) {
            // Cek jika ada file baru diupload untuk slot ini
            $uploaded_src = $srcs[$i]; // Default pake yg text
            if(isset($_FILES['gallery_file']['name'][$i]) && $_FILES['gallery_file']['error'][$i] === 0) {
                $fname = basename($_FILES['gallery_file']['name'][$i]);
                if(move_uploaded_file($_FILES['gallery_file']['tmp_name'][$i], $upload_dir . $fname)) {
                    $uploaded_src = $upload_dir . $fname;
                }
            }
            if($uploaded_src) {
                $new_items[] = ["src" => $uploaded_src, "tag" => $tags[$i]];
            }
        }
        $current_data['gallery_items'] = $new_items;
    }

    file_put_contents($json_file, json_encode($current_data, JSON_PRETTY_PRINT));
    $msg = "Data Tersimpan!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spencer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; margin:0; display:flex; height:100vh; background:#f4f4f4; }
        .sidebar { width:250px; background:#1B4D3E; color:#fff; display:flex; flex-direction:column; }
        .brand { padding:20px; font-weight:bold; border-bottom:1px solid #ffffff20; }
        .menu { flex:1; padding:10px; overflow-y:auto; }
        .menu a { display:block; padding:12px; color:#ffffff90; text-decoration:none; }
        .menu a.active, .menu a:hover { color:#fff; background:#ffffff10; }
        .main { flex:1; padding:30px; overflow-y:auto; }
        .card { background:#fff; padding:20px; margin-bottom:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
        input, select, textarea { width:100%; padding:10px; margin:5px 0 15px; border:1px solid #ddd; box-sizing:border-box; }
        .btn { padding:10px 20px; background:#C5A059; color:#fff; border:none; cursor:pointer; }
        .row-item { display:flex; gap:10px; align-items:center; background:#f9f9f9; padding:10px; margin-bottom:10px; border:1px solid #eee; }
        .media-prev { width:60px; height:60px; object-fit:cover; background:#eee; }
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
        'gallery'=>['Gallery Manager', []], // Spesial
        'social'=>['Social Media', ['social']]
    ];
    ?>

    <div class="sidebar">
        <div class="brand">SPENCER ADMIN</div>
        <div class="menu">
            <?php foreach($menus as $k=>$v): ?>
                <a href="?page=<?php echo $k; ?>" class="<?php echo $page==$k?'active':''; ?>"><?php echo $v[0]; ?></a>
            <?php endforeach; ?>
        </div>
        <div style="padding:20px;">
            <a href="?backup_data=true" style="color:#FFD700; text-decoration:none;">Backup Data</a><br><br>
            <a href="?logout=true" style="color:#ff9999; text-decoration:none;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h2><?php echo $menus[$page][0]; ?></h2>
        <?php if(isset($msg)) echo "<p style='color:green'>$msg</p>"; ?>

        <form method="post" enctype="multipart/form-data">
            
            <?php if($page == 'gallery'): ?>
                <div class="card">
                    <h3>Gallery Settings</h3>
                    <label>Judul Halaman</label><input type="text" name="gallery_title" value="<?php echo $current_data['gallery_title']??''; ?>">
                    <label>Sub Judul</label><input type="text" name="gallery_subtitle" value="<?php echo $current_data['gallery_subtitle']??''; ?>">
                    <label>Header Image</label>
                    <input type="text" name="img_gallery_hero" id="hero_img" value="<?php echo $current_data['img_gallery_hero']??''; ?>">
                    <button type="button" onclick="openMediaModal('hero_img')">Pilih</button>
                    
                    <hr style="margin:20px 0;">
                    
                    <h3>Daftar Kategori (Tags)</h3>
                    <p style="font-size:0.8rem; color:#666;">Pisahkan dengan koma. Contoh: Rooms, Dining, MICE, Wedding</p>
                    <input type="text" name="gallery_tags" value="<?php echo $current_data['gallery_tags']??'All, Rooms'; ?>">

                    <h3>Foto Galeri (Unlimited)</h3>
                    <div id="gallery-rows">
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
                                    // Generate Option dari Tags
                                    $tags = explode(',', $current_data['gallery_tags']);
                                    foreach($tags as $t) {
                                        $t = trim($t);
                                        $sel = ($t == $item['tag']) ? 'selected' : '';
                                        echo "<option value='$t' $sel>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="button" onclick="openMediaModal('input_<?php echo $i; ?>', 'prev_<?php echo $i; ?>')" style="background:#eee; color:#333;">Browse</button>
                            <button type="button" onclick="this.parentElement.remove()" style="background:#d9534f; color:#fff;">Hapus</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addGalleryRow()" class="btn" style="background:#1B4D3E;">+ Tambah Foto</button>
                </div>

            <?php else: ?>
                <?php 
                $prefixes = $menus[$page][1];
                $found = false;
                foreach($current_data as $key => $val) {
                    $match = false;
                    foreach($prefixes as $p) { if(strpos($key, $p) === 0) $match = true; }
                    if($match && !is_array($val)) { // Skip array (gallery items)
                        $found = true;
                        echo "<div class='card'><label>$key</label>";
                        if(strpos($key, 'img')===0 || strpos($key, 'hero')===0) {
                            echo "<div style='display:flex; gap:10px;'>
                                    <input type='text' name='$key' id='$key' value='$val'>
                                    <button type='button' onclick=\"openMediaModal('$key')\">Pilih</button>
                                  </div>
                                  <input type='file' name='$key'>";
                        } else {
                            echo "<input type='text' name='$key' value='$val'>";
                        }
                        echo "</div>";
                    }
                }
                if(!$found) echo "<p>Data belum diinisialisasi di data.json</p>";
                ?>
            <?php endif; ?>

            <div style="position:fixed; bottom:20px; right:20px;">
                <button type="submit" name="save_content" class="btn" style="padding:15px 30px; font-size:1.1rem; box-shadow:0 5px 15px rgba(0,0,0,0.2);">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>

    <div id="mediaModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999;">
        <div style="background:#fff; width:80%; height:80%; margin:5% auto; padding:20px; overflow:auto; border-radius:8px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3>Media Library</h3>
                <button onclick="document.getElementById('mediaModal').style.display='none'" style="background:red; color:#fff; border:none; padding:5px 10px;">X</button>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(100px, 1fr)); gap:10px;">
                <?php foreach($server_images as $img): ?>
                    <div onclick="selectImage('<?php echo $img; ?>')" style="cursor:pointer; border:1px solid #ddd; padding:5px;">
                        <?php if(preg_match('/\.(mp4|webm)$/i', $img)): ?>
                            <video src="<?php echo $img; ?>" style="width:100%; height:80px; object-fit:cover;"></video>
                        <?php else: ?>
                            <img src="<?php echo $img; ?>" style="width:100%; height:80px; object-fit:cover;">
                        <?php endif; ?>
                        <div style="font-size:0.7rem; overflow:hidden;"><?php echo basename($img); ?></div>
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
            if(targetPrev) document.getElementById(targetPrev).src = path;
            document.getElementById('mediaModal').style.display = 'none';
        }

        function addGalleryRow() {
            const id = Date.now(); // Unique ID
            const tags = document.querySelector('input[name="gallery_tags"]').value.split(',');
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
                <button type="button" onclick="openMediaModal('new_${id}')" style="background:#eee;">Browse</button>
                <button type="button" onclick="this.parentElement.remove()" style="background:#d9534f; color:#fff;">Hapus</button>
            </div>`;
            document.getElementById('gallery-rows').insertAdjacentHTML('beforeend', html);
        }
    </script>
</body>
</html>
