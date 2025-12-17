<?php
session_start();

// --- CONFIG ---
$json_file = 'data.json';
$config_file = 'config.json';
$upload_dir = 'assets/';

// --- BACKUP ---
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
    foreach ($current_data as $key => $val) {
        if (isset($_POST[$key]) && !is_array($_POST[$key])) $current_data[$key] = $_POST[$key];
    }
    foreach ($_FILES as $key => $file) {
        if (!is_array($file['name']) && $file['name'] && $file['error'] === 0) {
            $target = $upload_dir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $target)) $current_data[$key] = $target;
        }
    }
    // Gallery Items Logic
    if(isset($_POST['gallery_items_src'])) {
        $new_items = [];
        $srcs = $_POST['gallery_items_src'];
        $tags = $_POST['gallery_items_tag'];
        for($i=0; $i<count($srcs); $i++) {
            $uploaded_src = $srcs[$i];
            if(isset($_FILES['gallery_file']['name'][$i]) && $_FILES['gallery_file']['error'][$i] === 0) {
                $fname = basename($_FILES['gallery_file']['name'][$i]);
                if(move_uploaded_file($_FILES['gallery_file']['tmp_name'][$i], $upload_dir . $fname)) $uploaded_src = $upload_dir . $fname;
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
    <style>
        body { font-family: sans-serif; margin:0; display:flex; height:100vh; background:#f4f4f4; }
        .sidebar { width:250px; background:#1B4D3E; color:#fff; display:flex; flex-direction:column; }
        .brand { padding:20px; font-weight:bold; border-bottom:1px solid #ffffff20; }
        .menu { flex:1; padding:10px; overflow-y:auto; }
        .menu a { display:block; padding:12px; color:#ffffff90; text-decoration:none; }
        .menu a.active, .menu a:hover { color:#fff; background:#ffffff10; }
        .main { flex:1; padding:30px; overflow-y:auto; }
        .card { background:#fff; padding:20px; margin-bottom:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05); }
        input[type="text"], textarea, select { width:100%; padding:10px; margin:5px 0 10px; border:1px solid #ddd; box-sizing:border-box; }
        .btn { padding:10px 20px; background:#C5A059; color:#fff; border:none; cursor:pointer; font-weight:bold; border-radius:4px; }
        .media-box { background:#f9f9f9; padding:15px; border:1px solid #eee; border-radius:6px; margin-bottom:15px; }
        .media-prev { width:100%; height:120px; object-fit:cover; border-radius:4px; margin-bottom:10px; background:#ddd; }
        .row-item { display:flex; gap:10px; align-items:center; background:#f9f9f9; padding:10px; margin-bottom:10px; border:1px solid #eee; }
        
        /* Modal Library */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; }
        .modal-content { background:#fff; width:80%; height:80%; margin:5% auto; padding:20px; overflow:auto; border-radius:8px; }
        .gal-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:15px; }
        .gal-item { cursor:pointer; border:2px solid transparent; }
        .gal-item:hover { border-color:#C5A059; }
        .gal-item img, .gal-item video { width:100%; height:100px; object-fit:cover; display:block; }
    </style>
</head>
<body>
    <?php 
    $page = isset($_GET['page']) ? $_GET['page'] : 'home';
    $menus = [
        'home'=>['Home Page', ['hero','home_intro']],
        'rooms'=>['Rooms', ['room_deluxe','room_superior','room_executive']], // Ini akan auto detect room_deluxe_video
        'facilities'=>['Facilities', ['home_facil','facil_rooftop','facil_dinner']],
        'dining'=>['Dining', ['dining']],
        'meeting'=>['Meeting', ['meeting']],
        'wedding'=>['Wedding', ['wedding']],
        'gallery'=>['Gallery Manager', []],
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
                    <label>Judul</label><input type="text" name="gallery_title" value="<?php echo $current_data['gallery_title']??''; ?>">
                    <label>Sub Judul</label><input type="text" name="gallery_subtitle" value="<?php echo $current_data['gallery_subtitle']??''; ?>">
                    <label>Header Image</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="img_gallery_hero" id="hero_img" value="<?php echo $current_data['img_gallery_hero']??''; ?>">
                        <button type="button" class="btn" style="padding:5px 15px;" onclick="openMediaModal('hero_img')">Pilih</button>
                    </div>
                </div>
                <div class="card">
                    <h3>Photos</h3>
                    <label>Tags (Comma separated)</label><input type="text" name="gallery_tags" value="<?php echo $current_data['gallery_tags']??'All, Rooms'; ?>">
                    <div id="gallery-rows">
                        <?php 
                        $items = isset($current_data['gallery_items']) ? $current_data['gallery_items'] : [];
                        foreach($items as $i => $item): ?>
                        <div class="row-item">
                            <img src="<?php echo $item['src']; ?>" class="media-prev" id="prev_<?php echo $i; ?>" style="width:60px; height:60px; margin:0;">
                            <div style="flex:1;">
                                <input type="text" name="gallery_items_src[]" id="input_<?php echo $i; ?>" value="<?php echo $item['src']; ?>" placeholder="Path">
                                <input type="file" name="gallery_file[]">
                            </div>
                            <div style="width:150px;">
                                <select name="gallery_items_tag[]">
                                    <?php 
                                    $tags = explode(',', $current_data['gallery_tags']);
                                    foreach($tags as $t) {
                                        $t = trim($t); echo "<option value='$t' ".($t==$item['tag']?'selected':'').">$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="button" onclick="openMediaModal('input_<?php echo $i; ?>', 'prev_<?php echo $i; ?>')" style="padding:5px;">Browse</button>
                            <button type="button" onclick="this.parentElement.remove()" style="background:#d9534f; color:#fff; border:none; padding:5px;">X</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addGalleryRow()" class="btn">+ Tambah Foto</button>
                </div>

            <?php else: ?>
                <?php 
                $prefixes = $menus[$page][1];
                $groups = [];
                $has_content = false;
                foreach ($current_data as $key => $val) {
                    $match = false;
                    foreach($prefixes as $p) { if($key === $p || strpos($key, $p.'_') === 0) $match = true; }
                    if($match && !is_array($val)) {
                        $parts = explode('_', $key);
                        $grp = ucfirst($parts[0]);
                        if($parts[0] == 'room' && isset($parts[1])) $grp = 'Room '.$parts[1];
                        if($parts[0] == 'facil' && isset($parts[1])) $grp = 'Facility '.$parts[1];
                        $groups[$grp][] = ['k'=>$key, 'v'=>$val];
                        $has_content = true;
                    }
                }

                if(!$has_content): echo "<div class='card'><p>Data belum diinisialisasi.</p></div>"; 
                else:
                    foreach($groups as $grpName => $fields): ?>
                        <div class="card">
                            <h3 style="color:#1B4D3E; border-bottom:1px solid #eee; padding-bottom:10px; margin-top:0;"><?php echo $grpName; ?></h3>
                            <?php foreach($fields as $f): 
                                $k = $f['k']; $v = $f['v'];
                                $label = ucwords(str_replace(['_', 'img', 'room', 'facil'], [' ', '', '', ''], $k));
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
                                            <button type="button" class="btn" style="padding:5px 15px; background:#eee; color:#333; border:1px solid #ccc;" onclick="openMediaModal('<?php echo $k; ?>', 'prev_<?php echo $k; ?>')">Browse</button>
                                        </div>
                                        <div style="margin-top:5px; font-size:0.8rem;">Upload Baru: <input type="file" name="<?php echo $k; ?>"></div>
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

            <div style="height:60px;"></div>
            <div style="position:fixed; bottom:0; right:0; width:100%; background:#fff; padding:15px; box-shadow:0 -2px 10px rgba(0,0,0,0.1); text-align:right;">
                <button type="submit" name="save_content" class="btn">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>

    <div id="mediaModal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="margin:0;">Media Library</h3>
                <span onclick="document.getElementById('mediaModal').style.display='none'" style="font-size:2rem; cursor:pointer;">&times;</span>
            </div>
            <div class="gal-grid">
                <?php foreach($server_images as $img): ?>
                    <div class="gal-item" onclick="selectImage('<?php echo $img; ?>')">
                        <?php if(preg_match('/\.(mp4|webm|mov)$/i', $img)): ?>
                            <video src="<?php echo $img; ?>"></video>
                        <?php else: ?>
                            <img src="<?php echo $img; ?>" loading="lazy">
                        <?php endif; ?>
                        <div style="font-size:0.7rem; padding:5px; overflow:hidden;"><?php echo basename($img); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        let targetInput = ''; let targetPrev = '';
        function openMediaModal(id, prevId=null) { targetInput=id; targetPrev=prevId; document.getElementById('mediaModal').style.display='block'; }
        function selectImage(path) {
            document.getElementById(targetInput).value = path;
            if(targetPrev && document.getElementById(targetPrev)) document.getElementById(targetPrev).src = path;
            document.getElementById('mediaModal').style.display = 'none';
        }
        function addGalleryRow() {
            const id = Date.now();
            const tags = document.querySelector('input[name="gallery_tags"]').value.split(',');
            let opts = ''; tags.forEach(t => opts += `<option value="${t.trim()}">${t.trim()}</option>`);
            const html = `<div class="row-item"><div class="media-prev" style="width:60px; height:60px; margin:0; background:#ddd;"></div><div style="flex:1;"><input type="text" name="gallery_items_src[]" id="new_${id}" placeholder="Path"><input type="file" name="gallery_file[]"></div><div style="width:150px;"><select name="gallery_items_tag[]">${opts}</select></div><button type="button" onclick="openMediaModal('new_${id}')" style="padding:5px;">Browse</button><button type="button" onclick="this.parentElement.remove()" style="background:#d9534f; color:#fff; border:none; padding:5px;">X</button></div>`;
            document.getElementById('gallery-rows').insertAdjacentHTML('beforeend', html);
        }
    </script>
</body>
</html>
