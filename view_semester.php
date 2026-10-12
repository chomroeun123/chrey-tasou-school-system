<?php
require 'auth.php';
require 'db_connect.php';

$report_type = isset($_GET['type']) ? $_GET['type'] : 'sem1';
$print_date = isset($_GET['print_date']) ? $_GET['print_date'] : date('Y-m-d'); // សម្រាប់ Print

// ១. ទាញយកបញ្ជីមុខវិជ្ជាពី Database (Dynamic)
$subjects_list = [];
$sub_res = $conn->query("SELECT subject_name FROM subjects ORDER BY id ASC");
while ($row = $sub_res->fetch_assoc()) {
    $subjects_list[] = $row['subject_name'];
}
$num_subj = count($subjects_list);
if ($num_subj == 0) $num_subj = 1;

// ២. ច្រោះសិស្សតាមថ្នាក់ បើអ្នក Login ជាគ្រូ
$where = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class ";
}

// ៣. ទាញយកពិន្ទុទាំងអស់មកទុកក្នុង PHP Array
$scores_data = [];
$res_scores = $conn->query("SELECT student_id, subject_name, score_month, score_value FROM scores");
while($r = $res_scores->fetch_assoc()){
    $scores_data[$r['student_id']][$r['subject_name']][$r['score_month']] = $r['score_value'];
}

// ៤. ទាញយកសិស្ស និងគណនា
$students_sql = "SELECT id, student_code, khmer_name, gender, grade_level FROM students $where ORDER BY khmer_name ASC";
$result_students = $conn->query($students_sql);

$final_results = [];

if ($result_students->num_rows > 0) {
    while($stu = $result_students->fetch_assoc()) {
        $sid = $stu['id'];
        $total_score = 0;
        
        foreach($subjects_list as $subj) {
            // ទាញពិន្ទុតាមខែ (ឆមាសទី១)
            $m12 = $scores_data[$sid][$subj]['ធ្នូ'] ?? 0;
            $m1 = $scores_data[$sid][$subj]['មករា'] ?? 0;
            $m2 = $scores_data[$sid][$subj]['កុម្ភៈ'] ?? 0;
            $exam1 = $scores_data[$sid][$subj]['មីនា'] ?? 0;
            
            // ទាញពិន្ទុតាមខែ (ឆមាសទី២)
            $m5 = $scores_data[$sid][$subj]['ឧសភា'] ?? 0;
            $m6 = $scores_data[$sid][$subj]['មិថុនា'] ?? 0;
            $m7 = $scores_data[$sid][$subj]['កក្កដា'] ?? 0;
            $exam2 = $scores_data[$sid][$subj]['សីហា'] ?? 0;
            
            // រូបមន្តគណនា
            $pre_sem1 = ($m12 + $m1 + $m2) / 3;
            $sem1_score = ($pre_sem1 + $exam1) / 2;
            
            $pre_sem2 = ($m5 + $m6 + $m7) / 3;
            $sem2_score = ($pre_sem2 + $exam2) / 2;
            
            $yearly_score = ($sem1_score + $sem2_score) / 2;
            
            // បូកបញ្ចូលគ្នាតាមប្រភេទរបាយការណ៍ និងរក្សាទុកពិន្ទុមុខវិជ្ជានីមួយៗ
            if ($report_type == 'sem1') {
                $subj_score = $sem1_score;
            } else if ($report_type == 'sem2') {
                $subj_score = $sem2_score;
            } else {
                $subj_score = $yearly_score;
            }
            
            $stu['subject_scores'][$subj] = $subj_score;
            $total_score += $subj_score;
        }
        
        $avg_score = $total_score / $num_subj;
        
        $stu['total_score'] = $total_score;
        $stu['avg_score'] = $avg_score;
        $final_results[] = $stu;
    }
}

// ៥. រៀបចំណាត់ថ្នាក់ពីធំទៅតូច
usort($final_results, function($a, $b) {
    return $b['total_score'] <=> $a['total_score'];
});

