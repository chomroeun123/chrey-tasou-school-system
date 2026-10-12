<?php
require 'auth.php';
require 'db_connect.php';

// ច្រោះទិន្នន័យបើគាត់ជាគ្រូបន្ទុកថ្នាក់ចូលមើល
$where_rec = "";
$class_num = "";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
    $class_num = $_SESSION['assigned_class'];
    $where_rec = " AND st.grade_level = $class_num ";
}

// ទទួលថ្ងៃខែបច្ចុប្បន្ន និងបំប្លែងជាអក្សរខ្មែរ
$print_date = date('Y-m-d');
$ts = strtotime($print_date);
$kh_months = ['01'=>'មករា','02'=>'កុម្ភៈ','03'=>'មីនា','04'=>'មេសា','05'=>'ឧសភា','06'=>'មិថុនា','07'=>'កក្កដា','08'=>'សីហា','09'=>'កញ្ញា','10'=>'តុលា','11'=>'វិច្ឆិកា','12'=>'ធ្នូ'];
$kh_num = ['0'=>'០','1'=>'១','2'=>'២','3'=>'៣','4'=>'៤','5'=>'៥','6'=>'៦','7'=>'៧','8'=>'៨','9'=>'៩'];
$display_date = "ថ្ងៃទី".strtr(date('d', $ts), $kh_num)." ខែ".$kh_months[date('m', $ts)]." ឆ្នាំ".strtr(date('Y', $ts), $kh_num);

// ទាញយកស្ថិតិរួម
$stat_books = $conn->query("SELECT SUM(total_qty) AS total, SUM(available_qty) AS avail FROM books")->fetch_assoc();
$total_b = $stat_books['total'] ?? 0;
$avail_b = $stat_books['avail'] ?? 0;
$borrowed_b = $total_b - $avail_b;

// ទាញយកបញ្ជីអ្នកមិនទាន់សងសៀវភៅ
$sql_unreturned = "SELECT st.khmer_name, st.student_code, st.grade_level, b.book_title, br.borrow_date, DATEDIFF(CURDATE(), br.borrow_date) as days_borrowed
                   FROM borrow_records br
                   JOIN students st ON br.student_id = st.id
                   JOIN books b ON br.book_id = b.id
                   WHERE br.status = 'កំពុងខ្ចី' $where_rec
                   ORDER BY br.borrow_date ASC";
