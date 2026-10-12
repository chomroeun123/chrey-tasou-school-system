<?php
require 'auth.php';
require 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ទទួលទិន្នន័យពី Form
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

    // បញ្ចូលទិន្នន័យទៅក្នុង Database ថ្មី
    $stmt = $conn->prepare("INSERT INTO students 
        (student_code, khmer_name, gender, dob, grade_level, pob_village, pob_commune, pob_district, pob_province, curr_village, curr_commune, curr_district, curr_province, father_name, father_job, mother_name, mother_job, class_type, note) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // s = String (អក្សរ), i = Integer (លេខ)
    $stmt->bind_param("ssssissssssssssssss", 
        $student_code, $khmer_name, $gender, $dob, $grade_level, 
        $pob_village, $pob_commune, $pob_district, $pob_province, 
        $curr_village, $curr_commune, $curr_district, $curr_province, 
        $father_name, $father_job, $mother_name, $mother_job, 
        $class_type, $note
    );

    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">✅ បញ្ចូលទិន្នន័យសិស្សថ្មីបានដោយជោគជ័យ!</div>';
    } else {
        $message = '<div class="alert alert-danger">❌ បរាជ័យ៖ ' . $conn->error . '</div>';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បញ្ចូលសិស្សថ្មី</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }
        .form-section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; border-top: 4px solid #0d6efd;}
        .section-title { font-weight: bold; color: #0d6efd; margin-bottom: 15px; font-size: 18px; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;}
        label { font-size: 14px; font-weight: 500; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container mb-5" style="max-width: 900px;">
        <h4 class="text-center text-primary mb-4 fw-bold">ទម្រង់ចុះឈ្មោះសិស្សចូលរៀន</h4>
        
        <?php echo $message; ?>

        <form action="add_student.php" method="POST">
            
            <!-- ផ្នែកទី១៖ ព័ត៌មានផ្ទាល់ខ្លួន -->
            <div class="form-section">
                <div class="section-title">១. ព័ត៌មានផ្ទាល់ខ្លួន</div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>អត្តលេខ</label>
                        <input type="text" name="student_code" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>គោត្តនាម និងនាម</label>
                        <input type="text" name="khmer_name" class="form-control" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>ភេទ</label>
                        <select name="gender" class="form-select">
                            <option>ប្រុស</option>
                            <option>ស្រី</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>ថ្ងៃខែឆ្នាំកំណើត</label>
                        <input type="date" name="dob" class="form-control" required>
                    </div>
                </div>
            </div>

            <!-- ផ្នែកទី២៖ ទីកន្លែងកំណើត -->
            <div class="form-section" style="border-top-color: #198754;">
                <div class="section-title text-success">២. ទីកន្លែងកំណើត</div>
                <div class="row">
                    <div class="col-md-3 mb-3"><label>ភូមិ</label><input type="text" name="pob_village" class="form-control"></div>
                    <div class="col-md-3 mb-3"><label>ឃុំ/សង្កាត់</label><input type="text" name="pob_commune" class="form-control"></div>
                    <div class="col-md-3 mb-3"><label>ស្រុក/ខណ្ឌ</label><input type="text" name="pob_district" class="form-control"></div>
                    <div class="col-md-3 mb-3"><label>ខេត្ត/ក្រុង</label><input type="text" name="pob_province" class="form-control"></div>
                </div>
            </div>

            <!-- ផ្នែកទី៣៖ លំនៅបច្ចុប្បន្ន -->
            <div class="form-section" style="border-top-color: #ffc107;">
                <div class="section-title text-warning">៣. លំនៅបច្ចុប្បន្ន</div>
                <div class="row">
                    <div class="col-md-3 mb-3"><label>ភូមិ</label><input type="text" name="curr_village" class="form-control"></div>
                    <div class="col-md-3 mb-3"><label>ឃុំ/សង្កាត់</label><input type="text" name="curr_commune" class="form-control"></div>
                    <div class="col-md-3 mb-3"><label>ស្រុក/ខណ្ឌ</label><input type="text" name="curr_district" class="form-control"></div>
                    <div class="col-md-3 mb-3"><label>ខេត្ត/ក្រុង</label><input type="text" name="curr_province" class="form-control"></div>
                </div>
            </div>

            <!-- ផ្នែកទី៤៖ ព័ត៌មានមាតាបិតា និងផ្សេងៗ -->
            <div class="form-section" style="border-top-color: #6c757d;">
                <div class="section-title text-secondary">៤. ព័ត៌មានមាតាបិតា និងផ្សេងៗ</div>
                <div class="row">
                    <div class="col-md-3 mb-3"><label>ឈ្មោះឪពុក</label><input type="text" name="father_name" class="form-control"></div>
                    <div class="col-md-3 mb-3"><label>មុខរបរឪពុក</label><input type="text" name="father_job" class="form-control"></div>
                    <div class="col-md-3 mb-3"><label>ឈ្មោះម្តាយ</label><input type="text" name="mother_name" class="form-control"></div>
                    <div class="col-md-3 mb-3"><label>មុខរបរម្តាយ</label><input type="text" name="mother_job" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label>ថ្នាក់ទី</label>
                        <select name="grade_level" class="form-select">
                            <?php for($i=1; $i<=6; $i++) echo "<option value='$i'>ថ្នាក់ទី $i</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>ប្រភេទថ្នាក់</label>
                        <select name="class_type" class="form-select">
                            <option value="ធម្មតា">ធម្មតា</option>
                            <option value="គួរបន្ថែម">គួរបន្ថែម</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>ផ្សេងៗ (ចំណាំ)</label>
                        <input type="text" name="note" class="form-control" placeholder="ឧ. សិស្សក្រីក្រ ឬ មានជំងឺប្រចាំកាយ...">
                    </div>
                </div>
            </div>

            <!-- ប៊ូតុងបញ្ជូន -->
            <div class="text-end">
                <a href="index.php" class="btn btn-secondary px-4 me-2">បោះបង់</a>
                <button type="submit" class="btn btn-primary px-5 fw-bold">រក្សាទុកទិន្នន័យ</button>
            </div>

        </form>
    </div>

</body>
</html>