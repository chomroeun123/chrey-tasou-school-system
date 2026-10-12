<?php
require 'auth.php';
require 'db_connect.php';

$report_type = isset($_GET['type']) ? $_GET['type'] : 'month';
$selected_month = isset($_GET['month']) ? $_GET['month'] : 'តុលា';

// កំណត់ចំណងជើង និងពណ៌បៃតងដូចរូបគម្រូ
$title_kh = "";
if ($report_type == 'month') $title_kh = "លទ្ធផលប្រឡងប្រចាំខែ " . $selected_month;
else if ($report_type == 'sem1') $title_kh = "លទ្ធផលប្រឡងប្រចាំឆមាសទី១";
else if ($report_type == 'sem2') $title_kh = "លទ្ធផលប្រឡងប្រចាំឆមាសទី២";
else if ($report_type == 'yearly') $title_kh = "លទ្ធផលប្រឡងប្រចាំឆ្នាំ";

$where = ""; $class_num = "៣";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class_num = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class_num ";
}
$kh_num = ['0'=>'០','1'=>'១','2'=>'២','3'=>'៣','4'=>'៤','5'=>'៥','6'=>'៦','7'=>'៧','8'=>'៨','9'=>'៩'];
$class_num_kh = strtr($class_num, $kh_num);

$subjects_list = [];
$sub_res = $conn->query("SELECT subject_name FROM subjects ORDER BY id ASC");
while ($row = $sub_res->fetch_assoc()) $subjects_list[] = $row['subject_name'];
$num_subj = count($subjects_list) ?: 1;

$scores_data = [];
$res_scores = $conn->query("SELECT student_id, subject_name, score_month, score_value FROM scores");
while($r = $res_scores->fetch_assoc()){
    if ($report_type == 'month') {
        if ($r['score_month'] == $selected_month) $scores_data[$r['student_id']][$r['subject_name']] = $r['score_value'];
    } else {
        $scores_data[$r['student_id']][$r['subject_name']][$r['score_month']] = $r['score_value'];
    }
}