function getGrade($score) {
    if ($score >= 9) return 'A';
    if ($score >= 8) return 'B';
    if ($score >= 7) return 'C';
    if ($score >= 5) return 'D';
    if ($score > 0) return 'E';
    return '-';
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>លទ្ធផលឆមាស និងប្រចាំឆ្នាំ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }
        .container-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top:20px;}
        th { background-color: #6f42c1 !important; color: white !important; text-align: center; vertical-align: middle;}
        td { text-align: center; vertical-align: middle; }
        /* សម្រាប់អក្សរបញ្ឈរលើក្បាលតារាង */
        .vert-text { writing-mode: vertical-rl; transform: rotate(180deg); padding: 5px 0; margin: auto; font-size: 12px; }
        /* ពណ៌សម្រាប់ចំណាត់ថ្នាក់ 1,2,3 */
        .rank-1 { background-color: #ffd700 !important; font-weight: bold; }
        .rank-2 { background-color: #c0c0c0 !important; font-weight: bold; }
        .rank-3 { background-color: #cd7f32 !important; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid px-4 container-box mb-5">
        <h4 class="text-primary mb-4 fw-bold">
            🎓 លទ្ធផលឆមាស និងប្រចាំឆ្នាំ 
            <?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') ? '(ថ្នាក់ទី '.$_SESSION['assigned_class'].')' : ''; ?>
        </h4>
        
        <!-- កន្លែងរើសប្រភេទរបាយការណ៍ និងប៊ូតុង Print -->
        <form action="view_semester.php" method="GET" class="mb-4 d-flex align-items-center flex-wrap gap-2">
            
            <a href="view_semester.php?type=sem1" class="btn <?php echo $report_type=='sem1'?'btn-primary':'btn-outline-primary'; ?> me-2 fw-bold">លទ្ធផល ឆមាសទី១</a>
            <a href="view_semester.php?type=sem2" class="btn <?php echo $report_type=='sem2'?'btn-success':'btn-outline-success'; ?> me-2 fw-bold">លទ្ធផល ឆមាសទី២</a>
            <a href="view_semester.php?type=yearly" class="btn <?php echo $report_type=='yearly'?'btn-warning':'btn-outline-warning'; ?> me-4 fw-bold">លទ្ធផល ប្រចាំឆ្នាំ</a>
            
            <input type="hidden" name="type" value="<?php echo $report_type; ?>">
            <input type="date" name="print_date" class="form-control text-primary fw-bold" value="<?php echo $print_date; ?>" style="width: auto;" required title="ជ្រើសរើសថ្ងៃខែសម្រាប់បោះពុម្ព">
            
            <!-- ប៊ូតុង Print ដែលបញ្ជូនប្រភេទឆមាសទៅជាមួយ -->
            <button type="submit" formaction="print_semester_bulletins.php" formtarget="_blank" class="btn btn-primary fw-bold text-nowrap">🖨️ ព្រឹត្តិបត្តិរួម</button>
            <button type="submit" formaction="print_semester_ranking.php" formtarget="_blank" class="btn btn-warning fw-bold text-dark text-nowrap">🖨️ តារាងចំណាត់ថ្នាក់</button>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th rowspan="2" width="5%">ចំណាត់ថ្នាក់</th>
                        <th rowspan="2" width="10%">អត្តលេខ</th>
                        <th rowspan="2" width="15%">ឈ្មោះសិស្ស</th>
                        <th rowspan="2" width="5%">ភេទ</th>
                        <!-- បង្កើត Columns មុខវិជ្ជាដោយស្វ័យប្រវត្តិ -->
                        <th colspan="<?php echo count($subjects_list); ?>">មុខវិជ្ជា (<?php echo count($subjects_list); ?> មុខ)</th>
                        <th rowspan="2" width="8%">ពិន្ទុសរុប</th>
                        <th rowspan="2" width="8%">មធ្យមភាគ</th>
                        <th rowspan="2" width="5%">និទ្ទេស</th>
                    </tr>
                    <tr>
                        <?php foreach($subjects_list as $subj): ?>
                            <th><div class="vert-text"><?php echo $subj; ?></div></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($final_results) > 0) {
                        $rank = 1; $prev_score = -1; $actual_rank = 1;
                        foreach($final_results as $row) {
                            if ($prev_score != -1 && $row['total_score'] < $prev_score) $actual_rank = $rank;
                            
                            $rank_class = '';
                            if ($actual_rank == 1 && $row['total_score'] > 0) $rank_class = 'rank-1';
                            else if ($actual_rank == 2 && $row['total_score'] > 0) $rank_class = 'rank-2';
                            else if ($actual_rank == 3 && $row['total_score'] > 0) $rank_class = 'rank-3';
                            
                            $display_total = ($row['total_score'] > 0) ? round($row['total_score'], 2) : '-';
                            $display_avg = ($row['avg_score'] > 0) ? round($row['avg_score'], 2) : '-';
                            $grade = ($row['avg_score'] > 0) ? getGrade($row['avg_score']) : '-';
                            
                            echo "<tr class='$rank_class'>";
                            echo "<td><b class='text-danger'>".($row['total_score'] > 0 ? $actual_rank : '-')."</b></td>";
                            echo "<td><span class='badge bg-secondary'>{$row['student_code']}</span></td>";
                            echo "<td class='text-start fw-bold'>{$row['khmer_name']}</td>";
                            echo "<td>{$row['gender']}</td>";
                            
                            // បង្ហាញពិន្ទុតាមមុខវិជ្ជា
                            foreach ($subjects_list as $subj) {
                                $s_val = isset($row['subject_scores'][$subj]) && $row['subject_scores'][$subj] > 0 
                                         ? round($row['subject_scores'][$subj], 2) : '-';
                                echo "<td>{$s_val}</td>";
                            }
                            
                            echo "<td><b class='text-primary'>{$display_total}</b></td>";
                            echo "<td><b>{$display_avg}</b></td>";
                            echo "<td>$grade</td>";
                            echo "</tr>";
                            
                            $prev_score = $row['total_score']; $rank++;
                        }
                    } else {
                        echo "<tr><td colspan='".(count($subjects_list)+8)."' class='text-center text-danger'>គ្មានទិន្នន័យសិស្សក្នុងថ្នាក់នេះទេ</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>