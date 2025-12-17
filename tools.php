<?php
// Ganti ini jika ingin password lain
$password_saya = "Spencer123"; 

echo "Password Asli: " . $password_saya . "<br><br>";
echo "<strong>COPY KODE DI BAWAH INI KE CONFIG.JSON:</strong><br>";
echo '<input type="text" value="' . password_hash($password_saya, PASSWORD_DEFAULT) . '" style="width:100%; padding:10px;">';
?>