$unreturned = $conn->query($sql_unreturned);
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>របាយការណ៍បណ្ណាល័យ</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Khmer+OS+Muol+Light&family=Khmer+OS+Battambang&display=swap');
        @page { size: A4 portrait; margin: 1.5cm; }
        body { font-family: 'Khmer OS Battambang', sans-serif; background-color: #525659; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .a4-paper { background: white; width: 21cm; min-height: 29.7cm; padding: 1.5cm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.5); position: relative; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; align-items: flex-start; }
        .header-left { text-align: left; color: #000; font-family: 'Khmer OS Muol Light', cursive; font-size: 13px; line-height: 1.6; }
        .header-right { text-align: center; color: #000; font-family: 'Khmer OS Muol Light', cursive; font-size: 14px; line-height: 1.5; }
        .title { text-align: center; font-family: 'Khmer OS Muol Light', cursive; font-size: 18px; margin-bottom: 20px; text-decoration: underline; }
        
        /* Summary Box */
        .summary-box { display: flex; justify-content: space-between; margin-bottom: 20px; border: 1px solid #000; padding: 10px; font-weight: bold; font-size: 14px;}
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 13px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; vertical-align: middle; }
        th { background-color: #f2f2f2; font-family: 'Khmer OS Muol Light', cursive; font-weight: normal; }
        td.text-left { text-align: left; padding-left: 10px; }
        
        /* Footer Signatures */
        .footer-sig { display: flex; justify-content: space-between; margin-top: 40px; padding: 0 10px; }
        .sig-box { text-align: center; font-size: 14px; line-height: 1.6; color: #000; }
        
        @media print { body { background: white; padding: 0; } .a4-paper { box-shadow: none; width: 100%; min-height: auto; padding: 0; } }
    </style>
</head>
<body>

<div class="a4-paper">
    
    <div class="header">
        <div class="header-left">
            ការិយាល័យអប់រំ យុវជន និងកីឡាស្រុកអូររាំងឪ<br>
            សាលាបឋមសិក្សាជ្រៃតាសូ
            <?php if($class_num != "") echo "<br>ថ្នាក់ទី " . strtr($class_num, $kh_num); ?>
        </div>
        <div class="header-right">
            ព្រះរាជាណាចក្រកម្ពុជា<br>
            ជាតិ សាសនា ព្រះមហាក្សត្រ<br>
            <span style="font-size: 12px;">---- ⧫ ----</span>
        </div>
    </div>

    <div class="title">របាយការណ៍បណ្ណាល័យ និងបញ្ជីអ្នកមិនទាន់សងសៀវភៅ</div>

    <!-- ស្ថិតិសៀវភៅសរុប -->
    <?php if($class_num == ""): ?>
    <div class="summary-box">
        <div>សៀវភៅសរុប៖ <?php echo $total_b; ?> ក្បាល</div>
        <div>សៀវភៅមានក្នុងស្តុក៖ <?php echo $avail_b; ?> ក្បាល</div>
        <div style="color:red;">សៀវភៅកំពុងត្រូវខ្ចី៖ <?php echo $borrowed_b; ?> ក្បាល</div>
    </div>
    <?php endif; ?>

    <!-- តារាងបញ្ជីអ្នកមិនទាន់សង -->
    <table>
        <thead>
            <tr>
                <th width="8%">ល.រ</th>
                <th width="12%">អត្តលេខ</th>
                <th width="20%">ឈ្មោះសិស្ស</th>
                <th width="8%">ថ្នាក់ទី</th>
                <th width="30%">ចំណងជើងសៀវភៅ</th>
                <th width="12%">ថ្ងៃខ្ចី</th>
                <th width="10%">រយៈពេល</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($unreturned->num_rows > 0) {
                $i = 1;
                while($row = $unreturned->fetch_assoc()) {
                    $days = $row['days_borrowed'];
                    $day_style = ($days > 7) ? "color:red; font-weight:bold;" : ""; // លោតពណ៌ក្រហមបើលើស ៧ថ្ងៃ
                    
                    echo "<tr>";
                    echo "<td>".$i++."</td>";
                    echo "<td>{$row['student_code']}</td>";
                    echo "<td class='text-left fw-bold'>{$row['khmer_name']}</td>";
                    echo "<td>".strtr($row['grade_level'], $kh_num)."</td>";
                    echo "<td class='text-left'>{$row['book_title']}</td>";
                    echo "<td>".date('d-m-Y', strtotime($row['borrow_date']))."</td>";
                    echo "<td style='$day_style'>$days ថ្ងៃ</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' style='padding:20px;'>មិនមានសិស្សណាម្នាក់ជំពាក់សៀវភៅបណ្ណាល័យទេ</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- កន្លែងហត្ថលេខា -->
    <div class="footer-sig">
        <div class="sig-box">
            បានឃើញ និងឯកភាព<br>
            <span style="font-family: 'Khmer OS Muol Light', cursive; font-size: 13px;">នាយកសាលា</span><br><br><br><br><br>
            ..................................................
        </div>
        
        <div class="sig-box">
            <div contenteditable="true">ជ្រៃតាសូ, <?php echo $display_date; ?></div>
            <span style="font-family: 'Khmer OS Muol Light', cursive; font-size: 13px;">គ្រូបណ្ណារក្ស</span><br><br><br><br><br>
            ..................................................
        </div>
    </div>

</div>

<script>
    window.onload = function() {
        window.print();
    }
</script>

</body>
</html>