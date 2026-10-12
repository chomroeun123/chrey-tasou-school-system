<?php
require 'auth.php';
require 'db_connect.php';

$attendance_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$ts = strtotime($attendance_date);
$kh_months = ['01'=>'មករា','02'=>'កុម្ភៈ','03'=>'មីនា','04'=>'មេសា','05'=>'ឧសភា','06'=>'មិថុនា','07'=>'កក្កដា','08'=>'សីហា','09'=>'កញ្ញា','10'=>'តុលា','11'=>'វិច្ឆិកា','12'=>'ធ្នូ'];
$kh_num = ['0'=>'០','1'=>'១','2'=>'២','3'=>'៣','4'=>'៤','5'=>'៥','6'=>'៦','7'=>'៧','8'=>'៨','9'=>'៩'];
$display_date = "ថ្ងៃទី".strtr(date('d', $ts), $kh_num)." ខែ".$kh_months[date('m', $ts)]." ឆ្នាំ".strtr(date('Y', $ts), $kh_num);

$where = ""; $class_num = "៣";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class_num = strtr($_SESSION['assigned_class'], $kh_num);
    $where = " WHERE grade_level = ".$_SESSION['assigned_class'];
}

$students = $conn->query("SELECT id, khmer_name, gender FROM students $where ORDER BY khmer_name ASC");

// ទាញយកវត្តមាន និង Notes
$existing_att = []; $existing_notes = [];
$att_result = $conn->query("SELECT student_id, status, notes FROM attendance WHERE attendance_date = '$attendance_date'");
if($att_result){
    while($row = $att_result->fetch_assoc()) {
        $existing_att[$row['student_id']] = $row['status'];
        $existing_notes[$row['student_id']] = $row['notes'];
    }
}

$total_s = $students->num_rows;
$present_c = 0; $leave_c = 0; $absent_c = 0;
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បញ្ជីស្រង់វត្តមាន - <?php echo $attendance_date; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Khmer+OS+Muol+Light&family=Khmer+OS+Battambang&display=swap');
        @page { size: A4 portrait; margin: 1cm; }
        body { font-family: 'Khmer OS Battambang', sans-serif; background-color: #525659; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .a4-paper { background: white; width: 21cm; min-height: 29.7cm; padding: 1cm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
        .header { display: flex; justify-content: space-between; margin-bottom: 15px; align-items: flex-start; }
        .header-left { text-align: left; color: #000; font-family: 'Khmer OS Muol Light', cursive; font-size: 13px; line-height: 1.6; }
        .header-right { text-align: center; color: #000; font-family: 'Khmer OS Muol Light', cursive; font-size: 14px; line-height: 1.5; }
        .title { text-align: center; font-family: 'Khmer OS Muol Light', cursive; font-size: 18px; margin-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; vertical-align: middle; }
        th { background-color: #f2f2f2; font-family: 'Khmer OS Muol Light', cursive; font-weight: normal; }
        td.name-col { text-align: left; padding-left: 10px; }
        td.note-col { text-align: left; padding-left: 5px; color: red; font-size: 11px;}
        
        .check-mark { font-family: sans-serif; font-weight: bold; font-size: 14px; color: #000; }
        .summary-box { border: 1px solid #000; padding: 8px; margin-bottom: 20px; font-weight: bold; font-size: 13px; display: flex; justify-content: space-around;}
        
        .footer-sig { display: flex; justify-content: space-between; margin-top: 10px; padding: 0 20px; }
        .sig-box { text-align: center; font-size: 13px; line-height: 1.6; color: #000; }
        @media print { body { background: white; padding: 0; } .a4-paper { box-shadow: none; width: 100%; min-height: auto; padding: 0; } }
    </style>
</head>
<body>

<div class="a4-paper">
    <div class="header">
        <div class="header-left">
            ការិយាល័យអប់រំ យុវជន និងកីឡាស្រុកអូររាំងឪ<br>
            សាលាបឋមសិក្សាជ្រៃតាសូ<br>
            ថ្នាក់ទី <b><?php echo $class_num; ?></b> (ក)
        </div>
        <div class="header-right">
            ព្រះរាជាណាចក្រកម្ពុជា<br>ជាតិ សាសនា ព្រះមហាក្សត្រ<br><span style="font-size: 12px;">---- ⧫ ----</span>
        </div>
    </div>

    <div class="title">បញ្ជីស្រង់វត្តមានសិស្សប្រចាំថ្ងៃ</div>
    <div style="text-align: center; margin-top: -10px; margin-bottom: 15px; font-weight: bold;">
        កាលបរិច្ឆេទ៖ <?php echo date('d-m-Y', strtotime($attendance_date)); ?>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" rowspan="2">ល.រ</th>
                <th width="25%" rowspan="2">គោត្តនាម និងនាម</th>
                <th width="7%" rowspan="2">ភេទ</th>
                <th colspan="3">ស្ថានភាពវត្តមាន</th>
                <th width="30%" rowspan="2">ផ្សេងៗ (ចេញមុន/មូលហេតុ)</th>
            </tr>
            <tr>
                <th width="11%">វត្តមាន</th>
                <th width="11%">ច្បាប់</th>
                <th width="11%">អវត្តមាន</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($students->num_rows > 0) {
                $i = 1;
                while($row = $students->fetch_assoc()) {
                    $sid = $row['id'];
                    $status = isset($existing_att[$sid]) ? $existing_att[$sid] : 'វត្តមាន';
                    $note = isset($existing_notes[$sid]) ? $existing_notes[$sid] : '';
                    
                    if ($status == 'វត្តមាន') $present_c++;
                    else if ($status == 'ច្បាប់') $leave_c++;
                    else if ($status == 'អវត្តមាន') $absent_c++;
                    
                    echo "<tr>";
                    echo "<td>".$i++."</td>";
                    echo "<td class='name-col'>{$row['khmer_name']}</td>";
                    echo "<td>{$row['gender']}</td>";
                    echo "<td><span class='check-mark'>".($status=='វត្តមាន'?'✔':'')."</span></td>";
                    echo "<td><span class='check-mark'>".($status=='ច្បាប់'?'✔':'')."</span></td>";
                    echo "<td><span class='check-mark'>".($status=='អវត្តមាន'?'✔':'')."</span></td>";
                    echo "<td class='note-col'>$note</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' style='color:red;'>មិនទាន់មានទិន្នន័យសិស្សទេ</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="summary-box">
        <span>សិស្សសរុប៖ <?php echo $total_s; ?> នាក់</span>
        <span>វត្តមាន៖ <?php echo $present_c; ?> នាក់</span>
        <span>ច្បាប់៖ <?php echo $leave_c; ?> នាក់</span>
        <span>អវត្តមាន៖ <?php echo $absent_c; ?> នាក់</span>
    </div>

    <div class="footer-sig">
        <div class="sig-box">
            បានឃើញ និងឯកភាព<br>
            <span style="font-family: 'Khmer OS Muol Light', cursive; font-size: 13px;">នាយកសាលា</span><br><br><br><br><br>
            ..................................................
        </div>
        
        <div class="sig-box">
            <div contenteditable="true">ជ្រៃតាសូ, <?php echo $display_date; ?></div>
            <div style="font-family: 'Khmer OS Muol Light', cursive; font-size: 13px;">គ្រូបន្ទុកថ្នាក់ទី <?php echo $class_num; ?></div><br><br><br><br><br>
            <b><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : '..................................................'; ?></b>
        </div>
    </div>
</div>
<script>window.onload = function() { window.print(); }</script>
</body>
</html>