<?php
header('Content-Type: application/json');
$file = 'reviews.json';
$data = json_decode(file_get_contents($file), true);
if (!$data) $data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_guest') {
    $new_review = [
        "id" => uniqid("rev_"),
        "name" => htmlspecialchars($_POST['name']),
        "rating" => intval($_POST['rating']),
        "comment" => htmlspecialchars($_POST['comment']),
        "source" => "website",
        "visible" => false, 
        "date" => date("Y-m-d")
    ];
    array_unshift($data, $new_review);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode(["status" => "success", "message" => "Review terkirim! Menunggu moderasi."]);
}
?>
