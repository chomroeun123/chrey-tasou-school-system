<?php
require 'auth.php';
require 'db_connect.php';

if (!isset($_GET['id'])) { die("មានកំហុសក្នុងការទាញយកទិន្នន័យ។"); }
$student_id = (int)$_GET['id'];

// ១. ទាញយកព័ត៌មានសិស្ស
$st_res = $conn->query("SELECT * FROM students WHERE id = $student_id");
$student_data = $st_res->fetch_assoc();
if (!$student_data) die("រកមិនឃើញទិន្នន័យសិស្សនេះទេ។");

$grade_level = $student_data['grade_level'] ?? '3';

// ទាញយកព័ត៌មានលម្អិត (បើមានក្នុង Database វានឹងទាញមក បើអត់វានឹងចេញចន្លោះទទេ)
$dob = isset($student_data['date_of_birth']) && $student_data['date_of_birth'] != '0000-00-00' ? date('d/m/Y', strtotime($student_data['date_of_birth'])) : '........./........./...............';
$pob = $student_data['birth_place'] ?? '.................................................................';
$father = $student_data['father_name'] ?? '...................................................................';
$mother = $student_data['mother_name'] ?? '...................................................................';
$address = $student_data['current_address'] ?? '...................................................................';

// ២. ទាញយកចំនួនសិស្សសរុបក្នុងថ្នាក់
$count_res = $conn->query("SELECT COUNT(id) as total FROM students WHERE grade_level = $grade_level");
$total_students = $count_res->fetch_assoc()['total'];

// បំប្លែងលេខទៅជាលេខខ្មែរ
$kh_num = ['0'=>'០','1'=>'១','2'=>'២','3'=>'៣','4'=>'៤','5'=>'៥','6'=>'៦','7'=>'៧','8'=>'៨','9'=>'៩'];
$grade_level_kh = strtr($grade_level, $kh_num);
$total_students_kh = strtr($total_students, $kh_num);

// ៣. ទាញយកមុខវិជ្ជាពី Database
$subjects_list = [];
$sub_res = $conn->query("SELECT subject_name FROM subjects ORDER BY id ASC");
while ($row = $sub_res->fetch_assoc()) $subjects_list[] = $row['subject_name'];

// ៤. ទាញយកពិន្ទុ និងគណនាឆមាស
$scores_data = [];
$res_scores = $conn->query("SELECT subject_name, score_month, score_value FROM scores WHERE student_id = $student_id");
while($r = $res_scores->fetch_assoc()){
    $scores_data[$r['subject_name']][$r['score_month']] = $r['score_value'];
}

$sem1_results = []; $sem2_results = [];
$total_sem1 = 0; $total_sem2 = 0; 

foreach($subjects_list as $subj) {
    $m12 = $scores_data[$subj]['ធ្នូ'] ?? 0; $m1 = $scores_data[$subj]['មករា'] ?? 0;
    $m2 = $scores_data[$subj]['កុម្ភៈ'] ?? 0; $exam1 = $scores_data[$subj]['មីនា'] ?? 0;
    
    $m5 = $scores_data[$subj]['ឧសភា'] ?? 0; $m6 = $scores_data[$subj]['មិថុនា'] ?? 0;
    $m7 = $scores_data[$subj]['កក្កដា'] ?? 0; $exam2 = $scores_data[$subj]['សីហា'] ?? 0;
    
    $sem1_score = ((($m12 + $m1 + $m2) / 3) + $exam1) / 2;
    $sem2_score = ((($m5 + $m6 + $m7) / 3) + $exam2) / 2;
    
    $sem1_results[$subj] = $sem1_score;
    $sem2_results[$subj] = $sem2_score;
    
    $total_sem1 += $sem1_score;
    $total_sem2 += $sem2_score;
}

$num_subj = count($subjects_list) ?: 1;
$avg_sem1 = $total_sem1 / $num_subj;
$avg_sem2 = $total_sem2 / $num_subj;

