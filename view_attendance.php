<?php
require 'auth.php';
require 'db_connect.php';

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// ច្រោះសិស្សតាមថ្នាក់ បើអ្នក Login ជាគ្រូ
$where = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where = " WHERE st.grade_level = $class ";
}

$sql = "
SELECT st.student_code, st.khmer_name, st.gender, st.grade_level,
    SUM(CASE WHEN a.status = 'វត្តមាន' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN a.status = 'ច្បាប់' THEN 1 ELSE 0 END) AS leave_count,
    SUM(CASE WHEN a.status = 'អវត្តមាន' THEN 1 ELSE 0 END) AS absent_count
FROM students st
LEFT JOIN attendance a ON st.id = a.student_id AND DATE_FORMAT(a.attendance_date, '%Y-%m') = '$selected_month'
$where
GROUP BY st.id
ORDER BY st.khmer_name ASC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>របាយការណ៍វត្តមាន</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }
        .container-box { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top:20px;}
        th { background-color: #6c757d !important; color: white !important; text-align: center; vertical-align: middle;}
        td { text-align: center; vertical-align: middle; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container container-box mb-5">
        <h4 class="text-secondary mb-4 fw-bold">📊 របាយការណ៍វត្តមានប្រចាំខែ <?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') ? '(ថ្នាក់ទី '.$_SESSION['assigned_class'].')' : ''; ?></h4>
        
       <form action="view_attendance.php" method="GET" class="mb-4 d-flex align-items-center flex-wrap gap-2">
            <label class="me-2 fw-bold">ជ្រើសរើសខែ/ឆ្នាំ៖</label>
            <input type="month" name="month" class="form-control me-2" style="max-width: 200px;" value="<?php echo $selected_month; ?>" required>
            <button type="submit" class="btn btn-secondary text-nowrap">បង្ហាញ</button>
            
            <!-- បន្ថែមប៊ូតុងព្រីននេះ -->
            <a href="print_monthly_attendance.php?month=<?php echo $selected_month; ?>" target="_blank" class="btn btn-warning fw-bold text-dark text-nowrap ms-auto">
                🖨️ បោះពុម្ពបញ្ជីហៅវត្តមាន
            </a>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th width="5%">ល.រ</th>
                        <th width="15%">អត្តលេខ</th>
                        <th width="25%">ឈ្មោះសិស្ស</th>
                        <th width="10%">ភេទ</th>
                        <th width="10%">ថ្នាក់ទី</th>
                        <th width="10%" class="bg-success">វត្តមាន</th>
                        <th width="10%" class="bg-warning text-dark">ច្បាប់</th>
                        <th width="10%" class="bg-danger">អវត្តមាន</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        $i = 1;
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>".$i++."</td>";
                            echo "<td><span class='badge bg-secondary'>{$row['student_code']}</span></td>";
                            echo "<td class='text-start fw-bold'>{$row['khmer_name']}</td>";
                            echo "<td>{$row['gender']}</td>";
                            echo "<td>{$row['grade_level']}</td>";
                            echo "<td><b class='text-success'>{$row['present_count']}</b></td>";
                            echo "<td><b class='text-warning'>{$row['leave_count']}</b></td>";
                            echo "<td><b class='text-danger'>{$row['absent_count']}</b></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center text-danger'>គ្មានទិន្នន័យសិស្សក្នុងថ្នាក់នេះទេ</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>