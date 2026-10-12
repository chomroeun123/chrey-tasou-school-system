<?php
require 'auth.php';
require 'db_connect.php';

// ការពារមិនឱ្យគ្រូធម្មតាចូលទំព័រនេះបាន
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die('<div style="text-align:center; margin-top:50px;"><h3>អ្នកគ្មានសិទ្ធិចូលមើលទំព័រនេះទេ! ទំព័រនេះសម្រាប់តែនាយកសាលាប៉ុណ្ណោះ។</h3><a href="dashboard.php">ត្រលប់ក្រោយ</a></div>');
}

$message = "";
$edit_mode = false;
$edit_id = $edit_name = $edit_user = $edit_class = "";

// ១. ការបញ្ចូលគ្រូថ្មី (Insert)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_teacher'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $assigned_class = $_POST['assigned_class'];
    $role = 'teacher';

    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $message = '<div class="alert alert-warning">⚠️ ឈ្មោះគណនី (Username) នេះមានអ្នកប្រើរួចហើយ!</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role, assigned_class) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $full_name, $username, $password, $role, $assigned_class);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ បង្កើតគណនីគ្រូថ្មីបានជោគជ័យ!</div>';
        }
        $stmt->close();
    }
}

// ២. ការកែប្រែគណនី (Update)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_teacher'])) {
    $id = $_POST['teacher_id'];
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $assigned_class = $_POST['assigned_class'];
    
    // បើគាត់វាយ Password ថ្មី គឺ Update ទាំង Password. បើទុកចោលទទេ គឺរក្សា Password ចាស់ទុក.
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, password=?, assigned_class=? WHERE id=?");
        $stmt->bind_param("sssii", $full_name, $username, $password, $assigned_class, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, assigned_class=? WHERE id=?");
        $stmt->bind_param("ssii", $full_name, $username, $assigned_class, $id);
    }
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">✅ កែប្រែព័ត៌មានគណនីជោគជ័យ!</div>';
    } else {
        $message = '<div class="alert alert-danger">⚠️ មានបញ្ហាក្នុងការកែប្រែ!</div>';
    }
    $stmt->close();
}

// ៣. ការទាញទិន្នន័យមកដាក់ក្នុង Form ពេលចុចប៊ូតុង "កែប្រែ"
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM users WHERE id = $edit_id AND role = 'teacher'");
    if ($edit_result->num_rows > 0) {
        $edit_data = $edit_result->fetch_assoc();
        $edit_name = $edit_data['full_name'];
        $edit_user = $edit_data['username'];
        $edit_class = $edit_data['assigned_class'];
        $edit_mode = true; // ប្រាប់ថាឥឡូវស្ថិតក្នុង Mode កែប្រែ
    }
}

// ៤. ការលុបគណនី (Delete)
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id = $del_id AND role = 'teacher'");
    header("Location: manage_teachers.php");
    exit();
}

// ទាញយកបញ្ជីគ្រូទាំងអស់មកបង្ហាញក្នុងតារាង
$teachers = $conn->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY assigned_class ASC");
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>គ្រប់គ្រងគ្រូបង្រៀន</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mb-5">
        <h4 class="text-warning mb-4 fw-bold">👨‍🏫 គ្រប់គ្រងគណនីគ្រូបង្រៀន (Admin Only)</h4>
        <?php echo $message; ?>
        
        <div class="row">
            
            <!-- ផ្នែកខាងឆ្វេង៖ Form បញ្ចូល ឬកែប្រែ -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header fw-bold <?php echo $edit_mode ? 'bg-warning text-dark' : 'bg-primary text-white'; ?>">
                        <?php echo $edit_mode ? '✏️ កែប្រែគណនី' : '+ បង្កើតគណនីថ្មី'; ?>
                    </div>
                    <div class="card-body">
                        <form action="manage_teachers.php" method="POST">
                            <?php if($edit_mode): ?>
                                <!-- បញ្ជូន ID លាក់ ដើម្បីដឹងថាត្រូវ Update អ្នកណា -->
                                <input type="hidden" name="teacher_id" value="<?php echo $edit_id; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label>ឈ្មោះគ្រូ (Full Name)</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($edit_name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>ឈ្មោះគណនី (Username)</label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($edit_user); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>លេខសម្ងាត់ (Password)</label>
                                <input type="text" name="password" class="form-control" <?php echo $edit_mode ? 'placeholder="(ទុកទទេបើមិនចង់ដូរ)"' : 'required'; ?>>
                            </div>
                            <div class="mb-4">
                                <label>គ្រូបន្ទុកថ្នាក់ទី</label>
                                <select name="assigned_class" class="form-select" required>
                                    <?php 
                                    for($i=1; $i<=6; $i++){
                                        $selected = ($edit_class == $i) ? 'selected' : '';
                                        echo "<option value='$i' $selected>ថ្នាក់ទី $i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <?php if($edit_mode): ?>
                                <button type="submit" name="update_teacher" class="btn btn-warning w-100 fw-bold mb-2">រក្សាទុកការកែប្រែ</button>
                                <a href="manage_teachers.php" class="btn btn-secondary w-100 fw-bold">បោះបង់</a>
                            <?php else: ?>
                                <button type="submit" name="add_teacher" class="btn btn-primary w-100 fw-bold">បង្កើតគណនី</button>
                            <?php endif; ?>
                            
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- ផ្នែកខាងស្តាំ៖ តារាងបញ្ជីឈ្មោះគ្រូ -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ល.រ</th>
                                    <th>ឈ្មោះគ្រូ</th>
                                    <th>Username</th>
                                    <th>គ្រូបន្ទុកថ្នាក់ទី</th>
                                    <th>សកម្មភាព</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($teachers->num_rows > 0) {
                                    $i = 1;
                                    while($row = $teachers->fetch_assoc()) {
                                        echo "<tr>
                                            <td>".$i++."</td>
                                            <td class='text-start fw-bold text-primary'>{$row['full_name']}</td>
                                            <td>{$row['username']}</td>
                                            <td>ថ្នាក់ទី <b>{$row['assigned_class']}</b></td>
                                            <td class='text-nowrap'>
                                                <a href='manage_teachers.php?edit={$row['id']}' class='btn btn-sm btn-warning fw-bold'>កែប្រែ</a>
                                                <a href='manage_teachers.php?delete={$row['id']}' class='btn btn-sm btn-danger fw-bold' onclick='return confirm(\"តើពិតជាចង់លុបគណនីនេះមែនទេ?\")'>លុប</a>
                                            </td>
                                        </tr>";
                                    }
                                } else { 
                                    echo "<tr><td colspan='5' class='text-danger py-4'>មិនទាន់មានគណនីគ្រូនៅឡើយទេ</td></tr>"; 
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