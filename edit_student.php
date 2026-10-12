<?php
require 'auth.php';
require 'db_connect.php';

$message = "";

// ១. ទាញយកទិន្នន័យចាស់មកបង្ហាញក្នុង Form
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$student) { 
        header("Location: index.php"); 
        exit(); 
    }
}

// ២. ពេលចុចប៊ូតុង "ធ្វើបច្ចុប្បន្នភាពទិន្នន័យ"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    
    // ប្រមូលទិន្នន័យពី Form
    $student_code = $_POST['student_code'];
    $khmer_name = $_POST['khmer_name'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $grade_level = $_POST['grade_level'];
    
    $pob_village = $_POST['pob_village'];
    $pob_commune = $_POST['pob_commune'];
    $pob_district = $_POST['pob_district'];
    $pob_province = $_POST['pob_province'];
    
    $curr_village = $_POST['curr_village'];
    $curr_commune = $_POST['curr_commune'];
    $curr_district = $_POST['curr_district'];
    $curr_province = $_POST['curr_province'];
    
    $father_name = $_POST['father_name'];
    $father_job = $_POST['father_job'];
    $mother_name = $_POST['mother_name'];
    $mother_job = $_POST['mother_job'];
    
    $class_type = $_POST['class_type'];
    $note = $_POST['note'];

    // Update ទិន្នន័យទៅក្នុង Database ថ្មី
    $stmt = $conn->prepare("UPDATE students SET 
        student_code=?, khmer_name=?, gender=?, dob=?, grade_level=?, 
        pob_village=?, pob_commune=?, pob_district=?, pob_province=?, 
        curr_village=?, curr_commune=?, curr_district=?, curr_province=?, 
        father_name=?, father_job=?, mother_name=?, mother_job=?, 
        class_type=?, note=? WHERE id=?");
    
    $stmt->bind_param("ssssissssssssssssssi", 
        $student_code, $khmer_name, $gender, $dob, $grade_level, 
        $pob_village, $pob_commune, $pob_district, $pob_province, 
        $curr_village, $curr_commune, $curr_district, $curr_province, 
        $father_name, $father_job, $mother_name, $mother_job, 
        $class_type, $note, $id
    );

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        $message = '<div class="alert alert-danger">❌ បរាជ័យក្នុងការកែប្រែ៖ ' . $conn->error . '</div>';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>កែប្រែទិន្នន័យសិស្ស</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }
        .form-section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; border-top: 4px solid #ffc107;}
        .section-title { font-weight: bold; color: #d39e00; margin-bottom: 15px; font-size: 18px; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;}
        label { font-size: 14px; font-weight: 500; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container mb-5" style="max-width: 900px;">
        <h4 class="text-center text-warning mb-4 fw-bold">កែប្រែទិន្នន័យសិស្ស</h4>
        
        <?php echo $message; ?>

        <form action="edit_student.php?id=<?php echo $student['id']; ?>" method="POST">
            <!-- លាក់ ID ទុកសម្រាប់បញ្ជូនទៅកូដ PHP ខាងលើ -->
            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
            
            <!-- ផ្នែកទី១៖ ព័ត៌មានផ្ទាល់ខ្លួន -->
            <div class="form-section">
                <div class="section-title">១. ព័ត៌មានផ្ទាល់ខ្លួន</div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>អត្តលេខ</label>
                        <input type="text" name="student_code" class="form-control" value="<?php echo htmlspecialchars($student['student_code']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>គោត្តនាម និងនាម</label>
                        <input type="text" name="khmer_name" class="form-control" value="<?php echo htmlspecialchars($student['khmer_name']); ?>" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>ភេទ</label>
                        <select name="gender" class="form-select">
                            <option value="ប្រុស" <?php if($student['gender'] == 'ប្រុស') echo 'selected'; ?>>ប្រុស</option>
                            <option value="ស្រី" <?php if($student['gender'] == 'ស្រី') echo 'selected'; ?>>ស្រី</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>ថ្ងៃខែឆ្នាំកំណើត</label>
                        <input type="date" name="dob" class="form-control" value="<?php echo $student['dob']; ?>" required>
                    </div>
                </div>
            </div>

            <!-- ផ្នែកទី២៖ ទីកន្លែងកំណើត -->
            <div class="form-section">
                <div class="section-title">២. ទីកន្លែងកំណើត</div>
                <div class="row">
                    <div class="col-md-3 mb-3"><label>ភូមិ</label><input type="text" name="pob_village" class="form-control" value="<?php echo htmlspecialchars($student['pob_village'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>ឃុំ/សង្កាត់</label><input type="text" name="pob_commune" class="form-control" value="<?php echo htmlspecialchars($student['pob_commune'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>ស្រុក/ខណ្ឌ</label><input type="text" name="pob_district" class="form-control" value="<?php echo htmlspecialchars($student['pob_district'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>ខេត្ត/ក្រុង</label><input type="text" name="pob_province" class="form-control" value="<?php echo htmlspecialchars($student['pob_province'] ?? ''); ?>"></div>
                </div>
            </div>

            <!-- ផ្នែកទី៣៖ លំនៅបច្ចុប្បន្ន -->
            <div class="form-section">
                <div class="section-title">៣. លំនៅបច្ចុប្បន្ន</div>
                <div class="row">
                    <div class="col-md-3 mb-3"><label>ភូមិ</label><input type="text" name="curr_village" class="form-control" value="<?php echo htmlspecialchars($student['curr_village'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>ឃុំ/សង្កាត់</label><input type="text" name="curr_commune" class="form-control" value="<?php echo htmlspecialchars($student['curr_commune'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>ស្រុក/ខណ្ឌ</label><input type="text" name="curr_district" class="form-control" value="<?php echo htmlspecialchars($student['curr_district'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>ខេត្ត/ក្រុង</label><input type="text" name="curr_province" class="form-control" value="<?php echo htmlspecialchars($student['curr_province'] ?? ''); ?>"></div>
                </div>
            </div>

            <!-- ផ្នែកទី៤៖ ព័ត៌មានមាតាបិតា និងផ្សេងៗ -->
            <div class="form-section">
                <div class="section-title">៤. ព័ត៌មានមាតាបិតា និងផ្សេងៗ</div>
                <div class="row">
                    <div class="col-md-3 mb-3"><label>ឈ្មោះឪពុក</label><input type="text" name="father_name" class="form-control" value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>មុខរបរឪពុក</label><input type="text" name="father_job" class="form-control" value="<?php echo htmlspecialchars($student['father_job'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>ឈ្មោះម្តាយ</label><input type="text" name="mother_name" class="form-control" value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>"></div>
                    <div class="col-md-3 mb-3"><label>មុខរបរម្តាយ</label><input type="text" name="mother_job" class="form-control" value="<?php echo htmlspecialchars($student['mother_job'] ?? ''); ?>"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>ថ្នាក់ទី</label>
                        <select name="grade_level" class="form-select">
                            <?php 
                            for($i=1; $i<=6; $i++) {
                                $selected = ($student['grade_level'] == $i) ? 'selected' : '';
                                echo "<option value='$i' $selected>ថ្នាក់ទី $i</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>ប្រភេទថ្នាក់</label>
                        <select name="class_type" class="form-select">
                            <option value="ធម្មតា" <?php if(($student['class_type'] ?? '') == 'ធម្មតា') echo 'selected'; ?>>ធម្មតា</option>
                            <option value="គួរបន្ថែម" <?php if(($student['class_type'] ?? '') == 'គួរបន្ថែម') echo 'selected'; ?>>គួរបន្ថែម</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>ផ្សេងៗ (ចំណាំ)</label>
                        <input type="text" name="note" class="form-control" value="<?php echo htmlspecialchars($student['note'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- ប៊ូតុងបញ្ជូន -->
            <div class="text-end">
                <a href="index.php" class="btn btn-secondary px-4 me-2">បោះបង់</a>
                <button type="submit" class="btn btn-warning px-5 fw-bold text-dark">ធ្វើបច្ចុប្បន្នភាពទិន្នន័យ</button>
            </div>

        </form>
    </div>

</body>
</html>