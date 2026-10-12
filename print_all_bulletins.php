<?php
require 'auth.php';
require 'db_connect.php';

if (!isset($_GET['month'])) { die("សូមជ្រើសរើសខែជាមុនសិន។"); }
$month = $_GET['month'];

// ទទួលថ្ងៃខែ និងបំប្លែង
$print_date = isset($_GET['print_date']) ? $_GET['print_date'] : date('Y-m-d');
$ts = strtotime($print_date);
$kh_months = ['01'=>'មករា','02'=>'កុម្ភៈ','03'=>'មីនា','04'=>'មេសា','05'=>'ឧសភា','06'=>'មិថុនា','07'=>'កក្កដា','08'=>'សីហា','09'=>'កញ្ញា','10'=>'តុលា','11'=>'វិច្ឆិកា','12'=>'ធ្នូ'];
$kh_num = ['0'=>'០','1'=>'១','2'=>'២','3'=>'៣','4'=>'៤','5'=>'៥','6'=>'៦','7'=>'៧','8'=>'៨','9'=>'៩'];
$display_date = "ថ្ងៃទី".strtr(date('d', $ts), $kh_num)." ខែ".$kh_months[date('m', $ts)]." ឆ្នាំ".strtr(date('Y', $ts), $kh_num);

$where = "";
$class_num = "3";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class_num = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class_num ";
}
$class_num_kh = strtr($class_num, $kh_num);

// ១. ទាញយកមុខវិជ្ជា
$subjects_list = [];
$sub_res = $conn->query("SELECT subject_name FROM subjects ORDER BY id ASC");
while($row = $sub_res->fetch_assoc()){ $subjects_list[] = $row['subject_name']; }
$num_subj = count($subjects_list);
if($num_subj == 0) $num_subj = 1;

// ២. ទាញយកពិន្ទុទាំងអស់
$scores_data = [];
$res_scores = $conn->query("SELECT student_id, subject_name, score_value FROM scores WHERE score_month = '$month'");
while($r = $res_scores->fetch_assoc()){
    $scores_data[$r['student_id']][$r['subject_name']] = $r['score_value'];
}

// ៣. ទាញយកសិស្ស និងគណនា
$students_list = [];
$st_res = $conn->query("SELECT id, khmer_name, grade_level FROM students $where");
while($st = $st_res->fetch_assoc()){
    $sid = $st['id'];
    $total = 0;
    foreach($subjects_list as $sj){
        $total += isset($scores_data[$sid][$sj]) ? $scores_data[$sid][$sj] : 0;
    }
    $st['total_score'] = $total;
    $st['avg_score'] = $total / $num_subj;
    $students_list[] = $st;
}
usort($students_list, function($a, $b) { return $b['total_score'] <=> $a['total_score']; });

if (count($students_list) == 0) { die("រកមិនឃើញទិន្នន័យសិស្សទេ។"); }

