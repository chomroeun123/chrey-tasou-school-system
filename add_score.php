<?php
require 'auth.php';
require 'db_connect.php';
$message = "";

// ច្រោះសិស្សតាមថ្នាក់ បើអ្នក Login ជាគ្រូ
$where = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class ";
}
$students_result = $conn->query("SELECT id, student_code, khmer_name FROM students $where ORDER BY khmer_name ASC");

// បន្ថែមបន្ទាត់នេះ ដើម្បីទាញមុខវិជ្ជាមកដាក់ក្នុង Dropdown
$subjects_result = $conn->query("SELECT subject_name FROM subjects ORDER BY id ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id']; $subject_name = $_POST['subject_name'];
    $score_month = $_POST['score_month']; $score_value = $_POST['score_value'];
    
    $check_stmt = $conn->prepare("SELECT id FROM scores WHERE student_id=? AND subject_name=? AND score_month=?");
    $check_stmt->bind_param("iss", $student_id, $subject_name, $score_month);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $message = '<div class="alert alert-warning">⚠️ ពិន្ទុសម្រាប់មុខវិជ្ជានិងខែនេះ ត្រូវបានបញ្ចូលរួចហើយ!</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO scores (student_id, subject_name, score_month, score_value) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $student_id, $subject_name, $score_month, $score_value);
        if ($stmt->execute()) { $message = '<div class="alert alert-success">✅ រក្សាទុកជោគជ័យ!</div>'; }
        $stmt->close();
    }
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បញ្ចូលពិន្ទុ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container" style="max-width: 600px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h4 class="text-center text-success mb-4 fw-bold">បញ្ចូលពិន្ទុប្រចាំខែ <?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') ? '(ថ្នាក់ទី '.$_SESSION['assigned_class'].')' : ''; ?></h4>
        <?php echo $message; ?>
        <form action="add_score.php" method="POST">
            <div class="mb-3"><label>ជ្រើសរើសសិស្ស</label>
                <select name="student_id" class="form-select" required>
                    <option value="">-- សូមជ្រើសរើស --</option>
                    <?php while($row = $students_result->fetch_assoc()) echo "<option value='{$row['id']}'>{$row['student_code']} - {$row['khmer_name']}</option>"; ?>
                </select>
            </div>
           <div class="mb-3"><label class="fw-bold">មុខវិជ្ជា</label>
                <select name="subject_name" class="form-select" required>
                    <option value="">-- សូមជ្រើសរើសមុខវិជ្ជា --</option>
                    <?php 
                    // លុប Array ចាស់ចោល ហើយប្រើ Loop ទាញពី Database វិញ
                    while($sj = $subjects_result->fetch_assoc()) {
                        echo "<option value='{$sj['subject_name']}'>{$sj['subject_name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3"><label>ខែ</label>
                <select name="score_month" class="form-select" required>
                    <?php 
                    $months = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
                    foreach($months as $m) echo "<option value='$m'>$m</option>"; 
                    ?>
                </select>
            </div>
            <div class="mb-3"><label>ពិន្ទុ (លើ ១០)</label><input type="number" name="score_value" class="form-control" min="0" max="10" step="0.1" required></div>
            <button type="submit" class="btn btn-success w-100 fw-bold">រក្សាទុក</button>
        </form>
    </div>
</body>
</html>