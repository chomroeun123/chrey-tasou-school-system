<?php
require 'auth.php';
require 'db_connect.php';

$message = "";
$attendance_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// ច្រោះសិស្សតាមថ្នាក់
$where = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class ";
}

// ពេលចុចប៊ូតុង "រក្សាទុក"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_attendance'])) {
    $date = $_POST['attendance_date'];
    $statuses = $_POST['status']; // Array ផ្ទុកវត្តមាន
    $notes = $_POST['notes'];   // Array ផ្ទុកកំណត់សម្គាល់ (ផ្សេងៗ)
    
    foreach ($statuses as $student_id => $status) {
        $note_val = $conn->real_escape_string($notes[$student_id]);
        
        $check = $conn->query("SELECT id FROM attendance WHERE student_id = $student_id AND attendance_date = '$date'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE attendance SET status = '$status', notes = '$note_val' WHERE student_id = $student_id AND attendance_date = '$date'");
        } else {
            $conn->query("INSERT INTO attendance (student_id, attendance_date, status, notes) VALUES ($student_id, '$date', '$status', '$note_val')");
        }
    }
    $message = "<div class='alert alert-success fw-bold'>✅ រក្សាទុកវត្តមាន និងកំណត់សម្គាល់បានជោគជ័យ!</div>";
}

$students = $conn->query("SELECT id, student_code, khmer_name, gender FROM students $where ORDER BY khmer_name ASC");

// ទាញយកវត្តមាន និងកំណត់សម្គាល់ដែលមានស្រាប់
$existing_att = [];
$existing_notes = [];
$att_result = $conn->query("SELECT student_id, status, notes FROM attendance WHERE attendance_date = '$attendance_date'");
if($att_result){
    while($row = $att_result->fetch_assoc()) {
        $existing_att[$row['student_id']] = $row['status'];
        $existing_notes[$row['student_id']] = $row['notes'];
    }
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ស្រង់វត្តមានសិស្ស</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }
        .container-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top:20px;}
        th { background-color: #0dcaf0 !important; color: #000 !important; text-align: center; vertical-align: middle;}
        td { vertical-align: middle; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container container-box mb-5">
        <h4 class="text-info mb-4 fw-bold">📋 ស្រង់វត្តមានប្រចាំថ្ងៃ <?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') ? '(ថ្នាក់ទី '.$_SESSION['assigned_class'].')' : ''; ?></h4>
        
        <?php echo $message; ?>
        
        <form action="add_attendance.php" method="GET" class="mb-4 d-flex align-items-center bg-light p-3 rounded border flex-wrap gap-2">
            <label class="me-2 fw-bold">កាលបរិច្ឆេទ៖</label>
            <input type="date" name="date" class="form-control me-3" style="max-width: 200px;" value="<?php echo $attendance_date; ?>" onchange="this.form.submit()">
            
            <a href="print_daily_attendance.php?date=<?php echo $attendance_date; ?>" target="_blank" class="btn btn-warning fw-bold ms-auto">
                🖨️ បោះពុម្ពបញ្ជីនេះ
            </a>
        </form>

        <form action="add_attendance.php?date=<?php echo $attendance_date; ?>" method="POST">
            <input type="hidden" name="attendance_date" value="<?php echo $attendance_date; ?>">
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center">
                    <thead>
                        <tr>
                            <th width="5%">ល.រ</th>
                            <th width="20%">ឈ្មោះសិស្ស</th>
                            <th width="8%">ភេទ</th>
                            <th width="32%">ស្ថានភាពវត្តមាន</th>
                            <th width="35%">ផ្សេងៗ (ចេញមុន/ឈឺ/...)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($students->num_rows > 0) {
                            $i = 1;
                            while($row = $students->fetch_assoc()) {
                                $sid = $row['id'];
                                $status = isset($existing_att[$sid]) ? $existing_att[$sid] : 'វត្តមាន';
                                $note = isset($existing_notes[$sid]) ? $existing_notes[$sid] : '';
                                
                                echo "<tr>
                                    <td>".$i++."</td>
                                    <td class='text-start fw-bold'>{$row['khmer_name']}</td>
                                    <td>{$row['gender']}</td>
                                    <td>
                                        <div class='form-check form-check-inline'>
                                            <input class='form-check-input' type='radio' name='status[$sid]' value='វត្តមាន' ".($status=='វត្តមាន'?'checked':'')."> វត្តមាន
                                        </div>
                                        <div class='form-check form-check-inline'>
                                            <input class='form-check-input' type='radio' name='status[$sid]' value='ច្បាប់' ".($status=='ច្បាប់'?'checked':'')."> ច្បាប់
                                        </div>
                                        <div class='form-check form-check-inline'>
                                            <input class='form-check-input' type='radio' name='status[$sid]' value='អវត្តមាន' ".($status=='អវត្តមាន'?'checked':'')."> អវត្តមាន
                                        </div>
                                    </td>
                                    <td>
                                        <input type='text' name='notes[$sid]' class='form-control form-control-sm' value='".htmlspecialchars($note)."' placeholder='ឧ. សុំចេញមុនម៉ោង ៩:០០ (ឈឺពោះ)'>
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
            <button type="submit" name="submit_attendance" class="btn btn-info w-100 fw-bold mt-3 text-dark">💾 រក្សាទុកវត្តមាន</button>
        </form>
    </div>
</body>
</html>