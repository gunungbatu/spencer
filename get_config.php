<?php
// get_config.php - Pastikan berada di folder utama (root) yang sama dengan index.html
header('Content-Type: application/json');
error_reporting(0); // Matikan pesan error agar tidak merusak format JSON

$config_file = 'config.json';
$response = [
    "api_url" => "https://script.google.com/macros/s/AKfycbwd6bLCita-mPXVvrjGrCExO7xR2AcSCAtw5cftZ61_fHIvP104P2Fv49FVlmMMK8rRLw/exec",
    "hotel_wa" => "6281130700206"
];

if (file_exists($config_file)) {
    $data = json_decode(file_get_contents($config_file), true);
    if ($data) {
        if (!empty($data['api_url'])) $response["api_url"] = $data['api_url'];
        if (!empty($data['hotel_wa'])) $response["hotel_wa"] = $data['hotel_wa'];
    }
}

echo json_encode($response);
?>
