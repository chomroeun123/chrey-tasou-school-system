<?php
require 'auth.php';
require 'db_connect.php';

// ១. រាប់ចំនួនសិស្សសរុប
$sql_total = "SELECT COUNT(id) AS total FROM students";
$result_total = $conn->query($sql_total);
$total_students = $result_total->fetch_assoc()['total'];

// ២. រាប់ចំនួនសិស្សស្រី
$sql_girls = "SELECT COUNT(id) AS total_girls FROM students WHERE gender='ស្រី'";
$result_girls = $conn->query($sql_girls);
$total_girls = $result_girls->fetch_assoc()['total_girls'];

// ៣. រាប់ចំនួនសិស្សប្រុស (យកសរុប ដក ស្រី)
$total_boys = $total_students - $total_girls;
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ទំព័រដើម - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Khmer OS Siemreap', sans-serif; background-color: #f4f6f9; }
        .stat-card { border-radius: 10px; padding: 25px; color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 20px;}
        .bg-primary-custom { background: linear-gradient(45deg, #007bff, #0056b3); }
        .bg-pink-custom { background: linear-gradient(45deg, #e83e8c, #c2185b); }
        .bg-info-custom { background: linear-gradient(45deg, #17a2b8, #007281); }
        .icon-lg { font-size: 4rem; opacity: 0.7; }
        .card-title-kh { font-size: 18px; font-weight: bold; margin-bottom: 10px; opacity: 0.9;}
        .number-kh { font-size: 36px; font-weight: bold; margin: 0;}
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h4 class="text-primary mb-4 fw-bold">📊 ផ្ទាំងសង្ខេបទិន្នន័យ (Admin Dashboard)</h4>
        
        <div class="row">
            <!-- កាតសិស្សសរុប -->
            <div class="col-md-4">
                <div class="stat-card bg-primary-custom d-flex justify-content-between align-items-center">
                    <div>
                        <div class="card-title-kh">សិស្សសរុប</div>
                        <h2 class="number-kh"><?php echo $total_students; ?> <span style="font-size: 20px;">នាក់</span></h2>
                    </div>
                    <div class="icon-lg">🎓</div>
                </div>
            </div>
            
            <!-- កាតសិស្សស្រី -->
            <div class="col-md-4">
                <div class="stat-card bg-pink-custom d-flex justify-content-between align-items-center">
                    <div>
                        <div class="card-title-kh">សិស្សស្រី</div>
                        <h2 class="number-kh"><?php echo $total_girls; ?> <span style="font-size: 20px;">នាក់</span></h2>
                    </div>
                    <div class="icon-lg">👧</div>
                </div>
            </div>

            <!-- កាតសិស្សប្រុស -->
            <div class="col-md-4">
                <div class="stat-card bg-info-custom d-flex justify-content-between align-items-center">
                    <div>
                        <div class="card-title-kh">សិស្សប្រុស</div>
                        <h2 class="number-kh"><?php echo $total_boys; ?> <span style="font-size: 20px;">នាក់</span></h2>
                    </div>
                    <div class="icon-lg">👦</div>
                </div>
            </div>
        </div>

        <!-- ផ្ទាំងស្វាគមន៍ -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow-sm border-0" style="border-radius: 10px;">
                    <div class="card-body p-4 text-center">
                        <h4 class="text-success mb-3 fw-bold">សូមស្វាគមន៍មកកាន់ប្រព័ន្ធគ្រប់គ្រងសាលារៀន</h4>
                        <p class="text-muted" style="font-size: 16px;">
                            លោកគ្រូអាចប្រើប្រាស់ម៉ឺនុយខាងលើ ដើម្បីគ្រប់គ្រងបញ្ជីឈ្មោះសិស្ស បញ្ចូលពិន្ទុ និងបោះពុម្ពព្រឹត្តិបត្តិពិន្ទុប្រចាំខែ។
                        </p>
                        <hr class="my-4">
                        <a href="index.php" class="btn btn-outline-primary mx-2 px-4">បញ្ជីឈ្មោះសិស្ស</a>
                        <a href="view_scores.php" class="btn btn-outline-success mx-2 px-4">តារាងពិន្ទុប្រចាំខែ</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>