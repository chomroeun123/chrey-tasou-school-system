<?php
require 'auth.php';
require 'db_connect.php';
$selected_month = isset($_GET['month']) ? $_GET['month'] : 'តុលា';
$print_date = isset($_GET['print_date']) ? $_GET['print_date'] : date('Y-m-d');

// ច្រោះសិស្សតាមថ្នាក់
$where = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class ";
}

// ១. ទាញយកបញ្ជីមុខវិជ្ជាពី Database
$subjects_list = [];
$sub_res = $conn->query("SELECT subject_name FROM subjects ORDER BY id ASC");
while ($row = $sub_res->fetch_assoc()) {
    $subjects_list[] = $row['subject_name'];
}
$num_subj = count($subjects_list);
if ($num_subj == 0) $num_subj = 1; // ការពារកុំឱ្យ Error ចែកនឹងសូន្យ

// ២. ទាញយកពិន្ទុទាំងអស់ប្រចាំខែ
$scores_arr = [];
$sc_res = $conn->query("SELECT student_id, subject_name, score_value FROM scores WHERE score_month = '$selected_month'");
while ($sc_row = $sc_res->fetch_assoc()) {
    $scores_arr[$sc_row['student_id']][$sc_row['subject_name']] = $sc_row['score_value'];
}

// ៣. ទាញយកសិស្ស និងគណនាពិន្ទុ
$students_arr = [];
$st_res = $conn->query("SELECT id, student_code, khmer_name, gender, grade_level FROM students $where ORDER BY khmer_name ASC");
while ($st_row = $st_res->fetch_assoc()) {
    $sid = $st_row['id'];
    $total = 0;
    
    foreach ($subjects_list as $subj) {
        if (isset($scores_arr[$sid][$subj])) {
            $total += (float)$scores_arr[$sid][$subj];
        }
    }
    
    $st_row['total_score'] = $total;
    $st_row['average_score'] = $total / $num_subj;
    $students_arr[] = $st_row;
}

// ៤. តម្រៀបចំណាត់ថ្នាក់តាមពិន្ទុសរុប
usort($students_arr, function($a, $b) {
    return $b['total_score'] <=> $a['total_score'];
});
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ចំណាត់ថ្នាក់ពិន្ទុ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f8f9fa; }
        .container-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top:20px;}
        th { background-color: #198754 !important; color: white !important; text-align: center; vertical-align: middle;}
        td { text-align: center; vertical-align: middle; }
        .rank-1 { background-color: #ffd700 !important; font-weight: bold; }
        .rank-2 { background-color: #c0c0c0 !important; font-weight: bold; }
        .rank-3 { background-color: #cd7f32 !important; font-weight: bold; }
        .vert-text { writing-mode: vertical-rl; transform: rotate(180deg); padding: 5px 0; margin: auto; font-size: 12px; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid px-4 container-box mb-5">
        <h4 class="text-success mb-4 fw-bold">តារាងពិន្ទុ និងចំណាត់ថ្នាក់សិស្សប្រចាំខែ <?php echo $selected_month; ?> <?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') ? '(ថ្នាក់ទី '.$_SESSION['assigned_class'].')' : ''; ?></h4>
        
        <form action="view_scores.php" method="GET" class="mb-4 d-flex align-items-center flex-wrap gap-2" style="max-width: 800px;">
            <select name="month" class="form-select" style="width: auto;">
                <?php 
                $months = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
                foreach($months as $m) { $s=($m==$selected_month)?'selected':''; echo "<option value='$m' $s>$m</option>"; }
                ?>
            </select>
            <button type="submit" class="btn btn-success text-nowrap">បង្ហាញទិន្នន័យ</button>
            <input type="date" name="print_date" class="form-control text-primary fw-bold" value="<?php echo $print_date; ?>" style="width: auto;" required>
            
            <button type="submit" formaction="print_all_bulletins.php" formtarget="_blank" class="btn btn-primary fw-bold text-nowrap">🖨️ ព្រឹត្តិបត្តិរួម</button>
            <button type="submit" formaction="print_monthly_ranking.php" formtarget="_blank" class="btn btn-warning fw-bold text-dark text-nowrap">🖨️ តារាងចំណាត់ថ្នាក់</button>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th rowspan="2" width="5%">លេខ</th>
                        <th rowspan="2" width="10%">អត្តលេខ</th>
                        <th rowspan="2" width="15%">ឈ្មោះសិស្ស</th>
                        <th rowspan="2" width="5%">ថ្នាក់</th>
                        <!-- បង្កើត Columns មុខវិជ្ជាដោយស្វ័យប្រវត្តិ -->
                        <th colspan="<?php echo count($subjects_list); ?>">មុខវិជ្ជា (<?php echo count($subjects_list); ?> មុខ)</th>
                        <th rowspan="2" width="8%">សរុប</th>
                        <th rowspan="2" width="8%">មធ្យមភាគ</th>
                        <th rowspan="2" width="5%">និទ្ទេស</th>
                        <th rowspan="2" width="8%">សកម្មភាព</th>
                    </tr>
                    <tr>
                        <?php foreach($subjects_list as $subj): ?>
                            <th><div class="vert-text"><?php echo $subj; ?></div></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($students_arr) > 0) {
                        $rank = 1; $prev_score = -1; $actual_rank = 1;
                        foreach($students_arr as $row) {
                            if ($prev_score != -1 && $row['total_score'] < $prev_score) $actual_rank = $rank;
                            $rank_class = '';
                            if ($actual_rank == 1 && $row['total_score'] > 0) $rank_class = 'rank-1';
                            else if ($actual_rank == 2 && $row['total_score'] > 0) $rank_class = 'rank-2';
                            else if ($actual_rank == 3 && $row['total_score'] > 0) $rank_class = 'rank-3';

                            $avg = $row['average_score']; $grade = '-';
                            if ($avg > 0) {
                                if ($avg >= 8.5) $grade = 'A'; else if ($avg >= 7.0) $grade = 'B';
                                else if ($avg >= 5.0) $grade = 'C'; else $grade = 'F';
                            }
                            
                            echo "<tr class='$rank_class'>";
                            echo "<td>".($row['total_score'] > 0 ? $actual_rank : '-')."</td>";
                            echo "<td><span class='badge bg-secondary'>{$row['student_code']}</span></td>";
                            echo "<td class='text-start fw-bold'>{$row['khmer_name']}</td>";
                            echo "<td>ទី {$row['grade_level']}</td>";
                            
                            // បង្ហាញពិន្ទុតាមមុខវិជ្ជា
                            foreach ($subjects_list as $subj) {
                                $s_val = isset($scores_arr[$row['id']][$subj]) ? $scores_arr[$row['id']][$subj] : '-';
                                echo "<td>{$s_val}</td>";
                            }
                            
                            echo "<td><b class='text-primary'>".($row['total_score'] > 0 ? round($row['total_score'],2) : '-')."</b></td>";
                            echo "<td><b>".($row['average_score'] > 0 ? round($row['average_score'],2) : '-')."</b></td>";
                            echo "<td>$grade</td>";
                            echo "<td><a href='print_bulletin.php?id={$row['id']}&month={$selected_month}&print_date={$print_date}' target='_blank' class='btn btn-sm btn-info text-white fw-bold'>🖨️ Print</a></td>";
                            echo "</tr>";
                            
                            $prev_score = $row['total_score']; $rank++;
                        }
                    } else { echo "<tr><td colspan='".(count($subjects_list)+9)."' class='text-center text-danger'>គ្មានទិន្នន័យ</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>