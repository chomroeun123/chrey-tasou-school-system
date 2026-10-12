<?php
require 'auth.php';
require 'db_connect.php';

$message = "";

// ច្រោះសិស្សតាមថ្នាក់ បើអ្នក Login ជាគ្រូ
$where_stu = "";
$where_rec = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where_stu = " WHERE grade_level = $class ";
    $where_rec = " WHERE st.grade_level = $class ";
}

// ១. ដំណើរការពេលសិស្ស "ខ្ចីសៀវភៅ"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['borrow_book'])) {
    $student_id = $_POST['student_id'];
    $book_id = $_POST['book_id'];
    $borrow_date = date('Y-m-d'); // ចាប់យកថ្ងៃនេះស្វ័យប្រវត្តិ
    
    // ឆែកមើលក្រែងលោសៀវភៅនេះអស់ពីស្តុក
    $check_qty = $conn->query("SELECT available_qty FROM books WHERE id = $book_id");
    $qty_data = $check_qty->fetch_assoc();
    
    if ($qty_data && $qty_data['available_qty'] > 0) {
        // កត់ត្រាការខ្ចី
        $stmt = $conn->prepare("INSERT INTO borrow_records (student_id, book_id, borrow_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $student_id, $book_id, $borrow_date);
        
        if ($stmt->execute()) {
            // ដកចំនួនសៀវភៅក្នុងស្តុក (available_qty - 1)
            $conn->query("UPDATE books SET available_qty = available_qty - 1 WHERE id = $book_id");
            $message = '<div class="alert alert-success">✅ កត់ត្រាការខ្ចីសៀវភៅបានជោគជ័យ!</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger">⚠️ សុំទោស សៀវភៅនេះត្រូវបានគេខ្ចីអស់ពីស្តុកហើយ!</div>';
    }
}

// ២. ដំណើរការពេលសិស្សយកមក "សងវិញ"
if (isset($_GET['return'])) {
    $return_id = (int)$_GET['return'];
    
    // រកមើលលេខកូដសៀវភៅ ដើម្បីបូកបញ្ចូលក្នុងស្តុកវិញ
    $rec_query = $conn->query("SELECT book_id, status FROM borrow_records WHERE id = $return_id");
    if ($rec_query->num_rows > 0) {
        $rec_data = $rec_query->fetch_assoc();
        
        if ($rec_data['status'] == 'កំពុងខ្ចី') {
            $book_id = $rec_data['book_id'];
            $return_date = date('Y-m-d');
            
            // Update ស្ថានភាពទៅជា "បានសង"
            $conn->query("UPDATE borrow_records SET status = 'បានសង', return_date = '$return_date' WHERE id = $return_id");
            // បូកចំនួនសៀវភៅចូលស្តុកវិញ (available_qty + 1)
            $conn->query("UPDATE books SET available_qty = available_qty + 1 WHERE id = $book_id");
            
            header("Location: borrow_return.php");
            exit();
        }
    }
}

// ទាញយកទិន្នន័យសម្រាប់ដាក់ក្នុង Dropdown
$students = $conn->query("SELECT id, student_code, khmer_name FROM students $where_stu ORDER BY khmer_name ASC");
$books = $conn->query("SELECT id, book_title, category, available_qty FROM books WHERE available_qty > 0 ORDER BY book_title ASC");

// ទាញយកប្រវត្តិខ្ចីសង (បង្ហាញទាំងអ្នកកំពុងខ្ចី និងអ្នកសងហើយ)
$sql_records = "SELECT br.id, st.khmer_name, st.student_code, b.book_title, br.borrow_date, br.return_date, br.status 
                FROM borrow_records br
                JOIN students st ON br.student_id = st.id
                JOIN books b ON br.book_id = b.id
                $where_rec
                ORDER BY br.status DESC, br.borrow_date DESC";
$records = $conn->query($sql_records);
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ខ្ចី និង សងសៀវភៅ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mb-5">
        <h4 class="text-success mb-4 fw-bold">🔄 ប្រព័ន្ធកត់ត្រា ខ្ចី-សង សៀវភៅបណ្ណាល័យ</h4>
        <?php echo $message; ?>
        
        <div class="row">
            <!-- ផ្នែកខាងឆ្វេង៖ Form ខ្ចីសៀវភៅ -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-success text-white fw-bold">📥 កត់ត្រាការខ្ចីសៀវភៅថ្មី</div>
                    <div class="card-body">
                        <form action="borrow_return.php" method="POST">
                            <div class="mb-3">
                                <label class="fw-bold">ជ្រើសរើសសិស្ស</label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">-- សូមជ្រើសរើសសិស្ស --</option>
                                    <?php while($s = $students->fetch_assoc()): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo $s['student_code'] . ' - ' . $s['khmer_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="fw-bold">ជ្រើសរើសសៀវភៅ (ដែលនៅសល់ក្នុងស្តុក)</label>
                                <select name="book_id" class="form-select" required>
                                    <option value="">-- សូមជ្រើសរើសសៀវភៅ --</option>
                                    <?php while($b = $books->fetch_assoc()): ?>
                                        <option value="<?php echo $b['id']; ?>"><?php echo $b['book_title'] . ' (នៅសល់: ' . $b['available_qty'] . ')'; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" name="borrow_book" class="btn btn-success w-100 fw-bold">កត់ត្រាការខ្ចី</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- ផ្នែកខាងស្តាំ៖ តារាងប្រវត្តិអ្នកខ្ចី -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white fw-bold">📋 បញ្ជីសិស្សកំពុងខ្ចី និងប្រវត្តិសងសៀវភៅ</div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>អត្តលេខ</th>
                                    <th>ឈ្មោះសិស្ស</th>
                                    <th>ចំណងជើងសៀវភៅ</th>
                                    <th>ថ្ងៃខ្ចី</th>
                                    <th>ស្ថានភាព</th>
                                    <th>សកម្មភាព</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($records->num_rows > 0) {
                                    while($row = $records->fetch_assoc()) {
                                        $is_borrowing = ($row['status'] == 'កំពុងខ្ចី');
                                        $status_badge = $is_borrowing ? '<span class="badge bg-danger">កំពុងខ្ចី</span>' : '<span class="badge bg-primary">បានសង</span>';
                                        
                                        echo "<tr>
                                            <td>{$row['student_code']}</td>
                                            <td class='text-start fw-bold'>{$row['khmer_name']}</td>
                                            <td class='text-start text-success'>{$row['book_title']}</td>
                                            <td>".date('d-m-Y', strtotime($row['borrow_date']))."</td>
                                            <td>{$status_badge}</td>
                                            <td>";
                                        
                                        // បើកំពុងខ្ចី ទើបបង្ហាញប៊ូតុងសង
                                        if ($is_borrowing) {
                                            echo "<a href='borrow_return.php?return={$row['id']}' class='btn btn-sm btn-outline-success fw-bold' onclick='return confirm(\"តើសិស្សនេះពិតជាបានយកសៀវភៅមកសងវិញមែនទេ?\")'>✓ ទទួលសងវិញ</a>";
                                        } else {
                                            echo "<span class='text-muted' style='font-size:12px;'>សងនៅថ្ងៃ ".date('d-m-Y', strtotime($row['return_date']))."</span>";
                                        }
                                        
                                        echo "</td></tr>";
                                    }
                                } else { 
                                    echo "<tr><td colspan='6' class='text-danger py-4'>មិនទាន់មានកំណត់ត្រាខ្ចីសៀវភៅទេ</td></tr>"; 
                                }
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