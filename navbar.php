<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow" style="z-index: 1050;">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="dashboard.php">🏫 ប្រព័ន្ធគ្រប់គ្រងសាលារៀន</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link text-white fw-bold" href="dashboard.php">🏠 ទំព័រដើម</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="index.php">បញ្ជីសិស្ស</a></li>
                
               <!-- ម៉ឺនុយវត្តមាន -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">📋 វត្តមាន</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="add_attendance.php">ស្រង់វត្តមានប្រចាំថ្ងៃ</a></li>
                        <li><a class="dropdown-item" href="view_attendance.php">របាយការណ៍វត្តមានប្រចាំខែ</a></li>
                    </ul>
                </li>

                <!-- បន្ថែមម៉ឺនុយថ្មីនេះ -->
                <li class="nav-item">
                    <a class="nav-link text-white" href="cleaning_schedule.php">🧹 វេនសម្អាត</a>
                </li>
                
             <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">📝 ពិន្ទុ</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="add_score.php">បញ្ចូលពិន្ទុ</a></li>
                        <li><a class="dropdown-item" href="view_scores.php">ចំណាត់ថ្នាក់ & ព្រឹត្តិបត្តិ</a></li>
                        <li><a class="dropdown-item fw-bold text-primary" href="view_semester.php">លទ្ធផលឆមាស & ប្រចាំឆ្នាំ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <li><a class="dropdown-item fw-bold text-danger" href="honor_roll.php">🏆 តារាងកិត្តិយសសិស្ស</a></li>
                        
                        <!-- បន្ថែមម៉ឺនុយថ្មីនៅទីនេះ -->
                        <li><a class="dropdown-item fw-bold text-success" href="student_record_book.php">📘 សៀវភៅសិក្ខាគារិក</a></li>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="manage_subjects.php">📚 គ្រប់គ្រងមុខវិជ្ជា</a></li>
                    </ul>
                </li>
                
                <li class="nav-item"><a class="nav-link text-white" href="print_id_cards.php" target="_blank">🪪 កាតសិស្ស</a></li>

                <!-- បន្ថែមម៉ឺនុយបណ្ណាល័យនៅទីនេះ -->
               <!-- ម៉ឺនុយបណ្ណាល័យ (កូដចាស់) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">📚 បណ្ណាល័យ</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="manage_books.php">📖 គ្រប់គ្រងស្តុកសៀវភៅ</a></li>
                        <li><a class="dropdown-item" href="borrow_return.php">🔄 ខ្ចី និង សងសៀវភៅ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="library_report.php">📊 របាយការណ៍បណ្ណាល័យ</a></li>
                    </ul>
                </li>

                <!-- បន្ថែមម៉ឺនុយថ្មីនេះ -->
                <li class="nav-item">
                    <a class="nav-link text-white fw-bold" href="school_documents.php">📂 ឯកសាររដ្ឋបាល</a>
                </li>

                <!-- បង្ហាញម៉ឺនុយនេះតែសម្រាប់ Admin ប៉ុណ្ណោះ -->
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link text-warning fw-bold" href="manage_teachers.php">👨‍🏫 គ្រប់គ្រងគ្រូ</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-warning fw-bold" href="#" role="button" data-bs-toggle="dropdown">
                        👤 <?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'User'; ?> 
                        <?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') ? '(ថ្នាក់ទី '.$_SESSION['assigned_class'].')' : '(Admin)'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger" href="logout.php">ចាកចេញ (Logout)</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>