// ៥. ទាញយកវត្តមាន (គណនាច្បាប់ និងអវត្តមាន តាមឆមាស)
$sem1_leave = 0; $sem1_absent = 0;
$sem2_leave = 0; $sem2_absent = 0;
$att_res = $conn->query("SELECT attendance_date, status FROM attendance WHERE student_id = $student_id");
if($att_res) {
    while($a = $att_res->fetch_assoc()){
        $month = (int)date('m', strtotime($a['attendance_date']));
        // ឆមាសទី១ (ខែ ១១, ១២, ១, ២, ៣, ៤)
        if(in_array($month, [11, 12, 1, 2, 3, 4])) {
            if($a['status'] == 'ច្បាប់') $sem1_leave++;
            if($a['status'] == 'អវត្តមាន') $sem1_absent++;
        } else {
            // ឆមាសទី២ (ខែ ៥, ៦, ៧, ៨, ៩, ១០)
            if($a['status'] == 'ច្បាប់') $sem2_leave++;
            if($a['status'] == 'អវត្តមាន') $sem2_absent++;
        }
    }
}
$total_leave_absent = $sem1_leave + $sem1_absent + $sem2_leave + $sem2_absent;


function line($label, $val='') {
    return '<div class="row"><span>'.$label.'</span><span class="dots" style="text-align:center; color:#cc0000; font-weight:bold; font-size:12pt; border-bottom: 1.2px dotted var(--blue);">'.$val.'</span></div>';
}
function dlines($n) {
    return str_repeat('<div class="dline" contenteditable="true"></div>', $n);
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
<meta charset="UTF-8">
<title>សៀវភៅសិក្ខាគារិក - <?php echo $student_data['khmer_name']; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Moul&display=swap" rel="stylesheet">
<style>
@page { size: 297mm 210mm; margin: 0; }
:root { --blue:#0000cc; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; }
body { background:#bbb; color:var(--blue); font-family:'Khmer OS Battambang','Battambang','Khmer OS',serif; font-size:10pt; line-height:1.45; }
.sheet { width:297mm; height:210mm; display:flex; background:#fff; margin:0 auto 6mm; overflow:hidden; page-break-after:always; break-after:page; }
.page { width:148.5mm; height:210mm; padding:8mm; }
.muol { font-family:'Khmer OS Muol Light','Moul',serif; font-weight:400; }
.center { text-align:center; }
.row { display:flex; align-items:flex-end; margin:0; height:7mm; white-space:nowrap; }
.dots { flex:1; border-bottom:1.2px dotted var(--blue); margin:0 1.5mm 1.6mm; min-width:6mm; outline: none; }
.dline { border-bottom:1.2px dotted var(--blue); height:7mm; margin:0 2mm; outline: none; }

/* ក្រប & CSS ផ្សេងៗ */
.cover { position:relative; height:194mm; border:1.2mm double var(--blue); padding:7mm 6mm 6mm; display:flex; flex-direction:column; justify-content:space-between; }
.star { position:absolute; color:#e00; font-size:20pt; line-height:1; }
.cover .t1 { font-size:11pt; line-height:1.9; } .cover .t2 { font-size:15pt; margin-top:5mm; } .cover .t3 { font-size:22pt; margin-top:12mm; } .cover .t4 { font-size:13pt; }
.cover .rule { width:50mm; border-bottom:2px solid var(--blue); margin:4mm auto 0; } .cover .infoblk { font-size:10.5pt; }
.hdr { height:8mm; display:flex; gap:3mm; font-size:10pt; } .hdr .row { height:7mm; }
.box { border:0.9mm solid var(--blue); height:182mm; overflow:hidden; }
table { border-collapse:collapse; width:100%; table-layout:fixed; }
td { border:1px solid var(--blue); padding:0 1mm; text-align:center; height:6.9mm; font-size:9.5pt; line-height:1.2; white-space:nowrap; overflow:hidden; }
td.l { text-align:left; padding-left:1.5mm; }
tr:first-child td { border-top:0; } td:first-child { border-left:0; } td:last-child { border-right:0; }
.eval { display:flex; flex-direction:column; height:182mm; }
.eval .s { border-bottom:1px solid var(--blue); padding:0 2mm; overflow:hidden; text-align:center; }
.eval .s:last-child { border-bottom:0; } .eval .ttl { height:7mm; display:flex; align-items:center; justify-content:center; } .eval .s .row { text-align:left; }
.guide { height:194mm; border:1.2mm double var(--blue); padding:5mm 5mm; font-size:9.3pt; line-height:1.5; }
.guide h2 { text-align:center; text-decoration:underline; font-size:16pt; margin:3mm 0 3mm; } .guide p { margin:0 0 .6mm; } .guide .i1 { padding-left:5mm; } .guide .i2 { padding-left:10mm; }
.info { position:relative; height:194mm; border:0.9mm solid var(--blue); }
.photo { position:absolute; top:0; left:0; width:32mm; height:42mm; border-right:0.6mm solid var(--blue); border-bottom:0.6mm solid var(--blue); text-align:center; padding-top:11mm; font-size:13pt; line-height:2; }
.info .title { margin:10mm 0 0 32mm; text-align:center; font-size:20pt; }
.info .fields { position:absolute; top:56mm; left:6mm; right:6mm; font-size:10pt; }
.info .hist { position:absolute; left:0; right:0; bottom:0; } .info .hist td { height:11mm; white-space:normal; } .info .hist tr:first-child td { height:10mm; border-top:1px solid var(--blue); } .info .hist td:first-child { border-left:0; } .info .hist td:last-child { border-right:0; }
@media print { body { background:none; } .sheet { margin:0; } }
</style>
</head>
<body>

<!-- ================= សន្លឹកទី១ : ក្រប ================= -->
<div class="sheet">
  <div class="page"></div>
  <div class="page">
    <div class="cover">
      <span class="star" style="top:-6mm;left:-6mm">✸</span><span class="star" style="top:-6mm;right:-6mm">✸</span>
      <span class="star" style="bottom:-6mm;left:-6mm">✸</span><span class="star" style="bottom:-6mm;right:-6mm">✸</span>
      <div>
        <div class="center muol t1">ព្រះរាជាណាចក្រកម្ពុជា<br>ជាតិ សាសនា ព្រះមហាក្សត្រ<br>✶✶✶📖✶✶✶</div>
        <div class="center muol t2">ក្រសួងអប់រំ យុវជន និងកីឡា</div>
        <div class="center muol t3">សៀវភៅសិក្ខាគារិក</div>
      </div>
      <div>
        <div class="center muol t4">សាលាបឋមសិក្សាចំណេះទូទៅ</div>
        <div class="rule"></div>
      </div>
      <div class="infoblk">
        <?= line('សាលាបឋមសិក្សា:', 'ជ្រៃតាសូ') ?>
        <div class="row">
            <span>ឃុំ/សង្កាត់</span><span class="dots" style="text-align:center; color:#cc0000; font-weight:bold;">អំពិលតាពក</span>
            <span>ស្រុក/ខ័ណ្ឌ</span><span class="dots" style="text-align:center; color:#cc0000; font-weight:bold;">អូររាំងឪ</span>
            <span>ខេត្ត/ក្រុង</span><span class="dots" style="text-align:center; color:#cc0000; font-weight:bold;">ត្បូងឃ្មុំ</span>
        </div>
        <?= line('នាមត្រកូល និងនាមខ្លួន:', $student_data['khmer_name'] . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ភេទ: <span style="color:#cc0000;">' . $student_data['gender'] . '</span>') ?>
        <?= line('ថ្ងៃ ខែ ឆ្នាំកំណើត:', $dob) ?>
        <?= line('ទីកន្លែងកំណើត:', $pob) ?>
        <div class="center" style="font-size:14pt;margin-top:2mm">❁❁❁❁❁</div>
      </div>
    </div>
  </div>
</div>

<!-- ================= សន្លឹកទី២ : សេចក្ដីណែនាំ និង ប្រវត្តិរូបសិស្ស ================= -->
<div class="sheet">
  <div class="page">
    <div class="guide">
      <h2 class="muol">សេចក្ដីណែនាំ</h2>
      <p>&emsp;នាយកសាលាត្រូវចេញ និងគ្រប់គ្រងសៀវភៅសិក្ខាគារិក ហើយត្រូវអនុវត្តតាមចំណុចខាងក្រោម៖</p>
      <p>១. ចាប់ពីថ្នាក់ទី៣ ឡើងទៅសិស្សត្រូវមានសៀវភៅសិក្ខាគារិកដែលចេញឱ្យដោយនាយកសាលា។</p>
      <p>២. ត្រូវចុះនាមត្រកូល និងនាមខ្លួន ភេទ ថ្ងៃ ខែ ឆ្នាំកំណើត ទីកន្លែងកំណើតឈ្មោះ និងមុខរបរឪពុក/ម្ដាយ ឱ្យបានច្បាស់លាស់។</p>
      <p>៣. នៅបញ្ចប់ឆមាសនីមួយៗ និងបញ្ចប់ឆ្នាំសិក្សា គ្រូទទួលបន្ទុកថ្នាក់ត្រូវចុះពិន្ទុ ចំណាត់ថ្នាក់ និងមូលវិចារឱ្យបានត្រឹមត្រូវ។ ចំណែកនាយកសាលាក៏ត្រូវចុះមូលវិចារ ការសរសើរ ឬកំណែលម្អ នៅចុងឆ្នាំសិក្សានីមួយៗដែរ។</p>
      <p>៤. ចំពោះសិស្សផ្ទេរការសិក្សា នាយកសាលាត្រូវបូកសរុបលទ្ធផលនៃការសិក្សារបស់សិស្ស ឱ្យបានសព្វគ្រប់ រួចចុះហត្ថលេខាដោយដាក់ឈ្មោះ រួចផ្ញើមកសាលារៀនថ្មីតាមសំបុត្រសម្ងាត់។</p>
      <p>៥. ហាមផ្ដាច់មិនឱ្យកោសលុប ឬសរសេរបន្ថែម។ ក្នុងករណីមានកែតម្រូវត្រូវចុះហត្ថលេខា និងមានបញ្ជាក់បន្ថែមពីនាយកសាលា ដោយមានហត្ថលេខា និងចុះឈ្មោះផង។</p>
      <p>៦. របៀបរកមធ្យមភាគប្រចាំឆមាស និងដំណាច់ឆ្នាំសិក្សា :</p>
      <p class="i1"><b>ក. ឆមាសទី១</b> :</p>
      <p class="i2">មធ្យមភាគពិន្ទុខែក្នុងឆមាស ( ៤ ខែ ) បូកនិងមធ្យមភាគពិន្ទុប្រឡងក្នុងឆមាសទី១ ចែកនឹង ២ ។</p>
      <p class="i1"><b>ខ. ឆមាសទី២</b> :</p>
      <p class="i2">មធ្យមភាគពិន្ទុខែក្នុងឆមាស ( ៤ ខែ ) បូកនិងមធ្យមភាគពិន្ទុប្រឡងក្នុងឆមាសទី២ ចែកនឹង ២ ។</p>
      <p class="center">មធ្យមភាគឆមាសទី១ + ( មធ្យមភាគឆមាសទី២ × ២ )</p>
      <div class="row i1" style="height:8mm"><b>គ. ដំណាច់ឆ្នាំសិក្សា</b>&nbsp;:<span style="flex:1;border-bottom:2px solid var(--blue);margin:0 14mm 1.6mm 2mm"></span></div>
      <p class="center">៣</p>
      <p>៧. មធ្យមភាគឆមាសទី១ ទី២ និងដំណាច់ឆ្នាំ ដែលលោកគ្រូ/អ្នកគ្រូ រកឃើញ ហើយត្រូវស្រង់យកដាក់ត្រង់លទ្ធផលនៃការប្រឡងឆមាស ត្រង់ខ្ទង់ឆមាស និងដំណាច់ឆ្នាំ។</p>
      <p>កំណត់សម្គាល់: - ក្នុង ១ ឆមាស មាន ៤ ខែ</p>
      <p style="padding-left:24mm">- នាយកសាលាត្រូវចុះហត្ថលេខាលើរូបថតសិស្ស។</p>
      <div class="center" style="margin-top:6mm;font-size:20pt;color:var(--blue)">✷✷✷✷✷</div>
    </div>
  </div>

  <div class="page">
    <div class="info">
      <div class="photo muol">រូបថត<br>៤×៦</div>
      <div class="title muol">សៀវភៅសិក្ខាគារិក<div style="font-size:10pt;margin-top:1mm">✶✶✶📖✶✶✶</div></div>
      <div class="fields">
        <?= line('នាមត្រកូល និងនាមខ្លួន :', $student_data['khmer_name'] . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ភេទ: <span style="color:#cc0000;">' . $student_data['gender'] . '</span>') ?>
        <?= line('ថ្ងៃ ខែ ឆ្នាំកំណើត :', $dob) ?>
        <?= line('ឈ្មោះ និងមុខរបរឪពុក :', $father) ?>
        <?= line('ឈ្មោះ និងមុខរបរម្ដាយ :', $mother) ?>
        <?= line('អាសយដ្ឋានបច្ចុប្បន្ន :', $address) ?>
        <div style="text-align:right;margin-top:2mm" contenteditable="true">ថ្ងៃទី.......ខែ.........ឆ្នាំ២០...........</div>
        <div class="muol" style="text-align:right;margin:3mm 12mm 0 0;font-size:10pt">នាយកសាលា</div>
      </div>
      <table class="hist">
        <colgroup><col style="width:15%"><col style="width:12%"><col style="width:22%"><col style="width:18%"><col style="width:18%"><col style="width:15%"></colgroup>
        <tr><td>ឆ្នាំសិក្សា</td><td>ថ្នាក់ទី</td><td>គ្រឹះស្ថានសិក្សា</td><td>ថ្ងៃ ខែ ឆ្នាំចូលរៀន</td><td>ថ្ងៃ ខែ ឆ្នាំ ចេញ</td><td>អត្តលេខ</td></tr>
        <?php for ($i = 0; $i < 5; $i++): ?>
        <tr><td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td></tr>
        <?php endfor; ?>
      </table>
    </div>
  </div>
</div>

<!-- ================= សន្លឹកទី៣ ដល់ ទី៧ : លទ្ធផល និង ការវាយតម្លៃ (៥សន្លឹក) ================= -->
<?php for ($c = 0; $c < 5; $c++): 
    $is_current = ($c == 0);
    $d_grade = $is_current ? $grade_level_kh : '';
    $d_students = $is_current ? $total_students_kh : '';
    $d_year = $is_current ? '២០២៦-២០២៧' : '';
?>
<div class="sheet">
  <div class="page">
    <div class="hdr">
      <div class="row" style="flex:1"><span>នាមត្រកូល និងនាមខ្លួន : </span><span class="dots" style="text-align:center; color:#cc0000; font-weight:bold;"><?= $student_data['khmer_name'] ?></span></div>
      <div class="row"><span>ថ្នាក់ទី : </span><span class="dots" style="width:13mm;flex:none; text-align:center; color:#cc0000; font-weight:bold;" contenteditable="true"><?= $d_grade ?></span></div>
      <div class="row"><span>ចំនួនសិស្ស : </span><span class="dots" style="width:11mm;flex:none; text-align:center; color:#cc0000; font-weight:bold;" contenteditable="true"><?= $d_students ?></span><span>នាក់</span></div>
    </div>
    <div class="box">
      <table>
        <colgroup><col style="width:36%"><col style="width:14%"><col style="width:18%"><col style="width:14%"><col style="width:18%"></colgroup>
        <tr><td colspan="5">ក- លទ្ធផលនៃការប្រឡងឆមាស</td></tr>
        <tr><td rowspan="2">មុខវិជ្ជា</td><td colspan="2">ឆមាសទី១</td><td colspan="2">ឆមាសទី២</td></tr>
        <tr><td>ពិន្ទុ</td><td>ចំណាត់ថ្នាក់</td><td>ពិន្ទុ</td><td>ចំណាត់ថ្នាក់</td></tr>
        
        <?php foreach ($subjects_list as $s): 
            $s1 = ($is_current && isset($sem1_results[$s]) && $sem1_results[$s] > 0) ? round($sem1_results[$s], 2) : '';
            $s2 = ($is_current && isset($sem2_results[$s]) && $sem2_results[$s] > 0) ? round($sem2_results[$s], 2) : '';
        ?>
        <tr><td class="l"><?= $s ?></td><td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $s1 ?></td><td contenteditable="true"></td><td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $s2 ?></td><td contenteditable="true"></td></tr>
        <?php endforeach; ?>
        
        <?php
            $tot1 = ($is_current && $total_sem1>0) ? round($total_sem1,2) : '';
            $tot2 = ($is_current && $total_sem2>0) ? round($total_sem2,2) : '';
            $avg1 = ($is_current && $avg_sem1>0) ? round($avg_sem1,2) : '';
            $avg2 = ($is_current && $avg_sem2>0) ? round($avg_sem2,2) : '';
        ?>
        <tr><td class="l">សរុបពិន្ទុ</td><td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $tot1 ?></td><td contenteditable="true"></td><td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $tot2 ?></td><td contenteditable="true"></td></tr>
        <tr><td class="l">មធ្យមភាគ</td><td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $avg1 ?></td><td contenteditable="true"></td><td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $avg2 ?></td><td contenteditable="true"></td></tr>
        <tr><td class="l">មធ្យមភាគប្រចាំខែ</td><td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td></tr>
        <tr><td class="l">មធ្យមភាគប្រចាំឆមាស</td><td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td></tr>
        
        <tr><td colspan="5">លទ្ធផលដំណាច់ឆ្នាំ ( មធ្យមភាគ - ចំណាត់ថ្នាក់ )</td></tr>
        <tr><td colspan="5">ខ- ចំនួនពេលអវត្តមាន</td></tr>
      </table>
      <table style="border-top:none;">
        <colgroup><col style="width:21%"><col style="width:21%"><col style="width:21%"><col style="width:21%"><col style="width:16%"></colgroup>
        <tr><td colspan="2" style="border-top:none;">ឆមាសទី១</td><td colspan="2" style="border-top:none;">ឆមាសទី២</td><td rowspan="2" style="border-top:none;">សរុប</td></tr>
        <tr><td>មានច្បាប់</td><td>គ្មានច្បាប់</td><td>មានច្បាប់</td><td>គ្មានច្បាប់</td></tr>
        
        <?php
            $att_s1_l = ($is_current && $sem1_leave > 0) ? $sem1_leave : '';
            $att_s1_a = ($is_current && $sem1_absent > 0) ? $sem1_absent : '';
            $att_s2_l = ($is_current && $sem2_leave > 0) ? $sem2_leave : '';
            $att_s2_a = ($is_current && $sem2_absent > 0) ? $sem2_absent : '';
            $att_tot  = ($is_current && $total_leave_absent > 0) ? $total_leave_absent : '';
        ?>
        <tr>
            <td style="height:17mm; color:#cc0000; font-weight:bold;" contenteditable="true"><?= $att_s1_l ?></td>
            <td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $att_s1_a ?></td>
            <td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $att_s2_l ?></td>
            <td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $att_s2_a ?></td>
            <td style="color:#cc0000; font-weight:bold;" contenteditable="true"><?= $att_tot ?></td>
        </tr>
      </table>
    </div>
  </div>

  <div class="page">
    <div class="hdr">
      <div class="row" style="flex:1"><span>សាលា : </span><span class="dots" style="text-align:center; color:#cc0000; font-weight:bold;" contenteditable="true">ជ្រៃតាសូ</span><span>ឆ្នាំសិក្សា : </span><span class="dots" style="width:26mm;flex:none; text-align:center; color:#cc0000; font-weight:bold;" contenteditable="true"><?= $d_year ?></span></div>
    </div>
    <div class="box eval">
      <div class="s" style="height:35mm;padding:0">
        <div class="ttl" style="border-bottom:1px solid var(--blue)">គ- វាយតម្លៃសិស្សលើការសិក្សា</div>
        <table style="height:28mm">
          <tr><td style="height:7mm;border-left:0">ឆមាសទី១</td><td style="height:7mm">ឆមាសទី២</td><td style="height:7mm;border-right:0">ប្រចាំឆ្នាំ</td></tr>
          <tr><td style="border-left:0;vertical-align:top;height:21mm"><?= dlines(3) ?></td>
              <td style="vertical-align:top"><?= dlines(3) ?></td>
              <td style="border-right:0;vertical-align:top"><?= dlines(3) ?></td></tr>
        </table>
      </div>
      <div class="s" style="height:42mm">
        <div class="ttl">ឃ- លទ្ធផលប្រចាំឆ្នាំ</div>
        <?= line('ត្រូវបានឡើងថ្នាក់ទី') ?>
        <?= line('ត្រូវប្រឡងឡើងថ្នាក់ : មុខវិជ្ជាដែលត្រូវប្រឡង') ?>
        <?= dlines(1) ?>
        <div class="row"><span>ត្រូវបានឱ្យរៀនថ្នាក់ទី</span><span class="dots"></span><span>ត្រូវរៀនត្រួតថ្នាក់ទី</span><span class="dots"></span></div>
      </div>
      <div class="s" style="height:52mm">
        <div class="ttl">ង- ការសរសើរ និងកំណែលម្អ</div>
        <div style="display:flex;justify-content:space-around;height:7mm"><span>ការសរសើរ</span><span>កំណែលម្អ</span></div>
        <div style="margin-top:17mm" contenteditable="true">ថ្ងៃទី.......ខែ.........ឆ្នាំ២០...........</div>
        <div class="muol" style="margin-top:2mm;font-size:10pt">គ្រូប្រចាំថ្នាក់</div>
      </div>
      <div class="s" style="flex:1">
        <div class="ttl">ច- មូលវិចាររបស់នាយកសាលា</div>
        <?= dlines(2) ?>
        <div style="margin-top:1mm" contenteditable="true">ធ្វើនៅជ្រៃតាសូ, ថ្ងៃទី.......ខែ.........ឆ្នាំ២០...........</div>
        <div class="muol" style="margin-top:2mm;font-size:10pt">នាយកសាលា</div>
      </div>
    </div>
  </div>
</div>
<?php endfor; ?>

<script>
    // window.onload = function() { window.print(); }
</script>

</body>
</html>