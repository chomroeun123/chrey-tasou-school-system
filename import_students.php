<?php
require 'auth.php';
require 'db_connect.php';
$message = "";

if(isset($_POST['importSubmit'])){
    
    // ពិនិត្យមើលថាវាជា File CSV ដែរឬទេ
    $csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
    
    if(!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $csvMimes)){
        if(is_uploaded_file($_FILES['file']['tmp_name'])){
            
            // បើក File ជាលក្ខណៈ Read
            $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
            
            // រំលងជួរទី១ ព្រោះវាជាក្បាលតារាង (Header)
            fgetcsv($csvFile);
            
            $count = 0; $updated = 0;
            // អានទិន្នន័យម្តងមួយជួរ
            while(($line = fgetcsv($csvFile)) !== FALSE){
                if (empty($line[0]) || empty($line[1])) continue; // រំលងបើជួរខូច
                
                $student_code = $conn->real_escape_string($line[0]);
                $khmer_name   = $conn->real_escape_string($line[1]);
                $gender       = $conn->real_escape_string($line[2]);
                $dob          = $conn->real_escape_string($line[3]); // YYYY-MM-DD
                
                $pob_village  = $conn->real_escape_string($line[4]);
                $pob_commune  = $conn->real_escape_string($line[5]);
                $pob_district = $conn->real_escape_string($line[6]);
                $pob_province = $conn->real_escape_string($line[7]);
                
                $curr_village  = $conn->real_escape_string($line[8]);
                $curr_commune  = $conn->real_escape_string($line[9]);
                $curr_district = $conn->real_escape_string($line[10]);
                $curr_province = $conn->real_escape_string($line[11]);
                
                $father_name = $conn->real_escape_string($line[12]);
                $father_job  = $conn->real_escape_string($line[13]);
                $mother_name = $conn->real_escape_string($line[14]);
                $mother_job  = $conn->real_escape_string($line[15]);
                
                $class_type  = $conn->real_escape_string($line[16]);
                $grade_level = (int)$line[17];
                $note        = $conn->real_escape_string($line[18]);

                // ឆែកមើលក្រែងលោអត្តលេខនេះមានរួចហើយ
                $prevQuery = "SELECT id FROM students WHERE student_code = '$student_code'";
                $prevResult = $conn->query($prevQuery);
                
                if($prevResult->num_rows > 0){
                    // Update ទិន្នន័យចាស់
                    $stmt = $conn->prepare("UPDATE students SET khmer_name=?, gender=?, dob=?, pob_village=?, pob_commune=?, pob_district=?, pob_province=?, curr_village=?, curr_commune=?, curr_district=?, curr_province=?, father_name=?, father_job=?, mother_name=?, mother_job=?, class_type=?, grade_level=?, note=? WHERE student_code=?");
                    $stmt->bind_param("ssssssssssssssssiss", $khmer_name, $gender, $dob, $pob_village, $pob_commune, $pob_district, $pob_province, $curr_village, $curr_commune, $curr_district, $curr_province, $father_name, $father_job, $mother_name, $mother_job, $class_type, $grade_level, $note, $student_code);
                    $stmt->execute();
                    $updated++;
                } else {
                    // បញ្ចូលសិស្សថ្មី
                    $stmt = $conn->prepare("INSERT INTO students (student_code, khmer_name, gender, dob, pob_village, pob_commune, pob_district, pob_province, curr_village, curr_commune, curr_district, curr_province, father_name, father_job, mother_name, mother_job, class_type, grade_level, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssssssssssssiss", $student_code, $khmer_name, $gender, $dob, $pob_village, $pob_commune, $pob_district, $pob_province, $curr_village, $curr_commune, $curr_district, $curr_province, $father_name, $father_job, $mother_name, $mother_job, $class_type, $grade_level, $note);
                    $stmt->execute();
                    $count++;
                }
            }
            fclose($csvFile);
            $message = "<div class='alert alert-success'>✅ ជោគជ័យ! សិស្សថ្មី <b>$count នាក់</b> ត្រូវបានបញ្ចូល និង Update ចាស់ <b>$updated នាក់</b>។</div>";
        } else {
            $message = "<div class='alert alert-danger'>មានបញ្ហាក្នុងការ Upload!</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>សូមជ្រើសរើស File ជាប្រភេទ CSV!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>នាំចូលទិន្នន័យសិស្ស</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mb-5" style="max-width: 700px;">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-info text-dark fw-bold">📥 នាំចូលទិន្នន័យសិស្សពី Excel (CSV File)</div>
            <div class="card-body">
                <?php echo $message; ?>
                <div class="alert alert-secondary">
                    <strong>💡 របៀបប្រើប្រាស់៖</strong>
                    <ol class="mb-0">
                        <li>សូមចុចប៊ូតុង "នាំចេញ" នៅទំព័របញ្ជីសិស្ស ដើម្បីយក File គំរូ (Template)។</li>
                        <li>បំពេញទិន្នន័យសិស្សថ្មីនៅក្នុង File នោះតាមលំដាប់ Column។ (<b>ថ្ងៃកំណើតត្រូវដាក់ទម្រង់ YYYY-MM-DD</b> ឧ. 2016-05-20)</li>
                        <li>ពេលបំពេញរួច សូម <b>Save As</b> ជ្រើសរើសប្រភេទ <b>CSV UTF-8 (Comma delimited)</b> ដើម្បីកុំឱ្យខូចអក្សរខ្មែរ។</li>
                        <li>ជ្រើសរើស File នោះរួចចុចប៊ូតុង "នាំចូលទិន្នន័យ" ខាងក្រោម។</li>
                    </ol>
                </div>
                
                <form action="import_students.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="fw-bold mb-2">ជ្រើសរើស File (.csv)៖</label>
                        <input type="file" name="file" class="form-control" required accept=".csv">
                    </div>
                    <button type="submit" name="importSubmit" class="btn btn-info w-100 fw-bold">នាំចូលទិន្នន័យ (Import)</button>
                    <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">ត្រលប់ក្រោយ</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>