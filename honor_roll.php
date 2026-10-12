<?php
require 'auth.php';
require 'db_connect.php';

$report_type = isset($_GET['type']) ? $_GET['type'] : 'month';
$selected_month = isset($_GET['month']) ? $_GET['month'] : 'តុលា';

// កំណត់ចំណងជើង
$title_kh = "";
if ($report_type == 'month') $title_kh = "ប្រចាំខែ " . $selected_month;
else if ($report_type == 'sem1') $title_kh = "ប្រចាំឆមាសទី១";
else if ($report_type == 'sem2') $title_kh = "ប្រចាំឆមាសទី២";
else if ($report_type == 'yearly') $title_kh = "ប្រចាំឆ្នាំ";

// ច្រោះសិស្សតាមថ្នាក់
$where = ""; 
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class ";
}

// ១. ទាញមុខវិជ្ជា
$subjects_list = [];
$sub_res = $conn->query("SELECT subject_name FROM subjects ORDER BY id ASC");
while ($row = $sub_res->fetch_assoc()) $subjects_list[] = $row['subject_name'];
$num_subj = count($subjects_list) ?: 1;

// ២. ទាញពិន្ទុ
$scores_data = [];
$res_scores = $conn->query("SELECT student_id, subject_name, score_month, score_value FROM scores");
while($r = $res_scores->fetch_assoc()){
    if ($report_type == 'month') {
        if ($r['score_month'] == $selected_month) {
            $scores_data[$r['student_id']][$r['subject_name']] = $r['score_value'];
        }
    } else {
        $scores_data[$r['student_id']][$r['subject_name']][$r['score_month']] = $r['score_value'];
    }
}

// ៣. គណនាពិន្ទុ
$students = [];
$st_res = $conn->query("SELECT id, student_code, khmer_name, gender, grade_level FROM students $where");
while($stu = $st_res->fetch_assoc()) {
    $sid = $stu['id'];
    $total_score = 0;
    
    foreach($subjects_list as $subj) {
        if ($report_type == 'month') {
            $total_score += isset($scores_data[$sid][$subj]) ? (float)$scores_data[$sid][$subj] : 0;
        } else {
            $m12 = $scores_data[$sid][$subj]['ធ្នូ'] ?? 0;
            $m1 = $scores_data[$sid][$subj]['មករា'] ?? 0;
            $m2 = $scores_data[$sid][$subj]['កុម្ភៈ'] ?? 0;
            $exam1 = $scores_data[$sid][$subj]['មីនា'] ?? 0;
            
            $m5 = $scores_data[$sid][$subj]['ឧសភា'] ?? 0;
            $m6 = $scores_data[$sid][$subj]['មិថុនា'] ?? 0;
            $m7 = $scores_data[$sid][$subj]['កក្កដា'] ?? 0;
            $exam2 = $scores_data[$sid][$subj]['សីហា'] ?? 0;
            
            $sem1_score = ((($m12 + $m1 + $m2) / 3) + $exam1) / 2;
            $sem2_score = ((($m5 + $m6 + $m7) / 3) + $exam2) / 2;
            
            if ($report_type == 'sem1') $total_score += $sem1_score;
            else if ($report_type == 'sem2') $total_score += $sem2_score;
            else $total_score += ($sem1_score + $sem2_score) / 2;
        }
    }
    
    $stu['total_score'] = $total_score;
    $stu['avg_score'] = $total_score / $num_subj;
    $students[] = $stu;
}

// ៤. រៀបចំណាត់ថ្នាក់ និងយកតែ Top 5
usort($students, function($a, $b) { return $b['total_score'] <=> $a['total_score']; });
$top_students = array_slice($students, 0, 5); // យកតែ ៥ នាក់ដំបូង

