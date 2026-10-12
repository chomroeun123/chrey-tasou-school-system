<?php
require 'auth.php';
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['student_ids'])) {
    $student_ids = $_POST['student_ids'];
    
    if (!empty($student_ids)) {
        // បំប្លែង Array នៃ ID ទៅជាទម្រង់ 1,2,3... សម្រាប់លុបក្នុង SQL
        $ids = implode(',', array_map('intval', $student_ids)); 
        
        // លក្ខខណ្ឌសុវត្ថិភាព: Admin លុបបានទាំងអស់ តែគ្រូអាចលុបបានតែសិស្សក្នុងថ្នាក់ខ្លួនទេ
        if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
            $class = $_SESSION['assigned_class'];
            $sql = "DELETE FROM students WHERE id IN ($ids) AND grade_level = $class";
        } else {
            $sql = "DELETE FROM students WHERE id IN ($ids)";
        }
        
        if ($conn->query($sql)) {
            $_SESSION['success_msg'] = "បានលុបទិន្នន័យសិស្សដែលខុសចំនួន " . count($student_ids) . " នាក់ដោយជោគជ័យ!";
        } else {
            $_SESSION['error_msg'] = "មានបញ្ហាក្នុងការលុបទិន្នន័យសិស្ស!";
        }
    }
} else {
    $_SESSION['error_msg'] = "សូមជ្រើសរើសសិស្សយ៉ាងហោចណាស់ម្នាក់ដើម្បីលុប!";
}

header("Location: index.php");
exit();
?>