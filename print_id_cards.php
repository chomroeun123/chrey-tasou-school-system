<?php
require 'auth.php';
require 'db_connect.php';

// ទាញយកសិស្សទាំងអស់ (ឬលោកគ្រូអាចកំណត់សិស្សថ្នាក់ណាមួយក៏បាន)
$sql = "SELECT student_code, khmer_name, gender, dob, grade_level, photo FROM students ORDER BY khmer_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បោះពុម្ពកាតសិស្ស</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Khmer+OS+Muol+Light&family=Khmer+OS+Siemreap&display=swap');
        
        /* កំណត់ទំហំក្រដាស A4 */
        @page { size: A4 portrait; margin: 10mm; }
        
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f0f0f0; margin: 0; padding: 20px; }
        
        /* ធុងផ្ទុកកាត ឱ្យវារត់តម្រៀបគ្នា */
        .card-container { display: flex; flex-wrap: wrap; gap: 15px; justify-content: flex-start; }
        
        /* រចនាទំហំកាតស្តង់ដារបញ្ឈរ (CR80: 54mm x 86mm) */
        .id-card { 
            width: 54mm; 
            height: 86mm; 
            background: white; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); 
            overflow: hidden;
            position: relative;
            box-sizing: border-box;
            page-break-inside: avoid; /* ការពារកុំឱ្យកាតដាច់កណ្តាលពេលព្រីន */
        }
        
        /* ផ្នែកក្បាលកាតពណ៌ខៀវ */
        .card-header { 
            background: #004085; 
            color: white; 
            text-align: center; 
            padding: 8px 5px; 
        }
        .school-name { font-family: 'Khmer OS Muol Light', cursive; font-size: 9px; margin-bottom: 2px;}
        .card-title { font-size: 10px; font-weight: bold; color: #ffc107; }
        
        /* កន្លែងដាក់រូបថត */
        .photo-area { text-align: center; margin-top: 10px; }
        .photo-area img { 
            width: 25mm; 
            height: 30mm; 
            object-fit: cover; 
            border: 2px solid #004085; 
            border-radius: 4px; 
            background-color: #e9ecef;
        }
        
        /* ព័ត៌មានសិស្ស */
        .student-info { padding: 5px 10px; font-size: 10px; line-height: 1.6; color: #333;}
        .stu-name { font-family: 'Khmer OS Muol Light', cursive; font-size: 11px; color: #cc0000; text-align: center; margin-bottom: 5px;}
        
        /* បាតកាត (Footer) */
        .card-footer { 
            background: #ffc107; 
            color: #000; 
            text-align: center; 
            font-size: 9px; 
            font-weight: bold; 
            position: absolute; 
            bottom: 0; 
            width: 100%; 
            padding: 4px 0;
        }
        
        /* ប៊ូតុង Print សម្រាប់បង្ហាញតែលើកុំព្យូទ័រ */
        .print-btn { 
            display: block; width: 200px; margin: 0 auto 20px auto; 
            padding: 10px; text-align: center; background: #198754; 
            color: white; text-decoration: none; font-weight: bold; 
            border-radius: 5px; font-family: sans-serif; cursor: pointer;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .print-btn { display: none; } /* លាក់ប៊ូតុងពេលព្រីន */
            .id-card { box-shadow: none; border: 1px solid #999; }
        }
    </style>
</head>
<body>

    <!-- ប៊ូតុង Print -->
    <div class="print-btn" onclick="window.print()">🖨️ បោះពុម្ពកាតសិស្ស</div>

    <div class="card-container">
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                // កំណត់រូបថត (បើមានរូបយកទម្រង់ uploads/រូបថត បើអត់មានដាក់រូប default ក្លែងក្លាយ)
                $photo_path = ($row['photo'] != 'default.png' && !empty($row['photo'])) ? 'uploads/'.$row['photo'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                
                $dob = date('d-m-Y', strtotime($row['dob']));
                
                echo '
                <div class="id-card">
                    <div class="card-header">
                        <div class="school-name">សាលាបឋមសិក្សាជ្រៃតាសូ</div>
                        <div class="card-title">កាតសម្គាល់សិស្ស</div>
                    </div>
                    
                    <div class="photo-area">
                        <img src="'.$photo_path.'" alt="Student Photo">
                    </div>
                    
                    <div class="student-info">
                        <div class="stu-name">'.$row['khmer_name'].'</div>
                        <div><b>អត្តលេខ៖</b> '.$row['student_code'].'</div>
                        <div><b>ភេទ៖</b> '.$row['gender'].'</div>
                        <div><b>ថ្ងៃកំណើត៖</b> '.$dob.'</div>
                        <div><b>ថ្នាក់ទី៖</b> '.$row['grade_level'].' (ក)</div>
                    </div>
                    
                    <div class="card-footer">
                        ឆ្នាំសិក្សា ២០២៦-២០២៧
                    </div>
                </div>
                ';
            }
        } else {
            echo "<h2>មិនទាន់មានទិន្នន័យសិស្សទេ</h2>";
        }
        ?>
    </div>

</body>
</html>