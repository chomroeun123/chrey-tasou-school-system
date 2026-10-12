<?php
require 'auth.php';
require 'db_connect.php';

$report_type = isset($_GET['type']) ? $_GET['type'] : 'sem1';
$title_kh = "ឆមាសទី១";
if ($report_type == 'sem2') $title_kh = "ឆមាសទី២";
else if ($report_type == 'yearly') $title_kh = "ប្រចាំឆ្នាំ";

$print_date = isset($_GET['print_date']) ? $_GET['print_date'] : date('Y-m-d');
$ts = strtotime($print_date);
$kh_months = ['01'=>'មករា','02'=>'កុម្ភៈ','03'=>'មីនា','04'=>'មេសា','05'=>'ឧសភា','06'=>'មិថុនា','07'=>'កក្កដា','08'=>'សីហា','09'=>'កញ្ញា','10'=>'តុលា','11'=>'វិច្ឆិកា','12'=>'ធ្នូ'];
$kh_num = ['0'=>'០','1'=>'១','2'=>'២','3'=>'៣','4'=>'៤','5'=>'៥','6'=>'៦','7'=>'៧','8'=>'៨','9'=>'៩'];
$display_date = "ថ្ងៃទី".strtr(date('d', $ts), $kh_num)." ខែ".$kh_months[date('m', $ts)]." ឆ្នាំ".strtr(date('Y', $ts), $kh_num);

$class_num = "3";
$where = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class_num = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class_num ";
}
$class_num_kh = strtr($class_num, $kh_num);

// មុខវិជ្ជា
$subjects_list = [];
$sub_res = $conn->query("SELECT subject_name FROM subjects ORDER BY id ASC");
while ($row = $sub_res->fetch_assoc()) $subjects_list[] = $row['subject_name'];
$num_subj = count($subjects_list) ?: 1;

// ទាញយកពិន្ទុ
$scores_data = [];
$res_scores = $conn->query("SELECT student_id, subject_name, score_month, score_value FROM scores");
while($r = $res_scores->fetch_assoc()){
    $scores_data[$r['student_id']][$r['subject_name']][$r['score_month']] = $r['score_value'];
}

// គណនា
$students = [];
$result_students = $conn->query("SELECT id, khmer_name, gender, grade_level FROM students $where ORDER BY khmer_name ASC");
$total_s = 0; $total_f = 0; $pass_s = 0; $pass_f = 0;

