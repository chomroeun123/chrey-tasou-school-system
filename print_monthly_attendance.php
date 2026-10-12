<?php
require 'auth.php';
require 'db_connect.php';

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// ទាញយកឆ្នាំ និងខែ
$year = date('Y', strtotime($selected_month));
$month_num = date('m', strtotime($selected_month));
$days_in_month = date('t', strtotime($selected_month));

// បំប្លែងខែជាអក្សរខ្មែរ
$kh_months = ['01'=>'មករា','02'=>'កុម្ភៈ','03'=>'មីនា','04'=>'មេសា','05'=>'ឧសភា','06'=>'មិថុនា','07'=>'កក្កដា','08'=>'សីហា','09'=>'កញ្ញា','10'=>'តុលា','11'=>'វិច្ឆិកា','12'=>'ធ្នូ'];
$month_kh = $kh_months[$month_num];
$kh_num = ['0'=>'០','1'=>'១','2'=>'២','3'=>'៣','4'=>'៤','5'=>'៥','6'=>'៦','7'=>'៧','8'=>'៨','9'=>'៩'];
$year_kh = strtr($year, $kh_num);

// ច្រោះសិស្សតាមថ្នាក់
$where = "";
$class_num = "3"; // លេខអារ៉ាប់សម្រាប់ប្រើក្នុង SQL
$kh_num = ['0'=>'០','1'=>'១','2'=>'២','3'=>'៣','4'=>'៤','5'=>'៥','6'=>'៦','7'=>'៧','8'=>'៨','9'=>'៩'];

if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class_num = $_SESSION['assigned_class'];
    $where = " WHERE grade_level = $class_num ";
}
// បំប្លែងលេខថ្នាក់ទៅជាលេខខ្មែរ សម្រាប់បង្ហាញលើក្រដាស
$class_num_kh = strtr($class_num, $kh_num);

$students = $conn->query("SELECT id, khmer_name, gender FROM students $where ORDER BY khmer_name ASC");

// ទាញយកទិន្នន័យវត្តមានប្រចាំខែ
// ទាញយកទិន្នន័យវត្តមានប្រចាំខែ
$att_data = [];
$att_query = $conn->query("SELECT student_id, DAY(attendance_date) as d, status FROM attendance WHERE DATE_FORMAT(attendance_date, '%Y-%m') = '$selected_month'");
while($row = $att_query->fetch_assoc()) {
    $att_data[$row['student_id']][$row['d']] = $row['status'];
}

// បន្ថែម Array ទាំងនេះ សម្រាប់រាប់ទិន្នន័យសរុបប្រចាំថ្ងៃ និងសរុបរួម
$daily_present = array_fill(1, $days_in_month, 0);
$daily_leave = array_fill(1, $days_in_month, 0);
$daily_absent = array_fill(1, $days_in_month, 0);
$total_all_leave = 0;
$total_all_absent = 0;

// មុខងារបំប្លែងថ្ងៃនៃសប្តាហ៍ (0=អាទិត្យ, 1=ចន្ទ...)