function getGrade($score) {
    if ($score >= 9) return 'A'; if ($score >= 8) return 'B';
    if ($score >= 7) return 'C'; if ($score >= 5) return 'D';
    if ($score > 0) return 'E'; return 'F';
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>តារាងកិត្តិយសសិស្ស</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }
        .honor-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s; margin-bottom: 20px;}
        .honor-card:hover { transform: translateY(-5px); }
        .rank-badge { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; color: white; margin: 0 auto 10px; }
        .rank-1 { background: linear-gradient(45deg, #FFD700, #FDB931); box-shadow: 0 0 15px rgba(255, 215, 0, 0.5); }
        .rank-2 { background: linear-gradient(45deg, #C0C0C0, #E0E0E0); box-shadow: 0 0 15px rgba(192, 192, 192, 0.5); }
        .rank-3 { background: linear-gradient(45deg, #cd7f32, #d38d4a); box-shadow: 0 0 15px rgba(205, 127, 50, 0.5); }
        .rank-other { background: #0dcaf0; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mb-5 mt-4">
        <h3 class="text-center text-primary fw-bold mb-4">🏆 តារាងកិត្តិយសសិស្សពូកែ <?php echo $title_kh; ?></h3>
        
        <!-- Form ជ្រើសរើសប្រភេទ -->
        <form action="honor_roll.php" method="GET" class="d-flex justify-content-center mb-5 gap-2 flex-wrap">
            <select name="type" class="form-select" style="width: auto;" onchange="this.form.submit()">
                <option value="month" <?php if($report_type=='month') echo 'selected'; ?>>ប្រចាំខែ</option>
                <option value="sem1" <?php if($report_type=='sem1') echo 'selected'; ?>>ប្រចាំឆមាសទី១</option>
                <option value="sem2" <?php if($report_type=='sem2') echo 'selected'; ?>>ប្រចាំឆមាសទី២</option>
                <option value="yearly" <?php if($report_type=='yearly') echo 'selected'; ?>>ប្រចាំឆ្នាំ</option>
            </select>
            
            <?php if($report_type == 'month'): ?>
            <select name="month" class="form-select" style="width: auto;" onchange="this.form.submit()">
                <?php 
                $months = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
                foreach($months as $m) { $s=($m==$selected_month)?'selected':''; echo "<option value='$m' $s>$m</option>"; }
                ?>
            </select>
            <?php endif; ?>
            
            <a href="print_honor_roll.php?type=<?php echo $report_type; ?>&month=<?php echo $selected_month; ?>" target="_blank" class="btn btn-warning fw-bold text-dark">
                🖨️ បោះពុម្ពបិទជញ្ជាំង
            </a>
        </form>

        <!-- បង្ហាញកាតសិស្ស Top 5 -->
        <div class="row justify-content-center">
            <?php 
            if(count($top_students) > 0 && $top_students[0]['total_score'] > 0): 
                $rank = 1;
                foreach($top_students as $s): 
                    if ($s['total_score'] <= 0) continue;
                    
                    $badge_class = "rank-other";
                    if ($rank == 1) $badge_class = "rank-1";
                    else if ($rank == 2) $badge_class = "rank-2";
                    else if ($rank == 3) $badge_class = "rank-3";
            ?>
                <div class="col-md-4 col-sm-6 text-center">
                    <div class="card honor-card p-4">
                        <div class="rank-badge <?php echo $badge_class; ?>"><?php echo $rank; ?></div>
                        <h4 class="fw-bold text-dark mt-2"><?php echo $s['khmer_name']; ?></h4>
                        <span class="badge bg-secondary mb-3">អត្តលេខ៖ <?php echo $s['student_code']; ?></span>
                        <h5 class="text-primary fw-bold">ពិន្ទុ៖ <?php echo round($s['total_score'], 2); ?></h5>
                        <div class="text-muted">មធ្យមភាគ៖ <?php echo round($s['avg_score'], 2); ?> | និទ្ទេស៖ <b><?php echo getGrade($s['avg_score']); ?></b></div>
                    </div>
                </div>
            <?php 
                $rank++;
                endforeach; 
            else:
                echo "<div class='alert alert-danger text-center w-50'>មិនទាន់មានទិន្នន័យពិន្ទុសម្រាប់របាយការណ៍នេះទេ!</div>";
            endif;
            ?>
        </div>
    </div>
</body>
</html>