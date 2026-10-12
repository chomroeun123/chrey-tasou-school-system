<?php
require 'auth.php';
require 'db_connect.php';

$message = "";

// ១. ដំណើរការពេល Upload ឯកសារ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_doc'])) {
    $doc_title = $conn->real_escape_string($_POST['doc_title']);
    $uploaded_by = $_SESSION['admin_name']; // យកឈ្មោះអ្នកដែលកំពុង Login
    
    // រៀបចំការ Upload File
    $target_dir = "downloads/";
    $file_extension = strtolower(pathinfo($_FILES["doc_file"]["name"], PATHINFO_EXTENSION));
    
    // បង្កើតឈ្មោះ File ថ្មីកុំឱ្យជាន់គ្នា (ប្រើ Time)
    $new_file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.-]/", "_", $_FILES["doc_file"]["name"]);
    $target_file = $target_dir . $new_file_name;
    
    // អនុញ្ញាតតែឯកសារមួយចំនួន (PDF, Word, Excel, ZIP)
    $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar'];
    
    if (in_array($file_extension, $allowed_types)) {
        if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $target_file)) {
            // បញ្ចូលចូល Database
            $stmt = $conn->prepare("INSERT INTO documents (doc_title, file_name, file_type, uploaded_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $doc_title, $new_file_name, $file_extension, $uploaded_by);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">✅ Upload ឯកសារបានជោគជ័យ!</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">⚠️ មានបញ្ហាក្នុងការ Upload ឯកសារចូល Server!</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">⚠️ សូម Upload តែឯកសារប្រភេទ PDF, Word, Excel ឬ ZIP ប៉ុណ្ណោះ!</div>';
    }
}

// ២. ដំណើរការពេលចុច លុប ឯកសារ (មានតែអ្នក Upload ឬ Admin ទើបលុបបាន)
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    // រកមើលឈ្មោះ File ដើម្បីលុបចេញពី Folder ផងដែរ
    $check = $conn->query("SELECT file_name, uploaded_by FROM documents WHERE id = $del_id");
    if ($check->num_rows > 0) {
        $doc = $check->fetch_assoc();
        
        // លុបបានលុះត្រាតែខ្លួនឯងជាអ្នក Upload ឬជា Admin
        if ($_SESSION['role'] == 'admin' || $_SESSION['admin_name'] == $doc['uploaded_by']) {
            $file_path = "downloads/" . $doc['file_name'];
            if (file_exists($file_path)) {
                unlink($file_path); // លុប File ចេញពីកុំព្យូទ័រ
            }
            $conn->query("DELETE FROM documents WHERE id = $del_id");
            header("Location: school_documents.php");
            exit();
        } else {
            $message = '<div class="alert alert-danger">⚠️ អ្នកគ្មានសិទ្ធិលុបឯកសាររបស់អ្នកដទៃទេ!</div>';
        }
    }
}

// ទាញយកបញ្ជីឯកសារទាំងអស់មកបង្ហាញ
$documents = $conn->query("SELECT * FROM documents ORDER BY upload_date DESC");
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ឯកសាររដ្ឋបាលសាលា</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mb-5">
        <h4 class="text-primary mb-4 fw-bold">📂 ឃ្លាំងឯកសាររដ្ឋបាលសាលារៀន</h4>
        <?php echo $message; ?>
        
        <div class="row">
            <!-- ផ្នែកខាងឆ្វេង៖ Form សម្រាប់ Upload ឯកសារ -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white fw-bold">📤 Upload ឯកសារថ្មី</div>
                    <div class="card-body">
                        <!-- ត្រូវមាន enctype="multipart/form-data" ទើបអាច Upload File បាន -->
                        <form action="school_documents.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="fw-bold">ចំណងជើងឯកសារ</label>
                                <input type="text" name="doc_title" class="form-control" required placeholder="ឧ. សៀវភៅគោលការណ៍សាលា">
                            </div>
                            <div class="mb-4">
                                <label class="fw-bold mb-2">ជ្រើសរើស File (PDF, Word, Excel)</label>
                                <input type="file" name="doc_file" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                            </div>
                            <button type="submit" name="upload_doc" class="btn btn-primary w-100 fw-bold">អាប់ឡូត (Upload)</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- ផ្នែកខាងស្តាំ៖ តារាងបញ្ជីឯកសារដែលអាច Download បាន -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">ល.រ</th>
                                    <th width="40%">ចំណងជើងឯកសារ</th>
                                    <th width="15%">ប្រភេទ</th>
                                    <th width="20%">អ្នកអាប់ឡូត</th>
                                    <th width="20%">សកម្មភាព</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($documents->num_rows > 0) {
                                    $i = 1;
                                    while($row = $documents->fetch_assoc()) {
                                        // កំណត់ Icon ទៅតាមប្រភេទ File
                                        $icon = "📄";
                                        if ($row['file_type'] == 'pdf') $icon = "📕";
                                        if (in_array($row['file_type'], ['doc', 'docx'])) $icon = "📘";
                                        if (in_array($row['file_type'], ['xls', 'xlsx'])) $icon = "📗";
                                        if (in_array($row['file_type'], ['zip', 'rar'])) $icon = "📦";
                                        
                                        $file_url = "downloads/" . $row['file_name'];
                                        
                                        echo "<tr>
                                            <td>".$i++."</td>
                                            <td class='text-start fw-bold'>$icon {$row['doc_title']}</td>
                                            <td><span class='badge bg-secondary text-uppercase'>{$row['file_type']}</span></td>
                                            <td><small class='text-muted'>{$row['uploaded_by']}<br>".date('d-m-Y', strtotime($row['upload_date']))."</small></td>
                                            <td class='text-nowrap'>
                                                <a href='$file_url' download class='btn btn-sm btn-success fw-bold'>📥 ទាញយក</a>";
                                        
                                        // បង្ហាញប៊ូតុងលុប លុះត្រាតែជា Admin ឬជាម្ចាស់ឯកសារ
                                        if ($_SESSION['role'] == 'admin' || $_SESSION['admin_name'] == $row['uploaded_by']) {
                                            echo " <a href='school_documents.php?delete={$row['id']}' class='btn btn-sm btn-danger fw-bold' onclick='return confirm(\"តើពិតជាចង់លុបឯកសារនេះមែនទេ?\")'>លុប</a>";
                                        }
                                        
                                        echo "</td></tr>";
                                    }
                                } else { 
                                    echo "<tr><td colspan='5' class='text-danger py-4'>មិនទាន់មានឯកសាររដ្ឋបាលនៅឡើយទេ</td></tr>"; 
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