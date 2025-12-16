<?php
// send_email.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// 1. Ambil Data JSON dari Javascript
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

// 2. Setup Variabel dari Data Tamu
$booking_id = "SPN-" . date("Y") . "-" . rand(1000, 9999);
$guest_name = htmlspecialchars($data['nama']);
$guest_email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$room_type  = htmlspecialchars($data['kamar']);
$check_in   = htmlspecialchars($data['checkIn']);
$check_out  = htmlspecialchars($data['checkOut']);
$total_price= htmlspecialchars($data['totalHarga']); // Sudah diformat Rp di JS
$hotel_wa   = "https://wa.me/6281234567890"; // Ganti No WA Hotel
$hotel_map  = "https://goo.gl/maps/PlaceHolder"; // Ganti Link Maps

// Hitung Malam (Opsional, kasar)
$d1 = new DateTime($check_in);
$d2 = new DateTime($check_out);
$interval = $d1->diff($d2);
$nights = $interval->days . " Nights";

// 3. Siapkan Template Email (HTML)
// Kita masukkan HTML voucher kemarin ke dalam variabel $message
ob_start();
?>
<!DOCTYPE html>
<html>
<body style="background-color:#f4f4f4; padding:20px; font-family:Arial, sans-serif;">
    <div style="max-width:600px; margin:0 auto; background:#fff; border-radius:8px; overflow:hidden;">
        <div style="background:#1B4D3E; padding:30px; text-align:center;">
            <h1 style="color:#fff; margin:0;">SPENCER GREEN</h1>
            <p style="color:#C5A059; margin:5px 0 0;">Booking Confirmation</p>
        </div>
        
        <div style="padding:30px;">
            <h2 style="color:#333;">Halo, <?php echo $guest_name; ?></h2>
            <p style="color:#666;">Terima kasih telah memesan. Berikut detail reservasi Anda:</p>
            
            <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:10px; color:#999;">Booking ID</td>
                    <td style="padding:10px; font-weight:bold;"><?php echo $booking_id; ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:10px; color:#999;">Kamar</td>
                    <td style="padding:10px; font-weight:bold;"><?php echo $room_type; ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:10px; color:#999;">Check-In</td>
                    <td style="padding:10px; font-weight:bold;"><?php echo $check_in; ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:10px; color:#999;">Check-Out</td>
                    <td style="padding:10px; font-weight:bold;"><?php echo $check_out; ?> (<?php echo $nights; ?>)</td>
                </tr>
                <tr>
                    <td style="padding:10px; color:#999;">Total</td>
                    <td style="padding:10px; font-weight:bold; color:#C5A059; font-size:18px;"><?php echo $total_price; ?></td>
                </tr>
            </table>

            <div style="text-align:center; margin-top:30px;">
                <a href="<?php echo $hotel_wa; ?>" style="background:#1B4D3E; color:#fff; text-decoration:none; padding:12px 25px; border-radius:5px; font-weight:bold;">Hubungi Resepsionis</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$message = ob_get_clean();

// 4. Kirim Email ke TAMU
$subject = "Booking Confirmation: $booking_id";
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: Spencer Reservation <reservasi@spencergreen.com>" . "\r\n"; // Pastikan email ini ada di cPanel

$mail_guest = mail($guest_email, $subject, $message, $headers);

// 5. Kirim Email ke HOTEL (Notifikasi Sederhana)
$hotel_subject = "[NEW BOOKING] $guest_name - $room_type";
$hotel_msg = "Tamu baru: $guest_name\nCheckIn: $check_in\nWA: $data[whatsapp]";
$hotel_headers = "From: System <noreply@spencergreen.com>";
// Ganti email penerima notifikasi hotel di bawah ini
$mail_hotel = mail("reservasi@spencergreen.com", $hotel_subject, $hotel_msg, $hotel_headers);

if($mail_guest) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Mail server error"]);
}
?>
