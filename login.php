<?php
session_start();
require 'db_connect.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = md5($_POST['password']);

    // ទាញយក role និង assigned_class បន្ថែម
    $stmt = $conn->prepare("SELECT id, full_name, role, assigned_class FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role']; // admin ឬ teacher
        $_SESSION['assigned_class'] = $user['assigned_class']; // កម្រិតថ្នាក់
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = '<div class="alert alert-danger">ឈ្មោះគណនី ឬលេខសម្ងាត់មិនត្រឹមត្រូវទេ!</div>';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ចូលប្រព័ន្ធគ្រប់គ្រង</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #004085; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
    </style>
</head>
<body>
<div class="login-box">
    <h4 class="text-center text-primary mb-4 fw-bold">ប្រព័ន្ធគ្រប់គ្រងសាលារៀន</h4>
    <?php echo $error; ?>
    <form action="login.php" method="POST">
        <div class="mb-3">
            <label class="fw-bold">ឈ្មោះគណនី (Username)</label>
            <input type="text" name="username" class="form-control" required placeholder="admin">
        </div>
        <div class="mb-4">
            <label class="fw-bold">លេខសម្ងាត់ (Password)</label>
            <input type="password" name="password" class="form-control" required placeholder="123456">
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-bold">ចូលប្រព័ន្ធ</button>
    </form>
</div>
</body>
</html>