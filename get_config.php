<?php
header('Content-Type: application/json');
$config = json_decode(file_get_contents('config.json'), true);
echo json_encode([
    "api_url" => $config['api_url'] ?? '',
    "hotel_wa" => $config['hotel_wa'] ?? ''
]);
?>