$students = [];
$st_res = $conn->query("SELECT id, khmer_name, gender FROM students $where");
while($stu = $st_res->fetch_assoc()) {
    $sid = $stu['id'];
    $total_score = 0;
    
    foreach($subjects_list as $subj) {
        if ($report_type == 'month') {
            $total_score += isset($scores_data[$sid][$subj]) ? (float)$scores_data[$sid][$subj] : 0;
        } else {
            $m12 = $scores_data[$sid][$subj]['ធ្នូ'] ?? 0; $m1 = $scores_data[$subj]['មករា'] ?? 0; $m2 = $scores_data[$subj]['កុម្ភៈ'] ?? 0; $exam1 = $scores_data[$subj]['មីនា'] ?? 0;
            $m5 = $scores_data[$subj]['ឧសភា'] ?? 0; $m6 = $scores_data[$subj]['មិថុនា'] ?? 0; $m7 = $scores_data[$subj]['កក្កដា'] ?? 0; $exam2 = $scores_data[$subj]['សីហា'] ?? 0;
            
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

usort($students, function($a, $b) { return $b['total_score'] <=> $a['total_score']; });
$top_students = array_slice($students, 0, 5); 

// បំពេញចន្លោះឱ្យគ្រប់ ៥ បើថ្នាក់មានសិស្សតិច ឬពិន្ទុអត់ទាន់មាន
for($i = count($top_students); $i < 5; $i++) {
    $top_students[] = ['khmer_name' => '.........................', 'total_score' => 0, 'avg_score' => 0];
}

function getGrade($score) {
    if ($score >= 9) return 'A'; if ($score >= 8) return 'B';
    if ($score >= 7) return 'C'; if ($score >= 5) return 'D';
    if ($score > 0) return 'E'; return '-';
}

// Function សម្រាប់បង្កើតកាតសិស្ស
function createStudentCard($rank_num, $student) {
    $kh_num = ['1'=>'១', '2'=>'២', '3'=>'៣', '4'=>'៤', '5'=>'៥'];
    $rank_kh = $kh_num[(string)$rank_num];
    $name = $student['khmer_name'];
    $score = ($student['total_score'] > 0) ? round($student['total_score'], 2) : '-';
    $grade = ($student['avg_score'] > 0) ? getGrade($student['avg_score']) : '-';
    
    // ប្តូរពណ៌ Badge តាមចំណាត់ថ្នាក់
    $badge_color = "#e74c3c"; // ក្រហមលំនាំដើម
    if($rank_num == 1) $badge_color = "#e74c3c"; // ក្រហម
    if($rank_num == 2) $badge_color = "#d35400"; // ទឹកក្រូច
    if($rank_num == 3) $badge_color = "#27ae60"; // បៃតង
    if($rank_num == 4) $badge_color = "#8e44ad"; // ស្វាយ
    if($rank_num == 5) $badge_color = "#2980b9"; // ខៀវ

    return "
    <div class='student-card'>
        <div class='photo-frame'>
            <div class='photo-placeholder'>
                <div style='font-size:11px; color:#555;'>ពិន្ទុ</div>
                <div style='font-size:16px; font-weight:bold; color:#cc0000;'>$score</div>
                <div style='font-size:11px; color:#555; margin-top:3px;'>និទ្ទេស</div>
                <div style='font-size:16px; font-weight:bold; color:#cc0000;'>$grade</div>
            </div>
        </div>
        <div class='banner-wrapper'>
            <div class='rank-badge' style='background-color: $badge_color;'>$rank_kh</div>
            <div class='banner-bg'>$name</div>
            <div class='ribbon-tail left'></div>
            <div class='ribbon-tail right'></div>
        </div>
    </div>
    ";
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>តារាងកិត្តិយសសិស្ស</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Khmer+OS+Muol+Light&family=Khmer+OS+Battambang&display=swap');
        
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Khmer OS Battambang', sans-serif; background-color: #525659; margin: 0; display: flex; justify-content: center; padding: 20px;}
        
        .a4-paper { 
            background: white; 
            width: 21cm; 
            min-height: 29.7cm; 
            box-sizing: border-box; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5); 
            position: relative; 
            padding: 1.5cm 1cm;
            
            /* រចនាស៊ុមពណ៌មាស ២ជាន់ */
            border: 8px solid #d4af37;
            outline: 2px solid #b8860b;
            outline-offset: -12px;
        }
        
        /* ក្បាលរដ្ឋបាល */
        .header-center { text-align: center; color: #000080; font-family: 'Khmer OS Muol Light', cursive; font-size: 14px; line-height: 1.5; margin-bottom: 5px;}
        
        .header-left { position: absolute; left: 1.5cm; top: 2.5cm; text-align: left; color: #000080; font-family: 'Khmer OS Muol Light', cursive; font-size: 13px; line-height: 1.7; }
        
        /* ចំណងជើងធំ (ពណ៌បៃតងដូចគម្រូ) ត្រូវបានទាញឡើងលើ */
        .title-box { text-align: center; margin-top: 2cm; margin-bottom: 20px; }
        .title-main { font-family: 'Khmer OS Muol Light', cursive; font-size: 30px; color: #27ae60; margin-bottom: 5px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .title-sub { font-family: 'Khmer OS Muol Light', cursive; font-size: 16px; color: #27ae60; line-height: 1.6;}
        
        /* ===== រចនា Layout ពីរ៉ាមីត សម្រាប់សិស្ស ៥ នាក់ ===== */
        .tree-container { display: flex; flex-direction: column; align-items: center; width: 100%; margin-top: 10px; }
        .tree-row { display: flex; justify-content: center; width: 100%; margin-bottom: 25px; } /* បន្ថយគម្លាតជួរ */
        .tree-row-2 { gap: 40px; } 
        .tree-row-3 { gap: 60px; }
        
        /* ===== រចនាកាតសិស្សម្នាក់ៗ ===== */
        .student-card { display: flex; flex-direction: column; align-items: center; width: 220px; position: relative;}
        
        /* រង្វង់រូបថត */
        .photo-frame {
            width: 95px; height: 95px; /* បន្ថយទំហំបន្តិចកុំឱ្យវាស៊ីកម្ពស់ពេក */
            border-radius: 50%;
            border: 5px solid #d4af37;
            background-color: #fff;
            box-shadow: inset 0 0 0 2px #fff, inset 0 0 0 4px #b8860b, 0 4px 8px rgba(0,0,0,0.2);
            display: flex; align-items: center; justify-content: center;
            position: relative; z-index: 2;
            margin-bottom: -15px;
        }
        .photo-placeholder { text-align: center; font-family: 'Khmer OS Battambang', sans-serif; line-height: 1; }
        
        /* ខ្សែបូឈ្មោះ (Banner) */
        .banner-wrapper { position: relative; width: 190px; z-index: 1;}
        .banner-bg {
            background: linear-gradient(180deg, #f1c40f 0%, #d35400 100%);
            color: white;
            font-family: 'Khmer OS Muol Light', cursive;
            font-size: 13px;
            text-align: center;
            padding: 8px 10px 8px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            border: 1px solid #b8860b;
            min-height: 20px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        
        /* លេខចំណាត់ថ្នាក់ (Badge) */
        .rank-badge {
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px; height: 40px;
            border-radius: 50%;
            border: 3px solid #f1c40f;
            color: #fff;
            font-family: 'Khmer OS Muol Light', cursive;
            font-size: 18px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.4);
            z-index: 3;
        }
        
        .ribbon-tail { position: absolute; bottom: -10px; width: 20px; height: 30px; background: #b8860b; z-index: -1; }
        .ribbon-tail.left { left: 10px; clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%); }
        .ribbon-tail.right { right: 10px; clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%); }

        /* ===== ហត្ថលេខា នាយក និង គ្រូ ===== */
        .footer-container { 
            position: absolute; 
            bottom: 2cm; 
            left: 2.5cm; 
            right: 2.5cm; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-end;
        }
        
        .sig-left { text-align: center; }
        .sig-right { text-align: center; }
        
        .date-text {
            font-family: 'Khmer OS Battambang', sans-serif; 
            font-size: 13px; 
            color: #000; 
            margin-bottom: 5px; 
            outline: none;
            border-bottom: 1px dotted #27ae60;
            padding: 0 5px;
        }
        
        @media print { body { background: white; padding: 0; } .a4-paper { box-shadow: none; width: 100%; min-height: auto; margin: 0; } }
    </style>
</head>
<body>

<div class="a4-paper">
    
    <!-- ក្បាលរដ្ឋបាលកណ្តាល -->
    <div class="header-center">
        ព្រះរាជាណាចក្រកម្ពុជា<br>
        ជាតិ សាសនា ព្រះមហាក្សត្រ<br>
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Cambodian_Exquisite_Ornament.svg/512px-Cambodian_Exquisite_Ornament.svg.png" width="100" style="margin-top: 2px; opacity: 0.8;" alt="ornament">
    </div>

    <!-- ក្បាលរដ្ឋបាលឆ្វេង -->
    <div class="header-left">
        ការិយាល័យអប់រំ យុវជន និងកីឡាស្រុកអូររាំងឪ<br>
        ក្រុមបច្ចេកទេសកម្រិតទាប<br>
        សាលាបឋមសិក្សា ជ្រៃតាសូ<br>
        ថ្នាក់ទី "<b><?php echo $class_num_kh; ?></b>" ក
    </div>

    <!-- ចំណងជើងពណ៌បៃតង -->
    <div class="title-box">
        <div class="title-main">តារាងកិត្តិយស</div>
        <div class="title-sub">
            <?php echo $title_kh; ?><br>
            ថ្នាក់ទី "<?php echo $class_num_kh; ?>" ក ឆ្នាំសិក្សា ២០២៦ - ២០២៧
        </div>
    </div>

    <!-- ជួរតារាងកិត្តិយស (Layout ពីរ៉ាមីត) -->
    <div class="tree-container">
        <!-- ជួរទី១៖ លេខ ១ -->
        <div class="tree-row">
            <?php echo createStudentCard(1, $top_students[0]); ?>
        </div>
        
        <!-- ជួរទី២៖ លេខ ២ និង លេខ ៣ -->
        <div class="tree-row tree-row-2">
            <?php echo createStudentCard(2, $top_students[1]); ?>
            <?php echo createStudentCard(3, $top_students[2]); ?>
        </div>
        
        <!-- ជួរទី៣៖ លេខ ៤ និង លេខ ៥ -->
        <div class="tree-row tree-row-3">
            <?php echo createStudentCard(4, $top_students[3]); ?>
            <?php echo createStudentCard(5, $top_students[4]); ?>
        </div>
    </div>

    <!-- ហត្ថលេខា នាយក និង គ្រូ -->
    <div class="footer-container">
        <!-- ខាងឆ្វេង៖ នាយកសាលា -->
        <div class="sig-left">
            <div style="font-family: 'Khmer OS Battambang', sans-serif; font-size: 13px; color: #000; margin-bottom: 5px;">បានឃើញ និងឯកភាព</div>
            <div style="font-family: 'Khmer OS Muol Light', cursive; font-size: 14px; color: #27ae60;">នាយកសាលា</div>
            <br><br><br><br>
            <div style="border-bottom: 1.5px dotted #000; width: 120px; margin: 0 auto; outline: none;" contenteditable="true"></div>
        </div>
        
        <!-- ខាងស្តាំ៖ គ្រូបន្ទុកថ្នាក់ -->
        <div class="sig-right">
            <div class="date-text" contenteditable="true">ធ្វើនៅជ្រៃតាសូ, ថ្ងៃទី........ខែ..............ឆ្នាំ២០២....</div>
            <div style="font-family: 'Khmer OS Muol Light', cursive; font-size: 14px; color: #27ae60; margin-top: 5px;">គ្រូបន្ទុកថ្នាក់</div><br><br><br>
            <span style="font-family: 'Khmer OS Battambang', sans-serif; font-weight: bold; font-size: 14px; color: #27ae60;">
                <?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'ពេញ ចំរើន'; ?>
            </span>
        </div>
    </div>

</div>

<script>
    // window.onload = function() { window.print(); }
</script>

</body>
</html>