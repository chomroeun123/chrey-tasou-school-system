<?php
require 'auth.php';
require 'db_connect.php';

$message = "";

// ច្រោះសិស្សតាមថ្នាក់
$where = ""; $where_join = "";
$target_class = 3; // លំនាំដើម
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $target_class = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $target_class ";
    $where_join = " WHERE st.grade_level = $target_class ";
}

// ១. ដំណើរការរៀបចំវេនដោយស្វ័យប្រវត្តិ (Auto Generate)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['auto_generate'])) {
    
    // លុបវេនចាស់របស់ថ្នាក់នេះចោលសិន ដើម្បីកុំឱ្យជាន់គ្នា
    $conn->query("DELETE cs FROM cleaning_schedule cs INNER JOIN students st ON cs.student_id = st.id WHERE st.grade_level = $target_class");
    
    // ទាញយកសិស្សប្រុស និងស្រី ដាច់ដោយឡែកពីគ្នា
    $boys = []; $girls = [];
    $res = $conn->query("SELECT id, gender FROM students WHERE grade_level = $target_class");
    while($r = $res->fetch_assoc()) {
        if ($r['gender'] == 'ប្រុស') $boys[] = $r['id'];
        else $girls[] = $r['id'];
    }
    
    // ច្របល់ឈ្មោះឱ្យរញ៉េរញ៉ៃ (Random) ដើម្បីកុំឱ្យចំវេនដដែលៗរាល់ខែ
    shuffle($boys);
    shuffle($girls);
    
    $days_list = ['ចន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
    $day_index = 0;
    $task = "សម្អាតទូទៅ"; // ភារកិច្ចលំនាំដើម
    
    $stmt = $conn->prepare("INSERT INTO cleaning_schedule (student_id, day_of_week, task_desc) VALUES (?, ?, ?)");
    
    // បែងចែកសិស្សស្រីចូលតាមថ្ងៃមុន
    foreach ($girls as $gid) {
        $stmt->bind_param("iss", $gid, $days_list[$day_index], $task);
        $stmt->execute();
        $day_index = ($day_index + 1) % 6; // វិលជុំពី 0 ដល់ 5
    }
    
    // បន្តបែងចែកសិស្សប្រុសចូលតាមថ្ងៃម្តង ដើម្បីឱ្យចម្រុះគ្នា
    foreach ($boys as $bid) {
        $stmt->bind_param("iss", $bid, $days_list[$day_index], $task);
        $stmt->execute();
        $day_index = ($day_index + 1) % 6;
    }
    $stmt->close();
    
    $message = '<div class="alert alert-success">✅ រៀបចំវេនស្វ័យប្រវត្តិ (បែងចែកប្រុស-ស្រីស្មើៗគ្នា) បានជោគជ័យ!</div>';
}

// ២. ដំណើរការបន្ថែមសិស្សទៅក្នុងវេន (Manual)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule'])) {
    $student_id = (int)$_POST['student_id'];
    $day = $conn->real_escape_string($_POST['day_of_week']);
    $task = $conn->real_escape_string($_POST['task_desc']);
    
    $check = $conn->query("SELECT id FROM cleaning_schedule WHERE student_id = $student_id AND day_of_week = '$day'");
    if ($check->num_rows > 0) {
        $message = '<div class="alert alert-warning">⚠️ សិស្សនេះមានឈ្មោះក្នុងវេនថ្ងៃហ្នឹងរួចហើយ!</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO cleaning_schedule (student_id, day_of_week, task_desc) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $student_id, $day, $task);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ បន្ថែមសិស្សចូលវេនសម្អាតបានជោគជ័យ!</div>';
        }
        $stmt->close();
    }
}

// ៣. ដំណើរការលុបឈ្មោះពីវេន
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM cleaning_schedule WHERE id = $del_id");
    header("Location: cleaning_schedule.php");
    exit();
}

$students = $conn->query("SELECT id, student_code, khmer_name FROM students $where ORDER BY khmer_name ASC");

