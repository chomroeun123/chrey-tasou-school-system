<?php
require 'auth.php';
require 'db_connect.php';

// ច្រោះសិស្សតាមថ្នាក់ បើអ្នក Login ជាគ្រូ
$where = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class ";
}
$sql = "SELECT * FROM students $where ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បញ្ជីចុះឈ្មោះសិស្ស</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }
        .container-box { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px;}
        th { background-color: #e9ecef !important; color: #000 !important; text-align: center; vertical-align: middle; border: 1px solid #dee2e6; font-size: 14px;}
        td { text-align: center; vertical-align: middle; border: 1px solid #dee2e6; font-size: 14px; white-space: nowrap; }
        .table-title { font-size: 20px; font-weight: bold; background-color: white !important; color: blue !important; border: none !important;}
        .table-responsive { overflow-x: auto; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container-fluid px-4 container-box mb-5">
        
        <!-- បង្ហាញសារជោគជ័យ ក្រោយពេលលុបរួច -->
        <?php 
        if(isset($_SESSION['success_msg'])) {
            echo "<div class='alert alert-success fw-bold'>✅ " . $_SESSION['success_msg'] . "</div>";
            unset($_SESSION['success_msg']);
        }
        if(isset($_SESSION['error_msg'])) {
            echo "<div class='alert alert-danger fw-bold'>⚠️ " . $_SESSION['error_msg'] . "</div>";
            unset($_SESSION['error_msg']);
        }
        ?>

        <!-- បង្កើត Form ក្តោបតារាងទាំងមូល សម្រាប់បញ្ជូនទិន្នន័យដែលបានគ្រីស ទៅលុប -->
        <form action="bulk_delete_students.php" method="POST" onsubmit="return confirm('តើលោកគ្រូពិតជាចង់លុបទិន្នន័យសិស្សដែលបានជ្រើសរើសទាំងអស់នេះមែនទេ?');">
            
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="text-primary m-0">ប្រព័ន្ធគ្រប់គ្រងសិស្ស <?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') ? '(ថ្នាក់ទី '.$_SESSION['assigned_class'].')' : ''; ?></h5>
                
                <div>
                    <!-- ប៊ូតុង លុបអ្នកដែលជ្រើសរើស -->
                    <button type="submit" class="btn btn-danger fw-bold text-white me-2">🗑️ លុបអ្នកដែលជ្រើសរើស</button>
                    
                    <a href="import_students.php" class="btn btn-info fw-bold text-dark me-2">📥 នាំចូល (Import)</a>
                    <a href="export_students.php" class="btn btn-warning fw-bold text-dark me-2">📤 នាំចេញ (Export)</a>
                    <a href="add_student.php" class="btn btn-success fw-bold">+ បញ្ចូលសិស្សថ្មី</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th colspan="21" class="table-title py-3">បញ្ជីចុះឈ្មោះសិស្សចូលរៀន ឆ្នាំសិក្សា ២០២៦-២០២៧</th>
                        </tr>
                        <tr>
                            <!-- បន្ថែមប្រអប់ Select All នៅក្បាលតារាង -->
                            <th rowspan="2" style="width: 40px;">
                                <input type="checkbox" id="selectAll" class="form-check-input border-primary" style="width: 22px; height: 22px; cursor: pointer;" title="ជ្រើសរើសទាំងអស់">
                            </th>
                            <th rowspan="2">ល.រ</th><th rowspan="2">អត្តលេខ</th><th rowspan="2">គោត្តនាម និងនាម</th><th rowspan="2">ភេទ</th><th rowspan="2">ថ្ងៃខែឆ្នាំកំណើត</th>
                            <th colspan="4">ទីកន្លែងកំណើត</th><th colspan="4">លំនៅបច្ចុប្បន្ន</th><th colspan="2">ឪពុក</th><th colspan="2">ម្តាយ</th>
                            <th rowspan="2">ប្រភេទថ្នាក់</th><th rowspan="2">ផ្សេងៗ</th><th rowspan="2" class="bg-primary text-white">សកម្មភាព</th>
                        </tr>
                        <tr>
                            <th>ភូមិ</th><th>ឃុំ</th><th>ស្រុក</th><th>ខេត្ត</th>
                            <th>ភូមិ</th><th>ឃុំ</th><th>ស្រុក</th><th>ខេត្ត</th>
                            <th>ឈ្មោះឪពុក</th><th>មុខរបរឪពុក</th>
                            <th>ឈ្មោះម្តាយ</th><th>មុខរបរម្តាយ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            $i = 1;
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <!-- ប្រអប់ Checkbox សម្រាប់សិស្សម្នាក់ៗ -->
                                        <td>
                                            <input type='checkbox' name='student_ids[]' value='{$row['id']}' class='form-check-input student-checkbox border-secondary' style='width: 20px; height: 20px; cursor: pointer;'>
                                        </td>
                                        
                                        <td>".$i++."</td>
                                        <td>{$row['student_code']}</td>
                                        <td class='text-start fw-bold'>{$row['khmer_name']}</td>
                                        <td>{$row['gender']}</td>
                                        <td>".date('d-m-Y', strtotime($row['dob']))."</td>
                                        
                                        <td>".($row['pob_village'] ?? '-')."</td>
                                        <td>".($row['pob_commune'] ?? '-')."</td>
                                        <td>".($row['pob_district'] ?? '-')."</td>
                                        <td>".($row['pob_province'] ?? '-')."</td>

                                        <td>".($row['curr_village'] ?? '-')."</td>
                                        <td>".($row['curr_commune'] ?? '-')."</td>
                                        <td>".($row['curr_district'] ?? '-')."</td>
                                        <td>".($row['curr_province'] ?? '-')."</td>

                                        <td>".($row['father_name'] ?? '-')."</td>
                                        <td>".($row['father_job'] ?? '-')."</td>

                                        <td>".($row['mother_name'] ?? '-')."</td>
                                        <td>".($row['mother_job'] ?? '-')."</td>

                                        <td>".($row['class_type'] ?? '-')."</td>
                                        <td>".($row['note'] ?? '-')."</td>
                                        
                                        <td>
                                            <a href='edit_student.php?id={$row['id']}' class='btn btn-warning btn-sm'>កែប្រែ</a>
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='21' class='text-center text-danger py-4'>មិនទាន់មានទិន្នន័យ</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <!-- កូដ JavaScript សម្រាប់ចុច Select All ឱ្យគ្រីសទាំងអស់ស្វ័យប្រវត្តិ -->
    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.student-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = this.checked;
            }
        });
    </script>
</body>
</html>