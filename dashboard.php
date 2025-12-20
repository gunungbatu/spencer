<?php
session_start();
$json_file = 'data.json';
$config_file = 'config.json';
$upload_dir = 'assets/';

// --- FITUR BACKUP ---
if (isset($_GET['backup_data'])) {
    if (!class_exists('ZipArchive')) die("ZIP Extension not found.");
    $zip_file = 'backup_spencer_' . date('Y-m-d_H-i') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        if(file_exists('data.json')) $zip->addFile('data.json');
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
        readfile($zip_file); unlink($zip_file); exit;
    }
}

// --- AUTHENTICATION ---
if (isset($_GET['logout'])) { session_destroy(); header("Location: dashboard.php"); exit; }
if (isset($_POST['login'])) {
    $config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
    $pin = $config['admin_pin'] ?? 'Spencer123';
    if ($_POST['password'] === $pin) $_SESSION['loggedin'] = true;
}
if (!isset($_SESSION['loggedin'])) {
    echo '<body style="display:flex;height:100vh;justify-content:center;align-items:center;background:#f4f4f4;font-family:sans-serif;">
          <form method="post" style="background:#fff;padding:40px;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.1);">
          <h2>SPENCER ADMIN</h2><input type="password" name="password" placeholder="PIN" style="width:100%;padding:10px;margin:10px 0;"><button type="submit" name="login" style="width:100%;padding:10px;background:#1B4D3E;color:#fff;border:none;cursor:pointer;">LOGIN</button></form></body>';
    exit;
}

// --- SERVER MEDIA SCAN ---
function getServerImages($dir) {
    $images = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if (preg_match('/\.(jpg|jpeg|png|gif|webp|avif|mp4|webm)$/i', $file)) $images[] = $dir . $file;
        }
    }
    return $images;
}
$server_images = getServerImages($upload_dir);

