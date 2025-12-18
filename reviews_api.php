<?php
// reviews_api.php - Jembatan antara Form Website dan Database JSON
header('Content-Type: application/json');

$file = 'reviews.json';

// 1. Baca Data Lama
$data = [];
if (file_exists($file)) {
    $json_content = file_get_contents($file);
    $data = json_decode($json_content, true);
    if (!$data) $data = []; // Jaga-jaga jika file kosong/rusak
}

// 2. Proses Kiriman Data Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_guest') {
    
    // Ambil & Bersihkan Input
    $name = trim($_POST['name'] ?? 'Guest');
    $rating = intval($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    // Validasi sederhana
    if (empty($name) || empty($comment)) {
        echo json_encode(["status" => "error", "message" => "Mohon lengkapi Nama dan Komentar Anda."]);
        exit;
    }

    // Susun Data Review Baru
    $new_review = [
        "id" => uniqid("rev_"),
        "name" => htmlspecialchars($name), // Mencegah kode HTML berbahaya
        "rating" => $rating,
        "comment" => htmlspecialchars($comment),
        "source" => "website",
        "visible" => false, // Default FALSE (Wajib dimoderasi di Dashboard dulu)
        "date" => date("Y-m-d")
    ];

    // Masukkan ke urutan paling atas
    array_unshift($data, $new_review);

    // Simpan ke File JSON
    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT))) {
        // --- PESAN HOSPITALITY (UPDATED) ---
        echo json_encode([
            "status" => "success", 
            "message" => "Terima kasih! Cerita pengalaman Anda sangat berarti bagi kami. Semoga kami dapat menyambut Anda kembali segera di Spencer Green."
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan review. Hubungi admin."]);
    }
} else {
    // Jika diakses langsung tanpa POST
    echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
}
?>
