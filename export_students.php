<?php
require 'auth.php';
require 'db_connect.php';

// ច្រោះសិស្សតាមថ្នាក់ បើអ្នក Login ជាគ្រូ
$where = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class ";
}

$query = "SELECT student_code, khmer_name, gender, dob, pob_village, pob_commune, pob_district, pob_province, curr_village, curr_commune, curr_district, curr_province, father_name, father_job, mother_name, mother_job, class_type, grade_level, note FROM students $where ORDER BY grade_level ASC, khmer_name ASC";
$result = $conn->query($query);

if($result->num_rows > 0){
    $delimiter = ",";
    $filename = "បញ្ជីឈ្មោះសិស្ស_" . date('Y-m-d') . ".csv";
    
    // បង្កើត file pointer
    $f = fopen('php://memory', 'w');
    
    // បញ្ចូល BOM សម្រាប់ UTF-8 ដើម្បីឱ្យកុំព្យូទ័រ និង Excel ស្គាល់អក្សរខ្មែរ (Unicode)
    fputs($f, "\xEF\xBB\xBF");
    
    // បង្កើតក្បាលតារាង (Header)
    $fields = array('អត្តលេខ', 'ឈ្មោះសិស្ស', 'ភេទ', 'ថ្ងៃកំណើត(YYYY-MM-DD)', 'ភូមិកំណើត', 'ឃុំកំណើត', 'ស្រុកកំណើត', 'ខេត្តកំណើត', 'ភូមិបច្ចុប្បន្ន', 'ឃុំបច្ចុប្បន្ន', 'ស្រុកបច្ចុប្បន្ន', 'ខេត្តបច្ចុប្បន្ន', 'ឈ្មោះឪពុក', 'មុខរបរឪពុក', 'ឈ្មោះម្តាយ', 'មុខរបរម្តាយ', 'ប្រភេទថ្នាក់', 'ថ្នាក់ទី', 'ផ្សេងៗ');
    fputcsv($f, $fields, $delimiter);
    
    // បញ្ចូលទិន្នន័យម្តងមួយជួរៗ
    while($row = $result->fetch_assoc()){
        $lineData = array($row['student_code'], $row['khmer_name'], $row['gender'], $row['dob'], $row['pob_village'], $row['pob_commune'], $row['pob_district'], $row['pob_province'], $row['curr_village'], $row['curr_commune'], $row['curr_district'], $row['curr_province'], $row['father_name'], $row['father_job'], $row['mother_name'], $row['mother_job'], $row['class_type'], $row['grade_level'], $row['note']);
        fputcsv($f, $lineData, $delimiter);
    }
    
    // Move back to beginning of file
    fseek($f, 0);
    
    // Set headers ដើម្បី Download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    
    // Output ទិន្នន័យទាំងអស់
    fpassthru($f);
} else {
    echo "<script>alert('មិនមានទិន្នន័យសិស្សសម្រាប់ទាញយកទេ!'); window.location.href='index.php';</script>";
}
exit;
?>