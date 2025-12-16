<?php
session_start();
$json_file = 'data.json';
$review_file = 'reviews.json';
$upload_dir = 'assets/';
$config = json_decode(file_get_contents('config.json'), true);
$password_admin = $config['admin_pin']; // Pakai PIN yang sama dengan PMS

if (isset($_GET['logout'])) { session_destroy(); header("Location: dashboard.php"); exit; }
if (isset($_POST['login'])) {
    if ($_POST['password'] === $password_admin) { $_SESSION['loggedin'] = true; } 
    else { $error = "PIN Salah!"; }
}
if (!isset($_SESSION['loggedin'])) {
    echo '<form method="post" style="text-align:center; margin-top:100px;">
          <h2>CLIENT DASHBOARD</h2><input type="password" name="password" placeholder="PIN" required><br><br>
          <button type="submit" name="login">LOGIN</button></form>';
    exit;
}

// SAVE CONTENT
$current_data = json_decode(file_get_contents($json_file), true);
if (isset($_POST['save_content'])) {
    foreach ($current_data as $key => $val) { if (isset($_POST[$key])) $current_data[$key] = $_POST[$key]; }
    foreach ($_FILES as $key => $file) {
        if ($file['name'] && (strpos($key, 'img_')===0 || strpos($key, 'hero_video')!==false)) {
            $target = $upload_dir . basename($file['name']);
            if(move_uploaded_file($file['tmp_name'], $target)) $current_data[$key] = $target;
        }
    }
    file_put_contents($json_file, json_encode($current_data, JSON_PRETTY_PRINT));
    $msg = "Konten Tersimpan!";
}

// SAVE REVIEWS
$reviews = json_decode(file_get_contents($review_file), true);
if (isset($_POST['save_reviews'])) {
    $new_list = [];
    if(isset($_POST['rev_id'])) {
        foreach($_POST['rev_id'] as $i => $id) {
            if(!isset($_POST['del_'.$id])) { // Jika tidak dicentang hapus
                $new_list[] = [
                    "id" => $id, "name" => $_POST['rev_name'][$i], "rating" => $_POST['rev_rating'][$i],
                    "comment" => $_POST['rev_comment'][$i], "source" => $_POST['rev_source'][$i],
                    "date" => $_POST['rev_date'][$i], "visible" => isset($_POST['rev_vis'][$i])
                ];
            }
        }
    }
    if (!empty($_POST['new_name'])) {
        $new_list[] = ["id"=>uniqid("rev_"), "name"=>$_POST['new_name'], "rating"=>$_POST['new_rating'], 
                       "comment"=>$_POST['new_comment'], "source"=>$_POST['new_source'], "date"=>date("Y-m-d"), "visible"=>true];
    }
    file_put_contents($review_file, json_encode($new_list, JSON_PRETTY_PRINT));
    echo "<meta http-equiv='refresh' content='0'>";
}
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard</title><meta name="viewport" content="width=device-width, initial-scale=1">
<style>body{font-family:sans-serif;background:#f4f4f4;padding:20px;max-width:800px;margin:0 auto;} .card{background:#fff;padding:20px;margin-bottom:20px;border-radius:8px;} input,textarea,select{width:100%;padding:10px;margin:5px 0;box-sizing:border-box;} button{padding:10px 20px;background:#1B4D3E;color:#fff;border:none;cursor:pointer;}</style>
</head>
<body>
    <div style="display:flex;justify-content:space-between;"><h2>Spencer Editor</h2><a href="?logout=true">Logout</a></div>
    
    <form method="post" enctype="multipart/form-data">
        <?php 
        $groups = []; foreach($current_data as $k=>$v) { $p=explode('_',$k)[0]; $groups[$p][]=$k; }
        foreach($groups as $grp => $keys): ?>
        <div class="card">
            <h3>BAGIAN: <?php echo strtoupper($grp); ?></h3>
            <?php foreach($keys as $k): $v=$current_data[$k]; ?>
                <label><?php echo $k; ?></label>
                <?php if(strpos($k,'img_')===0 || strpos($k,'video')!==false): ?>
                    <input type="file" name="<?php echo $k; ?>">
                    <input type="hidden" name="<?php echo $k; ?>" value="<?php echo $v; ?>">
                    <small>File: <?php echo $v; ?></small>
                <?php elseif($k=='hero_type'): ?>
                    <select name="<?php echo $k; ?>"><option value="video" <?php if($v=='video')echo'selected';?>>Video</option><option value="slider" <?php if($v=='slider')echo'selected';?>>Slider</option></select>
                <?php else: ?>
                    <input type="text" name="<?php echo $k; ?>" value="<?php echo $v; ?>">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <button type="submit" name="save_content">SIMPAN KONTEN</button>
    </form>

    <form method="post">
        <div class="card" style="border-top:5px solid #C5A059;">
            <h3>REVIEW MANAGER</h3>
            <?php foreach($reviews as $i=>$r): ?>
            <div style="border-bottom:1px solid #eee; padding:10px 0;">
                <input type="hidden" name="rev_id[]" value="<?php echo $r['id']; ?>">
                <input type="hidden" name="rev_date[]" value="<?php echo $r['date']; ?>">
                <input type="hidden" name="rev_source[]" value="<?php echo $r['source']; ?>">
                <b><?php echo $r['name']; ?></b> (<?php echo $r['source']; ?>)
                <label><input type="checkbox" name="rev_vis[<?php echo $i; ?>]" value="1" <?php if($r['visible']) echo 'checked'; ?>> Tampil?</label>
                <label><input type="checkbox" name="del_<?php echo $r['id']; ?>"> Hapus</label>
                <textarea name="rev_comment[]"><?php echo $r['comment']; ?></textarea>
            </div>
            <?php endforeach; ?>
            
            <h4>+ Tambah Review Manual (Google)</h4>
            <select name="new_source"><option value="google">Google Maps</option><option value="website">Manual</option></select>
            <input type="text" name="new_name" placeholder="Nama Tamu">
            <select name="new_rating"><option value="5">5 Bintang</option><option value="4">4 Bintang</option></select>
            <textarea name="new_comment" placeholder="Komentar..."></textarea>
            <button type="submit" name="save_reviews" style="margin-top:10px;">SIMPAN REVIEW</button>
        </div>
    </form>
</body>
</html>
