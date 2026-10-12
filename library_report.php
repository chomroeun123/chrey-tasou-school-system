<?php
require 'auth.php';
require 'db_connect.php';

// ច្រោះសិស្សតាមថ្នាក់ បើអ្នក Login ជាគ្រូ
$where_rec = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where_rec = " AND st.grade_level = $class ";
}

// ទាញយកស្ថិតិ
$stat_books = $conn->query("SELECT SUM(total_qty) AS total, SUM(available_qty) AS avail FROM books")->fetch_assoc();
$total_b = $stat_books['total'] ?? 0;
$avail_b = $stat_books['avail'] ?? 0;
$borrowed_b = $total_b - $avail_b;

// ទាញយកបញ្ជីអ្នក "មិនទាន់សងសៀវភៅ" សរុប
$sql_unreturned = "SELECT st.khmer_name, st.student_code, st.grade_level, b.book_title, br.borrow_date, DATEDIFF(CURDATE(), br.borrow_date) as days_borrowed
                   FROM borrow_records br
                   JOIN students st ON br.student_id = st.id
                   JOIN books b ON br.book_id = b.id
                   WHERE br.status = 'កំពុងខ្ចី' $where_rec
                   ORDER BY br.borrow_date ASC";
$unreturned = $conn->query($sql_unreturned);
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>របាយការណ៍បណ្ណាល័យ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }
        .stat-card { border-radius: 8px; padding: 20px; color: white; margin-bottom: 20px;}
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mb-5">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-primary m-0 fw-bold">📊 របាយការណ៍បណ្ណាល័យសាលា</h4>
            <a href="print_library_report.php" target="_blank" class="btn btn-warning fw-bold text-dark">
                🖨️ បោះពុម្ពរបាយការណ៍
            </a>
        </div>
            
        <div class="row text-center fw-bold">
            <div class="col-md-4">
                <div class="stat-card bg-primary shadow-sm">
                    <h5>សៀវភៅសរុប</h5>
                    <h2><?php echo $total_b; ?> <small>ក្បាល</small></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card bg-success shadow-sm">
                    <h5>នៅសល់ក្នុងស្តុក</h5>
                    <h2><?php echo $avail_b; ?> <small>ក្បាល</small></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card bg-danger shadow-sm">
                    <h5>កំពុងត្រូវបានខ្ចី</h5>
                    <h2><?php echo $borrowed_b; ?> <small>ក្បាល</small></h2>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-danger text-white fw-bold">
                ⚠️ បញ្ជីសិស្សដែលមិនទាន់សងសៀវភៅ <?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') ? '(ថ្នាក់ទី '.$_SESSION['assigned_class'].')' : ''; ?>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ល.រ</th>
                            <th>អត្តលេខ</th>
                            <th>ឈ្មោះសិស្ស</th>
                            <th>ថ្នាក់ទី</th>
                            <th>ចំណងជើងសៀវភៅ</th>
                            <th>ថ្ងៃខ្ចីសៀវភៅ</th>
                            <th>រយៈពេលខ្ចី</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($unreturned->num_rows > 0) {
                            $i = 1;
                            while($row = $unreturned->fetch_assoc()) {
                                // បើខ្ចីលើសពី ១សប្តាហ៍ ឱ្យលោតពណ៌ក្រហមដាស់តឿន
                                $days = $row['days_borrowed'];
                                $day_class = ($days > 7) ? 'text-danger fw-bold' : 'text-success fw-bold';
                                
                                echo "<tr>
                                    <td>".$i++."</td>
                                    <td><span class='badge bg-secondary'>{$row['student_code']}</span></td>
                                    <td class='text-start fw-bold'>{$row['khmer_name']}</td>
                                    <td>{$row['grade_level']}</td>
                                    <td class='text-start text-primary'>{$row['book_title']}</td>
                                    <td>".date('d-m-Y', strtotime($row['borrow_date']))."</td>
                                    <td class='$day_class'>$days ថ្ងៃ</td>
                                </tr>";
                            }
                        } else { 
                            echo "<tr><td colspan='7' class='text-success py-4 fw-bold'>អស្ចារ្យ! មិនមានសិស្សណាម្នាក់ជំពាក់សៀវភៅសាលាទេ។</td></tr>"; 
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>