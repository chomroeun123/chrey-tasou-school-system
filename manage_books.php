<?php
require 'auth.php';
require 'db_connect.php';

$message = "";

// ១. បញ្ចូលសៀវភៅថ្មី
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_book'])) {
    $title = $_POST['book_title'];
    $category = $_POST['category'];
    $qty = (int)$_POST['total_qty'];
    
    $stmt = $conn->prepare("INSERT INTO books (book_title, category, total_qty, available_qty) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $title, $category, $qty, $qty); // ពេលបញ្ចូលដំបូង available = total
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">✅ បញ្ចូលសៀវភៅថ្មីទៅក្នុងស្តុកបានជោគជ័យ!</div>';
    } else {
        $message = '<div class="alert alert-danger">⚠️ មានបញ្ហាក្នុងការបញ្ចូលសៀវភៅ។</div>';
    }
    $stmt->close();
}

// ២. លុបសៀវភៅ
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // ឆែកមើលក្រែងលោសៀវភៅនេះមានសិស្សកំពុងខ្ចី
    $check = $conn->query("SELECT id FROM borrow_records WHERE book_id = $del_id AND status = 'កំពុងខ្ចី'");
    if ($check->num_rows > 0) {
        $message = '<div class="alert alert-danger">⚠️ មិនអាចលុបបានទេ ព្រោះមានសិស្សកំពុងខ្ចីសៀវភៅនេះ!</div>';
    } else {
        $conn->query("DELETE FROM books WHERE id = $del_id");
        header("Location: manage_books.php");
        exit();
    }
}

// ទាញយកបញ្ជីសៀវភៅ
$books = $conn->query("SELECT * FROM books ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>គ្រប់គ្រងស្តុកសៀវភៅ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mb-5">
        <h4 class="text-primary mb-4 fw-bold">📖 គ្រប់គ្រងស្តុកសៀវភៅបណ្ណាល័យ</h4>
        <?php echo $message; ?>
        
        <div class="row">
            <!-- Form បញ្ចូលសៀវភៅ -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white fw-bold">+ បញ្ចូលសៀវភៅថ្មី</div>
                    <div class="card-body">
                        <form action="manage_books.php" method="POST">
                            <div class="mb-3">
                                <label class="fw-bold">ចំណងជើងសៀវភៅ</label>
                                <input type="text" name="book_title" class="form-control" required placeholder="ឧ. រឿងមហាចោរទួលពង្រ">
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">ប្រភេទសៀវភៅ</label>
                                <select name="category" class="form-select" required>
                                    <option value="រឿងនិទាន">រឿងនិទានកុមារ</option>
                                    <option value="សៀវភៅពុម្ព">សៀវភៅពុម្ព</option>
                                    <option value="សៀវភៅអំណាន">សៀវភៅអំណានទូទៅ</option>
                                    <option value="សៀវភៅលំហាត់">សៀវភៅលំហាត់</option>
                                    <option value="ផ្សេងៗ">ផ្សេងៗ</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="fw-bold">ចំនួនសៀវភៅ (ក្បាល)</label>
                                <input type="number" name="total_qty" class="form-control" min="1" required>
                            </div>
                            <button type="submit" name="add_book" class="btn btn-primary w-100 fw-bold">រក្សាទុក</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- តារាងស្តុកសៀវភៅ -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ល.រ</th>
                                    <th>ចំណងជើងសៀវភៅ</th>
                                    <th>ប្រភេទ</th>
                                    <th class="text-primary">ចំនួនសរុប</th>
                                    <th class="text-success">នៅសល់អាចខ្ចីបាន</th>
                                    <th>សកម្មភាព</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($books->num_rows > 0) {
                                    $i = 1;
                                    while($row = $books->fetch_assoc()) {
                                        // បើនៅសល់ 0 លោតពណ៌ក្រហម
                                        $avail_class = ($row['available_qty'] > 0) ? 'text-success fw-bold' : 'text-danger fw-bold';
                                        
                                        echo "<tr>
                                            <td>".$i++."</td>
                                            <td class='text-start fw-bold'>{$row['book_title']}</td>
                                            <td><span class='badge bg-secondary'>{$row['category']}</span></td>
                                            <td class='text-primary fw-bold'>{$row['total_qty']}</td>
                                            <td class='$avail_class'>{$row['available_qty']}</td>
                                            <td>
                                                <a href='manage_books.php?delete={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"តើពិតជាចង់លុបសៀវភៅនេះមែនទេ?\")'>លុប</a>
                                            </td>
                                        </tr>";
                                    }
                                } else { 
                                    echo "<tr><td colspan='6' class='text-danger py-4'>មិនទាន់មានសៀវភៅក្នុងស្តុកនៅឡើយទេ</td></tr>"; 
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