if ($result_students->num_rows > 0) {
    while($stu = $result_students->fetch_assoc()) {
        $sid = $stu['id'];
        $total_score = 0;
        
        foreach($subjects_list as $subj) {
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
        
        $avg_score = $total_score / $num_subj;
        $stu['total_score'] = $total_score;
        $stu['average_score'] = $avg_score;
        $students[] = $stu;
    }
}

// រៀបចំណាត់ថ្នាក់
usort($students, function($a, $b) { return $b['total_score'] <=> $a['total_score']; });

$final_students = [];
$rank = 1; $prev_score = -1; $actual_rank = 1;
foreach($students as $row) {
    if ($prev_score != -1 && $row['total_score'] < $prev_score) $actual_rank = $rank;
    $row['rank'] = ($row['total_score'] > 0) ? $actual_rank : '-';
    
    $avg = $row['average_score'];
    if ($avg >= 9) $grade = 'A';
    else if ($avg >= 8) $grade = 'B';
    else if ($avg >= 7) $grade = 'C';
    else if ($avg >= 5) $grade = 'D';
    else if ($avg > 0) $grade = 'E';
    else $grade = '-';
    $row['grade'] = $grade;

    $total_s++;
    if ($row['gender'] == 'ស្រី') $total_f++;
    if ($avg >= 5.0) { $pass_s++; if ($row['gender'] == 'ស្រី') $pass_f++; }
    
    $final_students[] = $row;
    $prev_score = $row['total_score']; $rank++;
}

$perc_s = ($total_s > 0) ? round(($pass_s / $total_s) * 100) : 0;
$perc_f = ($total_f > 0) ? round(($pass_f / $total_f) * 100) : 0;

$half = ceil(count($final_students) / 2);
$rows_per_col = max($half, 19);
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ចំណាត់ថ្នាក់ <?php echo $title_kh; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Khmer+OS+Muol+Light&family=Khmer+OS+Battambang&display=swap');
        @page { size: A4 portrait; margin: 1cm; }
        body { font-family: 'Khmer OS Battambang', sans-serif; background-color: #525659; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .a4-paper { background: white; width: 21cm; min-height: 29.7cm; padding: 1.5cm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.5); position: relative; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; align-items: flex-start; }
        .header-left { text-align: left; color: #000080; font-family: 'Khmer OS Muol Light', cursive; font-size: 13px; line-height: 1.6; margin-top: 20px; }
        .header-right { text-align: center; color: #000080; font-family: 'Khmer OS Muol Light', cursive; font-size: 14px; line-height: 1.5; margin-top: -10px; }
        .title { text-align: center; font-family: 'Khmer OS Muol Light', cursive; color: #cc0000; font-size: 18px; margin-bottom: 15px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        table.main-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 10px;}
        table.main-table th, table.main-table td { border: 1px solid #000; padding: 4px; text-align: center; vertical-align: middle; }
        table.main-table th { background-color: #ffff00; font-family: 'Khmer OS Muol Light', cursive; font-weight: normal; }
        .vert-text { writing-mode: vertical-rl; transform: rotate(180deg); padding: 5px 0; margin: auto; }
        td.name-col { text-align: left; padding-left: 5px; white-space: nowrap; }
        td.red-txt { color: #cc0000; font-weight: bold; }
        table.stats-table { width: 48%; border-collapse: collapse; float: left; font-size: 11px; color: white;}
        table.stats-table td { border: 1px solid #fff; padding: 4px 8px; font-family: 'Khmer OS Battambang', sans-serif;}
        .bg-navy { background-color: #002060; }
        .bg-red { background-color: #ff0000; }
        .footer-sig { float: right; width: 45%; text-align: center; font-size: 12px; line-height: 1.6; color: #000080;}
        .date-txt { margin-bottom: 10px; }
        .clearfix::after { content: ""; clear: both; display: table; }
        @media print { body { background: white; padding: 0; } .a4-paper { box-shadow: none; width: 100%; min-height: auto; padding: 0; } }
    </style>
</head>
<body>
<div class="a4-paper">
    <div class="header">
        <div class="header-left">
            សាលាបឋមសិក្សា ជ្រៃតាសូ<br>
            ថ្នាក់ទី <?php echo $class_num_kh; ?> "ក"
        </div>
        <div class="header-right">
            ព្រះរាជាណាចក្រកម្ពុជា<br>ជាតិ សាសនា ព្រះមហាក្សត្រ<br><span style="font-size: 12px;">---- ⧫ ----</span>
        </div>
    </div>
    <div class="title">តារាងចំណាត់ថ្នាក់ លទ្ធផល<?php echo $title_kh; ?></div>

    <table class="main-table">
        <thead>
            <tr>
                <th width="3%">ល.រ</th><th width="20%">គោត្តនាម និងនាម</th><th width="5%">ភេទ</th>
                <th width="5%"><div class="vert-text">ពិន្ទុសរុប</div></th><th width="5%"><div class="vert-text">មធ្យមភាគ</div></th>
                <th width="5%"><div class="vert-text">ចំណាត់ថ្នាក់</div></th><th width="5%"><div class="vert-text">និទ្ទេស</div></th>
                <th width="3%">ល.រ</th><th width="20%">គោត្តនាម និងនាម</th><th width="5%">ភេទ</th>
                <th width="5%"><div class="vert-text">ពិន្ទុសរុប</div></th><th width="5%"><div class="vert-text">មធ្យមភាគ</div></th>
                <th width="5%"><div class="vert-text">ចំណាត់ថ្នាក់</div></th><th width="5%"><div class="vert-text">និទ្ទេស</div></th>
            </tr>
        </thead>
        <tbody>
            <?php for($i=0; $i<$rows_per_col; $i++): ?>
            <tr>
                <?php if(isset($final_students[$i])): $s = $final_students[$i]; ?>
                    <td><?= $i+1 ?></td><td class="name-col"><?= $s['khmer_name'] ?></td><td><?= $s['gender'] ?></td>
                    <td><?= ($s['total_score'] > 0) ? number_format($s['total_score'], 2) : '-' ?></td>
                    <td><?= ($s['average_score'] > 0) ? number_format($s['average_score'], 2) : '-' ?></td>
                    <td class="red-txt"><?= $s['rank'] ?></td><td class="red-txt"><?= $s['grade'] ?></td>
                <?php else: ?>
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                <?php endif; ?>

                <?php $right_idx = $i + $rows_per_col; ?>
                <?php if(isset($final_students[$right_idx])): $s = $final_students[$right_idx]; ?>
                    <td><?= $right_idx+1 ?></td><td class="name-col"><?= $s['khmer_name'] ?></td><td><?= $s['gender'] ?></td>
                    <td><?= ($s['total_score'] > 0) ? number_format($s['total_score'], 2) : '-' ?></td>
                    <td><?= ($s['average_score'] > 0) ? number_format($s['average_score'], 2) : '-' ?></td>
                    <td class="red-txt"><?= $s['rank'] ?></td><td class="red-txt"><?= $s['grade'] ?></td>
                <?php else: ?>
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                <?php endif; ?>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="clearfix mt-2">
        <table class="stats-table">
            <tr>
                <td class="bg-navy">សិស្សសរុបចំនួន <?= $total_s ?> នាក់</td>
                <td class="bg-navy">ជាប់សរុបចំនួន <?= $pass_s ?> នាក់</td>
                <td class="bg-navy text-center"><?= $perc_s ?>%</td>
            </tr>
            <tr>
                <td class="bg-red">សិស្សស្រីសរុបចំនួន <?= $total_f ?> នាក់</td>
                <td class="bg-red">ជាប់ចំនួន <?= $pass_f ?> នាក់</td>
                <td class="bg-red text-center"><?= $perc_f ?>%</td>
            </tr>
            <tr>
                <td colspan="3" style="color: #000080; background: white; text-align: center; border-color: white; padding-top: 10px;">
                    បានឃើញ និងឯកភាព<br><span style="font-family: 'Khmer OS Muol Light', cursive; font-size: 13px;">នាយកសាលា</span><br><br><br><br>
                </td>
            </tr>
        </table>
        <div class="footer-sig">
            <div class="date-txt" contenteditable="true">ជ្រៃតាសូ, <?php echo $display_date; ?></div>
            <div style="font-family: 'Khmer OS Muol Light', cursive;">គ្រូបន្ទុកថ្នាក់ទី <?php echo $class_num_kh; ?></div><br><br><br><br>
            <b style="font-size: 14px;"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'ពេញ ចំរើន'; ?></b>
        </div>
    </div>
</div>
<script>window.onload = function() { window.print(); }</script>
</body>
</html>