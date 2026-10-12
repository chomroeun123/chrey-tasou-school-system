<?php
require 'auth.php';
require 'db_connect.php';

// ច្រោះសិស្សតាមថ្នាក់ បើអ្នក Login ជាគ្រូ
$where = ""; 
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class ";
}

// ទាញយកបញ្ជីសិស្ស
$students = $conn->query("SELECT id, student_code, khmer_name, gender FROM students $where ORDER BY khmer_name ASC");
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ជ្រើសរើសសិស្ស - សៀវភៅសិក្ខាគារិក</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mb-5 mt-4">
        <h4 class="text-success mb-4 fw-bold">📘 បោះពុម្ពសៀវភៅសិក្ខាគារិកសិស្ស</h4>
        
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white fw-bold">បញ្ជីឈ្មោះសិស្សក្នុងថ្នាក់</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ល.រ</th>
                            <th>អត្តលេខ</th>
                            <th>ឈ្មោះសិស្ស</th>
                            <th>ភេទ</th>
                            <th>សកម្មភាព</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($students->num_rows > 0) {
                            $i = 1;
                            while($row = $students->fetch_assoc()) {
                                echo "<tr>
                                    <td>".$i++."</td>
                                    <td><span class='badge bg-secondary'>{$row['student_code']}</span></td>
                                    <td class='text-start fw-bold'>{$row['khmer_name']}</td>
                                    <td>{$row['gender']}</td>
                                    <td>
                                        <a href='print_record_book.php?id={$row['id']}' target='_blank' class='btn btn-sm btn-primary fw-bold'>🖨️ បោះពុម្ពសៀវភៅ</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-danger py-4'>មិនទាន់មានសិស្សទេ!</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>