// មុខងារបំប្លែងថ្ងៃនៃសប្តាហ៍ (0=អាទិត្យ, 1=ចន្ទ...)
$kh_days = ['0'=>'អា', '1'=>'ច', '2'=>'អ', '3'=>'ព', '4'=>'ព្រ', '5'=>'សុ', '6'=>'ស'];
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បញ្ជីហៅវត្តមានខែ <?php echo $month_kh; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Khmer+OS+Muol+Light&family=Khmer+OS+Battambang&display=swap');
        
        /* កំណត់ទម្រង់ក្រដាសផ្តេក (Landscape) */
        @page { size: A4 landscape; margin: 1cm; }
        
        body { font-family: 'Khmer OS Battambang', sans-serif; margin: 0; padding: 0; background-color: white; }
        
        .title { text-align: center; font-family: 'Khmer OS Muol Light', cursive; font-size: 16px; color: #000080; margin-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 15px; }
        th, td { border: 1px solid #000080; padding: 4px 2px; text-align: center; vertical-align: middle; }
        
        /* ពណ៌ក្បាលតារាង និងថ្ងៃឈប់សម្រាក */
        .bg-light-blue { background-color: #d9e1f2; }
        .bg-peach { background-color: #fce4d6; }
        .bg-green { background-color: #a9d08e; }
        .text-red { color: #ff0000; font-weight: bold; }
        
        th.stu-name { width: 150px; text-align: left; padding-left: 5px; }
        td.name-col { text-align: left; padding-left: 5px; white-space: nowrap; }
        
        .footer { display: flex; justify-content: space-between; margin-top: 20px; font-size: 13px; padding: 0 50px;}
        .sig-box { text-align: center; color: #000080; }
        
        /* លាក់ប៊ូតុងពេលព្រីន */
        .print-btn { display: block; width: 150px; margin: 10px auto; padding: 8px; text-align: center; background: #198754; color: white; cursor: pointer; border-radius: 5px; font-weight: bold; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

    <div class="print-btn" onclick="window.print()">🖨️ បោះពុម្ពបញ្ជីនេះ</div>

    <div class="title">
        បញ្ជីហៅវត្តមានសិស្សខែ <?php echo $month_kh; ?> ឆ្នាំសិក្សា ២០២៦ - ២០២៧
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="2%">ល.រ</th>
                <th rowspan="2" class="stu-name">គោត្តនាម និងនាម</th>
                <th rowspan="2" width="3%">ភេទ</th>
                
                <!-- បង្កើតថ្ងៃទី 1 ដល់ 31 -->
                <?php for($d=1; $d<=$days_in_month; $d++): 
                    $day_of_week = date('w', strtotime("$year-$month_num-$d"));
                    $class = ($day_of_week == 0) ? 'text-red' : '';
                ?>
                    <th class="<?php echo $class; ?>" style="width: 2%;"><?php echo $d; ?></th>
                <?php endfor; ?>
                
                <th colspan="3" class="bg-green" style="width: 8%;">ចំនួនអវត្តមាន</th>
                <th rowspan="2" style="width: 8%;">សេចក្តីផ្សេងៗ</th>
            </tr>
            <tr>
                <!-- បង្កើតថ្ងៃនៃសប្តាហ៍ (ច, អ, ព...) -->
                <?php for($d=1; $d<=$days_in_month; $d++): 
                    $day_of_week = date('w', strtotime("$year-$month_num-$d"));
                    $kh_d = $kh_days[$day_of_week];
                    
                    $bg_class = ''; $txt_class = '';
                    if ($day_of_week == 0) { $bg_class = 'bg-peach'; $txt_class = 'text-red'; } // អាទិត្យ
                    if ($day_of_week == 6) { $bg_class = 'bg-light-blue'; } // សៅរ៍
                ?>
                    <th class="<?php echo $bg_class.' '.$txt_class; ?>"><?php echo $kh_d; ?></th>
                <?php endfor; ?>
                
                <th class="bg-light-blue">ច្ប</th>
                <th class="bg-light-blue">អច្ប</th>
                <th class="bg-light-blue">សរុប</th>
            </tr>
        </thead>
       <tbody>
            <?php
            if ($students->num_rows > 0) {
                $i = 1;
                while($row = $students->fetch_assoc()) {
                    $sid = $row['id'];
                    $c_leave = 0; $c_absent = 0;
                    
                    echo "<tr>";
                    echo "<td>".$i++."</td>";
                    echo "<td class='name-col'>{$row['khmer_name']}</td>";
                    echo "<td>{$row['gender']}</td>";
                    
                    for($d=1; $d<=$days_in_month; $d++) {
                        $day_of_week = date('w', strtotime("$year-$month_num-$d"));
                        $bg_class = '';
                        if ($day_of_week == 0) $bg_class = 'bg-peach';
                        if ($day_of_week == 6) $bg_class = 'bg-light-blue';
                        
                        $status = isset($att_data[$sid][$d]) ? $att_data[$sid][$d] : '';
                        $mark = '';
                        if ($status == 'វត្តមាន') {
                            $daily_present[$d]++;
                        } else if ($status == 'ច្បាប់') { 
                            $mark = 'ច្ប'; 
                            $c_leave++; 
                            $daily_leave[$d]++;
                        } else if ($status == 'អវត្តមាន') { 
                            $mark = 'អ'; 
                            $c_absent++; 
                            $daily_absent[$d]++;
                        }
                        
                        echo "<td class='$bg_class' style='color:red;'>$mark</td>";
                    }
                    
                    $c_total = $c_leave + $c_absent;
                    $total_all_leave += $c_leave;
                    $total_all_absent += $c_absent;
                    
                    echo "<td>".($c_leave>0?$c_leave:'')."</td>";
                    echo "<td>".($c_absent>0?$c_absent:'')."</td>";
                    echo "<td><b style='color:red;'>".($c_total>0?$c_total:'')."</b></td>";
                    echo "<td></td>"; // សេចក្តីផ្សេងៗ
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='".($days_in_month + 7)."' style='color:red;'>គ្មានទិន្នន័យ</td></tr>";
            }
            ?>
            <!-- បន្ថែមជួរសរុបនៅទីនេះ -->
            <tr>
                <td colspan="3" class="bg-green text-end fw-bold" style="padding-right: 10px;">សរុបអវត្តមាន</td>
                <?php 
                for($d=1; $d<=$days_in_month; $d++) {
                    $day_of_week = date('w', strtotime("$year-$month_num-$d"));
                    $bg_class = 'bg-green';
                    if ($day_of_week == 0 || $day_of_week == 6) $bg_class = ''; // កុំដាក់ពណ៌នៅថ្ងៃឈប់សម្រាក
                    
                    $total_absent_day = $daily_leave[$d] + $daily_absent[$d];
                    echo "<td class='$bg_class' style='color:red; font-weight:bold;'>".($total_absent_day > 0 ? $total_absent_day : '')."</td>";
                }
                ?>
                <td class="bg-green" style="color:red; font-weight:bold;"><?php echo $total_all_leave > 0 ? $total_all_leave : ''; ?></td>
                <td class="bg-green" style="color:red; font-weight:bold;"><?php echo $total_all_absent > 0 ? $total_all_absent : ''; ?></td>
                <td class="bg-green" style="color:red; font-weight:bold;"><?php echo ($total_all_leave + $total_all_absent) > 0 ? ($total_all_leave + $total_all_absent) : ''; ?></td>
                <td class="bg-green"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="sig-box">
            បានឃើញ និងឯកភាព<br>
            <span style="font-family: 'Khmer OS Muol Light', cursive;">នាយកសាលា</span>
        </div>
       <div class="sig-box">
            ធ្វើនៅ ជ្រៃតាសូ, ថ្ងៃទី.........ខែ..............ឆ្នាំ២០២៦<br>
            <span style="font-family: 'Khmer OS Muol Light', cursive;">គ្រូបន្ទុកថ្នាក់ទី <?php echo $class_num_kh; ?></span><br><br><br><br>
            <b style="font-size: 14px;"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : '.......................................'; ?></b>
        </div>
    </div>

</body>
</html>