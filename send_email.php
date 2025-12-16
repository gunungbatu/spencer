<?php
// send_email.php
// Mengaktifkan Error Reporting untuk Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// 1. Ambil Data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

// 2. Setup Variabel
// PENTING: Ganti domain di bawah ini sesuai domain asli Anda!
$domain_anda = "spencergreenhotel.com"; 
$email_pengirim = "reservasi@" . $domain_anda; // Pastikan email ini ADA di cPanel
$email_penerima_hotel = "reservasi@" . $domain_anda; // Email hotel untuk terima notifikasi

$booking_id = "SPN-" . date("ymd") . "-" . rand(100, 999);
$guest_name = htmlspecialchars($data['nama']);
$guest_email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$room_type  = htmlspecialchars($data['kamar']);
$check_in   = htmlspecialchars($data['checkIn']);
$check_out  = htmlspecialchars($data['checkOut']);
$total_price= htmlspecialchars($data['totalHarga']); 
$wa_guest   = htmlspecialchars($data['whatsapp']);

// 3. Template Email (HTML)
$subject_guest = "Booking Confirmation: $booking_id - Spencer Green Hotel";

$message_guest = "
<html>
<head><title>Booking Confirmation</title></head>
<body style='font-family:sans-serif; color:#333;'>
    <div style='max-width:600px; margin:0 auto; border:1px solid #ddd; padding:20px;'>
        <h2 style='color:#1B4D3E;'>Booking Confirmed</h2>
        <p>Dear $guest_name,</p>
        <p>Terima kasih telah melakukan pemesanan. Berikut detailnya:</p>
        <table style='width:100%; text-align:left;'>
            <tr><th>Booking ID</th><td>$booking_id</td></tr>
            <tr><th>Kamar</th><td>$room_type</td></tr>
            <tr><th>Check-In</th><td>$check_in</td></tr>
            <tr><th>Check-Out</th><td>$check_out</td></tr>
            <tr><th>Total</th><td>$total_price</td></tr>
        </table>
        <br>
        <p>Silakan selesaikan pembayaran melalui WhatsApp Admin.</p>
    </div>
</body>
</html>
";

// 4. Headers (Sangat Penting untuk menghindari blokir server)
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: Spencer Reservation <$email_pengirim>" . "\r\n";
$headers .= "Reply-To: $email_pengirim" . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// 5. Eksekusi Kirim ke TAMU
$mail_guest_status = mail($guest_email, $subject_guest, $message_guest, $headers);

// 6. Eksekusi Kirim Notif ke HOTEL (Text polos saja biar cepat)
$msg_hotel = "New Booking!\nNama: $guest_name\nKamar: $room_type\nIn: $check_in\nOut: $check_out\nWA: $wa_guest";
$mail_hotel_status = mail($email_penerima_hotel, "[NEW BOOKING] $guest_name", $msg_hotel, "From: System <$email_pengirim>");

// 7. Response ke Javascript
if($mail_guest_status) {
    echo json_encode(["status" => "success", "message" => "Email sent"]);
} else {
    // Jika gagal, catat error di file error_log di hosting
    error_log("Gagal kirim email ke $guest_email");
    echo json_encode(["status" => "error", "message" => "Server refused to send email"]);
}
?>
