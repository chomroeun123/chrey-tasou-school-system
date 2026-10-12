<?php
$servername = "ឈ្មោះ_Host_ដែលគេផ្តល់ឱ្យ"; // ឧ. mysql.aivencloud.com
$username = "ឈ្មោះ_User"; 
$password = "លេខសម្ងាត់";
$dbname = "ឈ្មោះ_Database";

$conn = new mysqli($servername, $username, $password, $dbname);

// កំណត់ Charset ឱ្យត្រូវជាភាសាខ្មែរ
if (!$conn->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
    exit();
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>