// --- SAVE LOGIC ---
$current_data = json_decode(file_get_contents($json_file), true);
if (isset($_POST['save_content'])) {
    // Simpan Text
    foreach ($_POST as $key => $val) { if (!is_array($val)) $current_data[$key] = $val; }
    
    // Simpan File (Upload Baru)
    foreach ($_FILES as $key => $file) {
        if (!is_array($file['name']) && $file['error'] === 0) {
            $target = $upload_dir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $target)) $current_data[$key] = $target;
        }
    }
    
    // Simpan Gallery Array
    if (isset($_POST['gallery_items_src'])) {
        $items = [];
        for ($i=0; $i<count($_POST['gallery_items_src']); $i++) {
            $src = $_POST['gallery_items_src'][$i];
            if (isset($_FILES['gallery_file']['name'][$i]) && $_FILES['gallery_file']['error'][$i] === 0) {
                $target = $upload_dir . basename($_FILES['gallery_file']['name'][$i]);
                if (move_uploaded_file($_FILES['gallery_file']['tmp_name'][$i], $target)) $src = $target;
            }
            if ($src) $items[] = ["src" => $src, "tag" => $_POST['gallery_items_tag'][$i]];
        }
        $current_data['gallery_items'] = $items;
    }
    file_put_contents($json_file, json_encode($current_data, JSON_PRETTY_PRINT));
    $msg = "Perubahan Berhasil Disimpan!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Spencer Admin Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; margin:0; display:flex; background:#f0f2f5; height:100vh; overflow:hidden; }
        .sidebar { width:260px; background:#1B4D3E; color:#fff; display:flex; flex-direction:column; flex-shrink:0; }
        .menu { flex:1; padding:20px; overflow-y:auto; }
        .menu a { display:block; padding:12px; color:#ffffff90; text-decoration:none; border-radius:4px; margin-bottom:5px; }
        .menu a:hover, .menu a.active { background:rgba(255,255,255,0.1); color:#fff; }
        .main { flex:1; padding:40px; overflow-y:auto; position:relative; }
        .card { background:#fff; padding:25px; border-radius:12px; box-shadow:0 2px 15px rgba(0,0,0,0.05); margin-bottom:30px; }
        label { display:block; font-weight:bold; margin-bottom:8px; font-size:0.75rem; color:#1B4D3E; text-transform:uppercase; border-left:3px solid #C5A059; padding-left:8px; }
        input[type="text"], textarea, select { width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; margin-bottom:20px; box-sizing:border-box; }
        .media-box { background:#f8f9fa; padding:15px; border-radius:8px; border:1px solid #eee; margin-bottom:25px; }
        .media-prev { width:100%; height:150px; object-fit:cover; border-radius:6px; margin-bottom:12px; background:#e9ecef; }
        .btn-save { position:fixed; bottom:30px; right:30px; padding:15px 40px; background:#C5A059; color:#fff; border:none; border-radius:30px; font-weight:bold; cursor:pointer; box-shadow:0 5px 20px rgba(197,160,89,0.4); z-index:100; }
        .row-item { display:flex; gap:10px; background:#fff; padding:10px; border:1px solid #eee; margin-bottom:10px; border-radius:6px; align-items:center; }
        .btn-pilih { background:#eee; border:1px solid #ccc; padding:10px; cursor:pointer; white-space:nowrap; border-radius:4px; }
        .btn-pilih:hover { background: #ddd; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div style="padding:25px; font-weight:bold; font-size:1.2rem; border-bottom:1px solid rgba(255,255,255,0.1);">SPENCER ADMIN</div>
        <div class="menu">
            <?php 
            $page = $_GET['page'] ?? 'home';
            $nav = ['home'=>'Home Page','rooms'=>'Rooms & Tour','facilities'=>'Facilities','dining'=>'Dining','meeting'=>'Meeting','wedding'=>'Wedding','gallery'=>'Gallery Manager','social'=>'Social Media'];
            foreach($nav as $k=>$v) echo "<a href='?page=$k' class='".($page==$k?'active':'')."'>$v</a>";
            ?>
        </div>
        <div style="padding:20px; border-top:1px solid rgba(255,255,255,0.1);">
            <a href="?backup_data=1" style="color:#FFD700; text-decoration:none;"><i class="fas fa-download"></i> Backup Data</a><br><br>
            <a href="?logout=1" style="color:#ff6b6b; text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main">
        <?php if(isset($msg)) echo "<div style='padding:15px;background:#d4edda;color:#155724;border-radius:8px;margin-bottom:20px;'>$msg</div>"; ?>
        
        <form method="post" enctype="multipart/form-data">
            <?php if($page == 'gallery'): ?>
                <?php
$gallery_items = $current_data['gallery_items'] ?? [];
$gallery_tags  = $current_data['gallery_tags'] ?? 'Rooms, Dining, Facilities, Wedding, MICE';
?>

<div class="card">
    <h3>GALLERY SETTINGS</h3>

    <label>Gallery Title</label>
    <input type="text" name="gallery_title" value="<?= $current_data['gallery_title'] ?? '' ?>">

    <label>Gallery Subtitle</label>
    <input type="text" name="gallery_subtitle" value="<?= $current_data['gallery_subtitle'] ?? '' ?>">

    <label>Hero Image</label>
    <div class="media-box">
        <img src="<?= $current_data['img_gallery_hero'] ?? '' ?>" id="prev_img_gallery_hero" class="media-prev">
        <div style="display:flex;gap:10px;">
            <input type="text" name="img_gallery_hero" id="img_gallery_hero"
                   value="<?= $current_data['img_gallery_hero'] ?? '' ?>">
            <button type="button" class="btn-pilih"
                onclick="openMediaModal('img_gallery_hero','prev_img_gallery_hero')">
                Pilih Server
            </button>
        </div>
        <div style="margin-top:10px;">Upload Baru: <input type="file" name="img_gallery_hero"></div>
    </div>

    <label>Gallery Tags (pisahkan dengan koma)</label>
    <input type="text" name="gallery_tags" value="<?= $gallery_tags ?>">
</div>

<div class="card">
    <h3>GALLERY ITEMS</h3>

    <div id="gallery-items">
        <?php foreach($gallery_items as $i => $item): ?>
        <div class="row-item">
            <img src="<?= $item['src'] ?>" style="width:80px;height:60px;object-fit:cover;border-radius:4px;">

            <input type="text"
                   name="gallery_items_src[]"
                   value="<?= $item['src'] ?>"
                   style="flex:1">

            <select name="gallery_items_tag[]">
                <?php foreach(explode(',', $gallery_tags) as $tag): 
                    $tag = trim($tag); ?>
                    <option value="<?= $tag ?>" <?= ($item['tag']==$tag?'selected':'') ?>>
                        <?= $tag ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="button" onclick="openMediaModalForGallery(this)">Pilih</button>
            <button type="button" onclick="this.parentElement.remove()" style="color:#c00">✕</button>
        </div>
        <?php endforeach; ?>
    </div>

    <button type="button" onclick="addGalleryItem()" class="btn-pilih" style="margin-top:15px;">
        + Tambah Foto
    </button>
</div>
            <?php else: ?>
                <?php 
                // Prefix mapping sesuai file JSON Bapak
                $prefixes = [
                    'home'=>['hero','home_intro'],
                    'rooms'=>['room_'], 
                    'facilities'=>['home_facil','facil_','img_wedding_venue','wedding_title','wedding_desc','img_meeting_hero','meeting_title','meeting_desc'],
                    'dining'=>['dining_','img_dining'],
                    'meeting'=>['meeting_','img_meeting'],
                    'wedding'=>['wedding_','img_wedding'],
                    'social'=>['social_','header_']
                ];
                
                $groups = [];
                foreach($current_data as $k=>$v) {
                    if(is_array($v)) continue;
                    foreach($prefixes[$page] ?? [] as $p) {
                        if(strpos($k, $p) === 0) {
                            $g = explode('_', $k)[0];
                            if($g == 'room' && isset(explode('_',$k)[1])) $g = 'Room '.explode('_',$k)[1];
                            $groups[$g][] = ['k'=>$k, 'v'=>$v]; break;
                        }
                    }
                }

                foreach($groups as $gn => $fs): ?>
                <div class="card">
                    <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; color:#1B4D3E;"><?= strtoupper($gn) ?></h3>
                    <?php foreach($fs as $f): 
                        $is_media = (strpos($f['k'],'img')!==false || strpos($f['k'],'video')!==false || strpos($f['k'],'hero')!==false || strpos($f['k'],'slide')!==false);
                    ?>
                        <label><?= str_replace('_',' ',$f['k']) ?></label>
                        
                        <?php if($is_media): ?>
                            <div class="media-box">
                                <?php if(strpos($f['v'],'.mp4')!==false || strpos($f['v'],'.webm')!==false): ?>
                                    <video src="<?= $f['v'] ?>" class="media-prev" controls></video>
                                <?php else: ?>
                                    <img src="<?= $f['v'] ?>" id="prev_<?= $f['k'] ?>" class="media-prev">
                                <?php endif; ?>
                                
                                <div style="display:flex;gap:10px;">
                                    <input type="text" name="<?= $f['k'] ?>" id="<?= $f['k'] ?>" value="<?= $f['v'] ?>" style="margin:0;">
                                    <button type="button" class="btn-pilih" onclick="openMediaModal('<?= $f['k'] ?>','prev_<?= $f['k'] ?>')">Pilih Server</button>
                                </div>
                                <div style="margin-top:10px;font-size:0.8rem;color:#666;">Atau Upload Baru: <input type="file" name="<?= $f['k'] ?>"></div>
                            </div>
                        <?php elseif(strlen($f['v']) > 60): ?>
                            <textarea name="<?= $f['k'] ?>" rows="4"><?= $f['v'] ?></textarea>
                        <?php else: ?>
                            <input type="text" name="<?= $f['k'] ?>" value="<?= $f['v'] ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <button type="submit" name="save_content" class="btn-save">SIMPAN PERUBAHAN</button>
        </form>
    </div>

    <div id="mediaModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:9999;padding:50px;">
        <div style="background:#fff;width:100%;height:100%;border-radius:12px;padding:30px;overflow-y:auto;position:relative;">
            <span onclick="document.getElementById('mediaModal').style.display='none'" style="position:absolute;top:20px;right:30px;font-size:2rem;cursor:pointer;">&times;</span>
            <h3>Media Library (Hosting cPanel)</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:15px;">
                <?php foreach($server_images as $img): ?>
                <div onclick="selectImg('<?= $img ?>')" style="cursor:pointer;border:1px solid #eee;padding:5px;text-align:center;">
                    <?php if(strpos($img,'.mp4')!==false || strpos($img,'.webm')!==false): ?>
                        <div style="height:80px;display:flex;align-items:center;justify-content:center;background:#000;color:#fff;font-size:0.7rem;">VIDEO</div>
                    <?php else: ?>
                        <img src="<?= $img ?>" style="width:100%;height:80px;object-fit:cover;">
                    <?php endif; ?>
                    <div style="font-size:0.6rem;word-break:break-all;margin-top:5px;"><?= basename($img) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        let tInput = '', tPrev = '';
        function openMediaModal(id, prev='') { tInput=id; tPrev=prev; document.getElementById('mediaModal').style.display='block'; }
        function selectImg(path) {
            document.getElementById(tInput).value = path;
            if(tPrev && document.getElementById(tPrev)) {
                if(!path.includes('.mp4')) document.getElementById(tPrev).src = path;
            }
            document.getElementById('mediaModal').style.display='none';
        }
    </script>
</body>
</html>
