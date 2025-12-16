<?php
// send_email.php dengan SMTP PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Load Library PHPMailer
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// 2. Konfigurasi Akun Email (SESUAIKAN INI!)
$smtp_host = 'mail.spencergreenhotel.com'; // Biasanya mail.namadomain.com
$smtp_user = 'reservasi@spencergreenhotel.com'; // Email lengkap
$smtp_pass = 'PasswordEmailAnda123'; // PASSWORD EMAIL (Bukan password cPanel!)
$smtp_port = 465; // Port SSL biasanya 465, jika gagal coba 587 (TLS)

// 3. Ambil Data dari Javascript
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data"]);
    exit;
}

// 4. Setup Variabel Data
$booking_id = "SPN-" . date("ymd") . "-" . rand(100, 999);
$guest_name = htmlspecialchars($data['nama']);
$guest_email = $data['email'];
$room_type  = htmlspecialchars($data['kamar']);
$check_in   = htmlspecialchars($data['checkIn']);
$check_out  = htmlspecialchars($data['checkOut']);
$total_price= htmlspecialchars($data['totalHarga']);
$wa_link    = "https://wa.me/6281234567890"; // Ganti No WA Hotel

// 5. Template Email HTML
$email_body = "
<div style='font-family:Arial, sans-serif; max-width:600px; margin:0 auto; border:1px solid #ddd;'>
    <div style='background:#1B4D3E; padding:20px; text-align:center; color:#fff;'>
        <h2>SPENCER GREEN HOTEL</h2>
    </div>
    <div style='padding:20px;'>
        <h3>Booking Confirmed!</h3>
        <p>Halo $guest_name, pesanan Anda telah kami terima.</p>
        <table style='width:100%; text-align:left; margin-top:20px;'>
            <tr><th width='30%'>Booking ID</th><td>$booking_id</td></tr>
            <tr><th>Kamar</th><td>$room_type</td></tr>
            <tr><th>Check-In</th><td>$check_in</td></tr>
            <tr><th>Check-Out</th><td>$check_out</td></tr>
            <tr><th>Total</th><td style='color:#C5A059; font-weight:bold;'>$total_price</td></tr>
        </table>
        <br>
        <p>Silakan selesaikan pembayaran agar booking ini sah.</p>
        <center>
            <a href='$wa_link' style='background:#1B4D3E; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;'>Konfirmasi via WhatsApp</a>
        </center>
    </div>
</div>";

// 6. Proses Pengiriman via SMTP
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Pakai SSL
    $mail->Port       = $smtp_port;

    // Recipients
    $mail->setFrom($smtp_user, 'Spencer Reservations');
    $mail->addAddress($guest_email, $guest_name);     // Ke Tamu
    $mail->addBCC($smtp_user);                        // Copy ke Hotel (BCC)

    // Content
    $mail->isHTML(true);
    $mail->Subject = "Booking Confirmation: $booking_id";
    $mail->Body    = $email_body;

    $mail->send();
    echo json_encode(["status" => "success", "message" => "Email sent via SMTP"]);

} catch (Exception $e) {
    // Catat error jika gagal
    error_log("Mailer Error: " . $mail->ErrorInfo);
    echo json_encode(["status" => "error", "message" => "Mailer Error: " . $mail->ErrorInfo]);
}
?>
