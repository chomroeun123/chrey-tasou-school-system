<?php
// reset_data.php
require 'auth.php';
require 'db_connect.php';

if(isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $conn->query("DELETE FROM students WHERE grade_level = $class");
} else {
    $conn->query("DELETE FROM students");
}
echo "<h3 style='color:green; text-align:center; margin-top:50px;'>✅ បានលុបទិន្នន័យដែល Error ចោលជោគជ័យ! <br><br> <a href='index.php'>ត្រលប់ទៅទំព័រដើមវិញ</a></h3>";
?>