function getGrade($score) {
    if ($score >= 9) return 'A'; if ($score >= 8) return 'B';
    if ($score >= 7) return 'C'; if ($score >= 5) return 'D';
    if ($score == 0) return ''; return 'F';
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បោះពុម្ពព្រឹត្តិបត្តិរួម - ខែ<?php echo $month; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Khmer+OS+Muol+Light&family=Khmer+OS+Siemreap&display=swap');
        @page { size: A4 portrait; margin: 1cm; }
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #525659; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .page-break { page-break-after: always; }
        .a4-paper { background: white; width: 21cm; min-height: 29.7cm; padding: 1.5cm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.5); position: relative; margin-bottom: 20px;}
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; align-items: flex-start; }
        .header-left { text-align: left; color: #000080; font-family: 'Khmer OS Muol Light', cursive; font-size: 14px; line-height: 1.6; margin-top: 40px; }
        .header-right { text-align: center; color: #000080; font-family: 'Khmer OS Muol Light', cursive; font-size: 16px; line-height: 1.5; margin-top: -15px; }
        .title-box { text-align: center; margin: 10px 0 25px 0; }
        .title { font-family: 'Khmer OS Muol Light', cursive; color: #b8860b; font-size: 20px; margin-bottom: 5px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .student-name { font-family: 'Khmer OS Muol Light', cursive; color: #cc0000; font-size: 18px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 13px; }
        th, td { border: 1px solid #000080; padding: 5px; text-align: center; }
        th { background-color: #d1e0e0; color: #b8860b; font-family: 'Khmer OS Muol Light', cursive; font-weight: normal; }
        td.subj { text-align: left; padding-left: 10px; color: #000080; font-weight: 500; }
        td.num { color: #000080; }
        td.red-txt { color: #cc0000; font-weight: bold; }
        .footer-sig { display: flex; justify-content: space-between; margin-top: 30px; }
        .sig-box { text-align: center; color: #000080; font-size: 14px; line-height: 1.6; }
        .date-txt { font-weight: bold; margin-bottom: 15px; }
        @media print { body { background: white; padding: 0; display: block; } .a4-paper { box-shadow: none; width: 100%; min-height: auto; padding: 0; margin: 0; } html, body { height: 100%; } }
    </style>
</head>
<body>

<?php foreach($students_list as $data): $sid = $data['id']; ?>
<div class="a4-paper page-break">
    <div class="header">
        <div class="header-left">
            ការិយាល័យអប់រំ យុវជន និងកីឡាស្រុកអូររាំងឪ<br>
            សាលាបឋមសិក្សាជ្រៃតាសូ<br>
            ថ្នាក់ទី<?php echo htmlspecialchars($data['grade_level'] ?? '៣'); ?> (ក)
        </div>
        <div class="header-right">
            ព្រះរាជាណាចក្រកម្ពុជា<br>ជាតិ សាសនា ព្រះមហាក្សត្រ<br><span style="font-size: 14px;">---- ⧫ ----</span>
        </div>
    </div>
    <div class="title-box">
        <div class="title">ព្រឹត្តិបត្តិពិន្ទុប្រចាំខែ <?php echo $month; ?></div>
        <div class="student-name">សិស្សឈ្មោះ៖ <?php echo $data['khmer_name']; ?></div>
    </div>
    <table>
        <tr><th width="10%">លរ</th><th width="40%">មុខវិជ្ជា</th><th width="15%">ពិន្ទុ</th><th width="20%">ចំណាត់ថ្នាក់</th><th width="15%">និទ្ទេស</th></tr>
        
        <?php
        // បង្កើតជួរដេក (Rows) តាមមុខវិជ្ជាស្វ័យប្រវត្តិ
        foreach ($subjects_list as $index => $subj) {
            $score = isset($scores_data[$sid][$subj]) ? $scores_data[$sid][$subj] : 0;
            $grade = getGrade($score); 
            $display_score = ($score > 0) ? $score : ''; 
            echo "<tr><td class='num'>".($index+1)."</td><td class='subj'>{$subj}</td><td class='num'>$display_score</td><td class='red-txt'></td><td class='red-txt'>$grade</td></tr>";
        }
        $last_index = count($subjects_list);
        ?>
        
        <tr><td class='num'><?php echo $last_index+1; ?></td><td class='subj'>ពិន្ទុសរុប</td><td class='num'><?php echo ($data['total_score']>0)?$data['total_score']:''; ?></td><td></td><td></td></tr>
        <tr><td class='num'><?php echo $last_index+2; ?></td><td class='subj text-danger' style='color:#cc0000; font-weight:bold;'>មធ្យមភាគប្រឡង</td><td class='num'><?php echo ($data['avg_score']>0)?round($data['avg_score'],2):''; ?></td><td class='red-txt'></td><td class='red-txt'><?php echo ($data['avg_score']>0)?getGrade($data['avg_score']):''; ?></td></tr>
        <tr><td class='num red-txt'><?php echo $last_index+3; ?></td><td class='subj red-txt'>ចំនួនអវត្តមាន</td><td class='red-txt'>មានច្បាប់ ០ដង</td><td class='red-txt'>អត់ច្បាប់ ០ដង</td><td class='red-txt'>អវត្តមានសរុប ០ដង</td></tr>
    </table>
    <div class="footer-sig">
        <div class="sig-box">បានឃើញ និងឯកភាព<br><span style="color:#cc0000; font-weight:bold; font-family: 'Khmer OS Muol Light', cursive;">នាយកសាលា</span><br><br><br><br></div>
        <div class="sig-box" style="text-align: center;">មតិយោបល់អាណាព្យាបាលសិស្ស<br>...................................................................<br>ហត្ថលេខា................................................<br>ឈ្មោះ .......................................................</div>
        <div class="sig-box">
            <div class="date-txt" contenteditable="true">ជ្រៃតាសូ, <?php echo $display_date; ?></div>
            <div style="font-family: 'Khmer OS Muol Light', cursive;">គ្រូបន្ទុកថ្នាក់ទី <?php echo $class_num_kh; ?></div><br><br><br><br>
            <b><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'ពេញ ចំរើន'; ?></b>
        </div>
    </div>
</div>
<?php endforeach; ?>
<script>window.onload = function() { window.print(); }</script>
</body>
</html>