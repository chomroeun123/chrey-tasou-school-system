<?php
require 'auth.php';
require 'db_connect.php';

$where_join = "";
$class_num = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class_num = $_SESSION['assigned_class'];
    $where_join = " WHERE st.grade_level = $class_num ";
}

$kh_num = ['0'=>'០','1'=>'១','2'=>'២','3'=>'៣','4'=>'៤','5'=>'៥','6'=>'៦','7'=>'៧','8'=>'៨','9'=>'៩'];

$days = ['ចន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
$schedule = [];
foreach($days as $d) $schedule[$d] = [];

$sql_sch = "SELECT cs.day_of_week, cs.task_desc, st.khmer_name 
            FROM cleaning_schedule cs 
            JOIN students st ON cs.student_id = st.id 
            $where_join ORDER BY st.khmer_name ASC";
$res_sch = $conn->query($sql_sch);
while($r = $res_sch->fetch_assoc()) {
    $schedule[$r['day_of_week']][] = $r;
}

$max_rows = 0;
foreach($days as $d) {
    if(count($schedule[$d]) > $max_rows) {
        $max_rows = count($schedule[$d]);
    }
}
if ($max_rows < 5) $max_rows = 5; 
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>តារាងវេនសម្អាតប្រចាំសប្តាហ៍</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Khmer+OS+Muol+Light&family=Khmer+OS+Battambang&display=swap');
        
        /* កំណត់ទំព័រ A4 Landscape ឱ្យម៉ត់ចត់ */
        @page { size: A4 landscape; margin: 0.5cm; }
        
        body { font-family: 'Khmer OS Battambang', sans-serif; background-color: #525659; margin: 0; padding: 10px; display: flex; justify-content: center; }
        
        .a4-paper { 
            background: white; 
            width: 29.7cm; 
            height: 20cm; /* កំណត់កម្ពស់ឱ្យជាក់លាក់ដើម្បីកុំឱ្យលោតចូលទំព័រថ្មី */
            padding: 1cm; 
            box-sizing: border-box; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5); 
            position: relative;
            overflow: hidden; /* ការពារមិនឱ្យធ្លាយចេញ */
        }
        
        /* ក្បាលក្រដាសរដ្ឋបាល */
        .header { display: flex; justify-content: space-between; margin-bottom: 5px; align-items: flex-start; }
        .header-left { text-align: left; color: #000; font-family: 'Khmer OS Muol Light', cursive; font-size: 12px; line-height: 1.5; }
        .header-right { text-align: center; color: #000; font-family: 'Khmer OS Muol Light', cursive; font-size: 13px; line-height: 1.4; }
        
        .title-container { text-align: center; margin-bottom: 10px; }
        .title { font-family: 'Khmer OS Muol Light', cursive; font-size: 18px; color: #cc0000; margin-bottom: 2px; text-decoration: underline;}
        .subtitle { font-weight: bold; font-size: 14px; color: #000080;}
        
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 10px; }
        th, td { border: 2px solid #000; padding: 5px; text-align: center; vertical-align: top; }
        th { font-family: 'Khmer OS Muol Light', cursive; color: white; padding: 5px 0; }
        
        .bg-mon { background-color: #f39c12; }
        .bg-tue { background-color: #9b59b6; }
        .bg-wed { background-color: #27ae60; }
        .bg-thu { background-color: #e74c3c; }
        .bg-fri { background-color: #3498db; }
        .bg-sat { background-color: #34495e; }
        
        .stu-name { font-weight: bold; font-size: 14px; color: #000080; }
        .stu-task { font-size: 11px; color: #d35400; display: block; margin-top: 1px; }
        
        /* គណៈកម្មការថ្នាក់ */
        .committee-box { border: 1px solid #000; padding: 8px 10px; border-radius: 5px; display: flex; justify-content: space-between; margin-bottom: 10px;}
        .role-section { width: 32%; font-size: 11px; line-height: 1.4; }
        .role-title { font-family: 'Khmer OS Muol Light', cursive; font-size: 12px; color: #000080; }
        .editable-text { color: #cc0000; font-weight: bold; border-bottom: 1px dotted #cc0000; padding: 0 5px; outline: none; }
        
        /* កន្លែងហត្ថលេខា */
        .footer-sig { display: flex; justify-content: space-between; padding: 0 20px; font-size: 12px; font-weight: bold; color: #000080; }
        .sig-box-left { text-align: center; line-height: 1.5; }
        .sig-box-right { text-align: center; line-height: 1.5; }
        .date-line { font-weight: normal; margin-bottom: 2px; font-size: 12px; color: #000;}
        
        @media print { 
            body { background: white; padding: 0; } 
            .a4-paper { box-shadow: none; width: 100%; height: 100%; padding: 0; margin: 0; } 
        }
    </style>
</head>
<body>

<div class="a4-paper">
    <!-- ក្បាលក្រដាស -->
    <div class="header">
        <div class="header-left">
            ការិយាល័យអប់រំ យុវជន និងកីឡាស្រុកអូររាំងឪ<br>
            សាលាបឋមសិក្សាជ្រៃតាសូ<br>
            ថ្នាក់ទី <b><?php echo $class_num != "" ? strtr($class_num, $kh_num) : "៣"; ?></b> (ក)
        </div>
        <div class="header-right">
            ព្រះរាជាណាចក្រកម្ពុជា<br>ជាតិ សាសនា ព្រះមហាក្សត្រ<br><span style="font-size: 10px;">---- ⧫ ----</span>
        </div>
    </div>

    <!-- ចំណងជើង និងឆ្នាំសិក្សា -->
    <div class="title-container">
        <div class="title">តារាងវេនសម្អាតប្រចាំសប្តាហ៍ និងរចនាសម្ព័ន្ធថ្នាក់រៀន</div>
        <div class="subtitle">ឆ្នាំសិក្សា ២០២៦-២០២៧</div>
    </div>

    <!-- តារាងវេនសម្អាត -->
    <table>
        <thead>
            <tr>
                <th width="16%" class="bg-mon">ថ្ងៃចន្ទ</th>
                <th width="16%" class="bg-tue">ថ្ងៃអង្គារ</th>
                <th width="16%" class="bg-wed">ថ្ងៃពុធ</th>
                <th width="16%" class="bg-thu">ថ្ងៃព្រហស្បតិ៍</th>
                <th width="16%" class="bg-fri">ថ្ងៃសុក្រ</th>
                <th width="16%" class="bg-sat">ថ្ងៃសៅរ៍</th>
            </tr>
        </thead>
        <tbody>
            <?php for($i = 0; $i < $max_rows; $i++): ?>
            <tr>
                <?php foreach($days as $day): ?>
                    <td>
                        <?php if(isset($schedule[$day][$i])): ?>
                            <span class="stu-name"><?php echo $schedule[$day][$i]['khmer_name']; ?></span>
                            <?php if(!empty($schedule[$day][$i]['task_desc'])): ?>
                                <span class="stu-task">( <?php echo $schedule[$day][$i]['task_desc']; ?> )</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>
    
    <!-- គណៈកម្មការថ្នាក់ និងមុខងារ -->
    <div class="committee-box">
        <div class="role-section">
            <span class="role-title">១. ប្រធានថ្នាក់៖</span> 
            <span class="editable-text" contenteditable="true">ចុចវាយឈ្មោះ...</span><br>
            <b>មុខងារ៖</b> ទទួលខុសត្រូវរួម ដឹកនាំសិស្សក្នុងថ្នាក់ រាយការណ៍អវត្តមានជូនគ្រូ។
        </div>
        <div class="role-section">
            <span class="role-title">២. អនុប្រធានទី១៖</span> 
            <span class="editable-text" contenteditable="true">ចុចវាយឈ្មោះ...</span><br>
            <b>មុខងារ៖</b> ទទួលបន្ទុកផ្នែកវិន័យ សណ្តាប់ធ្នាប់ តាមដានសកម្មភាពសិក្សា។
        </div>
        <div class="role-section">
            <span class="role-title">៣. អនុប្រធានទី២៖</span> 
            <span class="editable-text" contenteditable="true">ចុចវាយឈ្មោះ...</span><br>
            <b>មុខងារ៖</b> ទទួលបន្ទុកផ្នែកអនាម័យ បរិស្ថាន តាមដានអ្នកប្រចាំការវេនសម្អាត។
        </div>
    </div>

    <!-- ហត្ថលេខា នាយកសាលា និង គ្រូបន្ទុកថ្នាក់ -->
    <div class="footer-sig">
        <div class="sig-box-left">
            បានឃើញ និងឯកភាព<br>
            <span style="font-family: 'Khmer OS Muol Light', cursive; font-size: 12px;">នាយកសាលា</span><br><br><br>
            ..................................................
        </div>
        
        <div class="sig-box-right">
            <!-- កាលបរិច្ឆេទចន្ទគតិ (ព.ស) និង សុរិយគតិ (គ.ស) (អាច Edit បាន) -->
            <div class="date-line" contenteditable="true">ថ្ងៃ......................ខែ................ឆ្នាំ...............ព.ស.២៥......</div>
            <div class="date-line" contenteditable="true">ធ្វើនៅជ្រៃតាសូ, ថ្ងៃទី.........ខែ................ឆ្នាំ២០២....</div>
            
            <div style="font-family: 'Khmer OS Muol Light', cursive; margin-top: 5px;">គ្រូបន្ទុកថ្នាក់ទី <?php echo $class_num != "" ? strtr($class_num, $kh_num) : "៣"; ?></div><br><br>
            <b style="font-size: 13px;"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'ពេញ ចំរើន'; ?></b>
        </div>
    </div>
</div>

</body>
</html>