// ៤. ទាញយកទិន្នន័យវេនសម្អាត រៀបជាថ្ងៃ
$days = ['ចន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
$schedule = [];
foreach($days as $d) $schedule[$d] = [];

$sql_sch = "SELECT cs.id, cs.day_of_week, cs.task_desc, st.khmer_name 
            FROM cleaning_schedule cs 
            JOIN students st ON cs.student_id = st.id 
            $where_join ORDER BY st.khmer_name ASC";
$res_sch = $conn->query($sql_sch);
while($r = $res_sch->fetch_assoc()) {
    $schedule[$r['day_of_week']][] = $r;
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>វេនសម្អាតថ្នាក់</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mb-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
            <h4 class="text-success m-0 fw-bold">🧹 កាលវិភាគវេនសម្អាតថ្នាក់រៀន</h4>
            <a href="print_cleaning_schedule.php" target="_blank" class="btn btn-warning fw-bold text-dark">
                🖨️ បោះពុម្ពបិទជញ្ជាំង
            </a>
        </div>
        
        <?php echo $message; ?>
        
        <div class="row">
            <!-- ផ្នែកខាងឆ្វេង៖ Form បញ្ចូល និងប៊ូតុងស្វ័យប្រវត្តិ -->
            <div class="col-md-4">
                
                <!-- កាតថ្មី៖ រៀបចំស្វ័យប្រវត្តិ -->
                <div class="card shadow-sm border-0 mb-3 bg-warning bg-opacity-25 border-warning border-start border-5">
                    <div class="card-body text-center">
                        <h6 class="fw-bold text-dark mb-2">🤖 បែងចែកវេនស្វ័យប្រវត្តិ</h6>
                        <p class="small text-muted mb-3">ប្រព័ន្ធនឹងចាប់ឆ្នោតបែងចែកសិស្សប្រុស-ស្រី ឱ្យស្មើគ្នាទៅតាមថ្ងៃទាំង៦។ (វេនចាស់នឹងត្រូវលុបចោល)</p>
                        <form action="cleaning_schedule.php" method="POST">
                            <button type="submit" name="auto_generate" class="btn btn-warning fw-bold w-100 text-dark shadow-sm" onclick="return confirm('តើលោកគ្រូពិតជាចង់រៀបចំវេនដោយស្វ័យប្រវត្តិមែនទេ? ទិន្នន័យវេនចាស់នឹងត្រូវលុបចោលទាំងអស់។')">
                                ⚡ ចុចដើម្បីបែងចែកវេន
                            </button>
                        </form>
                    </div>
                </div>

                <!-- កាតចាស់៖ បញ្ចូលដោយដៃ -->
                <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 80px;">
                    <div class="card-header bg-success text-white fw-bold">+ ចាត់តាំងដោយដៃ (Manual)</div>
                    <div class="card-body">
                        <form action="cleaning_schedule.php" method="POST">
                            <div class="mb-3">
                                <label class="fw-bold">ជ្រើសរើសសិស្ស</label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">-- សូមជ្រើសរើស --</option>
                                    <?php while($s = $students->fetch_assoc()): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo $s['khmer_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">ជ្រើសរើសថ្ងៃ</label>
                                <select name="day_of_week" class="form-select" required>
                                    <?php foreach($days as $d): ?>
                                        <option value="<?php echo $d; ?>">ថ្ងៃ<?php echo $d; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="fw-bold">ភារកិច្ច (ជាជម្រើស)</label>
                                <input type="text" name="task_desc" class="form-control" placeholder="ឧ. បោសសំរាម, ជូតក្តារខៀន...">
                            </div>
                            <button type="submit" name="add_schedule" class="btn btn-success w-100 fw-bold">បញ្ចូលទៅក្នុងវេន</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- ផ្នែកខាងស្តាំ៖ បង្ហាញកាលវិភាគជាកាតៗ -->
            <div class="col-md-8">
                <div class="row">
                    <?php foreach($days as $day): 
                        $bg_class = "bg-primary";
                        if($day=='អង្គារ') $bg_class = "bg-secondary";
                        if($day=='ពុធ') $bg_class = "bg-success";
                        if($day=='ព្រហស្បតិ៍') $bg_class = "bg-danger";
                        if($day=='សុក្រ') $bg_class = "bg-info text-dark";
                        if($day=='សៅរ៍') $bg_class = "bg-dark";
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header <?php echo $bg_class; ?> text-white fw-bold text-center fs-5">
                                📅 ថ្ងៃ<?php echo $day; ?>
                            </div>
                            <div class="card-body p-2">
                                <ul class="list-group list-group-flush">
                                    <?php if(empty($schedule[$day])): ?>
                                        <li class="list-group-item text-center text-muted small py-3">មិនទាន់មានអ្នកសម្អាត</li>
                                    <?php else: ?>
                                        <?php foreach($schedule[$day] as $s): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <b class="text-primary fs-6"><?php echo $s['khmer_name']; ?></b> 
                                                <small class="text-muted d-block"><?php echo $s['task_desc']; ?></small>
                                            </span>
                                            <a href="cleaning_schedule.php?delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('តើលោកគ្រូពិតជាចង់លុបឈ្មោះនេះមែនទេ?')">✖</a>
                                        </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>