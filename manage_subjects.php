<?php
require 'auth.php';
require 'db_connect.php';

$message = "";

// បញ្ចូលមុខវិជ្ជាថ្មី
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    
    // ឆែកមើលក្រែងលោមានរួចហើយ
    $check = $conn->query("SELECT id FROM subjects WHERE subject_name = '$subject_name'");
    if ($check->num_rows > 0) {
        $message = '<div class="alert alert-warning">⚠️ មុខវិជ្ជានេះមានក្នុងបញ្ជីរួចហើយ!</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        $stmt->bind_param("s", $subject_name);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ បន្ថែមមុខវិជ្ជាថ្មីជោគជ័យ!</div>';
        }
        $stmt->close();
    }
}

// លុបមុខវិជ្ជា
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM subjects WHERE id = $del_id");
    header("Location: manage_subjects.php");
    exit();
}

$subjects = $conn->query("SELECT * FROM subjects ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>គ្រប់គ្រងមុខវិជ្ជា</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mb-5" style="max-width: 800px;">
        <h4 class="text-primary mb-4 fw-bold">📚 គ្រប់គ្រងបញ្ជីមុខវិជ្ជា</h4>
        <?php echo $message; ?>
        
        <div class="row">
            <!-- ផ្ទាំងបញ្ចូល -->
            <div class="col-md-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white fw-bold">+ បន្ថែមមុខវិជ្ជាថ្មី</div>
                    <div class="card-body">
                        <form action="manage_subjects.php" method="POST">
                            <div class="mb-3">
                                <label class="fw-bold">ឈ្មោះមុខវិជ្ជា</label>
                                <input type="text" name="subject_name" class="form-control" required placeholder="ឧ. ភាសាអង់គ្លេស">
                            </div>
                            <button type="submit" name="add_subject" class="btn btn-primary w-100 fw-bold">រក្សាទុក</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- តារាងបង្ហាញ -->
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light">
                                <tr><th>ល.រ</th><th>ឈ្មោះមុខវិជ្ជា</th><th>សកម្មភាព</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($subjects->num_rows > 0) {
                                    $i = 1;
                                    while($row = $subjects->fetch_assoc()) {
                                        echo "<tr>
                                            <td>".$i++."</td>
                                            <td class='text-start fw-bold text-success'>{$row['subject_name']}</td>
                                            <td>
                                                <a href='manage_subjects.php?delete={$row['id']}' class='btn btn-sm btn-danger fw-bold' onclick='return confirm(\"តើពិតជាចង់លុបមុខវិជ្ជានេះមែនទេ?\")'>លុប</a>
                                            </td>
                                        </tr>";
                                    }
                                } else { echo "<tr><td colspan='3' class='text-danger py-3'>មិនទាន់មានមុខវិជ្ជាទេ</td></tr>"; }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>