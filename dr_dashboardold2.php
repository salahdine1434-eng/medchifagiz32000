<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// تأكد أنه طبيب
if ($_SESSION['role'] != 'doctor') {
    header("Location: login.php");
    exit;
}

require 'db.php';

// نظام Online بسيط: بمجرد فتح الداشبورد (أي بعد تسجيل الدخول) يصبح المستخدم متصلاً.
// يُضبط = 0 لاحقاً في logout.php عند تسجيل الخروج.
$pdo->prepare("UPDATE users SET is_online = 1 WHERE id = ?")->execute([$_SESSION['user_id']]);

// ✅ نجيبو الطبيب أولا
$stmt = $pdo->prepare("
    SELECT doctors.*, users.full_name 
    FROM doctors
    JOIN users ON doctors.user_id = users.id
    WHERE doctors.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();
if(isset($_SESSION['is_clinic_staff']) && $_SESSION['is_clinic_staff'] == 1){

    $doctor = [
        'id' => $_SESSION['staff_id'],
        'full_name' => $_SESSION['name'],
        'is_profile_complete' => 1
    ];

} else {

    if(!$doctor){
        header("Location: complete_doctor_profile.php");
        exit();
    }

    if($doctor['is_profile_complete'] == 0){
        header("Location: complete_doctor_profile.php");
        exit();
    }
}
// ✅ نجيبو الحجوزات تاع هذا الطبيب
$stmt2 = $pdo->prepare("
   SELECT * FROM appointments
WHERE doctor_id = ?
AND status = 'pending'
ORDER BY created_at DESC
");

$stmt2->execute([$doctor['id']]);
$appointments = $stmt2->fetchAll(PDO::FETCH_ASSOC);
$today = date('Y-m-d');

$stmtToday = $pdo->prepare("
    SELECT a.*,
           mr.id AS medical_record_id
    FROM appointments a
    LEFT JOIN medical_records mr
           ON mr.patient_id = a.patient_id
          AND mr.doctor_id  = a.doctor_id
    WHERE a.doctor_id = ?
    AND a.status = 'confirmed'
    AND a.appointment_date = ?
    ORDER BY a.appointment_time ASC
");

$stmtToday->execute([$doctor['id'], $today]);

$todayPatients = $stmtToday->fetchAll(PDO::FETCH_ASSOC);
$stmtMyAppointments = $pdo->prepare("
    SELECT * FROM appointments
    WHERE doctor_id = ?
    AND status = 'confirmed'
    AND appointment_date > CURDATE()
    ORDER BY appointment_date ASC, appointment_time ASC
");

$stmtMyAppointments->execute([$doctor['id']]);
$myAppointments = $stmtMyAppointments->fetchAll(PDO::FETCH_ASSOC);
$stmtHistory = $pdo->prepare("
    SELECT * FROM appointments
    WHERE doctor_id = ?
    AND status IN ('completed', 'no_show')
    ORDER BY appointment_date DESC, appointment_time DESC
");

$stmtHistory->execute([$doctor['id']]);

$historyAppointments = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
// ✅ من بعد نتحقق


// ✅ جلب المرضى المقيمين من medical_records (لا يلمس patients)
$stmtResidentPatients = $pdo->prepare("
    SELECT id, full_name, phone, address, admission_date, residency_status
    FROM medical_records
    WHERE doctor_id        = ?
      AND residency_status = 'مقيم'
    ORDER BY admission_date DESC, created_at DESC
");
try {
    $stmtResidentPatients->execute([$doctor['id']]);
    $residentPatients = $stmtResidentPatients->fetchAll(PDO::FETCH_ASSOC);
    $inpatientCount   = count($residentPatients);
} catch (Exception $e) {
    $residentPatients = [];
    $inpatientCount   = 0;
}

// في بداية الملف — معالجة API التقرير
if (isset($_POST['action']) && $_POST['action'] === 'save_rapport_medical') {
    require_once 'rapport_medical_api.php';
    handleRapportRequest(); exit;
}
// معالجة API بطاقة العلاج
if (isset($_POST['action']) && in_array($_POST['action'], ['save_fiche', 'load_fiche'])) {
    require_once 'fiche_traitement_api.php';
    handleFicheRequest(); exit;
}
?>
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الطبيب - MedChifaGiz</title>
    <link rel="stylesheet" href="dr_dashboard.css">
    <link rel="stylesheet" href="dashboard_fixes.css">
    <link rel="stylesheet" href="patient_inline.css">
    <link rel="stylesheet" href="patient_inline_v2.css">
    <link rel="stylesheet" href="patient_compact.css">
    <link rel="stylesheet" href="dr_statistics.css">
    <link rel="stylesheet" href="rapport_medical.css">
    <link rel="stylesheet" href="medfile_ui_v2.css">
    
    <!-- ميزة توليد التقارير الطبية بالذكاء الاصطناعي (Groq) -->
    <link rel="stylesheet" href="medical_report.css">
    <!-- أرشيف التقارير الطبية -->
    <link rel="stylesheet" href="medical_reports_archive.css">
    <link rel="stylesheet" href="ai_file_organizer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside class="sidebar" id="mainSidebar">

        <!-- Logo -->
        <div class="sidebar-logo">
            <img src="medchifagz.png" alt="MedChifaGiz Logo">
            <div class="sidebar-logo-text">
                <span class="brand">MedChifaGiz</span>
                <span class="tagline">المنصة الطبية الذكية</span>
            </div>
        </div>

        <!-- Doctor Mini Card -->
        <div class="sidebar-doctor">
            <div class="sidebar-doctor-avatar">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="sidebar-doctor-info">
                <div class="doc-name">د. <?php echo htmlspecialchars($doctor['full_name']); ?></div>
                <div class="doc-role"><?php echo htmlspecialchars($doctor['specialty'] ?? 'طبيب'); ?></div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">

            <!-- ── القائمة الرئيسية ── -->
            <p class="snav-label">القائمة الرئيسية</p>

            <!-- الرئيسية — Accordion -->
            <div class="snav-group" id="sng-home">
                <div class="snav-header" onclick="snavToggle('home')">
                    <i class="fas fa-th-large"></i>
                    <span>الرئيسية</span>
                    <i class="fas fa-chevron-down snav-arrow"></i>
                </div>
                <div class="snav-body" id="snb-home">
                    <div class="snav-item" onclick="snavAddPatient()">
                        <i class="fas fa-user-plus"></i><span>إضافة مريض</span>
                    </div>
                    <div class="snav-item" onclick="snavCard('todayPatients')">
                        <i class="fas fa-users"></i><span>مرضى اليوم</span>
                    </div>
                    <div class="snav-item" onclick="snavCard('newBookings')">
                        <i class="fas fa-calendar-alt"></i><span>حجوزات جديدة</span>
                    </div>
                    <div class="snav-item" onclick="snavCard('myAppointments')">
                        <i class="fas fa-calendar-check"></i><span>المواعيد القادمة</span>
                    </div>
                    <div class="snav-item" onclick="snavCard('appointmentsHistory')">
                        <i class="fas fa-history"></i><span>سجل المواعيد</span>
                    </div>
                    <div class="snav-item" onclick="snavCard('qrScanner')">
                        <i class="fas fa-qrcode"></i><span>مسح كود QR</span>
                    </div>
                   
                </div>
            </div>

            <!-- ── إدارة المرضى ── -->
            <p class="snav-label">إدارة المرضى</p>

            <!-- جناح النساء — Accordion -->
            <div class="snav-header snav-direct" id="sng-women" onclick="snavGo('women')">
                <i class="fas fa-female"></i>
                <span data-ar="جناح النساء" data-fr="Pavillon Femmes" data-en="Women's Ward">جناح النساء</span>
            </div>

            <!-- جناح الرجال — Accordion -->
            <div class="snav-header snav-direct" id="sng-men" onclick="snavGo('men')">
                <i class="fas fa-male"></i>
                <span data-ar="جناح الرجال" data-fr="Pavillon Hommes" data-en="Men's Ward">جناح الرجال</span>
            </div>

            <!-- مركز البيانات — Accordion -->
            <div class="snav-group" id="sng-ai">
                <div class="snav-header" onclick="snavToggle('ai')">
                    <i class="fas fa-brain"></i>
                    <span data-ar="مركز البيانات" data-fr="IA" data-en="AI">مركز البيانات</span>
                    <i class="fas fa-chevron-down snav-arrow"></i>
                </div>
                <div class="snav-body" id="snb-ai">
                    <div class="snav-item" onclick="snavGoAI('autoOrganize')">
                        <i class="fas fa-folder-open"></i><span>تنظيم الملفات تلقائياً</span>
                    </div>
                    <div class="snav-item" onclick="snavGoAI('patientArchive')">
                        <i class="fas fa-archive"></i><span>أرشيف المرضى</span>
                    </div>
                    <div class="snav-item" onclick="snavGoAI('reportGen')">
                        <i class="fas fa-file-medical-alt"></i><span>توليد التقارير الطبية</span>
                    </div>
                    <div class="snav-item" onclick="snavGoAI('reportsArchive')">
                        <i class="fas fa-folder-minus"></i><span>أرشيف التقارير الطبية</span>
                    </div>
                </div>
            </div>

            <!-- الخدمات — Accordion -->
            <div class="snav-group" id="sng-services">
                <div class="snav-header" onclick="snavToggle('services')">
                    <i class="fas fa-box-open"></i>
                    <span data-ar="الخدمات" data-fr="Services" data-en="Services">الخدمات</span>
                    <i class="fas fa-chevron-down snav-arrow"></i>
                </div>
                <div class="snav-body" id="snb-services">
                    <div class="snav-item" onclick="snavGo('services')">
                        <i class="fas fa-flask"></i><span>النتائج المخبرية</span>
                    </div>
                    <div class="snav-item" onclick="snavGo('services')">
                        <i class="fas fa-kit-medical"></i><span>دليل المستلزمات الطبية</span>
                    </div>
                    <div class="snav-item" onclick="snavGo('services')">
                        <i class="fas fa-robot"></i><span>مساعد المستلزمات الذكي</span>
                    </div>
                </div>
            </div>

            <!-- 📊 الإحصائيات — عنصر مباشر داخل قسم إدارة المرضى -->
            <div class="snav-header snav-direct" id="sng-stats" onclick="openStatistics()">
                <i class="fas fa-chart-column"></i>
                <span data-ar="الإحصائيات" data-fr="Statistiques" data-en="Statistics">الإحصائيات</span>
            </div>

            <!-- ── التواصل الطبي ── -->
            <p class="snav-label">التواصل الطبي</p>

            <div class="snav-group" id="sng-medcomm">
                <div class="snav-header" onclick="snavToggle('medcomm')">
                    <i class="fas fa-stethoscope"></i>
                    <span>التواصل الطبي</span>
                    <i class="fas fa-chevron-down snav-arrow"></i>
                </div>
                <div class="snav-body" id="snb-medcomm">
                    <div class="snav-item" onclick="snavGoMedComm('consultation')">
                        <i class="fas fa-comment-medical"></i><span>الاستشارة الطبية</span>
                    </div>
                    <div class="snav-item" onclick="snavGoMedComm('followup')">
                        <i class="fas fa-user-clock"></i><span>متابعة المرضى</span>
                    </div>
                </div>
            </div>

            <!-- ── الحساب ── -->
            <p class="snav-label">الحساب</p>

            <!-- الإعدادات — مباشر بدون Dropdown -->
            <div class="snav-header snav-direct" id="sng-settings" onclick="snavGo('settings')">
                <i class="fas fa-sliders-h"></i>
                <span data-ar="الإعدادات" data-fr="Paramètres" data-en="Settings">الإعدادات</span>
            </div>

        </nav>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-item logout-item" style="text-decoration:none;">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </aside>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- ═══════════════ TOP BAR ═══════════════ -->
    <header class="top-bar">
        <div class="header-content">
            <div class="header-left">
                <!-- Mobile toggle button -->
                <div style="display:flex; align-items:center; gap:12px;">
                    <button class="sidebar-toggle-btn" onclick="toggleSidebar()" id="sidebarToggleBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 id="pageTitle" data-ar="لوحة التحكم" data-fr="Tableau de bord" data-en="Dashboard">لوحة التحكم</h1>
                        <span class="header-subtitle"><?php echo date('l, d F Y'); ?></span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <div class="notification-btn" onclick="toggleNotifications()">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                <div class="language-btn" onclick="toggleLanguage()">
                    <i class="fas fa-language"></i>
                </div>
                <div class="theme-toggle" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i>
                </div>
            </div>
        </div>

        <!-- Notifications Panel -->
        <div class="notifications-panel" id="notificationsPanel">
            <div class="notifications-header">
                <h3 data-ar="التنبيهات" data-fr="Notifications" data-en="Notifications">التنبيهات</h3>
                <button onclick="closeNotifications()"><i class="fas fa-times"></i></button>
            </div>
            <div class="notifications-list">
                <div class="notification-item unread">
                    <i class="fas fa-user-plus"></i>
                    <div class="notification-content">
                        <p data-ar="حجز جديد من المريض محمد علي" data-fr="Nouvelle réservation de Mohamed Ali" data-en="New booking from Mohamed Ali">حجز جديد من المريض محمد علي</p>
                        <span class="time" data-ar="منذ 5 دقائق" data-fr="Il y a 5 minutes" data-en="5 minutes ago">منذ 5 دقائق</span>
                    </div>
                </div>
                <div class="notification-item unread">
                    <i class="fas fa-flask"></i>
                    <div class="notification-content">
                        <p data-ar="نتائج التحليل جاهزة للمريض فاطمة" data-fr="Résultats d'analyse prêts pour Fatima" data-en="Lab results ready for Fatima">نتائج التحليل جاهزة للمريض فاطمة</p>
                        <span class="time" data-ar="منذ 30 دقيقة" data-fr="Il y a 30 minutes" data-en="30 minutes ago">منذ 30 دقيقة</span>
                    </div>
                </div>
                <div class="notification-item">
                    <i class="fas fa-comment"></i>
                    <div class="notification-content">
                        <p data-ar="رسالة جديدة من د. خالد" data-fr="Nouveau message du Dr. Khaled" data-en="New message from Dr. Khaled">رسالة جديدة من د. خالد</p>
                        <span class="time" data-ar="منذ ساعة" data-fr="Il y a 1 heure" data-en="1 hour ago">منذ ساعة</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ═══════════════ MAIN CONTENT ═══════════════ -->
    <main class="main-content">

        <!-- ── HOME INTERFACE ── -->
        <section class="interface active" id="home-interface">
            <div class="page-header">
                <h2 data-ar="لوحة التحكم" data-fr="Tableau de bord" data-en="Dashboard">لوحة التحكم</h2>
                <p>مرحباً، د. <?php echo htmlspecialchars($doctor['full_name']); ?> • <?php echo date('d/m/Y'); ?></p>
            </div>

            <div class="cards-grid">
                <div class="main-card" onclick="toggleAddPatientForm()">
                    <div class="card-icon"><i class="fas fa-user-plus"></i></div>
                    <h3 data-ar="إضافة مريض" data-fr="Ajouter un patient" data-en="Add Patient">إضافة مريض</h3>
                </div>

                <div class="main-card" onclick="toggleCard('todayPatients')">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <h3 data-ar="مرضى اليوم" data-fr="Patients du jour" data-en="Today's Patients">مرضى اليوم</h3>
                    <div class="card-count"><?= count($todayPatients) ?></div>
                </div>

                <div class="main-card" onclick="toggleCard('newBookings')">
                    <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                    <h3 data-ar="حجوزات جديدة" data-fr="Nouvelles réservations" data-en="New Bookings">حجوزات جديدة</h3>
                    <div class="card-count"><?= count($appointments) ?></div>
                </div>

                <div class="main-card" onclick="toggleCard('myAppointments')">
                    <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3 data-ar="المواعيد القادمة" data-fr="Mes rendez-vous" data-en="My Appointments">المواعيد القادمة</h3>
                    <div class="card-count"><?= count($myAppointments) ?></div>
                </div>

                <div class="main-card" onclick="toggleCard('appointmentsHistory')">
                    <div class="card-icon"><i class="fas fa-history"></i></div>
                    <h3 data-ar="سجل المواعيد" data-fr="Historique" data-en="Appointments History">سجل المواعيد</h3>
                    <div class="card-count"><?= count($historyAppointments) ?></div>
                </div>

                <div class="main-card" onclick="toggleCard('qrScanner')">
                    <div class="card-icon"><i class="fas fa-qrcode"></i></div>
                    <h3 data-ar="مسح كود QR" data-fr="Scanner QR" data-en="Scan QR Code">مسح كود QR</h3>
                </div>

               


            </div><!-- /cards-grid -->

            <!-- Add Patient Inline Form — Accordion -->
            <div id="addPatientFormSection" style="display:none; animation: fadeIn 0.35s ease;">
                <div class="card-content" style="display:block; margin-bottom:24px; padding:0; overflow:visible;">
                    <div class="content-header" style="padding:16px 20px;">
                        <h2><i class="fas fa-user-plus" style="color:var(--primary);margin-left:8px;"></i> إضافة مريض جديد</h2>
                        <button onclick="toggleAddPatientForm()"><i class="fas fa-times"></i></button>
                    </div>

                    <!-- Accordion wrapper — مطابق تماماً لأقسام patient_inline.js -->
                    <div class="patient-file-inner" id="apf-file-inner" style="background:var(--bg-main);">
                        <div class="pif-accordion" id="apf-accordion">

                            <!-- ① المعلومات الشخصية — مطابق pif-sec-1 -->
                            <div class="pif-section" id="apf-sec-info">
                                <div class="pif-sec-header" onclick="apfToggleSection(this.parentElement)">
                                    <div class="pif-sec-icon"><i class="fas fa-user-circle"></i></div>
                                    <span class="pif-sec-title">المعلومات الشخصية</span>
                                    <span class="pif-sec-status">فارغ</span>
                                    <i class="fas fa-chevron-down pif-sec-arrow"></i>
                                </div>
                                <div class="pif-sec-body">
                                    <div class="pif-sec-body-inner">
                                        <div class="pif-grid-2">
                                            <div class="form-group">
                                                <label>اسم ولقب المريض <span style="color:#ef4444;">*</span></label>
                                                <input type="text" id="apf_full_name" placeholder="الاسم الكامل">
                                            </div>
                                            <div class="form-group">
                                                <label>تاريخ ومكان الميلاد</label>
                                                <input type="text" id="apf_birth_info" placeholder="مثال: 1990 — تلمسان">
                                            </div>
                                            <div class="form-group">
                                                <label>الجنس <span style="color:#ef4444;">*</span></label>
                                                <select id="apf_gender">
                                                    <option value="">-- اختر --</option>
                                                    <option value="ذكر">ذكر</option>
                                                    <option value="أنثى">أنثى</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>العمر <span style="color:#ef4444;">*</span></label>
                                                <input type="number" id="apf_age" min="0" max="150" placeholder="مثال: 35">
                                            </div>
                                            <div class="form-group">
                                                <label>الحالة العائلية</label>
                                                <input type="text" id="apf_marital_status" placeholder="أعزب / متزوج...">
                                            </div>
                                            <div class="form-group">
                                                <label>طبيعة العمل</label>
                                                <input type="text" id="apf_job" placeholder="موظف / طالب...">
                                            </div>
                                            <div class="form-group">
                                                <label>العنوان</label>
                                                <input type="text" id="apf_address" placeholder="المدينة، الحي...">
                                            </div>
                                            <div class="form-group">
                                                <label>رقم الهاتف</label>
                                                <input type="tel" id="apf_phone" placeholder="0555 123 456">
                                            </div>
                                            <div class="form-group">
    <label>البريد الإلكتروني</label>
   <input
    type="email"
    id="apf_email"
    placeholder="example@gmail.com"
    onblur="checkPatientAccount()">
</div>

<div class="form-group">
    <label>حالة الحساب</label>
    <input type="text" id="apf_account_status" value="جاري التحقق..." readonly>
</div>
                                            <div class="form-group">
                                                <label>تاريخ الدخول</label>
                                                <input type="date" id="apf_admission_date">
                                            </div>
                                            <div class="form-group">
                                                <label>الحالة</label>
                                                <select id="apf_residency_status">
                                                    <option value="">-- اختر --</option>
                                                    <option value="مقيم">مقيم</option>
                                                    <option value="غير مقيم">غير مقيم</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>رقم الغرفة</label>
                                                <input type="text" id="apf_room_number" placeholder="مثال: غرفة 12">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ② الفحص والأعراض — مطابق pif-sec-2 -->
                            <div class="pif-section" id="apf-sec-exam">
                                <div class="pif-sec-header" onclick="apfToggleSection(this.parentElement)">
                                    <div class="pif-sec-icon"><i class="fas fa-stethoscope"></i></div>
                                    <span class="pif-sec-title">الفحص والأعراض</span>
                                    <span class="pif-sec-status">فارغ</span>
                                    <i class="fas fa-chevron-down pif-sec-arrow"></i>
                                </div>
                                <div class="pif-sec-body">
                                    <div class="pif-sec-body-inner">
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                            <div class="form-group">
                                                <label>سبب الفحص <span style="color:#ef4444;">*</span></label>
                                                <textarea id="apf_reason_exam" rows="3" placeholder="سبب الزيارة..." style="min-height:100px;"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>الأعراض</label>
                                                <textarea id="apf_symptoms" rows="3" placeholder="الأعراض التي يشعر بها المريض..." style="min-height:100px;"></textarea>
                                            </div>
                                        </div>
                                        <div class="pif-grid-2">
                                            <div class="form-group">
                                                <label>ضغط الدم</label>
                                                <input type="text" id="apf_blood_pressure" placeholder="120/80">
                                            </div>
                                            <div class="form-group">
                                                <label>نسبة السكر في الدم</label>
                                                <input type="text" id="apf_blood_sugar" placeholder="mg/dL">
                                            </div>
                                            <div class="form-group">
                                                <label>معدل ضربات القلب</label>
                                                <input type="text" id="apf_heart_rate" placeholder="bpm">
                                            </div>
                                            <div class="form-group">
                                                <label>درجة الحرارة</label>
                                                <input type="text" id="apf_temperature" placeholder="°C">
                                            </div>
                                            <div class="form-group">
                                                <label>نسبة الأكسجين</label>
                                                <input type="text" id="apf_oxygen_level" placeholder="%">
                                            </div>
                                        </div>
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                            <div class="form-group">
                                                <label>الأمراض المزمنة (المريض)</label>
                                                <textarea id="apf_chronic_patient" rows="3" placeholder="السكري، الضغط..." style="min-height:90px;"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>الأمراض المزمنة (العائلة)</label>
                                                <textarea id="apf_chronic_family" rows="3" placeholder="أمراض وراثية..." style="min-height:90px;"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ③ متابعة الحمل — مطابق buildPregnancyContent في patient_inline.js -->
                            <div class="pif-section" id="apf-sec-preg">
                                <div class="pif-sec-header" onclick="apfToggleSection(this.parentElement)">
                                    <div class="pif-sec-icon"><i class="fas fa-baby"></i></div>
                                    <span class="pif-sec-title">متابعة الحمل</span>
                                    <span class="pif-sec-status">فارغ</span>
                                    <i class="fas fa-chevron-down pif-sec-arrow"></i>
                                </div>
                                <div class="pif-sec-body">
                                    <div class="pif-sec-body-inner">
                                        <div class="preg-sub-card blue">
                                            <div class="preg-sub-title"><i class="fas fa-baby-carriage"></i> بطاقة الحمل</div>
                                            <div class="form-group">
                                                <label>تاريخ آخر دورة</label>
                                                <input type="date" id="apf_last_period_date">
                                            </div>
                                            <div class="form-group">
                                                <label>تاريخ الولادة المتوقع</label>
                                                <input type="date" id="apf_expected_delivery_date">
                                            </div>
                                            <div class="pif-grid-2">
                                                <div class="form-group">
                                                    <label>فصيلة الدم</label>
                                                    <input type="text" id="apf_preg_blood_type" placeholder="فصيلة الدم...">
                                                </div>
                                                <div class="form-group">
                                                    <label>عدد مرات الحمل (G)</label>
                                                    <input type="number" id="apf_pregnancies_count" placeholder="عدد مرات الحمل (G)...">
                                                </div>
                                                <div class="form-group">
                                                    <label>عدد الولادات (P)</label>
                                                    <input type="number" id="apf_births_count" placeholder="عدد الولادات (P)...">
                                                </div>
                                                <div class="form-group">
                                                    <label>إجهاضات سابقة</label>
                                                    <input type="number" id="apf_miscarriages_count" placeholder="إجهاضات سابقة...">
                                                </div>
                                                <div class="form-group">
                                                    <label>قيصرية سابقة</label>
                                                    <input type="number" id="apf_c_sections_count" placeholder="قيصرية سابقة...">
                                                </div>
                                                <div class="form-group">
                                                    <label>حالة الأب</label>
                                                    <input type="text" id="apf_father_status" placeholder="حالة الأب...">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>أمراض مزمنة</label>
                                                <textarea id="apf_preg_chronic_diseases" rows="3" placeholder="أمراض مزمنة..."></textarea>
                                            </div>

                                            <div class="form-group">
                                                <label>ملاحظات عامة</label>
                                                <textarea id="apf_pregnancy_notes" rows="3" placeholder="ملاحظات عامة..."></textarea>
                                            </div>
                                        </div>
                                        <div class="preg-sub-card teal">
                                            <div class="preg-sub-title"><i class="fas fa-heartbeat"></i> متابعة الحمل</div>
                                            <div class="pif-grid-2">
                                                <div class="form-group">
                                                    <label>الوزن</label>
                                                    <input type="text" id="apf_preg_weight" placeholder="الوزن...">
                                                </div>
                                                <div class="form-group">
                                                    <label>ضغط الدم</label>
                                                    <input type="text" id="apf_preg_blood_pressure" placeholder="ضغط الدم...">
                                                </div>
                                                <div class="form-group">
                                                    <label>السكر</label>
                                                    <input type="text" id="apf_preg_sugar_level" placeholder="السكر...">
                                                </div>
                                                <div class="form-group">
                                                    <label>نبض الجنين</label>
                                                    <input type="text" id="apf_fetal_heartbeat" placeholder="نبض الجنين...">
                                                </div>
                                                <div class="form-group">
                                                    <label>حركة الجنين</label>
                                                    <input type="text" id="apf_fetal_movement" placeholder="حركة الجنين...">
                                                </div>
                                                <div class="form-group">
                                                    <label>وزن/حجم الجنين</label>
                                                    <input type="text" id="apf_fetal_weight" placeholder="وزن/حجم الجنين...">
                                                </div>
                                                <div class="form-group">
                                                    <label>وضعية الجنين</label>
                                                    <input type="text" id="apf_fetal_position" placeholder="وضعية الجنين...">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>ملاحظات الإيكوغرافيا</label>
                                                <textarea id="apf_echo_notes" rows="3" placeholder="ملاحظات الإيكوغرافيا..."></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>ملاحظات الطبيب</label>
                                                <textarea id="apf_followup_notes" rows="3" placeholder="ملاحظات الطبيب..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- ④ الفحوصات التكميلية — مطابق pif-sec-3 -->
                            <div class="pif-section" id="apf-sec-labs">
                                <div class="pif-sec-header" onclick="apfToggleSection(this.parentElement)">
                                    <div class="pif-sec-icon"><i class="fas fa-flask"></i></div>
                                    <span class="pif-sec-title">الفحوصات التكميلية</span>
                                    <span class="pif-sec-status">فارغ</span>
                                    <i class="fas fa-chevron-down pif-sec-arrow"></i>
                                </div>
                                <div class="pif-sec-body">
                                    <div class="pif-sec-body-inner">
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                            <div class="form-group">
                                                <label>التحاليل الطبية</label>
                                                <textarea id="apf_medical_tests" rows="4" placeholder="NFS، CRP، Glycémie..." style="min-height:100px;"></textarea>
                                                <button type="button" class="pif-btn pif-btn-primary" id="sendLabRequestBtn" style="margin-top:8px;display:block;width:100%;">📤 إرسال للمخبر</button>
                                            </div>
                                            <div class="form-group">
                                                <label>الأشعة (Radiologie)</label>
                                                <textarea id="apf_radiology" rows="4" placeholder="Echo abdominale، Rx thorax..." style="min-height:100px;"></textarea>
                                                <button type="button" class="pif-btn pif-btn-primary" id="sendRadiologyRequestBtn" style="margin-top:8px;display:block;width:100%;">📤 إرسال للأشعة</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ⑤ Fiche de traitement — مطابق buildFicheContent في patient_inline.js -->
                            <div class="pif-section" id="apf-sec-fiche">
                                <div class="pif-sec-header" onclick="apfToggleSection(this.parentElement)">
                                    <div class="pif-sec-icon"><i class="fas fa-notes-medical"></i></div>
                                    <span class="pif-sec-title">Fiche de traitement</span>
                                    <span class="pif-sec-status">فارغ</span>
                                    <i class="fas fa-chevron-down pif-sec-arrow"></i>
                                </div>
                                <div class="pif-sec-body">
                                    <div class="pif-sec-body-inner">
                                        <div style="background:#f0f9ff;padding:16px;border-radius:10px;border:1px solid #bae6fd;margin-bottom:4px;">
                                            <p style="color:#0369a1;font-size:0.78rem;font-weight:600;margin:0 0 12px 0;">
                                                <i class="fas fa-info-circle"></i> بطاقة خاصة بالممرض — يكتب الطبيب التعليمات العلاجية هنا
                                            </p>
                                            <div class="form-group">
                                                <label>🩺 التشخيص / Diagnostic</label>
                                                <textarea id="apf_fiche_diagnostic" rows="2" placeholder="اكتب التشخيص..."></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>💊 الأدوية والعلاجات / Médicaments &amp; traitements</label>
                                                <textarea id="apf_fiche_medications" rows="3" placeholder="مثال: Paracetamol 500mg — 3 fois/jour..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pif-actions-row">
                                        <button class="pif-btn pif-btn-success" id="apf-fiche-save-btn" onclick="apfSaveFicheTraitement()">💾 حفظ بطاقة العلاج</button>
                                        <button class="pif-btn pif-btn-primary" onclick="apfSendFicheToNurse()">📤 إرسال fiche للممرض</button>
                                        <button class="pif-btn pif-btn-ghost" onclick="apfPrintFicheTraitement()">🖨️ طباعة الفيش</button>
                                    </div>
                                </div>
                            </div>
                            <!-- ⑥ الوصفة الطبية — مطابق buildRxContent في patient_inline.js -->
                            <div class="pif-section" id="apf-sec-rx">
                                <div class="pif-sec-header" onclick="apfToggleSection(this.parentElement)">
                                    <div class="pif-sec-icon"><i class="fas fa-prescription"></i></div>
                                    <span class="pif-sec-title">الوصفة الطبية</span>
                                    <span class="pif-sec-status">فارغ</span>
                                    <i class="fas fa-chevron-down pif-sec-arrow"></i>
                                </div>
                                <div class="pif-sec-body">
                                    <div class="pif-sec-body-inner">
                                        <div class="pif-rx-sheet">
                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                                <div class="form-group">
                                                    <label>اسم المريض</label>
                                                    <input type="text" id="apf_rx_patient_name" placeholder="اسم المريض...">
                                                </div>
                                                <div class="form-group">
                                                    <label>التاريخ</label>
                                                    <input type="date" id="apf_rx_date">
                                                </div>
                                            </div>
                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                                <div class="form-group">
                                                    <label>الأدوية</label>
                                                    <textarea id="apf_rx_prescription" rows="4" placeholder="اكتب الأدوية هنا..." style="min-height:110px;"></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>تعليمات الطبيب</label>
                                                    <textarea id="apf_rx_doctor_notes" rows="4" placeholder="تعليمات الطبيب..." style="min-height:110px;"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pif-actions-row">
                                        <button class="pif-btn pif-btn-success" onclick="apfSavePrescription()">💾 حفظ الوصفة</button>
                                        <button class="pif-btn pif-btn-primary" onclick="apfSendPrescriptionToPharmacy()">📤 إرسال الوصفة للصيدلي</button>
                                        <button class="pif-btn pif-btn-ghost" onclick="apfPrintPrescription()">🖨️ طباعة</button>
                                    </div>
                                </div>
                            </div>
                            <!-- ⑦ التقرير الطبي / Rapport Médical — نسخة طبق الأصل من buildRapportContent في patient_inline.js -->
                            <div class="pif-section" id="apf-sec-rapport">
                                <div class="pif-sec-header" onclick="apfToggleSection(this.parentElement)">
                                    <div class="pif-sec-icon"><i class="fas fa-file-medical-alt"></i></div>
                                    <span class="pif-sec-title">التقرير الطبي / Rapport Médical</span>
                                    <span class="pif-sec-status" id="apf-rapport-status-badge">فارغ</span>
                                    <i class="fas fa-chevron-down pif-sec-arrow"></i>
                                </div>
                                <div class="pif-sec-body">
                                    <div class="pif-sec-body-inner">
                                        <div class="pif-rapport-sheet" id="apf-rapport-sheet" style="background:transparent;border:none;box-shadow:none;padding:0;font-family:'Cairo',sans-serif;">

                                            <!-- عناصر مخفية للحفاظ على عمل الـ JS (الترويسة) — لا تحذف -->
                                            <div style="display:none;">
                                                <span class="inst-main" id="apf-rapport-inst-main">Centre Hospitalo-Universitaire</span>
                                                <strong id="apf-rapport-chef">Pr. ST HEBRI</strong>
                                                <span class="inst-service" id="apf-rapport-service">Service de Médecine Interne</span>
                                                <span id="apf-rapport-doctor-display"></span>
                                            </div>

                                            <!-- ══ بيانات أساسية 2×2 ══ -->
                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                                <div class="form-group">
                                                    <label>التاريخ</label>
                                                    <input type="date" id="apf_rapport_date" class="rapport-field">
                                                </div>
                                                <div class="form-group">
                                                    <label>اسم المريض</label>
                                                    <input type="text" id="apf_rapport_patient_name" class="rapport-field" placeholder="اسم المريض...">
                                                </div>
                                                <div class="form-group">
                                                    <label>السن</label>
                                                    <input type="text" id="apf_rapport_age" class="rapport-field" placeholder="السن...">
                                                </div>
                                                <div class="form-group">
                                                    <label>الطبيب المعالج</label>
                                                    <input type="text" id="apf_rapport_doctor" class="rapport-field" placeholder="Dr. ...">
                                                </div>
                                            </div>

                                            <!-- ══ محتوى التقرير ══ -->
                                            <div class="form-group" style="margin-bottom:0;">
                                                <label>محتوى التقرير الطبي</label>
                                                <textarea id="apf_rapport_content"
                                                          class="rapport-field"
                                                          rows="8"
                                                          placeholder="اكتب محتوى التقرير الطبي هنا...&#10;&#10;(التشخيص — الأعراض — العلاج — التوصيات...)"
                                                          style="min-height:170px;"></textarea>
                                            </div>

                                        </div><!-- /.pif-rapport-sheet -->
                                    </div>
                                    <div class="pif-actions-row">
                                        <button class="pif-btn pif-btn-success" id="apf-rapport-save-btn" onclick="apfSaveRapportMedical()">💾 حفظ التقرير</button>
                                        <button class="pif-btn pif-btn-ghost" onclick="apfPrintRapportMedical()">🖨️ طباعة PDF</button>
                                    </div>
                                </div>
                            </div>

                            <!-- ⑧ المواعيد القادمة — مطابق pif-sec-6 في patient_inline.js -->
                            <div class="pif-section" id="apf-sec-appt">
                                <div class="pif-sec-header" onclick="apfToggleSection(this.parentElement)">
                                    <div class="pif-sec-icon"><i class="fas fa-calendar-plus"></i></div>
                                    <span class="pif-sec-title">المواعيد القادمة</span>
                                    <span class="pif-sec-status">فارغ</span>
                                    <i class="fas fa-chevron-down pif-sec-arrow"></i>
                                </div>
                                <div class="pif-sec-body">
                                    <div class="pif-sec-body-inner">
                                        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                                            <div class="form-group" style="margin-bottom:0;flex:1;min-width:170px;">
                                                <label>هل يحتاج موعد؟</label>
                                                <select id="apf_needs_appointment" onchange="apfToggleApptFields(this.value)">
                                                    <option value="no">لا</option>
                                                    <option value="yes">نعم</option>
                                                </select>
                                            </div>
                                            <div id="apf_appt_fields" style="display:none;flex:2;min-width:240px;">
                                                <div style="display:flex;gap:12px;">
                                                    <div class="form-group" style="margin-bottom:0;flex:1;">
                                                        <label>التاريخ</label>
                                                        <input type="date" id="apf_next_appointment_date">
                                                    </div>
                                                    <div class="form-group" style="margin-bottom:0;flex:1;">
                                                        <label>الوقت</label>
                                                        <input type="time" id="apf_next_appointment_time">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pif-actions-row">
                                        <button class="pif-btn pif-btn-ghost" onclick="apfPrintMedicalRecord()">🖨️ طباعة السجل</button>
                                        <button class="pif-btn pif-btn-primary" onclick="apfSaveRecord()">💾 حفظ الملف</button>
                                    </div>
                                </div>
                            </div>
                        </div><!-- /.pif-accordion -->
                    </div><!-- /.patient-file-inner -->

                    <!-- Action Row -->
                    <div class="pif-actions-row">
                        <button class="pif-btn pif-btn-ghost" onclick="toggleAddPatientForm()">
                            <i class="fas fa-times" style="margin-left:6px;"></i> إلغاء
                        </button>
                        <button class="pif-btn pif-btn-primary" onclick="apfResetAll()" title="مسح جميع الحقول">
                            <i class="fas fa-redo" style="margin-left:6px;"></i> إعادة تعيين
                        </button>
                        <button class="pif-btn pif-btn-success" onclick="apfSaveRecord()" id="apfSaveBtn">
                            <i class="fas fa-save" style="margin-left:6px;"></i> حفظ في الأرشيف
                        </button>
                    </div>
                </div>
            </div>

            <!-- Today Patients Content -->
            <div class="card-content" id="todayPatients">
                <div class="content-header">
                    <h2 data-ar="مرضى اليوم" data-fr="Patients du jour" data-en="Today's Patients">مرضى اليوم</h2>
                    <button onclick="closeCardContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="patients-list">
<?php if(!empty($todayPatients)): ?>
    <?php foreach($todayPatients as $patient): ?>
        <div class="patient-item"
             data-admission-date="<?= htmlspecialchars($patient['admission_date'] ?? '') ?>"
             data-residency-status="<?= htmlspecialchars($patient['residency_status'] ?? '') ?>"
             data-appt-patient-id="<?= (int)($patient['patient_id'] ?? 0) ?>"
             data-medical-record-id="<?= (int)($patient['medical_record_id'] ?? 0) ?>">
            <div class="patient-info">
                <h4 style="color:var(--primary);" onclick="openPatientFile(<?= (int)($patient['patient_id'] ?? 0) ?>)">
                    <?= htmlspecialchars($patient['patient_name']) ?>
                </h4>
                <p class="appointment-time"><?= date('H:i', strtotime($patient['appointment_time'])) ?></p>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <button onclick="markCompleted(<?= $patient['id'] ?>)"
                        style="background:#10b981;color:white;border:none;padding:7px 14px;border-radius:8px;cursor:pointer;font-size:0.82rem;font-weight:600;transition:0.2s;">
                    ✅ حضر
                </button>
                <button onclick="markNoShow(<?= $patient['id'] ?>)"
                        style="background:#ef4444;color:white;border:none;padding:7px 14px;border-radius:8px;cursor:pointer;font-size:0.82rem;font-weight:600;transition:0.2s;">
                    ❌ لم يحضر
                </button>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center; padding:30px; color:var(--text-muted); font-size:0.9rem;">لا يوجد مرضى اليوم</p>
<?php endif; ?>
                </div>
            </div>

            <!-- New Bookings Content -->
            <div class="card-content" id="newBookings">
                <div class="content-header">
                    <h2 data-ar="حجوزات جديدة" data-fr="Nouvelles réservations" data-en="New Bookings">حجوزات جديدة</h2>
                    <button onclick="closeCardContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="bookings-list">
<?php if(!empty($appointments)): ?>
    <?php foreach($appointments as $app): ?>
        <div class="booking-item">
            <div class="booking-patient">
                <i class="fas fa-user-circle"></i>
                <div>
                    <h4><?= htmlspecialchars($app['patient_name']) ?></h4>
                    <p><?= htmlspecialchars($app['phone']) ?></p>
                </div>
            </div>
            <div class="booking-datetime">
                <input type="date" value="<?= htmlspecialchars($app['appointment_date'] ?? '') ?>" id="appt_date_<?= $app['id'] ?>">
                <input type="time" value="<?= htmlspecialchars($app['appointment_time'] ?? '') ?>" id="appt_time_<?= $app['id'] ?>">
                <button onclick="confirmAppointment(<?= $app['id'] ?>)">✅ تأكيد</button>
                <button onclick="rejectAppointment(<?= $app['id'] ?>)"
                        style="background:#ef4444;color:#fff;border:none;padding:8px 14px;border-radius:8px;cursor:pointer;font-size:0.82rem;font-weight:600;">
                    ❌ رفض
                </button>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center;padding:30px;color:var(--text-muted);font-size:0.9rem;">لا توجد حجوزات جديدة</p>
<?php endif; ?>
                </div>
            </div>

            <!-- My Appointments Content -->
            <div class="card-content" id="myAppointments">
                <div class="content-header">
                    <h2>مواعيدي القادمة</h2>
                    <button onclick="closeCardContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="appointments-list bookings-list">
<?php if(!empty($myAppointments)): ?>
    <?php foreach($myAppointments as $app): ?>
        <div class="booking-item">
            <div class="booking-patient">
                <i class="fas fa-user-circle"></i>
                <div>
                    <h4><?= htmlspecialchars($app['patient_name']) ?></h4>
                    <p><?= htmlspecialchars($app['appointment_date']) ?> — <?= date('H:i', strtotime($app['appointment_time'])) ?></p>
                </div>
            </div>
            <div class="booking-datetime">
                <button onclick="openRescheduleModal(<?= $app['id'] ?>)">✏️ تعديل</button>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center;padding:30px;color:var(--text-muted);font-size:0.9rem;">لا توجد مواعيد مؤكدة</p>
<?php endif; ?>
                </div>
            </div>

            <!-- Appointments History -->
            <div class="card-content" id="appointmentsHistory">
                <div class="content-header">
                    <h2>سجل المواعيد</h2>
                    <button onclick="closeCardContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="appointments-list bookings-list">
<?php if(!empty($historyAppointments)): ?>
    <?php foreach($historyAppointments as $app): ?>
        <div class="booking-item">
            <div class="booking-patient">
                <i class="fas fa-user-circle"></i>
                <div>
                    <h4><?= htmlspecialchars($app['patient_name']) ?></h4>
                    <p><?= htmlspecialchars($app['appointment_date']) ?> — <?= date('H:i', strtotime($app['appointment_time'])) ?></p>
                </div>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <?php if($app['status'] == 'completed'): ?>
                    <span style="background:rgba(16,185,129,0.12);color:#10b981;padding:6px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;">✅ حضر</span>
                <?php else: ?>
                    <span style="background:rgba(239,68,68,0.12);color:#ef4444;padding:6px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;">❌ ماجاش</span>
                <?php endif; ?>
                <button onclick="openRescheduleModal(<?= $app['id'] ?>)"
                        style="background:rgba(14,165,233,0.1);color:var(--primary);border:none;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:0.82rem;font-weight:600;">
                    🔄 إعادة موعد
                </button>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center;padding:30px;color:var(--text-muted);font-size:0.9rem;">لا يوجد سجل بعد</p>
<?php endif; ?>
                </div>
            </div>

            <!-- QR Scanner -->
            <div class="card-content" id="qrScanner">
                <div class="content-header">
                    <h2 data-ar="مسح كود QR" data-fr="Scanner QR" data-en="Scan QR Code">مسح كود QR</h2>
                    <button onclick="closeCardContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="qr-scanner">
                    <div class="scanner-area">
                        <i class="fas fa-qrcode"></i>
                        <p data-ar="امسح كود QR للوصول إلى الملف الطبي للمريض" data-fr="Scannez le QR code du dossier patient" data-en="Scan patient QR code">امسح كود QR للوصول إلى الملف الطبي للمريض</p>
                        <button class="scan-btn" data-ar="مسح الكود" data-fr="Scanner" data-en="Scan Code">مسح الكود</button>
                    </div>
                    <!-- تمت إزالة السجل الطبي الوهمي / البيانات التجريبية من قسم QR.
                         لا يُعرض أي ملف طبي أو مريض قبل المسح. يظهر محتوى الـ QR الحقيقي بعد المسح. -->
                </div>
            </div>

            <!-- Statistics -->
            <div class="card-content" id="statistics">
                <div class="content-header">
                    <h2 data-ar="إحصائيات" data-fr="Statistiques" data-en="Statistics">إحصائيات</h2>
                    <button onclick="closeCardContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="statistics-content">
                    <canvas id="weeklyChart"></canvas>
                    <div class="stats-summary">
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <h4>85</h4>
                                <p data-ar="مريض هذا الأسبوع" data-fr="Patients cette semaine" data-en="Patients this week">مريض هذا الأسبوع</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-calendar-check"></i>
                            <div>
                                <h4>92%</h4>
                                <p data-ar="نسبة الحضور" data-fr="Taux de présence" data-en="Attendance rate">نسبة الحضور</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- ══════════════════════════════════════════
                 INPATIENTS: Modal الملف الطبي
            ══════════════════════════════════════════ -->
            <div id="inp-file-modal-overlay"
                 style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:9998;align-items:center;justify-content:center;backdrop-filter:blur(4px);"
                 onclick="if(event.target===this)inpCloseFileModal()">
                <div id="inp-file-modal-box"
                     style="background:#fff;border-radius:20px;width:min(680px,96vw);max-height:88vh;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.28);overflow:hidden;position:relative;direction:rtl;font-family:'Cairo',sans-serif;animation:inpModalIn .28s cubic-bezier(.4,0,.2,1);">
                    <div id="inp-file-modal-content" style="display:flex;flex-direction:column;flex:1;min-height:0;height:100%;"></div>
                </div>
            </div>
            <style>
                @keyframes inpModalIn {
                    from { opacity:0; transform:scale(.94) translateY(18px); }
                    to   { opacity:1; transform:scale(1)   translateY(0);    }
                }
            </style>

            <!-- ══════════════════════════════════════════
                 INPATIENTS: نافذة تسجيل الخروج التجريبية
                 (داخل قسم المرضى المقيمون فقط)
            ══════════════════════════════════════════ -->
            <div id="inp-discharge-overlay"
                 style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(3px);">
                <div style="background:#fff;border-radius:18px;padding:28px 24px 22px;width:min(400px,90vw);box-shadow:0 20px 60px rgba(0,0,0,.25);position:relative;direction:rtl;font-family:'Cairo',sans-serif;">
                    <!-- زاوية إغلاق -->
                    <button onclick="inpCloseDischarge()"
                            style="position:absolute;left:14px;top:14px;background:rgba(239,68,68,.1);border:none;color:#ef4444;width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-times"></i>
                    </button>

                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                        <div style="width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,#ef4444,#f87171);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;flex-shrink:0;">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div>
                            <h3 style="margin:0;font-size:0.98rem;font-weight:800;color:#0f172a;">تسجيل خروج المريض</h3>
                            <p id="inp-discharge-patient-name" style="margin:0;font-size:0.78rem;color:#64748b;"></p>
                        </div>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:6px;">
                            <i class="fas fa-calendar-alt" style="color:#0ea5e9;margin-left:4px;"></i> تاريخ الخروج <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="date" id="inp-discharge-date"
                               style="width:100%;padding:9px 13px;border:1px solid rgba(14,165,233,.25);border-radius:9px;font-size:0.85rem;font-family:'Cairo',sans-serif;background:#f8fafc;color:#0f172a;outline:none;box-sizing:border-box;">
                    </div>

                    <div style="margin-bottom:22px;">
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:6px;">
                            <i class="fas fa-clipboard-list" style="color:#0ea5e9;margin-left:4px;"></i> نوع الخروج <span style="color:#ef4444;">*</span>
                        </label>
                        <select id="inp-discharge-type"
                                style="width:100%;padding:9px 13px;border:1px solid rgba(14,165,233,.25);border-radius:9px;font-size:0.85rem;font-family:'Cairo',sans-serif;background:#f8fafc;color:#0f172a;outline:none;cursor:pointer;box-sizing:border-box;">
                            <option value="">-- اختر نوع الخروج --</option>
                            <option value="شفاء">✅ شفاء</option>
                            <option value="تحويل الى مصلحة اخرى ">🔄الى مصلحة اخرى تحويل</option>
                            <option value="خروج بطلب شخصي">🚪 خروج بطلب شخصي</option>
                            <option value="وفاة">🕊️ وفاة</option>
                        </select>
                    </div>

                    <!-- حقل المصلحة — يظهر فقط عند تحويل -->
                    <div id="inpTransferSvcField" style="display:none;margin-bottom:14px;animation:pfmFieldIn .2s ease;">
                        <label style="display:block;font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:6px;"><i class="fas fa-hospital" style="color:#0ea5e9;margin-left:4px;"></i> اسم المصلحة المحوّل إليها <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="inpTransferSvcName" placeholder="مثال: مصلحة الجراحة..." style="width:100%;padding:9px 13px;border:1px solid rgba(14,165,233,.25);border-radius:9px;font-size:0.85rem;font-family:'Cairo',sans-serif;background:#f0f9ff;color:#0f172a;outline:none;box-sizing:border-box;">
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button onclick="inpCloseDischarge()"
                                class="pif-btn pif-btn-ghost" style="flex:1;">
                            إلغاء
                        </button>
                        <button onclick="inpConfirmDischarge()"
                                class="pif-btn pif-btn-success" style="flex:2;">
                            <i class="fas fa-check" style="margin-left:6px;"></i> تأكيد الخروج
                        </button>
                    </div>
                </div>
            </div>

        </section>

        <!-- ── AI INTERFACE ── -->
        <section class="interface" id="ai-interface">
            <div class="page-header">
                <h2>مركز البيانات الذكي</h2>
                <p>أدوات ذكية لتحسين سير العمل الطبي</p>
            </div>

            <div class="ai-hero-card">
                <div class="pulsing-brain"><i class="fas fa-brain"></i></div>
                <h2 data-ar="مساعدك الذكي جاهز للعمل" data-fr="Votre assistant intelligent est prêt" data-en="Your Smart Assistant is Ready">مساعدك الذكي جاهز للعمل</h2>
                <p data-ar="استخدم قوة الذكاء الاصطناعي لتحسين سير عملك الطبي" data-fr="Utilisez la puissance de l'IA pour améliorer votre pratique médicale" data-en="Use the power of AI to enhance your medical workflow">استخدم قوة الذكاء الاصطناعي لتحسين سير عملك الطبي</p>
                <div class="ai-features">
                    <div class="ai-feature">
                        <i class="fas fa-chart-line"></i>
                        <p data-ar="دقة 97%" data-fr="Précision 97%" data-en="97% Accuracy">دقة 97%</p>
                    </div>
                    <div class="ai-feature">
                        <i class="fas fa-clock"></i>
                        <p data-ar="توفير 3 ساعات يومياً" data-fr="Économie de 3h/jour" data-en="Save 3 hours daily">توفير 3 ساعات يومياً</p>
                    </div>
                    <div class="ai-feature">
                        <i class="fas fa-shield-alt"></i>
                        <p data-ar="آمن ومشفر" data-fr="Sécurisé et crypté" data-en="Secure & Encrypted">آمن ومشفر</p>
                    </div>
                </div>
            </div>

            <div class="ai-cards-grid">
                <div class="ai-card" onclick="toggleAICard('autoOrganize')">
                    <div class="ai-badge">AI</div>
                    <i class="fas fa-folder-open"></i>
                    <h3 data-ar="تنظيم الملفات تلقائياً" data-fr="Organisation automatique" data-en="Auto Organize Files">تنظيم الملفات تلقائياً</h3>
                </div>
                <div class="ai-card" onclick="toggleAICard('patientArchive')">
                   
                    <i class="fas fa-archive"></i>
                    <h3 data-ar="أرشيف المرضى" data-fr="Archive des patients" data-en="Patient Archive">أرشيف المرضى</h3>
                </div>
                <div class="ai-card" onclick="toggleAICard('reportGen')">
                    <div class="ai-badge">AI</div>
                    <i class="fas fa-file-medical"></i>
                    <h3 data-ar="توليد تقارير طبية" data-fr="Générer des rapports" data-en="Generate Reports">توليد تقارير طبية</h3>
                </div>
                <div class="ai-card" onclick="toggleAICard('reportsArchive')">
                    <div class="ai-badge">AI</div>
                    <i class="fas fa-folder-open"></i>
                    <h3 data-ar="أرشيف التقارير الطبية" data-fr="Archive des rapports" data-en="Reports Archive">أرشيف التقارير الطبية</h3>
                </div>
            </div>

            <div class="ai-content" id="autoOrganize">
                <?php
define('AFO_EMBEDDED', true);
include __DIR__ . '/ai_file_organizer.php';
?>
            </div>

       
                <div class="ai-content" id="patientArchive">

    <div class="content-header">
        <h2 data-ar="أرشيف المرضى"
            data-fr="Archive des patients"
            data-en="Patient Archive">
            أرشيف المرضى
        </h2>

        <button onclick="closeAIContent()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="archive-search">
      <input
    type="text"
    id="archiveSearch"
    placeholder="ابحث عن مريض..."
    oninput="searchArchivePatients(this.value)">
    </div>

    <div class="archive-list">

<?php

require 'db.php';

$stmtArchive = $pdo->query("
    SELECT * FROM archived_records
    ORDER BY created_at DESC
");

$archives = $stmtArchive->fetchAll(PDO::FETCH_ASSOC);

foreach($archives as $archive):

?>

        <div class="archive-item">

            <div class="archive-patient-info">

                <i class="fas fa-user-circle"></i>

                <div>
                    <h4><?= htmlspecialchars($archive['patient_name']) ?></h4>

                    <p>
                        آخر زيارة :
                        <?= htmlspecialchars($archive['created_at']) ?>
                    </p>
                </div>

            </div>

            <button class="view-records-btn" data-id="<?= (int)($archive['medical_record_id'] ?? 0) ?>">
                عرض السجلات
            </button>

        </div>

<?php endforeach; ?>

    </div>

</div>
            <div class="ai-content" id="reportGen">
                <div class="content-header">
                    <h2 data-ar="توليد تقارير طبية" data-fr="Générer des rapports" data-en="Generate Reports">توليد تقارير طبية</h2>
                    <button onclick="closeAIContent()"><i class="fas fa-times"></i></button>
                </div>
                <!-- نقطة تركيب ميزة توليد التقارير الطبية بالذكاء الاصطناعي.
                     تُبنى الواجهة بالكامل عبر medical_report.js وتتصل بـ generate_medical_report.php -->
                <div id="mrai-root" data-endpoint="generate_medical_report.php"></div>
            </div>

            <!-- أرشيف التقارير الطبية (قسم مستقل عن أرشيف المرضى) -->
            <div class="ai-content" id="reportsArchive">
                <div class="content-header">
                    <h2 data-ar="أرشيف التقارير الطبية" data-fr="Archive des rapports" data-en="Reports Archive">أرشيف التقارير الطبية</h2>
                    <button onclick="closeAIContent()"><i class="fas fa-times"></i></button>
                </div>
                <!-- تُبنى الواجهة بالكامل عبر medical_reports_archive.js -->
                <div id="mra-root" data-endpoint="medical_reports_archive.php"></div>
            </div>

        </section>

        <!-- ── MESSAGES INTERFACE ── -->
        <section class="interface" id="messages-interface">
            <div class="page-header">
                <h2>المحادثات</h2>
                <p>تواصل مع مرضاك وزملائك الأطباء</p>
            </div>
            <div class="messages-cards">
                <div class="message-card" onclick="toggleMessageCard('patientChats')">
                    <i class="fas fa-user-injured"></i>
                    <h3 data-ar="المرضى" data-fr="Patients" data-en="Patients">المرضى</h3>
                    <span class="message-count">8</span>
                </div>
                <div class="message-card" onclick="toggleMessageCard('doctorChats')">
                    <i class="fas fa-user-md"></i>
                    <h3 data-ar="شبكة الأطباء" data-fr="Réseau de médecins" data-en="Doctors Network">شبكة الأطباء</h3>
                    <span class="message-count">3</span>
                </div>
            </div>

            <div class="chat-content" id="patientChats">
                <div class="content-header">
                    <h2 data-ar="محادثات المرضى" data-fr="Chats patients" data-en="Patient Chats">محادثات المرضى</h2>
                    <button onclick="closeMessageContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="chat-list">
                    <div class="chat-item">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='50' height='50'%3E%3Ccircle cx='25' cy='25' r='25' fill='%234A90E2'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='white' font-size='20' font-family='Arial'%3Eم%3C/text%3E%3C/svg%3E" alt="patient">
                        <div class="chat-info">
                            <h4>محمد علي</h4>
                            <p>شكراً دكتور على المتابعة...</p>
                        </div>
                        <div class="chat-meta">
                            <span class="time">10:30</span>
                            <span class="unread-badge">2</span>
                        </div>
                    </div>
                    <div class="chat-item">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='50' height='50'%3E%3Ccircle cx='25' cy='25' r='25' fill='%2350C878'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='white' font-size='20' font-family='Arial'%3Eف%3C/text%3E%3C/svg%3E" alt="patient">
                        <div class="chat-info">
                            <h4>فاطمة حسن</h4>
                            <p>هل يمكن تغيير الموعد؟</p>
                        </div>
                        <div class="chat-meta">
                            <span class="time">أمس</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-content" id="doctorChats">
                <!-- سيتم تحميل واجهة المحادثة الكاملة هنا عبر JavaScript -->
            </div>
        </section>

        <!-- ── SERVICES INTERFACE ── -->
        <section class="interface" id="services-interface">
            <div class="page-header">
                <h2>الخدمات الخارجية</h2>
                <p>نتائج المختبر، نتائج الأشعة، والمستلزمات الطبية</p>
            </div>

            <!-- QR Quick Access Card -->
            <div class="svc-qr-access-card">
                <div class="svc-qr-access-inner">
                    <div class="svc-qr-access-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="svc-qr-access-text">
                        <h4>الوصول السريع بواسطة QR</h4>
                        <p>امسح رمز QR لفتح نتائج التحاليل أو الأشعة مباشرة</p>
                    </div>
                    <button class="scan-btn svc-qr-scan-btn">
                        <i class="fas fa-camera"></i>
                        مسح QR
                    </button>
                </div>
            </div>

            <!-- 3 Service Cards -->
            <div class="services-cards">
                <div class="service-card" onclick="toggleServiceCard('labResults')">
                    <i class="fas fa-flask"></i>
                    <h3 data-ar="النتائج المخبرية" data-fr="Résultats de laboratoire" data-en="Lab Results">النتائج المخبرية</h3>
                </div>
                <div class="service-card" onclick="toggleServiceCard('xrayResults')">
                    <i class="fas fa-x-ray"></i>
                    <h3 data-ar="نتائج الأشعة" data-fr="Résultats radiologie" data-en="Radiology Results">نتائج الأشعة</h3>
                </div>
                <div class="service-card" onclick="toggleServiceCard('medicalSupplies')">
                    <i class="fas fa-clinic-medical"></i>
                    <h3 data-ar="دليل المستلزمات الطبية" data-fr="Guide des fournitures" data-en="Medical Supplies Guide">دليل المستلزمات الطبية</h3>
                </div>
            </div>

            <!-- Lab Results Content -->
            <div class="service-content" id="labResults">
                <div class="content-header">
                    <h2 data-ar="النتائج المخبرية" data-fr="Résultats de laboratoire" data-en="Lab Results">النتائج المخبرية</h2>
                    <button onclick="closeServiceContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="svc-results-toolbar">
                    <div class="svc-results-count" id="labResultsCount">
                        <i class="fas fa-flask"></i>
                        <span>3 نتائج واردة</span>
                    </div>
                    <div class="svc-results-filter">
                        <button class="svc-filter-btn active" onclick="filterResults('lab','all',this)">الكل</button>
                        <button class="svc-filter-btn" onclick="filterResults('lab','new',this)">جديدة</button>
                        <button class="svc-filter-btn" onclick="filterResults('lab','reviewed',this)">تمت المراجعة</button>
                    </div>
                </div>
                <div class="svc-results-grid" id="labResultsGrid">
                    <!-- Rendered by JS -->
                </div>
            </div>

            <!-- Xray Results Content -->
            <div class="service-content" id="xrayResults">
                <div class="content-header">
                    <h2 data-ar="نتائج الأشعة" data-fr="Résultats radiologie" data-en="Radiology Results">نتائج الأشعة</h2>
                    <button onclick="closeServiceContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="svc-results-toolbar">
                    <div class="svc-results-count" id="xrayResultsCount">
                        <i class="fas fa-x-ray"></i>
                        <span>2 نتائج واردة</span>
                    </div>
                    <div class="svc-results-filter">
                        <button class="svc-filter-btn active" onclick="filterResults('xray','all',this)">الكل</button>
                        <button class="svc-filter-btn" onclick="filterResults('xray','new',this)">جديدة</button>
                        <button class="svc-filter-btn" onclick="filterResults('xray','reviewed',this)">تمت المراجعة</button>
                    </div>
                </div>
                <div class="svc-results-grid" id="xrayResultsGrid">
                    <!-- Rendered by JS -->
                </div>
            </div>

            <!-- Medical Supplies Content (unchanged) -->
            <div class="service-content" id="medicalSupplies">
                <div class="content-header">
                    <h2 data-ar="دليل المستلزمات الطبية" data-fr="Guide des fournitures" data-en="Medical Supplies Guide">دليل المستلزمات الطبية</h2>
                    <button onclick="closeServiceContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="supplies-list">
                    <div class="supply-item">
                        <i class="fas fa-store"></i>
                        <div class="supply-info">
                            <h4>صيدلية النور</h4>
                            <p>شارع الجمهورية - جميع المستلزمات الطبية</p>
                            <span class="location"><i class="fas fa-map-marker-alt"></i> 2.5 كم</span>
                        </div>
                    </div>
                    <div class="supply-item">
                        <i class="fas fa-store"></i>
                        <div class="supply-info">
                            <h4>مركز الشفاء الطبي</h4>
                            <p>حي السلام - أجهزة ومعدات طبية</p>
                            <span class="location"><i class="fas fa-map-marker-alt"></i> 3.8 كم</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Viewer Modal (inline, for lab & xray reports) -->
            <div class="svc-report-modal" id="svcReportModal" onclick="closeSvcReportModal(event)">
                <div class="svc-report-modal-box" onclick="event.stopPropagation()">
                    <div class="svc-report-modal-header">
                        <h3 id="svcReportModalTitle">التقرير</h3>
                        <button onclick="closeSvcReportModal()" class="svc-report-close-btn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="svc-report-modal-body" id="svcReportModalBody"></div>
                </div>
            </div>
        </section>

        <!-- ── SETTINGS INTERFACE ── -->
        <section class="interface" id="settings-interface">
            <div class="page-header">
                <h2>الإعدادات</h2>
                <p>إدارة حسابك وتفضيلاتك</p>
            </div>

            <div class="settings-profile-header">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Ccircle cx='40' cy='40' r='40' fill='%230ea5e9'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='white' font-size='32' font-family='Arial'%3Eد%3C/text%3E%3C/svg%3E" alt="Profile">
                <h3>د. <?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                <p><?php echo htmlspecialchars($doctor['specialty'] ?? ''); ?> • <?php echo htmlspecialchars($doctor['experience'] ?? '0'); ?> سنة خبرة</p>
            </div>

            <div class="settings-list">
                <div class="settings-group">
                    <h4 class="settings-group-title">الحساب</h4>
                    <div class="settings-option" onclick="openEditProfile()">
                        <div class="settings-option-content">
                            <div class="settings-icon"><i class="fas fa-user-edit"></i></div>
                            <div class="settings-text">
                                <span>تعديل الملف الشخصي</span>
                                <small>تحديث الاسم والمعلومات</small>
                            </div>
                        </div>
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    <div class="settings-option" onclick="openChangePassword()">
                        <div class="settings-option-content">
                            <div class="settings-icon"><i class="fas fa-lock"></i></div>
                            <div class="settings-text">
                                <span>تغيير كلمة المرور</span>
                                <small>حماية حسابك</small>
                            </div>
                        </div>
                        <i class="fas fa-chevron-left"></i>
                    </div>
                </div>

                <div class="settings-group">
                    <h4 class="settings-group-title">المرضى</h4>
                    <div class="settings-option" onclick="openMyPatients()">
                        <div class="settings-option-content">
                            <div class="settings-icon"><i class="fas fa-users"></i></div>
                            <div class="settings-text">
                                <span>مرضاي</span>
                                <small>عرض وإدارة جميع المرضى</small>
                            </div>
                        </div>
                        <i class="fas fa-chevron-left"></i>
                    </div>
                </div>

                <div class="settings-group">
                    <h4 class="settings-group-title">التطبيق</h4>
                    <div class="settings-option">
                        <div class="settings-option-content">
                            <div class="settings-icon"><i class="fas fa-bell"></i></div>
                            <div class="settings-text">
                                <span>الإشعارات</span>
                                <small>إدارة التنبيهات</small>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="settings-option">
                        <div class="settings-option-content">
                            <div class="settings-icon"><i class="fas fa-moon"></i></div>
                            <div class="settings-text">
                                <span>الوضع الليلي</span>
                                <small>تفعيل المظهر الداكن</small>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" onchange="toggleTheme()">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="settings-group">
                    <a href="logout.php" class="settings-option logout-option" style="text-decoration:none;">
                        <div class="settings-option-content">
                            <div class="settings-icon logout-icon"><i class="fas fa-sign-out-alt"></i></div>
                            <div class="settings-text">
                                <span style="color:#ef4444;font-weight:600;">تسجيل الخروج</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </section>
<?php

$stmtMale = $pdo->query("
    SELECT * FROM medical_records
    WHERE residency_status = 'مقيم'
    AND gender = 'ذكر'
");

$malePatients = $stmtMale->fetchAll(PDO::FETCH_ASSOC);

$stmtFemale = $pdo->query("
    SELECT * FROM medical_records
    WHERE residency_status = 'مقيم'
    AND gender = 'أنثى'
");

$femalePatients = $stmtFemale->fetchAll(PDO::FETCH_ASSOC);

?>
        <!-- ── MEN WARD INTERFACE ── -->
        <section class="interface" id="men-interface">
            <div class="page-header">
                <h2><i class="fas fa-male" style="margin-left:8px;color:var(--primary);"></i> جناح الرجال</h2>
                <p id="men-ward-summary">12 مريض • 8 غرف مشغولة</p>
            </div>
            <div class="ward-rooms-grid" id="men-rooms-grid">
                <?php foreach($malePatients as $patient): ?>

<div class="patient-card">

    <h3><?= htmlspecialchars($patient['full_name']) ?></h3>

    <p><?= htmlspecialchars($patient['reason_exam']) ?></p>

</div>

<?php endforeach; ?>
                <!-- الغرف تُولَّد بواسطة JavaScript -->
            </div>
        </section>

        <!-- ── WOMEN WARD INTERFACE ── -->
        <section class="interface" id="women-interface">
            <div class="page-header">
                <h2><i class="fas fa-female" style="margin-left:8px;color:var(--primary);"></i> جناح النساء</h2>
                <p id="women-ward-summary">9 مريضات • 6 غرف مشغولة</p>
            </div>
            <div class="ward-rooms-grid" id="women-rooms-grid">
                <?php foreach($femalePatients as $patient): ?>

<div class="patient-card">

    <h3><?= htmlspecialchars($patient['full_name']) ?></h3>

    <p><?= htmlspecialchars($patient['reason_exam']) ?></p>

</div>

<?php endforeach; ?>
                <!-- الغرف تُولَّد بواسطة JavaScript -->
            </div>
        </section>

        <!-- ── CONSULTATION INTERFACE ── -->
        <section class="interface" id="medcomm-consultation-interface">
            <!-- مفتاح التنقّل قائمة⇄تفاصيل (CSS فقط — لا JavaScript) -->
            <input type="checkbox" id="caseOpen" class="cnc-toggle">


            <!-- شريط علوي: العنوان + زر استشارة جديدة -->
            <div class="cslt-toolbar">
                <div class="cslt-toolbar-info">
                    <h2><i class="fas fa-comments-medical"></i> الاستشارة الطبية</h2>
                    <p>إدارة الاستشارات الطبية الداخلية والخارجية</p>
                </div>
                <label for="cncOpen" class="cslt-new-btn" id="csltNewBtn">
                    <i class="fas fa-plus"></i>
                    <span>استشارة جديدة</span>
                </label>
            </div>

            <!-- مدخلات التبديل (CSS فقط — بدون أي منطق برمجي) -->
            <input type="radio" name="cslt-scope" id="cslt-scope-internal" class="cslt-radio" checked>
            <input type="radio" name="cslt-scope" id="cslt-scope-external" class="cslt-radio">
            <input type="radio" name="cslt-status" id="cslt-status-inbox" class="cslt-radio" checked>
            <input type="radio" name="cslt-status" id="cslt-status-sent" class="cslt-radio">
            <input type="radio" name="cslt-status" id="cslt-status-closed" class="cslt-radio">

            <!-- المستوى الأول: نوع الاستشارة -->
            <div class="cslt-scope-tabs">
                <label for="cslt-scope-internal" class="cslt-scope-tab">
                    <i class="fas fa-hospital"></i><span>الاستشارات الداخلية</span>
                </label>
                <label for="cslt-scope-external" class="cslt-scope-tab">
                    <i class="fas fa-network-wired"></i><span>الاستشارات الخارجية</span>
                </label>
            </div>

            <!-- المستوى الثاني: حالة الاستشارة -->
            <div class="cslt-status-tabs">
                <label for="cslt-status-inbox" class="cslt-status-tab">
                    <i class="fas fa-inbox"></i><span>الواردة</span>
                </label>
                <label for="cslt-status-sent" class="cslt-status-tab">
                    <i class="fas fa-paper-plane"></i><span>المرسلة</span>
                </label>
                <label for="cslt-status-closed" class="cslt-status-tab">
                    <i class="fas fa-circle-check"></i><span>المغلقة</span>
                </label>
            </div>

            <!-- منطقة المحتوى: قائمة الحالات الطبية — Placeholders فقط لإظهار شكل البطاقات -->
            <div class="cslt-panel">

                <!-- الواردة -->
                <div class="cslt-view" data-status="inbox">
                    <!-- Skeleton تحميل قائمة الحالات (يُظهره الـ Backend أثناء الجلب) -->
                    <div class="cslt-skeleton mc-hidden">
                        <div class="mc-skel-card"><span class="mc-skel mc-skel-circle"></span><div class="mc-skel-body"><div class="mc-skel mc-skel-line md"></div><div class="mc-skel mc-skel-line lg"></div><div class="mc-skel mc-skel-line sm"></div></div></div>
                        <div class="mc-skel-card"><span class="mc-skel mc-skel-circle"></span><div class="mc-skel-body"><div class="mc-skel mc-skel-line md"></div><div class="mc-skel mc-skel-line lg"></div><div class="mc-skel mc-skel-line sm"></div></div></div>
                        <div class="mc-skel-card"><span class="mc-skel mc-skel-circle"></span><div class="mc-skel-body"><div class="mc-skel mc-skel-line md"></div><div class="mc-skel mc-skel-line lg"></div><div class="mc-skel mc-skel-line sm"></div></div></div>
                    </div>
                    <div class="consult-list">
                        <!-- بطاقات تعريضية (Placeholder) — تُستبدل بالبيانات عند ربطها بقاعدة البيانات -->

                        <label for="caseOpen" class="case-card-link"><article class="case-card case-status-new">
                            <span class="case-accent"></span>
                            <div class="case-head">
                                <span class="case-num"><i class="fas fa-hashtag"></i> CASE-000154</span>
                                <span class="case-badge status"><span class="dot"></span> جديدة</span>
                            </div>
                            <div class="case-tags">
                                <span class="case-tag type"><i class="fas fa-user-md"></i> رأي طبي</span>
                                <span class="case-tag prio urgent"><span class="pdot"></span> عاجلة جداً</span>
                            </div>
                            <div class="case-sender">
                                <span class="case-avatar"><i class="fas fa-user-md"></i></span>
                                <span class="case-sender-info">
                                    <span class="case-skel" style="width:150px;"></span>
                                    <small>الطبيب المرسل</small>
                                </span>
                            </div>
                            <div class="case-foot">
                                <span class="case-date"><i class="fas fa-calendar-day"></i> <span class="case-skel" style="width:78px;height:9px;"></span></span>
                                <span class="case-metrics">
                                    <span class="case-metric"><i class="fas fa-paperclip"></i> —</span>
                                    <span class="case-metric"><i class="fas fa-user-group"></i> —</span>
                                </span>
                            </div>
                        </article></label>

                        <label for="caseOpen" class="case-card-link"><article class="case-card case-status-review">
                            <span class="case-accent"></span>
                            <div class="case-head">
                                <span class="case-num"><i class="fas fa-hashtag"></i> CASE-000153</span>
                                <span class="case-badge status"><span class="dot"></span> قيد المراجعة</span>
                            </div>
                            <div class="case-tags">
                                <span class="case-tag type"><i class="fas fa-x-ray"></i> طلب تفسير أشعة</span>
                                <span class="case-tag prio medium"><span class="pdot"></span> مستعجلة</span>
                            </div>
                            <div class="case-sender">
                                <span class="case-avatar"><i class="fas fa-user-md"></i></span>
                                <span class="case-sender-info">
                                    <span class="case-skel" style="width:130px;"></span>
                                    <small>الطبيب المرسل</small>
                                </span>
                            </div>
                            <div class="case-foot">
                                <span class="case-date"><i class="fas fa-calendar-day"></i> <span class="case-skel" style="width:78px;height:9px;"></span></span>
                                <span class="case-metrics">
                                    <span class="case-metric"><i class="fas fa-paperclip"></i> —</span>
                                    <span class="case-metric"><i class="fas fa-user-group"></i> —</span>
                                </span>
                            </div>
                        </article></label>

                    </div>
                </div>

                <!-- المرسلة -->
                <div class="cslt-view" data-status="sent">
                    <div class="consult-list">
                        <!-- بطاقات تعريضية (Placeholder) -->

                        <label for="caseOpen" class="case-card-link"><article class="case-card case-status-replied">
                            <span class="case-accent"></span>
                            <div class="case-head">
                                <span class="case-num"><i class="fas fa-hashtag"></i> CASE-000151</span>
                                <span class="case-badge status"><span class="dot"></span> تم الرد</span>
                            </div>
                            <div class="case-tags">
                                <span class="case-tag type"><i class="fas fa-heart-pulse"></i> متابعة حالة</span>
                                <span class="case-tag prio normal"><span class="pdot"></span> عادية</span>
                            </div>
                            <div class="case-sender">
                                <span class="case-avatar"><i class="fas fa-user-md"></i></span>
                                <span class="case-sender-info">
                                    <span class="case-skel" style="width:140px;"></span>
                                    <small>الطبيب المستشار</small>
                                </span>
                            </div>
                            <div class="case-foot">
                                <span class="case-date"><i class="fas fa-calendar-day"></i> <span class="case-skel" style="width:78px;height:9px;"></span></span>
                                <span class="case-metrics">
                                    <span class="case-metric"><i class="fas fa-paperclip"></i> —</span>
                                    <span class="case-metric"><i class="fas fa-user-group"></i> —</span>
                                </span>
                            </div>
                        </article></label>

                        <label for="caseOpen" class="case-card-link"><article class="case-card case-status-closed">
                            <span class="case-accent"></span>
                            <div class="case-head">
                                <span class="case-num"><i class="fas fa-hashtag"></i> CASE-000148</span>
                                <span class="case-badge status"><span class="dot"></span> مغلقة</span>
                            </div>
                            <div class="case-tags">
                                <span class="case-tag type"><i class="fas fa-comments"></i> مناقشة حالة</span>
                                <span class="case-tag prio normal"><span class="pdot"></span> عادية</span>
                            </div>
                            <div class="case-sender">
                                <span class="case-avatar"><i class="fas fa-user-md"></i></span>
                                <span class="case-sender-info">
                                    <span class="case-skel" style="width:120px;"></span>
                                    <small>الطبيب المستشار</small>
                                </span>
                            </div>
                            <div class="case-foot">
                                <span class="case-date"><i class="fas fa-calendar-day"></i> <span class="case-skel" style="width:78px;height:9px;"></span></span>
                                <span class="case-metrics">
                                    <span class="case-metric"><i class="fas fa-paperclip"></i> —</span>
                                    <span class="case-metric"><i class="fas fa-user-group"></i> —</span>
                                </span>
                            </div>
                        </article></label>

                    </div>
                </div>

                <!-- المغلقة -->
                <div class="cslt-view" data-status="closed">
                    <div class="consult-list">
                        <!-- الحالات المغلقة تُدرج هنا بعد ربطها بقاعدة البيانات -->
                    </div>
                    <div class="cslt-empty">
                        <div class="cslt-empty-icon"><i class="fas fa-folder-open"></i></div>
                        <h3>لا توجد استشارات بعد</h3>
                        <p>لم تُنشئ أي حالة استشارة حتى الآن. ابدأ بإنشاء استشارة جديدة لتظهر هنا كبطاقة حالة.</p>
                        <label for="cncOpen" class="cslt-empty-btn"><i class="fas fa-plus"></i> إنشاء استشارة جديدة</label>
                    </div>
                </div>

            </div>
        
            <!-- ═══ صفحة تفاصيل الحالة (تُعرض بدل القائمة عبر :checked — لا Modal) ═══ -->
            <div id="caseDetail">

                <!-- 1) شريط الإجراءات العلوي -->
                <div class="cd-actionbar">
                    <div class="cd-ab-title">
                        <span class="cd-ab-num"><i class="fas fa-hashtag"></i> CASE-000154</span>
                        <span class="cd-status-badge"><span class="dot"></span> جديدة</span>
                    </div>
                    <div class="cd-ab-actions">
                        <button type="button" class="cd-action-btn status"><i class="fas fa-pen"></i> تغيير الحالة</button>
                        <button type="button" class="cd-action-btn adddoc"><i class="fas fa-user-plus"></i> إضافة طبيب مشارك</button>
                        <button type="button" class="cd-action-btn addfile"><i class="fas fa-paperclip"></i> إضافة مرفق</button>
                        <button type="button" class="cd-action-btn print"><i class="fas fa-print"></i> طباعة</button>
                        <button type="button" class="cd-action-btn closecase"><i class="fas fa-lock"></i> إغلاق الحالة</button>
                        <label for="caseOpen" class="cd-action-btn backbtn"><i class="fas fa-arrow-right"></i> الرجوع</label>
                    </div>
                </div>

                <!-- التخطيط: محتوى رئيسي + شريط جانبي -->
                <div class="cd-layout">

                    <!-- ═══ المحتوى الرئيسي ═══ -->
                    <div class="cd-main">

                        <!-- 2) بطاقة معلومات الحالة -->
                        <div class="cd-header case-status-new">
                            <div class="cd-header-top">
                                <span class="cd-case-num"><i class="fas fa-hashtag"></i> CASE-000154</span>
                                <span class="cd-status-badge"><span class="dot"></span> جديدة</span>
                            </div>
                            <div class="cd-header-meta">
                                <div class="cd-meta-item">
                                    <span class="cd-meta-label"><i class="fas fa-tag"></i> نوع الاستشارة</span>
                                    <span class="cd-meta-value">رأي طبي</span>
                                </div>
                                <div class="cd-meta-item">
                                    <span class="cd-meta-label"><i class="fas fa-triangle-exclamation"></i> الأولوية</span>
                                    <span class="cd-meta-value cd-prio-inline urgent"><span class="pdot"></span> عاجلة جداً</span>
                                </div>
                                <div class="cd-meta-item">
                                    <span class="cd-meta-label"><i class="fas fa-circle-dot"></i> الحالة الحالية</span>
                                    <span class="cd-meta-value">جديدة</span>
                                </div>
                                <div class="cd-meta-item">
                                    <span class="cd-meta-label"><i class="fas fa-calendar-plus"></i> تاريخ الإنشاء</span>
                                    <span class="cd-meta-value"><span class="cd-line" style="width:96px;"></span></span>
                                </div>
                                <div class="cd-meta-item">
                                    <span class="cd-meta-label"><i class="fas fa-clock-rotate-left"></i> آخر تحديث</span>
                                    <span class="cd-meta-value"><span class="cd-line" style="width:96px;"></span></span>
                                </div>
                                <div class="cd-meta-item">
                                    <span class="cd-meta-label"><i class="fas fa-paperclip"></i> عدد المرفقات</span>
                                    <span class="cd-meta-value"><span class="cd-num-skel"></span></span>
                                </div>
                                <div class="cd-meta-item">
                                    <span class="cd-meta-label"><i class="fas fa-user-group"></i> عدد المشاركين</span>
                                    <span class="cd-meta-value"><span class="cd-num-skel"></span></span>
                                </div>
                                <div class="cd-meta-item">
                                    <span class="cd-meta-label"><i class="fas fa-comment-dots"></i> عدد الرسائل</span>
                                    <span class="cd-meta-value"><span class="cd-num-skel"></span></span>
                                </div>
                            </div>
                        </div>

                        <!-- 3) المشاركون (بطاقات Avatars) -->
                        <div class="cd-card">
                            <div class="cd-card-title"><i class="fas fa-user-group"></i> المشاركون في الحالة</div>
                            <div class="cd-part-grid">
                                <div class="cd-part-card">
                                    <span class="cd-part-av"><i class="fas fa-user-md"></i></span>
                                    <span class="cd-part-info">
                                        <span class="cd-line" style="width:120px;height:11px;"></span>
                                        <small class="role-send">الطبيب المرسل</small>
                                    </span>
                                </div>
                                <div class="cd-part-card">
                                    <span class="cd-part-av consult"><i class="fas fa-user-md"></i></span>
                                    <span class="cd-part-info">
                                        <span class="cd-line" style="width:110px;height:11px;"></span>
                                        <small class="role-consult">طبيب مشارك</small>
                                    </span>
                                </div>
                                <div class="cd-part-card">
                                    <span class="cd-part-av admin"><i class="fas fa-user-shield"></i></span>
                                    <span class="cd-part-info">
                                        <span class="cd-line" style="width:100px;height:11px;"></span>
                                        <small class="role-admin">Service Admin</small>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات المريض (خصوصية مفعّلة) -->
                        <div class="cd-card">
                            <div class="cd-card-title"><i class="fas fa-user-injured"></i> معلومات المريض</div>
                            <div class="cd-patient-locked">
                                <div class="cd-lock-icon"><i class="fas fa-lock"></i></div>
                                <b>بيانات المريض مخفية</b>
                                <span>تم تفعيل الخصوصية لهذه الحالة، لذا لن تظهر أي بيانات شخصية للمريض.</span>
                            </div>
                        </div>

                        <!-- التبويبات -->
                        <input class="cd-tab-radio" type="radio" name="cd-tab" id="cd-tab-chat" checked>
                        <input class="cd-tab-radio" type="radio" name="cd-tab" id="cd-tab-files">
                        <input class="cd-tab-radio" type="radio" name="cd-tab" id="cd-tab-log">

                        <div class="cd-tabs-bar">
                            <label class="cd-tab" for="cd-tab-chat"><i class="fas fa-comments"></i> المحادثة</label>
                            <label class="cd-tab" for="cd-tab-files"><i class="fas fa-paperclip"></i> المرفقات</label>
                            <label class="cd-tab" for="cd-tab-log"><i class="fas fa-list-check"></i> سجل العمليات</label>
                        </div>

                        <!-- 4) المحادثة + صندوق الكتابة المطوّر -->
                        <div class="cd-tab-panel panel-chat">
                            <div class="cd-card">
                                <!-- Skeleton تحميل المحادثة (يُظهره الـ Backend أثناء الجلب) -->
                                <div class="cd-chat-skeleton mc-hidden">
                                    <div class="mc-skel-msg"><span class="mc-skel mc-skel-circle" style="width:34px;height:34px;"></span><div class="mc-skel-bubble"><div class="mc-skel mc-skel-line lg"></div><div class="mc-skel mc-skel-line md"></div></div></div>
                                    <div class="mc-skel-msg right"><span class="mc-skel mc-skel-circle" style="width:34px;height:34px;"></span><div class="mc-skel-bubble"><div class="mc-skel mc-skel-line md" style="margin:0;"></div></div></div>
                                    <div class="mc-skel-msg"><span class="mc-skel mc-skel-circle" style="width:34px;height:34px;"></span><div class="mc-skel-bubble"><div class="mc-skel mc-skel-line lg"></div><div class="mc-skel mc-skel-line sm"></div></div></div>
                                </div>
                                <!-- Empty State للمحادثة (يُظهره الـ Backend عند غياب الرسائل) -->
                                <div class="cd-empty mc-hidden">
                                    <div class="cd-empty-icon"><i class="fas fa-comments"></i></div>
                                    <h4>لا توجد رسائل بعد</h4>
                                    <p>ابدأ المحادثة بكتابة أول رسالة إلى المشاركين في هذه الحالة.</p>
                                </div>
                                <div class="cd-chat-window">
                                    <div class="cd-msg received">
                                        <span class="cd-msg-av"><i class="fas fa-user-md"></i></span>
                                        <span class="cd-msg-body">
                                            <span class="cd-msg-meta"><b>الطبيب المرسل</b><span>—:—</span></span>
                                            <span class="cd-bubble">
                                                <span class="cd-line" style="width:210px;"></span>
                                                <span class="cd-line" style="width:160px;"></span>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="cd-msg sent">
                                        <span class="cd-msg-av"><i class="fas fa-user"></i></span>
                                        <span class="cd-msg-body">
                                            <span class="cd-msg-meta"><b>أنت</b><span>—:—</span></span>
                                            <span class="cd-bubble">
                                                <span class="cd-line" style="width:180px;"></span>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="cd-msg received">
                                        <span class="cd-msg-av"><i class="fas fa-user-md"></i></span>
                                        <span class="cd-msg-body">
                                            <span class="cd-msg-meta"><b>طبيب مشارك</b><span>—:—</span></span>
                                            <span class="cd-bubble">
                                                <span class="cd-line" style="width:200px;"></span>
                                                <span class="cd-line" style="width:120px;"></span>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                                <div class="cd-composer">
                                    <textarea rows="1" placeholder="اكتب رسالتك إلى المشاركين في هذه الحالة..."></textarea>
                                    <div class="cd-composer-bar">
                                        <button type="button" class="cd-tool-btn" title="إرفاق ملف"><i class="fas fa-paperclip"></i></button>
                                        <button type="button" class="cd-tool-btn" title="إدراج Emoji"><i class="fas fa-face-smile"></i></button>
                                        <span class="cd-char-count">0 / 2000</span>
                                        <button type="button" class="cd-send-btn"><i class="fas fa-paper-plane"></i> إرسال</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5) المرفقات (بطاقات ملفات حديثة) -->
                        <div class="cd-tab-panel panel-files">
                            <div class="cd-card">
                                <div class="cd-card-title"><i class="fas fa-folder-open"></i> مرفقات الحالة</div>
                                <!-- Empty State للمرفقات (يُظهره الـ Backend عند غياب الملفات) -->
                                <div class="cd-empty mc-hidden">
                                    <div class="cd-empty-icon"><i class="fas fa-paperclip"></i></div>
                                    <h4>لا توجد مرفقات</h4>
                                    <p>لم تُرفق أي ملفات بهذه الحالة حتى الآن.</p>
                                </div>
                                <div class="cd-files-grid">
                                    <div class="cd-file-card">
                                        <div class="cd-file-head">
                                            <span class="cd-file-ico pdf"><i class="fas fa-file-pdf"></i></span>
                                            <span class="cd-file-meta">
                                                <span class="cd-file-name">ملف PDF</span>
                                                <span class="cd-file-sub">PDF <span class="sep"></span> <span class="cd-line" style="width:44px;height:8px;"></span></span>
                                            </span>
                                        </div>
                                        <div class="cd-file-actions">
                                            <button type="button" class="cd-file-btn view"><i class="fas fa-eye"></i> عرض</button>
                                            <button type="button" class="cd-file-btn dl"><i class="fas fa-download"></i> تحميل</button>
                                        </div>
                                    </div>
                                    <div class="cd-file-card">
                                        <div class="cd-file-head">
                                            <span class="cd-file-ico xray"><i class="fas fa-x-ray"></i></span>
                                            <span class="cd-file-meta">
                                                <span class="cd-file-name">أشعة</span>
                                                <span class="cd-file-sub">صورة إشعاعية <span class="sep"></span> <span class="cd-line" style="width:44px;height:8px;"></span></span>
                                            </span>
                                        </div>
                                        <div class="cd-file-actions">
                                            <button type="button" class="cd-file-btn view"><i class="fas fa-eye"></i> عرض</button>
                                            <button type="button" class="cd-file-btn dl"><i class="fas fa-download"></i> تحميل</button>
                                        </div>
                                    </div>
                                    <div class="cd-file-card">
                                        <div class="cd-file-head">
                                            <span class="cd-file-ico lab"><i class="fas fa-vials"></i></span>
                                            <span class="cd-file-meta">
                                                <span class="cd-file-name">تحاليل</span>
                                                <span class="cd-file-sub">نتائج مخبرية <span class="sep"></span> <span class="cd-line" style="width:44px;height:8px;"></span></span>
                                            </span>
                                        </div>
                                        <div class="cd-file-actions">
                                            <button type="button" class="cd-file-btn view"><i class="fas fa-eye"></i> عرض</button>
                                            <button type="button" class="cd-file-btn dl"><i class="fas fa-download"></i> تحميل</button>
                                        </div>
                                    </div>
                                    <div class="cd-file-card">
                                        <div class="cd-file-head">
                                            <span class="cd-file-ico report"><i class="fas fa-file-medical"></i></span>
                                            <span class="cd-file-meta">
                                                <span class="cd-file-name">تقرير طبي</span>
                                                <span class="cd-file-sub">مستند <span class="sep"></span> <span class="cd-line" style="width:44px;height:8px;"></span></span>
                                            </span>
                                        </div>
                                        <div class="cd-file-actions">
                                            <button type="button" class="cd-file-btn view"><i class="fas fa-eye"></i> عرض</button>
                                            <button type="button" class="cd-file-btn dl"><i class="fas fa-download"></i> تحميل</button>
                                        </div>
                                    </div>
                                    <div class="cd-file-card">
                                        <div class="cd-file-head">
                                            <span class="cd-file-ico image"><i class="fas fa-image"></i></span>
                                            <span class="cd-file-meta">
                                                <span class="cd-file-name">صور</span>
                                                <span class="cd-file-sub">وسائط <span class="sep"></span> <span class="cd-line" style="width:44px;height:8px;"></span></span>
                                            </span>
                                        </div>
                                        <div class="cd-file-actions">
                                            <button type="button" class="cd-file-btn view"><i class="fas fa-eye"></i> عرض</button>
                                            <button type="button" class="cd-file-btn dl"><i class="fas fa-download"></i> تحميل</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 6) سجل العمليات (Timeline مطوّر) -->
                        <div class="cd-tab-panel panel-log">
                            <div class="cd-card">
                                <div class="cd-card-title"><i class="fas fa-timeline"></i> سجل عمليات الحالة</div>
                                <!-- Empty State لسجل العمليات (يُظهره الـ Backend عند غياب السجل) -->
                                <div class="cd-empty mc-hidden">
                                    <div class="cd-empty-icon"><i class="fas fa-list-check"></i></div>
                                    <h4>لا يوجد سجل عمليات</h4>
                                    <p>ستظهر هنا كل العمليات التي تُجرى على هذه الحالة.</p>
                                </div>
                                <div class="cd-timeline">
                                    <div class="cd-tl-item">
                                        <div class="cd-tl-line"><span class="cd-tl-dot"><i class="fas fa-plus"></i></span></div>
                                        <div class="cd-tl-content">
                                            <div class="cd-tl-title">تم إنشاء الحالة</div>
                                            <span class="cd-tl-doctor"><i class="fas fa-user-md"></i> <span class="cd-line"></span></span>
                                            <span class="cd-tl-time"></span>
                                        </div>
                                    </div>
                                    <div class="cd-tl-item">
                                        <div class="cd-tl-line"><span class="cd-tl-dot"><i class="fas fa-paper-plane"></i></span></div>
                                        <div class="cd-tl-content">
                                            <div class="cd-tl-title">تم إرسال الاستشارة</div>
                                            <span class="cd-tl-doctor"><i class="fas fa-user-md"></i> <span class="cd-line"></span></span>
                                            <span class="cd-tl-time"></span>
                                        </div>
                                    </div>
                                    <div class="cd-tl-item">
                                        <div class="cd-tl-line"><span class="cd-tl-dot"><i class="fas fa-envelope-open"></i></span></div>
                                        <div class="cd-tl-content">
                                            <div class="cd-tl-title">تم فتح الحالة</div>
                                            <span class="cd-tl-doctor"><i class="fas fa-user-md"></i> <span class="cd-line"></span></span>
                                            <span class="cd-tl-time"></span>
                                        </div>
                                    </div>
                                    <div class="cd-tl-item">
                                        <div class="cd-tl-line"><span class="cd-tl-dot done"><i class="fas fa-reply"></i></span></div>
                                        <div class="cd-tl-content">
                                            <div class="cd-tl-title">تم الرد على الاستشارة</div>
                                            <span class="cd-tl-doctor"><i class="fas fa-user-md"></i> <span class="cd-line"></span></span>
                                            <span class="cd-tl-time"></span>
                                        </div>
                                    </div>
                                    <div class="cd-tl-item">
                                        <div class="cd-tl-line"><span class="cd-tl-dot"><i class="fas fa-pen"></i></span></div>
                                        <div class="cd-tl-content">
                                            <div class="cd-tl-title">تم تغيير حالة الاستشارة</div>
                                            <span class="cd-tl-doctor"><i class="fas fa-user-md"></i> <span class="cd-line"></span></span>
                                            <span class="cd-tl-time"></span>
                                        </div>
                                    </div>
                                    <div class="cd-tl-item">
                                        <div class="cd-tl-line"><span class="cd-tl-dot closed"><i class="fas fa-lock"></i></span></div>
                                        <div class="cd-tl-content">
                                            <div class="cd-tl-title">تم إغلاق الحالة</div>
                                            <span class="cd-tl-doctor"><i class="fas fa-user-md"></i> <span class="cd-line"></span></span>
                                            <span class="cd-tl-time"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ═══ 7) الشريط الجانبي للحالة ═══ -->
                    <aside class="cd-aside">
                        <div class="cd-side-card">
                            <div class="cd-side-head"><i class="fas fa-circle-info"></i> ملخّص الحالة</div>
                            <div class="cd-side-body">
                                <div class="cd-side-row">
                                    <span class="cd-side-label"><i class="fas fa-circle-dot"></i> الحالة الحالية</span>
                                    <span class="cd-side-badge st-new"><span class="dot"></span> جديدة</span>
                                </div>
                                <div class="cd-side-row">
                                    <span class="cd-side-label"><i class="fas fa-triangle-exclamation"></i> الأولوية</span>
                                    <span class="cd-side-badge prio-urgent"><span class="dot"></span> عاجلة جداً</span>
                                </div>
                                <div class="cd-side-row">
                                    <span class="cd-side-label"><i class="fas fa-user-group"></i> عدد المشاركين</span>
                                    <span class="cd-side-num"><span class="cd-num-skel" style="width:16px;"></span></span>
                                </div>
                                <div class="cd-side-row">
                                    <span class="cd-side-label"><i class="fas fa-comment-dots"></i> عدد الرسائل</span>
                                    <span class="cd-side-num"><span class="cd-num-skel" style="width:16px;"></span></span>
                                </div>
                                <div class="cd-side-row">
                                    <span class="cd-side-label"><i class="fas fa-paperclip"></i> عدد الملفات</span>
                                    <span class="cd-side-num"><span class="cd-num-skel" style="width:16px;"></span></span>
                                </div>
                                <div class="cd-side-row">
                                    <span class="cd-side-label"><i class="fas fa-calendar-plus"></i> تاريخ الإنشاء</span>
                                    <span class="cd-side-val"><span class="cd-line" style="width:74px;height:8px;"></span></span>
                                </div>
                                <div class="cd-side-row">
                                    <span class="cd-side-label"><i class="fas fa-clock-rotate-left"></i> آخر تحديث</span>
                                    <span class="cd-side-val"><span class="cd-line" style="width:74px;height:8px;"></span></span>
                                </div>
                            </div>
                        </div>
                    </aside>

                </div>

            </div>
            <!-- ═══ نهاية صفحة تفاصيل الحالة ═══ -->

        </section>


        <!-- ── متابعة المرضى ── -->
<section class="interface" id="medcomm-followup-interface">

    <div class="page-header">
        <h2>
            <i class="fas fa-user-clock" style="margin-left:8px;color:var(--primary);"></i>
            متابعة المرضى
        </h2>
        <p>التواصل مع المرضى ومتابعة حالتهم الصحية</p>
    </div>

    <div class="followup-chat-layout">

        <!-- قائمة المرضى -->
        <div class="followup-sidebar">

            <div class="followup-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="ابحث عن مريض...">
            </div>

            <div class="patient-chat-item active">

                <div class="patient-avatar">
                    م
                </div>

                <div class="patient-details">

                    <h4>محمد بلعيد</h4>

                    <span>التهاب الزائدة — آخر رسالة قبل 5 دقائق</span>

                </div>

                <div class="patient-time">

                    <span>09:21</span>

                    <div class="patient-badge">2</div>

                </div>

            </div>

        </div>

        <!-- المحادثة -->
        <div class="followup-chat-window">

            <div class="chat-header">

                <div class="chat-user">

                 <div class="patient-avatar large" id="chatPatientAvatar">
</div>

                    <div>

                        <h3 id="chatPatientName"></h3>


                    </div>

                </div>

            </div>

            <div class="chat-messages">
          <div id="emptyChat"
     style="
        height:100%;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        color:#94a3b8;
        text-align:center;
        padding:40px;
">

    <i class="fas fa-comments"
       style="font-size:60px;margin-bottom:20px;color:#38bdf8;"></i>

    <h3 style="margin-bottom:10px;">
        مرحبًا بك في التواصل الطبي
    </h3>

    <p>
        اختر مريضًا من القائمة لبدء المحادثة.
    </p>

</div>
            </div>
      <div id="noAccountBox" style="display:none;padding:40px;text-align:center;">

    <i class="fas fa-user-slash"
       style="font-size:55px;color:#94a3b8;margin-bottom:20px;"></i>

    <h3>هذا المريض لا يملك حسابًا</h3>

    <p style="margin:15px 0;color:#64748b">
        لا يمكن بدء محادثة حتى يقوم المريض بإنشاء حساب.
    </p>

    <button id="invitePatientBtn"
            class="pif-btn pif-btn-primary">

        📧 إرسال دعوة

    </button>

</div>
            <div class="chat-input">

    <button type="button">
        <i class="fas fa-paperclip"></i>
    </button>

    <input
        type="text"
        id="doctorMessageInput"
        placeholder="اكتب رسالة...">

    <button
        type="button"
        class="send-btn"
        onclick="sendDoctorMessage()">

        <i class="fas fa-paper-plane"></i>

    </button>

    <button type="button" id="doctorMicBtn" title="رسالة صوتية">
        <i class="fas fa-microphone"></i>
    </button>

    <button type="button" id="doctorMedFileBtn" title="إرفاق الملف الطبي">
        <i class="fas fa-folder-open"></i>
    </button>

</div>

        </div>

    </div>

</section>

       

        <!-- ═══════════════ 📊 الإحصائيات ═══════════════ -->
        <section class="interface" id="stats-interface">
            <div class="page-header">
                <h2><i class="fas fa-chart-column" style="margin-left:8px;color:var(--primary);"></i> الإحصائيات</h2>
                <p>تحليلات بيانية حقيقية لملفاتك الطبية ونشاطك</p>
            </div>

            <!-- التحميل -->
            <div class="stats-loading" id="statsLoading">
                <i class="fas fa-circle-notch"></i>
                <span>جارٍ تحميل الإحصائيات...</span>
            </div>

            <!-- خطأ -->
            <div class="stats-error" id="statsError">
                <i class="fas fa-triangle-exclamation"></i>
                <span>تعذّر تحميل الإحصائيات. حاول مرة أخرى.</span>
            </div>

            <!-- المحتوى -->
            <div class="stats-wrap" id="statsWrap" style="display:none;">

                <!-- القسم 1: إحصائيات الملفات الطبية -->
                <div class="stat-panel">
                    <div class="stat-panel-head">
                        <div class="stat-panel-icon"><i class="fas fa-folder-open"></i></div>
                        <div class="stat-panel-titles">
                            <span class="stat-panel-title">📊 إحصائيات الملفات الطبية</span>
                            <span class="stat-panel-sub">إجمالي / هذا الأسبوع / هذا الشهر / اليوم</span>
                        </div>
                    </div>
                    <div class="stat-canvas-box" id="boxFiles">
                        <canvas id="statFilesChart"></canvas>
                        <div class="stat-empty"><i class="fas fa-folder"></i><span>لا توجد بيانات بعد</span></div>
                    </div>
                </div>

                <!-- القسم 2: النشاط الأسبوعي -->
                <div class="stat-panel">
                    <div class="stat-panel-head">
                        <div class="stat-panel-icon"><i class="fas fa-calendar-week"></i></div>
                        <div class="stat-panel-titles">
                            <span class="stat-panel-title">📈 نشاط الطبيب الأسبوعي</span>
                            <span class="stat-panel-sub">المرضى الذين تمت معاينتهم خلال أيام الأسبوع</span>
                        </div>
                    </div>
                    <div class="stat-canvas-box tall" id="boxWeekly">
                        <canvas id="statWeeklyChart"></canvas>
                        <div class="stat-empty"><i class="fas fa-calendar-week"></i><span>لا توجد بيانات بعد</span></div>
                    </div>
                </div>

                <!-- القسم 3: المقيمون / غير المقيمين -->
                <div class="stat-panel">
                    <div class="stat-panel-head">
                        <div class="stat-panel-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-panel-titles">
                            <span class="stat-panel-title">👥 المرضى المقيمون وغير المقيمين</span>
                            <span class="stat-panel-sub">النِّسب الحقيقية من ملفاتك الطبية</span>
                        </div>
                    </div>
                    <div class="stat-canvas-box" id="boxResidency">
                        <canvas id="statResidencyChart"></canvas>
                        <div class="stat-empty"><i class="fas fa-users"></i><span>لا توجد بيانات بعد</span></div>
                    </div>
                </div>

                <!-- الأقسام 4 و 5: الرجال + النساء (شبكة) -->
                <div class="stat-doughnut-grid">
                    <!-- القسم 4: الرجال -->
                    <div class="stat-panel">
                        <div class="stat-panel-head">
                            <div class="stat-panel-icon"><i class="fas fa-mars"></i></div>
                            <div class="stat-panel-titles">
                                <span class="stat-panel-title">👨 الرجال</span>
                                <span class="stat-panel-sub">مقيمون / غير مقيمين</span>
                            </div>
                        </div>
                        <div class="stat-canvas-box" id="boxMen">
                            <canvas id="statMenChart"></canvas>
                            <div class="stat-empty"><i class="fas fa-mars"></i><span>لا توجد بيانات بعد</span></div>
                        </div>
                    </div>

                    <!-- القسم 5: النساء -->
                    <div class="stat-panel">
                        <div class="stat-panel-head">
                            <div class="stat-panel-icon"><i class="fas fa-venus"></i></div>
                            <div class="stat-panel-titles">
                                <span class="stat-panel-title">👩 النساء</span>
                                <span class="stat-panel-sub">مقيمات / غير مقيمات</span>
                            </div>
                        </div>
                        <div class="stat-canvas-box" id="boxWomen">
                            <canvas id="statWomenChart"></canvas>
                            <div class="stat-empty"><i class="fas fa-venus"></i><span>لا توجد بيانات بعد</span></div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <!-- ══════════════ MODALS (unchanged from original) ══════════════ -->

    <!-- Settings Modals -->
    <div class="settings-modal" id="editProfileModal">
        <div class="settings-modal-content">
            <div class="settings-modal-header">
                <h3>تعديل الملف الشخصي</h3>
                <button onclick="closeModal('editProfileModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="settings-modal-body">
                <div class="form-group">
                    <label>الاسم الكامل</label>
                    <input type="text" value="<?php echo htmlspecialchars($doctor['full_name']); ?>" class="form-input">
                </div>
                <div class="form-group">
                    <label>التخصص</label>
                    <input type="text" value="<?php echo htmlspecialchars($doctor['specialty'] ?? ''); ?>" class="form-input">
                </div>
                <div class="form-group">
                    <label>سنوات الخبرة</label>
                    <input type="number" value="<?php echo htmlspecialchars($doctor['experience'] ?? 0); ?>" class="form-input">
                </div>
                <div class="form-group">
                    <label>رقم الهاتف</label>
                    <input type="text" value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>" class="form-input">
                </div>
            </div>
            <div class="settings-modal-footer">
                <button class="btn-secondary" onclick="closeModal('editProfileModal')">إلغاء</button>
                <button class="btn-primary" onclick="saveProfile()">حفظ</button>
            </div>
        </div>
    </div>

    <div class="settings-modal" id="changePasswordModal">
        <div class="settings-modal-content">
            <div class="settings-modal-header">
                <h3>تغيير كلمة المرور</h3>
                <button onclick="closeModal('changePasswordModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="settings-modal-body">
                <div class="form-group">
                    <label>كلمة المرور الحالية</label>
                    <input type="password" placeholder="أدخل كلمة المرور الحالية" class="form-input">
                </div>
                <div class="form-group">
                    <label>كلمة المرور الجديدة</label>
                    <input type="password" placeholder="أدخل كلمة المرور الجديدة" class="form-input">
                </div>
                <div class="form-group">
                    <label>تأكيد كلمة المرور</label>
                    <input type="password" placeholder="أعد إدخال كلمة المرور" class="form-input">
                </div>
            </div>
            <div class="settings-modal-footer">
                <button class="btn-secondary" onclick="closeModal('changePasswordModal')">إلغاء</button>
                <button class="btn-primary" onclick="changePassword()">تغيير</button>
            </div>
        </div>
    </div>

    <div class="settings-modal patients-modal" id="myPatientsModal">
        <div class="settings-modal-content">
            <div class="settings-modal-header">
                <h3>مرضاي</h3>
                <button onclick="closeModal('myPatientsModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="settings-modal-body">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="بحث عن مريض..." onkeyup="searchPatients(this.value)">
                </div>
                <div id="patientsList"></div>
                <div id="appointmentsContainer"></div>
            </div>
        </div>
    </div>

    <!-- Old bottom-bar preserved (hidden by CSS) -->
    <nav class="bottom-bar">
        <div class="nav-item active" onclick="switchInterface('home', this)">
            <i class="fas fa-home"></i>
            <span data-ar="الرئيسية" data-fr="Accueil" data-en="Home">الرئيسية</span>
        </div>
        <div class="nav-item" onclick="switchInterface('ai', this)">
            <i class="fas fa-robot"></i>
            <span data-ar="مركز البيانات" data-fr="IA" data-en="AI">مركز البيانات</span>
        </div>
        <div class="nav-item" onclick="switchInterface('messages', this)">
            <i class="fas fa-comments"></i>
            <span data-ar="المحادثات" data-fr="Messages" data-en="Messages">المحادثات</span>
        </div>
        <div class="nav-item" onclick="switchInterface('services', this)">
            <i class="fas fa-box"></i>
            <span data-ar="الخدمات" data-fr="Services" data-en="Services">الخدمات</span>
        </div>
        <div class="nav-item" onclick="switchInterface('settings', this)">
            <i class="fas fa-cog"></i>
            <span data-ar="الإعدادات" data-fr="Paramètres" data-en="Settings">الإعدادات</span>
        </div>
    </nav>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; width:90%; max-width:420px; padding:28px; border-radius:20px; box-shadow:0 20px 50px rgba(0,0,0,0.2);">
            <h3 style="margin-bottom:20px; font-family:'Cairo',sans-serif; color:#0f172a;">🔄 إعادة برمجة موعد</h3>
            <input type="hidden" id="reschedule_id">
            <label style="font-size:0.85rem;font-weight:600;color:#475569;display:block;margin-bottom:4px;">التاريخ الجديد:</label>
            <input type="date" id="reschedule_date" style="width:100%; padding:10px 14px; margin:0 0 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:0.88rem;">
            <label style="font-size:0.85rem;font-weight:600;color:#475569;display:block;margin-bottom:4px;">الوقت الجديد:</label>
            <input type="time" id="reschedule_time" style="width:100%; padding:10px 14px; margin:0 0 20px; border:1px solid #e2e8f0; border-radius:10px; font-size:0.88rem;">
            <div style="display:flex; gap:10px;">
                <button onclick="saveReschedule()" style="flex:1; background:linear-gradient(135deg,#10b981,#34d399); color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:700; font-family:'Cairo',sans-serif;">💾 حفظ</button>
                <button onclick="closeRescheduleModal()" style="flex:1; background:#ef4444; color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:700; font-family:'Cairo',sans-serif;">إلغاء</button>
            </div>
        </div>
    </div>

    
    <!-- Scripts -->
    <script>
        window.DOCTOR_SPECIALTY = '<?php echo addslashes(trim($doctor["specialty"] ?? "")); ?>';
        // معرّف المستخدم الحالي (الطبيب) كما هو مخزّن في الجلسة — يُستخدم فقط لتحديد
        // جهة/لون رسائل المراسلة الطبية عبر مقارنة sender_id بهذا المعرّف، دون أي
        // تعديل على الـ API أو قاعدة البيانات.
        window.CURRENT_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
        /* إخفاء قسم متابعة الحمل في كارد إضافة مريض إذا لم يكن التخصص نساء وتوليد */
        (function() {
            function apfHidePregnancyIfNeeded() {
                var spec = (window.DOCTOR_SPECIALTY || '').toLowerCase();
                var isG  = spec.indexOf('نساء') !== -1 || spec.indexOf('توليد') !== -1
                        || spec.indexOf('gynec') !== -1 || spec.indexOf('obst') !== -1;
                var sec = document.getElementById('apf-sec-preg');
                if (sec) sec.style.display = isG ? '' : 'none';
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', apfHidePregnancyIfNeeded);
            } else {
                apfHidePregnancyIfNeeded();
            }
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="dr_dashboard.js"></script>
    <!-- مرحلة حفظ وعرض الاستشارة (Front-End wiring) — إضافة فقط -->
    <script src="consultation_save.js"></script>
    <!-- المرحلة الثانية: ربط صفحة تفاصيل الاستشارة — إضافة فقط -->
    <script src="consultation_details.js"></script>
    <script src="dashboard_fixes.js"></script>
    <script src="patient_inline.js"></script>
        <script src="today_patients_send.js"></script> 
    <script src="dr_statistics.js"></script>
    <script>
    window.HOSPITAL_NAME    = "<?= htmlspecialchars($hospital_name ?? 'CHU Hassani Abdelkader') ?>";
    window.HOSPITAL_SERVICE = "<?= htmlspecialchars($service ?? 'Service de Médecine Interne') ?>";
    window.CHEF_SERVICE     = "<?= htmlspecialchars($chef ?? 'Pr. ST HEBRI') ?>";
    window.DOCTOR_NAME      = "<?= htmlspecialchars($_SESSION['doctor_name'] ?? '') ?>";
    window.RAPPORT_SAVE_URL = "rapport_medical_api.php";
    window.RAPPORT_LOAD_URL = "rapport_medical_api.php?action=load_rapport_medical";
    window.FICHE_SAVE_URL   = "fiche_traitement_api.php";
    window.FICHE_LOAD_URL   = "fiche_traitement_api.php?action=load_fiche";
</script>
<script src="rapport_medical.js"></script>

    <script>

    /* ═══════════════════════════════════════════════════
       RAPPORT MÉDICAL — كارد إضافة مريض
       نسخة طبق الأصل من patient_inline.js:
       buildRapportContent + saveRapportMedical + printRapportMedical
    ═══════════════════════════════════════════════════ */
    (function apfRapportModule() {

        /* ── تهيئة: تعبئة globals من window.* ── */
        function apfRapportInit() {
            var H = window.HOSPITAL_NAME    || 'Centre Hospitalo-Universitaire - Hassani Abdelkader Sidi Bel Abbes';
            var S = window.HOSPITAL_SERVICE || 'Service de Médecine Interne';
            var C = window.CHEF_SERVICE     || 'Pr. ST HEBRI';
            var D = window.DOCTOR_NAME      || '';
            var today = new Date().toISOString().split('T')[0];

            /* ترويسة */
            var instEl = document.getElementById('apf-rapport-inst-main');
            if (instEl) instEl.textContent = H;
            var chefEl = document.getElementById('apf-rapport-chef');
            if (chefEl) chefEl.textContent = C;
            var svcEl  = document.getElementById('apf-rapport-service');
            if (svcEl)  svcEl.textContent  = S;

            /* الطبيب المعالج — display + input */
            var docDisp = document.getElementById('apf-rapport-doctor-display');
            if (docDisp) docDisp.textContent = D;
            var docInp = document.getElementById('apf_rapport_doctor');
            if (docInp && !docInp.value) docInp.value = D;

            /* التاريخ — اليوم */
            var dateInp = document.getElementById('apf_rapport_date');
            if (dateInp && !dateInp.value) dateInp.value = today;

            /* ── مزامنة اسم المريض من حقل المعلومات الشخصية ── */
            var nameGlobal = document.getElementById('apf_full_name');
            var rapportName = document.getElementById('apf_rapport_patient_name');
            if (nameGlobal && rapportName) {
                nameGlobal.addEventListener('input', function () {
                    if (!rapportName._userEdited) rapportName.value = this.value;
                });
            }
            /* ── مزامنة العمر ── */
            var ageGlobal = document.getElementById('apf_age');
            var rapportAge = document.getElementById('apf_rapport_age');
            if (ageGlobal && rapportAge) {
                ageGlobal.addEventListener('input', function () {
                    if (!rapportAge._userEdited) rapportAge.value = this.value;
                });
            }
            /* تحديد التعديل اليدوي */
            [rapportName, rapportAge].forEach(function (el) {
                if (el) el.addEventListener('input', function () { this._userEdited = true; });
            });

            /* ── تحميل تقرير محفوظ من API إذا وُجد مريض مرتبط ── */
            /* للمرضى الجدد لا يوجد patient_id بعد — يُحمَّل عند الحاجة */

            /* ── تحديث status badge عند الكتابة ── */
            var reportSection = document.getElementById('apf-sec-rapport');
            if (reportSection) {
                reportSection.addEventListener('input', function () {
                    var badge = document.getElementById('apf-rapport-status-badge');
                    if (!badge) return;
                    var fields = reportSection.querySelectorAll('input.rapport-field, textarea.rapport-field');
                    var hasFilled = Array.from(fields).some(function (el) {
                        return el.value && el.value.trim() !== '';
                    });
                    badge.textContent   = hasFilled ? '✓ مكتمل' : 'فارغ';
                    badge.classList.toggle('done', hasFilled);
                });
            }
        }

        /* ── حفظ التقرير AJAX — نفس منطق saveRapportMedical تماماً ── */
        window.apfSaveRapportMedical = function () {
            var btn = document.getElementById('apf-rapport-save-btn');

            var getVal = function (id) {
                var el = document.getElementById(id);
                return el ? el.value.trim() : '';
            };

            var rapportDate    = getVal('apf_rapport_date');
            var rapportPatient = getVal('apf_rapport_patient_name');
            var rapportAge     = getVal('apf_rapport_age');
            var rapportDoctor  = getVal('apf_rapport_doctor');
            var rapportContent = getVal('apf_rapport_content');

            // ✅ FIX 2: تحقق من وجود ID حقيقي
            var patientId = window._apfCurrentRecordId || 0;

            if (!patientId) {
                /* لا يوجد ID بعد — احفظ مؤقتاً وأبلغ المستخدم */
                sessionStorage.setItem('apf_rapport_new', JSON.stringify({
                    rapport_date:    rapportDate,
                    rapport_patient: rapportPatient,
                    rapport_age:     rapportAge,
                    rapport_doctor:  rapportDoctor,
                    rapport_content: rapportContent,
                }));
                if (btn) {
                    btn.innerHTML = '📋 محفوظ — سيُرسَل تلقائياً عند حفظ الملف';
                    btn.style.background = 'linear-gradient(135deg,#0ea5e9,#38bdf8)';
                    var badge = document.getElementById('apf-rapport-status-badge');
                    if (badge) { badge.textContent = '✓ جاهز'; badge.classList.add('done'); }
                    setTimeout(function () {
                        btn.innerHTML = '💾 حفظ التقرير';
                        btn.style.background = '';
                    }, 3500);
                }
                return;
            }

            /* UI: loading */
            if (btn) {
                btn.disabled  = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left:4px;"></i> جاري الحفظ...';
            }

            var formData = new FormData();
            formData.append('action',          'save_rapport_medical');
            formData.append('patient_id',      patientId);   /* ✅ FIX 2: ID حقيقي */
            formData.append('rapport_date',    rapportDate);
            formData.append('rapport_patient', rapportPatient);
            formData.append('rapport_age',     rapportAge);
            formData.append('rapport_doctor',  rapportDoctor);
            formData.append('rapport_content', rapportContent);

            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) formData.append('_token', csrfMeta.content);

            fetch(window.RAPPORT_SAVE_URL || 'rapport_medical_api.php', {
                method: 'POST', body: formData, credentials: 'same-origin'
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!btn) return;
                btn.disabled = false;
                if (res && res.success !== false) {
                    btn.innerHTML = '<i class="fas fa-check" style="margin-left:4px;"></i> تم الحفظ!';
                    btn.style.background = 'linear-gradient(135deg,#10b981,#34d399)';
                    var badge = document.getElementById('apf-rapport-status-badge');
                    if (badge) { badge.textContent = '✓ محفوظ'; badge.classList.add('done'); }
                } else {
                    btn.innerHTML = '⚠️ خطأ: ' + (res.message || 'فشل الحفظ');
                    btn.style.background = 'linear-gradient(135deg,#ef4444,#f87171)';
                }
                setTimeout(function () {
                    btn.innerHTML = '💾 حفظ التقرير';
                    btn.style.background = '';
                }, 2800);
            })
            .catch(function () {
                /* Fallback: حفظ محلي في sessionStorage */
                sessionStorage.setItem('apf_rapport_new', JSON.stringify({
                    rapport_date:    rapportDate,
                    rapport_patient: rapportPatient,
                    rapport_age:     rapportAge,
                    rapport_doctor:  rapportDoctor,
                    rapport_content: rapportContent,
                }));
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-hdd" style="margin-left:4px;"></i> محفوظ محلياً';
                    btn.style.background = 'linear-gradient(135deg,#f59e0b,#fbbf24)';
                    var badge = document.getElementById('apf-rapport-status-badge');
                    if (badge) { badge.textContent = '✓ محلي'; badge.classList.add('done'); }
                    setTimeout(function () {
                        btn.innerHTML = '💾 حفظ التقرير';
                        btn.style.background = '';
                    }, 2800);
                }
            });
        };

        /* ── طباعة A4 — نسخة طبق الأصل من printRapportMedical ── */
        window.apfPrintRapportMedical = function () {
            var getVal = function (id) {
                var el = document.getElementById(id);
                return el ? el.value.trim() : '';
            };
            var esc = function (s) {
                return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            };

            var hospitalName    = window.HOSPITAL_NAME    || 'Centre Hospitalo-Universitaire - Hassani Abdelkader Sidi Bel Abbes';
            var hospitalService = window.HOSPITAL_SERVICE || 'Service de Médecine Interne';
            var chefService     = window.CHEF_SERVICE     || 'Pr. ST HEBRI';

            var dateVal = getVal('apf_rapport_date');
            var displayDate = dateVal
                ? new Date(dateVal).toLocaleDateString('fr-DZ', {day:'2-digit', month:'2-digit', year:'numeric'})
                : new Date().toLocaleDateString('fr-DZ');

            var contentEsc = esc(getVal('apf_rapport_content'));

            var win = window.open('', '_blank', 'width=860,height=1100');
            if (!win) { alert('يرجى السماح بالنوافذ المنبثقة للطباعة.'); return; }

            win.document.write('<!DOCTYPE html>\n<html lang="fr" dir="ltr">\n<head>\n<meta charset="UTF-8">\n');
            win.document.write('<title>Rapport Médical — ' + esc(getVal('apf_rapport_patient_name')) + '</title>\n');
            win.document.write('<style>\n');
            win.document.write('*{margin:0;padding:0;box-sizing:border-box;}\n');
            win.document.write('body{font-family:"Times New Roman",Times,serif;color:#000;background:#fff;}\n');
            win.document.write('.page{width:210mm;min-height:297mm;padding:18mm 20mm 24mm;margin:0 auto;position:relative;}\n');
            win.document.write('.rp-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #000;gap:16px;}\n');
            win.document.write('.rp-inst{font-size:11px;line-height:1.7;flex:1;}\n');
            win.document.write('.rp-inst .inst-main{font-weight:700;font-size:11.5px;text-decoration:underline;display:block;margin-bottom:2px;}\n');
            win.document.write('.rp-inst .inst-service{font-style:italic;text-decoration:underline;}\n');
            win.document.write('.rp-logo{border:2px solid #555;padding:10px 16px;text-align:center;min-width:80px;}\n');
            win.document.write('.rp-logo .logo-text{font-size:24px;font-weight:900;font-family:Arial,sans-serif;letter-spacing:-1px;line-height:1;}\n');
            win.document.write('.rp-logo .logo-sub{font-size:7px;color:#555;line-height:1.4;margin-top:4px;}\n');
            win.document.write('.rp-doctor{font-size:11px;line-height:1.7;text-align:right;flex:1;}\n');
            win.document.write('.rp-doctor .doc-lbl{text-decoration:underline;font-weight:700;display:block;}\n');
            win.document.write('.rp-title{text-align:center;font-size:18px;font-weight:900;letter-spacing:5px;text-transform:uppercase;margin:22px 0 8px;}\n');
            win.document.write('.rp-title-line{width:80px;border-top:3px solid #000;margin:0 auto 22px;}\n');
            win.document.write('.rp-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 30px;margin-bottom:20px;font-size:12px;}\n');
            win.document.write('.rp-row{display:flex;align-items:baseline;gap:6px;border-bottom:1px solid #bbb;padding-bottom:3px;}\n');
            win.document.write('.rp-row label{font-weight:700;white-space:nowrap;}\n');
            win.document.write('.rp-row span{flex:1;min-height:16px;}\n');
            win.document.write('.rp-content{min-height:155mm;font-size:13px;line-height:2;white-space:pre-wrap;padding:0 4px;background:repeating-linear-gradient(transparent,transparent 31px,#ddd 31px,#ddd 32px);}\n');
            win.document.write('.rp-sig{position:absolute;bottom:22mm;right:22mm;text-align:center;}\n');
            win.document.write('.rp-sig .sig-title{font-size:12px;font-weight:700;margin-bottom:38px;}\n');
            win.document.write('.rp-sig .sig-line{width:130px;border-top:1px solid #000;margin:0 auto;padding-top:4px;font-size:10px;color:#555;}\n');
            win.document.write('.no-print{text-align:center;padding:14px;background:#f0f9ff;border-bottom:1px solid #bae6fd;}\n');
            win.document.write('.no-print button{background:linear-gradient(135deg,#0ea5e9,#06b6d4);color:#fff;border:none;padding:10px 28px;border-radius:8px;font-size:14px;font-family:Arial,sans-serif;cursor:pointer;font-weight:700;margin:0 5px;}\n');
            win.document.write('.no-print .btn-close{background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;}\n');
            win.document.write('@media print{.no-print{display:none!important;}.page{padding:15mm 18mm 18mm;}}\n');
            win.document.write('</style>\n</head>\n<body>\n');
            win.document.write('<div class="no-print">');
            win.document.write('<button onclick="window.print()">🖨️ طباعة / Imprimer</button>');
            win.document.write('<button class="btn-close" onclick="window.close()">✕ إغلاق</button>');
            win.document.write('</div>\n<div class="page">\n');
            win.document.write('  <div class="rp-header">\n');
            win.document.write('    <div class="rp-inst">');
            win.document.write('<span class="inst-main">' + esc(hospitalName) + '</span>');
            win.document.write('Médecin chef service ' + esc(chefService) + '<br>');
            win.document.write('<span class="inst-service">' + esc(hospitalService) + '</span></div>\n');
            win.document.write('    <div class="rp-logo"><div class="logo-text">CHU</div>');
            win.document.write('<div class="logo-sub">المركز الاستشفائي<br>الجامعي<br>عبد القادر حساني</div></div>\n');
            win.document.write('    <div class="rp-doctor"><span class="doc-lbl">Médecin traitant :</span>');
            win.document.write(esc(getVal('apf_rapport_doctor')) + '</div>\n');
            win.document.write('  </div>\n');
            win.document.write('  <div class="rp-title">RAPPORT MÉDICAL</div>\n');
            win.document.write('  <div class="rp-title-line"></div>\n');
            win.document.write('  <div class="rp-grid">\n');
            win.document.write('    <div class="rp-row"><label>Le :</label><span>' + esc(displayDate) + '</span></div>\n');
            win.document.write('    <div class="rp-row"><label>Patient(e) :</label><span>' + esc(getVal('apf_rapport_patient_name')) + '</span></div>\n');
            win.document.write('    <div class="rp-row"><label>Age :</label><span>' + esc(getVal('apf_rapport_age')) + '</span></div>\n');
            win.document.write('    <div class="rp-row"><label>Médecin traitant :</label><span>' + esc(getVal('apf_rapport_doctor')) + '</span></div>\n');
            win.document.write('  </div>\n');
            win.document.write('  <div class="rp-content">' + contentEsc + '</div>\n');
            win.document.write('  <div class="rp-sig"><div class="sig-title">Médecin traitant</div>');
            win.document.write('<div class="sig-line">Signature &amp; Cachet</div></div>\n');
            win.document.write('</div>\n');
            win.document.write('<script>window.addEventListener("load",function(){setTimeout(function(){window.print();},400);});<\/script>\n');
            win.document.write('</body></html>');
            win.document.close();
        };

        /* ── تحميل تقرير محفوظ لمريض موجود من API ── */
        /* يُستدعى من خارج إذا كان هناك patient_id متاح */
        window.apfLoadRapportForPatient = function (patientId) {
            if (!patientId || patientId === 'new') return;
            var url = (window.RAPPORT_LOAD_URL || 'rapport_medical_api.php?action=load_rapport_medical')
                    + '&patient_id=' + encodeURIComponent(patientId);
            fetch(url, {credentials: 'same-origin'})
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.success || !res.data) return;
                var data = res.data;
                var setVal = function (id, val) {
                    var el = document.getElementById(id);
                    if (el && val) { el.value = val; el._userEdited = true; }
                };
                setVal('apf_rapport_date',         data.rapport_date);
                setVal('apf_rapport_patient_name', data.rapport_patient);
                setVal('apf_rapport_age',          data.rapport_age);
                setVal('apf_rapport_doctor',       data.rapport_doctor);
                setVal('apf_rapport_content',      data.rapport_content);
                /* تحديث status badge */
                var badge = document.getElementById('apf-rapport-status-badge');
                if (badge) { badge.textContent = '✓ مكتمل'; badge.classList.add('done'); }
            })
            .catch(function () {
                /* استرجاع من sessionStorage إن وُجد */
                var local = sessionStorage.getItem('apf_rapport_new');
                if (local) {
                    try {
                        var d = JSON.parse(local);
                        var setVal = function (id, val) {
                            var el = document.getElementById(id);
                            if (el && val) { el.value = val; el._userEdited = true; }
                        };
                        setVal('apf_rapport_date',         d.rapport_date);
                        setVal('apf_rapport_patient_name', d.rapport_patient);
                        setVal('apf_rapport_age',          d.rapport_age);
                        setVal('apf_rapport_doctor',       d.rapport_doctor);
                        setVal('apf_rapport_content',      d.rapport_content);
                    } catch(e) {}
                }
            });
        };

        /* ── حقن CSS الكاملة تماماً كما في patient_inline.js ── */
        (function injectApfRapportCSS() {
            if (document.getElementById('apf-rapport-styles')) return;
            var s = document.createElement('style');
            s.id = 'apf-rapport-styles';
            s.textContent = [
                '/* ═══ APF RAPPORT MÉDICAL STYLES — مطابق pif-rapport-styles في patient_inline.js ═══ */',
                '.pif-rapport-sheet{background:#fff;border-radius:14px;border:1px solid rgba(14,165,233,.15);overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.04);font-family:"Times New Roman",serif;}',
                'body.dark-mode .pif-rapport-sheet{background:#1e293b;border-color:rgba(255,255,255,.07);}',
                '.pif-rapport-header{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:12px;padding:14px 18px 12px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-bottom:2px solid rgba(14,165,233,.2);}',
                'body.dark-mode .pif-rapport-header{background:linear-gradient(135deg,#0f172a,#1e293b);}',
                '.pif-rapport-header-left{text-align:right;direction:rtl;}',
                '.pif-rapport-institution{font-size:.7rem;font-weight:700;color:#0f172a;line-height:1.55;font-family:"Cairo",sans-serif;}',
                'body.dark-mode .pif-rapport-institution{color:#e2e8f0;}',
                '.pif-rapport-institution .inst-main{font-size:.75rem;font-weight:800;color:#0ea5e9;border-bottom:1px solid #0ea5e9;padding-bottom:2px;margin-bottom:4px;display:block;}',
                '.pif-rapport-institution .inst-service{font-size:.65rem;color:#64748b;font-style:italic;text-decoration:underline;}',
                '.pif-rapport-logo{display:flex;flex-direction:column;align-items:center;gap:4px;}',
                '.pif-rapport-logo-chu{width:56px;height:56px;border-radius:12px;background:linear-gradient(135deg,#0ea5e9,#06b6d4);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(14,165,233,.35);}',
                '.pif-rapport-logo-chu span{font-size:1.35rem;font-weight:900;color:#fff;letter-spacing:-1px;font-family:"Arial Black",sans-serif;}',
                '.pif-rapport-logo-sub{font-size:.5rem;color:#64748b;text-align:center;line-height:1.3;font-family:"Cairo",sans-serif;max-width:68px;}',
                '.pif-rapport-header-right{text-align:left;direction:ltr;font-family:"Cairo",sans-serif;}',
                '.pif-rapport-doctor-name{font-size:.72rem;font-weight:700;color:#0f172a;line-height:1.7;}',
                'body.dark-mode .pif-rapport-doctor-name{color:#e2e8f0;}',
                '.pif-rapport-doctor-name span:first-child{display:block;font-size:.65rem;color:#64748b;font-weight:600;}',
                '.pif-rapport-title-bar{text-align:center;padding:13px 18px;border-bottom:1px solid rgba(14,165,233,.1);background:#fafcff;}',
                'body.dark-mode .pif-rapport-title-bar{background:#0f172a;}',
                '.pif-rapport-title-bar h2{font-size:1.05rem;font-weight:900;color:#0f172a;letter-spacing:3px;text-transform:uppercase;margin:0;font-family:"Times New Roman",serif;}',
                'body.dark-mode .pif-rapport-title-bar h2{color:#f1f5f9;}',
                '.rapport-title-line{width:56px;height:3px;background:linear-gradient(90deg,#0ea5e9,#06b6d4);margin:8px auto 0;border-radius:3px;}',
                '.pif-rapport-patient-info{padding:12px 18px;border-bottom:1px solid rgba(14,165,233,.08);display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;direction:ltr;font-family:"Cairo",sans-serif;}',
                '.pif-rapport-info-row{display:flex;align-items:baseline;gap:6px;font-size:.8rem;}',
                '.pif-rapport-info-row label{font-weight:700;color:#475569;white-space:nowrap;font-size:.76rem!important;margin-bottom:0!important;}',
                'body.dark-mode .pif-rapport-info-row label{color:#94a3b8;}',
                '.pif-rapport-info-row input{border:none!important;border-bottom:1px dashed rgba(14,165,233,.4)!important;border-radius:0!important;background:transparent!important;padding:2px 4px!important;font-size:.82rem!important;font-weight:600!important;color:#0f172a!important;flex:1;min-width:0;outline:none!important;box-shadow:none!important;font-family:"Cairo",sans-serif;}',
                'body.dark-mode .pif-rapport-info-row input{color:#f1f5f9!important;border-bottom-color:rgba(14,165,233,.3)!important;}',
                '.pif-rapport-info-row input:focus{border-bottom-color:#0ea5e9!important;box-shadow:none!important;background:rgba(14,165,233,.03)!important;}',
                '.pif-rapport-body{padding:14px 18px;border-bottom:1px solid rgba(14,165,233,.08);min-height:180px;background:repeating-linear-gradient(transparent,transparent 31px,rgba(14,165,233,.06) 31px,rgba(14,165,233,.06) 32px);}',
                'body.dark-mode .pif-rapport-body{background:repeating-linear-gradient(transparent,transparent 31px,rgba(255,255,255,.04) 31px,rgba(255,255,255,.04) 32px);}',
                '.pif-rapport-body-label{font-size:.7rem;font-weight:700;color:#0ea5e9;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;font-family:"Cairo",sans-serif;display:flex;align-items:center;gap:6px;}',
                '.pif-rapport-body-label::after{content:"";flex:1;height:1px;background:linear-gradient(90deg,rgba(14,165,233,.3),transparent);}',
                '.pif-rapport-body textarea{width:100%!important;min-height:160px!important;border:none!important;border-radius:0!important;background:transparent!important;resize:vertical!important;font-size:.9rem!important;font-family:"Times New Roman",Times,serif!important;color:#1e293b!important;line-height:32px!important;padding:0 4px!important;outline:none!important;box-shadow:none!important;}',
                'body.dark-mode .pif-rapport-body textarea{color:#e2e8f0!important;}',
                '.pif-rapport-footer{display:flex;justify-content:flex-end;padding:12px 18px 16px;background:#fafcff;}',
                'body.dark-mode .pif-rapport-footer{background:#0f172a;}',
                '.pif-rapport-signature-block{text-align:center;}',
                '.pif-rapport-signature-label{font-size:.7rem;font-weight:700;color:#475569;margin-bottom:36px;font-family:"Cairo",sans-serif;}',
                'body.dark-mode .pif-rapport-signature-label{color:#94a3b8;}',
                '.pif-rapport-signature-line{width:130px;border-top:1px solid #94a3b8;margin:0 auto;padding-top:4px;font-size:.62rem;color:#94a3b8;font-family:"Cairo",sans-serif;}',
                '@media(max-width:520px){',
                '  .pif-rapport-header{grid-template-columns:1fr;}',
                '  .pif-rapport-header-left,.pif-rapport-header-right{text-align:center;direction:ltr;}',
                '  .pif-rapport-patient-info{grid-template-columns:1fr;}',
                '}'
            ].join('\n');
            document.head.appendChild(s);
        })();

        /* ── تشغيل التهيئة بعد تحميل الصفحة ── */
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', apfRapportInit);
        } else {
            apfRapportInit();
        }

    })();

    /* ═══════════════════════════════════════════════════
       FICHE DE TRAITEMENT — Save & Load
       يعمل لكلا السياقين:
       1. apfSaveFicheTraitement()  ← كارد إضافة مريض (APF)
       2. saveFicheTraitement(recordId) ← مرضى الملف الطبي (patient_inline)
    ═══════════════════════════════════════════════════ */
    (function ficheModule() {

        /* ── حفظ الفيش — كارد إضافة مريض ── */
        window.apfSaveFicheTraitement = function () {
            var btn = document.getElementById('apf-fiche-save-btn');
            // ✅ FIX 1: اقرأ _apfCurrentRecordId في لحظة الاستدعاء (قد يكون عُيِّن للتو)
            var recordId = window._apfCurrentRecordId || 0;

            var getVal = function (id) {
                var el = document.getElementById(id);
                return el ? el.value.trim() : '';
            };

            var diagnostic  = getVal('apf_fiche_diagnostic');
            var medications = getVal('apf_fiche_medications');

            if (!diagnostic && !medications) {
                if (btn) {
                    btn.innerHTML = '⚠️ الفيش فارغ — اكتب بيانات أولاً';
                    btn.style.background = 'linear-gradient(135deg,#f59e0b,#fbbf24)';
                    setTimeout(function () {
                        btn.innerHTML = '💾 حفظ بطاقة العلاج';
                        btn.style.background = '';
                    }, 2500);
                }
                return;
            }

            if (!recordId) {
                /* ✅ FIX 1: لا يوجد record بعد — احفظ مؤقتاً وأبلغ المستخدم بوضوح */
                sessionStorage.setItem('apf_fiche_pending', JSON.stringify({
                    fiche_diagnostic:  diagnostic,
                    fiche_medications: medications,
                }));
                if (btn) {
                    btn.innerHTML = '📋 محفوظ — سيُرسَل تلقائياً عند حفظ الملف';
                    btn.style.background = 'linear-gradient(135deg,#0ea5e9,#38bdf8)';
                    setTimeout(function () { btn.innerHTML = '💾 حفظ بطاقة العلاج'; btn.style.background = ''; }, 3500);
                }
                return;
            }

            _doSaveFiche(recordId, diagnostic, medications, btn, 'apf-fiche-save-btn');
        };

        /* ── حفظ الفيش — مرضى patient_inline (يُستدعى من rapport_medical.js أو dashboard) ── */
        window.saveFicheTraitement = function (recordId) {
            var btnId = 'fiche-save-btn-' + recordId;
            var btn   = document.getElementById(btnId);

            var getVal = function (id) {
                var el = document.getElementById(id);
                return el ? el.value.trim() : '';
            };

            // ✅ FIX: patient_inline.js يبني الحقول بـ IDs: mirror_fiche_diagnostic_{id}
            // injectFicheSaveButton يُعيِّن fiche_diagnostic_{id} فقط إذا لم يكن ID موجوداً
            var diagnostic  = getVal('mirror_fiche_diagnostic_'  + recordId)
                           || getVal('fiche_diagnostic_'          + recordId);
            var medications = getVal('mirror_fiche_medications_' + recordId)
                           || getVal('fiche_medications_'         + recordId);

            _doSaveFiche(recordId, diagnostic, medications, btn, btnId);
        };

        /* ── الإرسال المشترك عبر AJAX ── */
        function _doSaveFiche(recordId, diagnostic, medications, btn, btnId) {
            if (btn) {
                btn.disabled  = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left:4px;"></i> جاري الحفظ...';
            }

            var fd = new FormData();
            fd.append('action',            'save_fiche');
            fd.append('medical_record_id', recordId);
            fd.append('fiche_diagnostic',  diagnostic);
            fd.append('fiche_medications', medications);

            fetch(window.FICHE_SAVE_URL || 'fiche_traitement_api.php', {
                method: 'POST', body: fd, credentials: 'same-origin'
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!btn) return;
                btn.disabled = false;
                if (res && res.success) {
                    btn.innerHTML = '<i class="fas fa-check" style="margin-left:4px;"></i> تم الحفظ!';
                    btn.style.background = 'linear-gradient(135deg,#10b981,#34d399)';
                    /* تحديث status badge إن وُجد */
                    var badge = document.getElementById('fiche-status-' + recordId);
                    if (badge) { badge.textContent = '✓ محفوظ'; }
                    var apfBadge = document.querySelector('#apf-sec-fiche .pif-sec-status');
                    if (apfBadge) { apfBadge.textContent = '✓ محفوظ'; }
                } else {
                    btn.innerHTML = '⚠️ خطأ: ' + (res.message || 'فشل الحفظ');
                    btn.style.background = 'linear-gradient(135deg,#ef4444,#f87171)';
                }
                setTimeout(function () {
                    var label = btn.id && btn.id.startsWith('apf') ? '💾 حفظ بطاقة العلاج' : '💾 حفظ الفيش';
                    btn.innerHTML = label;
                    btn.style.background = '';
                }, 2800);
            })
            .catch(function () {
                /* fallback محلي */
                sessionStorage.setItem('fiche_' + recordId, JSON.stringify({
                    fiche_diagnostic:  diagnostic,
                    fiche_medications: medications,
                }));
                if (btn) {
                    btn.disabled  = false;
                    btn.innerHTML = '💾 محفوظ محلياً';
                    btn.style.background = 'linear-gradient(135deg,#f59e0b,#fbbf24)';
                    setTimeout(function () { btn.innerHTML = '💾 حفظ الفيش'; btn.style.background = ''; }, 2800);
                }
            });
        }

        /* ── تحميل الفيش لمريض موجود ── */
        window.loadFicheTraitement = function (recordId) {
            if (!recordId) return;

            /* جرب sessionStorage أولاً */
            var cached = sessionStorage.getItem('fiche_' + recordId);
            if (cached) {
                try { _fillFicheFields(recordId, JSON.parse(cached)); } catch(e) {}
            }

            /* ثم من الـ server */
            var base = window.FICHE_LOAD_URL || 'fiche_traitement_api.php?action=load_fiche';
            var sep  = base.includes('?') ? '&' : '?';
            var url  = base + sep + 'medical_record_id=' + encodeURIComponent(recordId);

            fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.success && res.data) {
                    _fillFicheFields(recordId, res.data);
                }
            })
            .catch(function () { /* صامت */ });
        };

        function _fillFicheFields(recordId, data) {
            /* ✅ FIX: الـ IDs الحقيقية في patient_inline.js هي:
               mirror_fiche_diagnostic_{id} / mirror_fiche_medications_{id}
               (وليس fiche_diagnostic_{id} كما كان خطأً) */
            var setVal = function (id, val) {
                var el = document.getElementById(id);
                if (el && val) el.value = val;
            };

            /* حقول patient_inline الـ inline accordion */
            setVal('mirror_fiche_diagnostic_'  + recordId, data.fiche_diagnostic);
            setVal('mirror_fiche_medications_' + recordId, data.fiche_medications);

            /* حقول APF (كارد إضافة مريض) */
            setVal('apf_fiche_diagnostic',  data.fiche_diagnostic);
            setVal('apf_fiche_medications', data.fiche_medications);

            /* تحديث status badge */
            var badge = document.getElementById('fiche-status-' + recordId);
            if (!badge) badge = document.querySelector('#pif-sec-fiche-' + recordId + ' .pif-sec-status');
            if (badge && (data.fiche_diagnostic || data.fiche_medications)) {
                badge.textContent = '✓ مكتمل';
            }
        }

    })();

    /* —— فتح/إغلاق الـ Sidebar على الهاتف —— */
    function toggleSidebar() {
        document.getElementById('mainSidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }
    function closeSidebar() {
        document.getElementById('mainSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('active');
    }

    /* —— Accordion: فتح/إغلاق قسم مع إغلاق الباقي —— */
    var _snavCurrent = 'home';

    function snavToggle(name) {
        var groups = ['home', 'ai', 'services', 'men', 'women', 'medcomm'];
        groups.forEach(function(g) {
            var body   = document.getElementById('snb-' + g);
            var header = document.querySelector('#sng-' + g + ' .snav-header, #sng-' + g);
            if (!body) return;
            if (g === name) {
                var isOpen = body.classList.contains('snb-open');
                if (isOpen) {
                    body.classList.remove('snb-open');
                    if (header) header.classList.remove('snav-open');
                } else {
                    body.classList.add('snb-open');
                    if (header) header.classList.add('snav-open');
                    _snavActivateSection(name);
                    // أزل active-direct عن عناصر مباشرة لأننا دخلنا dropdown
                    document.querySelectorAll('.snav-direct, .snav-header.snav-active-direct').forEach(function(el){
                        el.classList.remove('snav-active-direct');
                    });
                }
            } else {
                body.classList.remove('snb-open');
                if (header) header.classList.remove('snav-open');
            }
        });
    }

    /* —— الانتقال المباشر لواجهة (الإعدادات وغيرها) —— */
    function snavGo(name) {
        _snavActivateSection(name);

        // أزل active من كل العناصر
        document.querySelectorAll('.snav-direct, .snav-header').forEach(function(el){
            el.classList.remove('snav-active-direct');
        });
        document.querySelectorAll('.snav-item').forEach(function(i){
            i.classList.remove('snav-item-active');
        });
        // أزل snav-open عن كل الـ dropdowns (لأننا انتقلنا لواجهة أخرى)
        document.querySelectorAll('.snav-body').forEach(function(b){ b.classList.remove('snb-open'); });
        document.querySelectorAll('.snav-header').forEach(function(h){ h.classList.remove('snav-open'); });

        // فعّل العنصر المباشر المناسب
        var target = document.getElementById('sng-' + name);
        if (target) target.classList.add('snav-active-direct');

        if (window.innerWidth <= 768) closeSidebar();
    }

    /* —— فتح كرت محدد داخل home —— */
    function snavCard(cardId) {
        _snavActivateSection('home');
        // تأكد أن dropdown home مفتوح
        var homeBody = document.getElementById('snb-home');
        if (homeBody) homeBody.classList.add('snb-open');
        var homeHdr  = document.querySelector('#sng-home .snav-header');
        if (homeHdr) homeHdr.classList.add('snav-open');

        // أزل active من العناصر المباشرة
        document.querySelectorAll('.snav-direct, .snav-header').forEach(function(el){
            el.classList.remove('snav-active-direct');
        });

        if (typeof closeAllCardContents === 'function') closeAllCardContents();
        var clickedEl = event && event.currentTarget;
        setTimeout(function() {
            var el = document.getElementById(cardId);
            if (el) {
                el.classList.add('active');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            // active state على عنصر الـ submenu
            document.querySelectorAll('.snav-item').forEach(function(i){ i.classList.remove('snav-item-active'); });
            if (clickedEl) clickedEl.classList.add('snav-item-active');
        }, 40);
        if (window.innerWidth <= 768) closeSidebar();
    }

    /* —— إضافة مريض —— */
    function snavAddPatient() {
        _snavActivateSection('home');
        // أزل active من العناصر المباشرة
        document.querySelectorAll('.snav-direct, .snav-header').forEach(function(el){
            el.classList.remove('snav-active-direct');
        });
        if (typeof closeAllCardContents === 'function') closeAllCardContents();
        var clickedEl = event && event.currentTarget;
        setTimeout(function() {
            if (typeof toggleAddPatientForm === 'function') {
                var sec = document.getElementById('addPatientFormSection');
                if (sec && sec.style.display === 'none') toggleAddPatientForm();
            }
            document.querySelectorAll('.snav-item').forEach(function(i){ i.classList.remove('snav-item-active'); });
            if (clickedEl) clickedEl.classList.add('snav-item-active');
        }, 40);
        if (window.innerWidth <= 768) closeSidebar();
    }

    /* —— تبديل الواجهة الرئيسية (الدالة الداخلية) —— */
    function _snavActivateSection(name) {
        // إخفاء كل الواجهات وإظهار الهدف
        document.querySelectorAll('.interface').forEach(function(i){ i.classList.remove('active'); });
        var target = document.getElementById(name + '-interface');
        if (target) target.classList.add('active');
        // تحديث عنوان الصفحة
        var titles = { home:'لوحة التحكم', ai:'مركز البيانات', messages:'المحادثات', services:'الخدمات', settings:'الإعدادات', men:'جناح الرجال', women:'جناح النساء' };
        var pt = document.getElementById('pageTitle');
        if (pt && titles[name]) pt.textContent = titles[name];
        // إغلاق محتويات مفتوحة
        if (typeof closeAllCardContents === 'function') closeAllCardContents();
        _snavCurrent = name;
    }

    /* —— توافق مع switchInterface الأصلي (bottom-bar) —— */
    function switchInterface(name) {
        snavToggle(name);
        _snavActivateSection(name);
    }

    /* —— تهيئة: جميع الأقسام مغلقة افتراضياً —— */
    document.addEventListener('DOMContentLoaded', function() {
        // لا نفتح أي قسم تلقائياً — المستخدم يختار
    });
    </script>

<!-- ════════════════════════════════════════════════════════════
     INPATIENTS DEMO LOGIC
     يعمل فقط داخل قسم "المرضى المقيمون" — لا يلمس أي جزء آخر
════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── بيانات المرضى التجريبيين ── */
    var DEMO_INPATIENTS = {
        'demo-1': {
            id: 'demo-1',
            name: 'محمد بلعيد',
            birthDate: '1981-03-12',
            age: '45',
            gender: 'ذكر',
            maritalStatus: 'متزوج',
            job: 'موظف',
            address: 'تلمسان — حي النصر',
            phone: '0555 123 456',
            admissionDate: '2026-05-15',
            residencyStatus: 'مقيم',
            room: 'غرفة 12',
            reasonExam: 'آلام حادة بالبطن مع ارتفاع الحرارة',
            symptoms: 'ألم شديد في البطن السفلي، حرارة 38.5°C، غثيان وفقدان شهية',
            bloodPressure: '130/85',
            bloodSugar: '0.92 g/l',
            heartRate: '88 / دقيقة',
            temperature: '38.5 °C',
            oxygenLevel: '97%',
            weight: '78 كغ',
            height: '175 سم',
            chronicPatient: 'لا توجد أمراض مزمنة',
            chronicFamily: 'لا يوجد',
            medicalTests: 'NFS — CRP — Glycémie — Bilan hépatique',
            radiology: 'Echo Abdominale',
            diagnosis: 'التهاب زائدة دودية مشتبه به — قيد المتابعة',
            medications: 'Paracetamol 500mg × 3/j\nAmoxicillin 1g × 2/j',
            allergies: 'لا توجد حساسية معروفة',
            surgeries: 'لا توجد عمليات سابقة',
            doctorNotes: 'المريض تحت المراقبة الطبية وتحسن حالته مستقر.'
        },
        'demo-2': {
            id: 'demo-2',
            name: 'أحمد بن صالح',
            birthDate: '1964-07-22',
            age: '62',
            gender: 'ذكر',
            maritalStatus: 'متزوج',
            job: 'متقاعد',
            address: 'سيدي بلعباس — وسط المدينة',
            phone: '0661 789 012',
            admissionDate: '2026-05-18',
            residencyStatus: 'مقيم',
            room: 'غرفة 07',
            reasonExam: 'ضيق في التنفس مع آلام في الصدر',
            symptoms: 'ضيق تنفسي عند المجهود، ألم صدري، دوخة، تورم القدمين',
            bloodPressure: '145/90',
            bloodSugar: '1.35 g/l',
            heartRate: '95 / دقيقة',
            temperature: '37.2 °C',
            oxygenLevel: '94%',
            weight: '82 كغ',
            height: '170 سم',
            chronicPatient: 'ضغط دم مرتفع، قصور قلبي',
            chronicFamily: 'أمراض القلب (الوالد)',
            medicalTests: 'NFS — Pro-BNP — Troponine — ECG',
            radiology: 'Rx Thorax — Echocardiographie',
            diagnosis: 'قصور قلبي احتقاني — متابعة مكثفة',
            medications: 'Bisoprolol 5mg × 1/j\nFurosémide 40mg × 1/j\nRamipril 5mg × 1/j',
            allergies: 'حساسية لـ Aspirine',
            surgeries: 'عملية قلب مفتوح — 2018',
            doctorNotes: 'حالة المريض مستقرة مع مراقبة مستمرة للوظيفة القلبية.'
        }
    };

    /* ── State ── */
    var activeInpItem  = null;  // الـ patient-item المفتوح حالياً
    var dischargeTarget = null; // ID المريض المراد تسجيل خروجه

    /* ═══════════════════════════════════════════════════
       inpToggle — يفتح الملف الطبي في Modal بدل inline
    ═══════════════════════════════════════════════════ */
    window.inpToggle = function (rowEl, demoId, patientName) {
        var overlay = document.getElementById('inp-file-modal-overlay');
        var content = document.getElementById('inp-file-modal-content');
        if (!overlay || !content) return;

        /* ابنِ المحتوى */
        inpBuildFileContent(content, demoId, patientName);

        /* افتح الـ modal */
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.inpCloseFileModal = function () {
        var overlay = document.getElementById('inp-file-modal-overlay');
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
    };

    /* inpCloseActive — kept for compatibility (discharge close btn) */
    function inpCloseActive() { inpCloseFileModal(); }

    /* ═══════════════════════════════════════════════════
       inpBuildFileContent — فيش الملف الطبي داخل Modal
    ═══════════════════════════════════════════════════ */
    function inpBuildFileContent(inner, demoId, patientName) {
        var p = DEMO_INPATIENTS[demoId] || {};

        function fRow(icon, iconColor, label, inputHtml) {
            return '<div style="display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid rgba(14,165,233,.06);">'
                 +   '<div style="width:28px;height:28px;border-radius:7px;background:rgba(14,165,233,.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">'
                 +     '<i class="' + icon + '" style="color:' + iconColor + ';font-size:0.75rem;"></i>'
                 +   '</div>'
                 +   '<div style="flex:1;min-width:0;">'
                 +     '<div style="font-size:0.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">' + label + '</div>'
                 +     inputHtml
                 +   '</div>'
                 + '</div>';
        }

        function fInput(val, ph, type) {
            type = type || 'text';
            return '<input type="' + type + '" value="' + (val || '') + '" placeholder="' + (ph || '—') + '" '
                 + 'style="width:100%;border:none;border-bottom:1px dashed rgba(14,165,233,.25);'
                 + 'background:transparent;padding:3px 2px;font-size:0.84rem;font-weight:600;'
                 + 'color:#0f172a;font-family:\'Cairo\',sans-serif;outline:none;box-sizing:border-box;" '
                 + 'onfocus="this.style.borderBottomColor=\'#0ea5e9\'" '
                 + 'onblur="this.style.borderBottomColor=\'rgba(14,165,233,.25)\'">';
        }

        function fTextarea(val, ph, rows) {
            return '<textarea rows="' + (rows || 2) + '" placeholder="' + (ph || '—') + '" '
                 + 'style="width:100%;border:none;border-bottom:1px dashed rgba(14,165,233,.25);'
                 + 'background:transparent;padding:3px 2px;font-size:0.84rem;font-weight:600;'
                 + 'color:#0f172a;font-family:\'Cairo\',sans-serif;outline:none;resize:vertical;'
                 + 'box-sizing:border-box;line-height:1.55;" '
                 + 'onfocus="this.style.borderBottomColor=\'#0ea5e9\'" '
                 + 'onblur="this.style.borderBottomColor=\'rgba(14,165,233,.25)\'">'
                 + (val || '') + '</textarea>';
        }

        function fSelect(options, selected) {
            var opts = options.map(function(o) {
                return '<option value="' + o + '"' + (o === selected ? ' selected' : '') + '>' + o + '</option>';
            }).join('');
            return '<select style="width:100%;border:none;border-bottom:1px dashed rgba(14,165,233,.25);'
                 + 'background:transparent;padding:3px 2px;font-size:0.84rem;font-weight:600;'
                 + 'color:#0f172a;font-family:\'Cairo\',sans-serif;outline:none;cursor:pointer;'
                 + 'box-sizing:border-box;">'
                 + opts + '</select>';
        }

        function fSectionTitle(icon, title) {
            return '<div style="display:flex;align-items:center;gap:8px;margin:18px 0 6px;padding-bottom:6px;border-bottom:2px solid rgba(14,165,233,.15);">'
                 +   '<div style="width:26px;height:26px;border-radius:7px;background:linear-gradient(135deg,#0ea5e9,#06b6d4);'
                 +     'display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
                 +     '<i class="' + icon + '" style="color:#fff;font-size:0.72rem;"></i>'
                 +   '</div>'
                 +   '<span style="font-size:0.78rem;font-weight:800;color:#0ea5e9;letter-spacing:.3px;">' + title + '</span>'
                 + '</div>';
        }

        var html = [];

        /* ── ID فريد للـ dropdown ── */
        var ddId = 'inp-discharge-dd-' + demoId;

        /* ── رأس الـ Modal ── */
        html.push(
            '<div style="background:linear-gradient(135deg,#0ea5e9,#06b6d4);padding:16px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;position:relative;">',
            '  <div style="display:flex;align-items:center;gap:10px;">',
            '    <div style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800;color:#fff;flex-shrink:0;">',
            '      ' + patientName.charAt(0),
            '    </div>',
            '    <div>',
            '      <div style="font-size:1rem;font-weight:800;color:#fff;line-height:1.2;">' + patientName + '</div>',
            '      <div style="font-size:0.72rem;color:rgba(255,255,255,.85);margin-top:2px;display:flex;gap:12px;flex-wrap:wrap;">',
            '        <span><i class="fas fa-door-open" style="margin-left:4px;"></i>' + (p.room || '—') + '</span>',
            '        <span><i class="fas fa-calendar-check" style="margin-left:4px;"></i>دخول: ' + (p.admissionDate || '—') + '</span>',
            '      </div>',
            '    </div>',
            '  </div>',
            '  <div style="display:flex;align-items:center;gap:8px;position:relative;">',
            '    <span style="background:rgba(255,255,255,.22);color:#fff;font-size:0.7rem;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid rgba(255,255,255,.3);">🏥 مقيم</span>',

            /* ── زر تسجيل الخروج (dropdown trigger) ── */
            '    <div style="position:relative;">',
            '      <button id="inp-dd-btn-' + demoId + '" ',
            '        onclick="inpToggleDischargeDD(\'' + ddId + '\',\'' + demoId + '\',\'' + patientName + '\')" ',
            '        style="display:flex;align-items:center;gap:6px;background:rgba(239,68,68,.85);border:1px solid rgba(255,255,255,.3);color:#fff;padding:7px 13px;border-radius:9px;cursor:pointer;font-size:0.78rem;font-weight:700;font-family:\'Cairo\',sans-serif;white-space:nowrap;">',
            '        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج <i class="fas fa-chevron-down" style="font-size:0.65rem;margin-right:2px;"></i>',
            '      </button>',
            /* ── القائمة المنسدلة ── */
            '      <div id="' + ddId + '" ',
            '        style="display:none;position:absolute;left:0;top:calc(100% + 6px);min-width:230px;background:#fff;border-radius:13px;',
            'box-shadow:0 12px 36px rgba(15,23,42,.22);border:1px solid rgba(14,165,233,.12);z-index:10000;overflow:hidden;direction:rtl;font-family:\'Cairo\',sans-serif;">',
            '        <div style="padding:12px 14px 8px;border-bottom:1px solid rgba(14,165,233,.1);">',
            '          <div style="font-size:0.72rem;font-weight:800;color:#0ea5e9;margin-bottom:8px;"><i class="fas fa-calendar-alt" style="margin-left:5px;"></i>تاريخ الخروج</div>',
            '          <input type="date" id="inp-dd-date-' + demoId + '" ',
            '            style="width:100%;padding:7px 10px;border:1px solid rgba(14,165,233,.25);border-radius:8px;font-size:0.82rem;font-family:\'Cairo\',sans-serif;background:#f8fafc;color:#0f172a;outline:none;box-sizing:border-box;">',
            '        </div>',
            '        <div style="padding:8px 14px 10px;">',
            '          <div style="font-size:0.72rem;font-weight:800;color:#0ea5e9;margin-bottom:6px;"><i class="fas fa-clipboard-list" style="margin-left:5px;"></i>نوع الخروج</div>',
            '          <div style="display:flex;flex-direction:column;gap:4px;">',
            '            <button onclick="inpDDSelectType(\'' + demoId + '\',\'شفاء\')" class="inp-dd-type-btn" style="text-align:right;padding:8px 12px;border:1px solid rgba(16,185,129,.2);border-radius:8px;background:rgba(16,185,129,.06);color:#065f46;font-size:0.82rem;font-weight:700;cursor:pointer;font-family:\'Cairo\',sans-serif;transition:background .15s;">✅ شفاء</button>',
            '            <button onclick="inpDDSelectType(\'' + demoId + '\',\'تحويل الى مصلحة اخرى\')" class="inp-dd-type-btn" style="text-align:right;padding:8px 12px;border:1px solid rgba(14,165,233,.2);border-radius:8px;background:rgba(14,165,233,.06);color:#0c4a6e;font-size:0.82rem;font-weight:700;cursor:pointer;font-family:\'Cairo\',sans-serif;transition:background .15s;">🔄الى مصلحة اخرى تحويل</button>',
            '            <button onclick="inpDDSelectType(\'' + demoId + '\',\'خروج بطلب شخصي\')" class="inp-dd-type-btn" style="text-align:right;padding:8px 12px;border:1px solid rgba(245,158,11,.2);border-radius:8px;background:rgba(245,158,11,.06);color:#78350f;font-size:0.82rem;font-weight:700;cursor:pointer;font-family:\'Cairo\',sans-serif;transition:background .15s;">🚪 خروج بطلب شخصي</button>',
            '            <button onclick="inpDDSelectType(\'' + demoId + '\',\'وفاة\')" class="inp-dd-type-btn" style="text-align:right;padding:8px 12px;border:1px solid rgba(100,116,139,.2);border-radius:8px;background:rgba(100,116,139,.06);color:#1e293b;font-size:0.82rem;font-weight:700;cursor:pointer;font-family:\'Cairo\',sans-serif;transition:background .15s;">🕊️ وفاة</button>',
            '          </div>',
            '        </div>',
            '      </div>',
            '    </div>',

            '    <button onclick="inpCloseFileModal()" style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">✕</button>',
            '  </div>',
            '</div>'
        );

        /* ── مساعد عرض القيمة (للملف القرائي) ── */
        function fVal(val, fallback) {
            return '<span style="color:#0f172a;font-weight:600;font-size:0.85rem;">' + (val || fallback || '—') + '</span>';
        }
        function fBlock(icon, iconColor, label, val) {
            return '<div style="display:flex;flex-direction:column;gap:2px;padding:8px 0;border-bottom:1px solid rgba(14,165,233,.07);">'
                 + '<span style="font-size:0.68rem;font-weight:700;color:#94a3b8;display:flex;align-items:center;gap:5px;">'
                 + '<i class="' + icon + '" style="color:' + iconColor + ';font-size:0.65rem;"></i>' + label + '</span>'
                 + fVal(val)
                 + '</div>';
        }
        function fSecTitle(icon, title) {
            return '<div style="display:flex;align-items:center;gap:8px;margin:20px 0 10px;padding-bottom:8px;border-bottom:2px solid rgba(14,165,233,.15);">'
                 + '<div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#0ea5e9,#06b6d4);display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
                 + '<i class="' + icon + '" style="color:#fff;font-size:0.72rem;"></i></div>'
                 + '<span style="font-size:0.82rem;font-weight:800;color:#0ea5e9;">' + title + '</span>'
                 + '</div>';
        }
        function fGrid2(items) {
            return '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0 18px;">' + items.join('') + '</div>';
        }
        function fBadge(val, color) {
            color = color || '#0ea5e9';
            return '<span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:0.75rem;font-weight:700;background:' + color + '22;color:' + color + ';">' + (val || '—') + '</span>';
        }

        /* ── جسم الفيش (scrollable) — ورقة طبية للقراءة فقط ── */
        html.push('<div style="padding:0 18px 18px;background:#fff;overflow-y:auto;flex:1;min-height:0;overscroll-behavior:contain;">');

        /* ══ ① المعلومات الشخصية ══ */
        html.push(fSecTitle('fas fa-user-circle', 'المعلومات الشخصية'));
        html.push(fGrid2([
            fBlock('fas fa-user',            '#0ea5e9', 'الاسم واللقب',        p.name || patientName),
            fBlock('fas fa-birthday-cake',   '#8b5cf6', 'تاريخ الميلاد',       p.birthDate),
            fBlock('fas fa-hourglass-half',  '#f59e0b', 'العمر',               p.age ? p.age + ' سنة' : ''),
            fBlock('fas fa-venus-mars',      '#ec4899', 'الجنس',               p.gender),
            fBlock('fas fa-heart',           '#ef4444', 'الحالة العائلية',     p.maritalStatus),
            fBlock('fas fa-briefcase',       '#10b981', 'طبيعة العمل',         p.job),
            fBlock('fas fa-map-marker-alt',  '#8b5cf6', 'العنوان',             p.address),
            fBlock('fas fa-phone',           '#10b981', 'رقم الهاتف',          p.phone),
            fBlock('fas fa-calendar-check',  '#0ea5e9', 'تاريخ الدخول',        p.admissionDate),
            fBlock('fas fa-door-open',       '#f59e0b', 'رقم الغرفة',          p.room),
        ]));

        /* ══ ② الفحص والأعراض ══ */
        html.push(fSecTitle('fas fa-stethoscope', 'الفحص والأعراض'));
        html.push(fBlock('fas fa-question-circle','#f59e0b', 'سبب الفحص',   p.reasonExam));
        html.push(fBlock('fas fa-thermometer-half','#ef4444','الأعراض',      p.symptoms));
        html.push(fGrid2([
            fBlock('fas fa-tint',           '#0ea5e9', 'ضغط الدم',            p.bloodPressure),
            fBlock('fas fa-vial',           '#8b5cf6', 'نسبة السكر في الدم',  p.bloodSugar),
            fBlock('fas fa-heartbeat',      '#ef4444', 'معدل ضربات القلب',    p.heartRate),
            fBlock('fas fa-thermometer',    '#ef4444', 'درجة الحرارة',         p.temperature),
            fBlock('fas fa-lungs',          '#0ea5e9', 'نسبة الأكسجين',       p.oxygenLevel),
            fBlock('fas fa-weight',         '#8b5cf6', 'الوزن',               p.weight ? p.weight + ' كغ' : ''),
            fBlock('fas fa-ruler-vertical', '#8b5cf6', 'الطول',               p.height ? p.height + ' سم' : ''),
        ]));
        html.push(fBlock('fas fa-disease', '#ef4444', 'الأمراض المزمنة — المريض', p.chronicPatient));
        html.push(fBlock('fas fa-users',   '#8b5cf6', 'الأمراض المزمنة — العائلة', p.chronicFamily));

        /* ══ ③ متابعة الحمل — تظهر فقط في تخصص أمراض النساء والتوليد ══ */
        var _spec = (window.DOCTOR_SPECIALTY || '').toLowerCase();
        var isGyneco = _spec.indexOf('نساء') !== -1
                    || _spec.indexOf('توليد') !== -1
                    || _spec.indexOf('gynec') !== -1
                    || _spec.indexOf('obst') !== -1;
        if (isGyneco) {
            html.push(fSecTitle('fas fa-baby', 'متابعة الحمل'));
            html.push('<div style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-radius:10px;padding:12px;margin-bottom:10px;border:1px solid rgba(14,165,233,.12);">');
            html.push('<div style="font-size:0.72rem;font-weight:800;color:#0ea5e9;margin-bottom:8px;display:flex;align-items:center;gap:5px;"><i class="fas fa-baby-carriage"></i> بطاقة الحمل</div>');
            html.push(fGrid2([
                fBlock('fas fa-calendar-alt',   '#0ea5e9', 'تاريخ آخر دورة',        p.lastPeriodDate),
                fBlock('fas fa-calendar-check', '#10b981', 'تاريخ الولادة المتوقع',  p.expectedDeliveryDate),
                fBlock('fas fa-tint',           '#ef4444', 'فصيلة الدم',             p.pregBloodType),
                fBlock('fas fa-baby',           '#8b5cf6', 'عدد الحمل / الولادات',   (p.pregnanciesCount ? 'G' + p.pregnanciesCount : '') + (p.birthsCount ? ' P' + p.birthsCount : '')),
                fBlock('fas fa-procedures',     '#f59e0b', 'إجهاض / قيصرية',         (p.miscarriagesCount || '0') + ' / ' + (p.cSectionsCount || '0')),
                fBlock('fas fa-male',           '#0ea5e9', 'حالة الأب',              p.fatherStatus),
            ]));
            html.push(fBlock('fas fa-disease',    '#ef4444', 'أمراض مزمنة',        p.pregChronicDiseases));
            html.push(fBlock('fas fa-sticky-note','#64748b', 'ملاحظات',             p.pregnancyNotes));
            html.push('</div>');
            html.push('<div style="background:linear-gradient(135deg,#ecfeff,#e0f2fe);border-radius:10px;padding:12px;border:1px solid rgba(6,182,212,.12);">');
            html.push('<div style="font-size:0.72rem;font-weight:800;color:#0ea5e9;margin-bottom:8px;display:flex;align-items:center;gap:5px;"><i class="fas fa-heartbeat"></i> متابعة الحمل</div>');
            html.push(fGrid2([
                fBlock('fas fa-weight',         '#8b5cf6', 'الوزن',              p.pregWeight),
                fBlock('fas fa-tint',           '#0ea5e9', 'ضغط الدم',           p.pregBloodPressure),
                fBlock('fas fa-vial',           '#8b5cf6', 'السكر',              p.pregSugarLevel),
                fBlock('fas fa-heartbeat',      '#ef4444', 'نبض الجنين',         p.fetalHeartbeat),
                fBlock('fas fa-baby',           '#10b981', 'حركة الجنين',        p.fetalMovement),
                fBlock('fas fa-ruler',          '#f59e0b', 'وزن/حجم الجنين',    p.fetalWeight),
                fBlock('fas fa-arrows-alt-v',   '#0ea5e9', 'وضعية الجنين',      p.fetalPosition),
            ]));
            html.push(fBlock('fas fa-eye',        '#8b5cf6', 'ملاحظات الإيكوغرافيا', p.echoNotes));
            html.push(fBlock('fas fa-notes-medical','#0ea5e9','ملاحظات الطبيب',       p.followupNotes));
            html.push('</div>');
        }

        /* ══ ④ الفحوصات التكميلية ══ */
        html.push(fSecTitle('fas fa-flask', 'الفحوصات التكميلية'));
        html.push(fBlock('fas fa-vials',  '#8b5cf6', 'التحاليل الطبية',    p.medicalTests));
        html.push(fBlock('fas fa-x-ray',  '#f59e0b', 'الأشعة (Radiologie)', p.radiology));

        /* ══ ⑤ السوابق المرضية ══ */
        html.push(fSecTitle('fas fa-history', 'السوابق المرضية'));
        html.push(fBlock('fas fa-allergies', '#f59e0b', 'الحساسية',          p.allergies));
        html.push(fBlock('fas fa-procedures','#10b981', 'العمليات الجراحية', p.surgeries));

        /* ══ ⑥ Fiche de traitement ══ */
        html.push(fSecTitle('fas fa-notes-medical', 'Fiche de traitement'));
        html.push('<div style="background:#f0f9ff;border-radius:10px;padding:12px 14px;border:1px solid #bae6fd;">');
        html.push('<div style="font-size:0.7rem;color:#0369a1;font-weight:600;margin-bottom:10px;"><i class="fas fa-info-circle" style="margin-left:4px;"></i>بطاقة خاصة بالممرض</div>');
        html.push(fBlock('fas fa-diagnoses','#0ea5e9', '🩺 التشخيص / Diagnostic',              p.diagnosis));
        html.push(fBlock('fas fa-pills',    '#10b981', '💊 الأدوية والعلاجات',                p.medications));
        html.push('</div>');

        /* ══ ⑦ التشخيص والعلاج ══ */
        html.push(fSecTitle('fas fa-diagnoses', 'التشخيص والعلاج'));
        html.push(fBlock('fas fa-search-plus',  '#0ea5e9', 'التشخيص',         p.diagnosis));
        html.push(fBlock('fas fa-pills',        '#10b981', 'الأدوية الحالية', p.medications));
        html.push(fBlock('fas fa-notes-medical','#0ea5e9', 'ملاحظات الطبيب',  p.doctorNotes));

        /* ══ ⑧ الوصفة الطبية ══ */
        html.push(fSecTitle('fas fa-prescription', 'الوصفة الطبية'));
        html.push('<div style="background:#fff;border:1px solid rgba(14,165,233,.15);border-radius:10px;padding:14px;">');
        html.push('<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid rgba(14,165,233,.1);">');
        html.push('<span style="font-size:0.8rem;font-weight:800;color:#0f172a;">' + (p.name || patientName) + '</span>');
        html.push('<span style="font-size:0.72rem;color:#64748b;">' + (p.admissionDate || '—') + '</span>');
        html.push('</div>');
        html.push('<div style="font-size:0.84rem;color:#0f172a;line-height:1.7;white-space:pre-wrap;font-family:\'Cairo\',sans-serif;">' + (p.medications || '—') + '</div>');
        if (p.doctorNotes) {
            html.push('<div style="margin-top:10px;padding-top:8px;border-top:1px dashed rgba(14,165,233,.15);font-size:0.78rem;color:#64748b;font-style:italic;">' + p.doctorNotes + '</div>');
        }
        html.push('</div>');

        /* ══ ⑨ التقرير الطبي ══ */
        html.push(fSecTitle('fas fa-file-medical-alt', 'التقرير الطبي / Rapport Médical'));
        html.push('<div style="background:#f8fafc;border:1px solid rgba(14,165,233,.1);border-radius:10px;padding:14px;min-height:60px;">');
        html.push('<div style="font-size:0.84rem;color:#64748b;line-height:1.7;font-style:italic;">لا يوجد تقرير طبي مُسجَّل بعد.</div>');
        html.push('</div>');

        html.push('</div>'); /* /جسم الفيش */

        /* ── شريط الأزرار السفلي ── */
        html.push(
            '<div style="display:flex;gap:8px;padding:12px 18px;border-top:1px solid rgba(14,165,233,.08);background:rgba(14,165,233,.02);flex-shrink:0;">',
            '  <button class="pif-btn pif-btn-ghost" onclick="inpCloseFileModal()" style="flex:0 0 auto;min-width:90px;">',
            '    <i class="fas fa-times" style="margin-left:5px;"></i> إغلاق',
            '  </button>',
            '  <button class="pif-btn pif-btn-primary" style="flex:0 0 auto;">',
            '    <i class="fas fa-print" style="margin-left:5px;"></i> طباعة الملف',
            '  </button>',
            '</div>'
        );

        inner.innerHTML = html.join('');

        /* ── تهيئة تاريخ الـ dropdown بتاريخ اليوم ── */
        var ddDateEl = document.getElementById('inp-dd-date-' + demoId);
        if (ddDateEl && !ddDateEl.value) {
            ddDateEl.value = new Date().toISOString().split('T')[0];
        }

        /* ── إغلاق الـ dropdown عند الضغط خارجه ── */
        setTimeout(function() {
            document.addEventListener('click', function _inpDDClose(e) {
                var dd = document.getElementById(ddId);
                var btn = document.getElementById('inp-dd-btn-' + demoId);
                if (dd && !dd.contains(e.target) && btn && !btn.contains(e.target)) {
                    dd.style.display = 'none';
                    document.removeEventListener('click', _inpDDClose);
                }
            });
        }, 100);
    }

    /* ═══════════════════════════════════════════════════
       نافذة تسجيل الخروج
    ═══════════════════════════════════════════════════ */
    window.inpOpenDischarge = function (demoId, patientName) {
        dischargeTarget = demoId;

        /* ضع تاريخ اليوم افتراضياً */
        var today = new Date().toISOString().split('T')[0];
        var dateEl = document.getElementById('inp-discharge-date');
        if (dateEl) dateEl.value = today;

        /* أعد خيار نوع الخروج */
        var typeEl = document.getElementById('inp-discharge-type');
        if (typeEl) typeEl.value = '';

        /* اسم المريض */
        var nameEl = document.getElementById('inp-discharge-patient-name');
        if (nameEl) nameEl.textContent = patientName;

        /* أظهر الـ overlay */
        var ov = document.getElementById('inp-discharge-overlay');
        if (ov) { ov.style.display = 'flex'; }
    };

    window.inpCloseDischarge = function () {
        var ov = document.getElementById('inp-discharge-overlay');
        if (ov) ov.style.display = 'none';
        dischargeTarget = null;
    };

    window.inpConfirmDischarge = function () {
        var dateEl = document.getElementById('inp-discharge-date');
        var typeEl = document.getElementById('inp-discharge-type');

        var dateVal = dateEl ? dateEl.value.trim() : '';
        var typeVal = typeEl ? typeEl.value.trim() : '';

        /* validation */
        if (!dateVal) {
            dateEl && (dateEl.style.borderColor = '#ef4444');
            dateEl && dateEl.focus();
            return;
        }
        if (!typeVal) {
            typeEl && (typeEl.style.borderColor = '#ef4444');
            typeEl && typeEl.focus();
            return;
        }

        /* أعد اللون الأصلي */
        if (dateEl) dateEl.style.borderColor = '';
        if (typeEl) typeEl.style.borderColor = '';

        /* احذف البطاقة من القائمة فقط */
        if (dischargeTarget) {
            var cardId = 'demo-inpatient-' + dischargeTarget.replace('demo-', '');
            var card   = document.getElementById(cardId);
            if (card) {
                /* animation خروج ناعم */
                card.style.transition  = 'opacity .4s ease, transform .4s ease, max-height .5s ease';
                card.style.maxHeight   = card.offsetHeight + 'px';
                card.style.overflow    = 'hidden';

                setTimeout(function () {
                    card.style.opacity   = '0';
                    card.style.transform = 'translateX(40px)';
                    card.style.maxHeight = '0';
                    card.style.marginBottom = '0';
                    card.style.padding   = '0';
                }, 10);

                setTimeout(function () {
                    if (card.parentNode) card.parentNode.removeChild(card);
                    /* إذا فرغت القائمة أضف رسالة */
                    inpCheckEmptyList();
                }, 480);
            }
        }

        /* أغلق الـ overlay */
        inpCloseDischarge();

        /* toast تأكيد */
        inpShowToast('تم تسجيل الخروج بنجاح — ' + typeVal);
    };

    /* ══ تحقق من فراغ القائمة ══ */
    function inpCheckEmptyList() {
        var list = document.getElementById('inpatients-patients-list');
        if (!list) return;
        var cards = list.querySelectorAll('.patient-item');
        if (cards.length === 0) {
            list.innerHTML =
                '<p style="text-align:center;padding:30px;color:var(--text-muted);font-size:0.9rem;">' +
                '<i class="fas fa-bed" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:10px;"></i>' +
                'لا يوجد مرضى مقيمون حالياً</p>';
        }
    }

    /* ══ Toast بسيط ══ */
    function inpShowToast(msg) {
        var old = document.getElementById('inp-discharge-toast');
        if (old) old.remove();

        var t = document.createElement('div');
        t.id = 'inp-discharge-toast';
        t.style.cssText = [
            'position:fixed;bottom:28px;right:28px;z-index:10000;',
            'background:linear-gradient(135deg,#10b981,#34d399);',
            'color:#fff;padding:12px 20px;border-radius:12px;',
            'font-family:\'Cairo\',sans-serif;font-size:0.85rem;font-weight:700;',
            'box-shadow:0 8px 24px rgba(16,185,129,.35);',
            'display:flex;align-items:center;gap:8px;',
            'animation:inpToastIn .35s cubic-bezier(.4,0,.2,1);'
        ].join('');
        t.innerHTML = '<i class="fas fa-check-circle"></i> ' + msg;

        /* keyframes */
        if (!document.getElementById('inp-toast-style')) {
            var s = document.createElement('style');
            s.id = 'inp-toast-style';
            s.textContent = '@keyframes inpToastIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}';
            document.head.appendChild(s);
        }

        document.body.appendChild(t);
        setTimeout(function () {
            t.style.transition = 'opacity .4s ease';
            t.style.opacity = '0';
            setTimeout(function () { if (t.parentNode) t.remove(); }, 420);
        }, 3000);
    }

    /* ══ إغلاق نافذة الخروج عند الضغط خارجها ══ */
    document.addEventListener('click', function (e) {
        var ov = document.getElementById('inp-discharge-overlay');
        if (ov && e.target === ov) inpCloseDischarge();
    });

    /* ══ إتاحة inpCloseActive عالمياً ══ */
    window.inpCloseActive = inpCloseActive;

    /* ════════════════════════════════════════════════
       inpAccToggle — accordion داخل الـ modal
    ════════════════════════════════════════════════ */
    window.inpAccToggle = function(sectionEl) {
        if (!sectionEl) return;
        sectionEl.classList.toggle('pif-open');
    };

    /* ════════════════════════════════════════════════
       inpToggleDischargeDD — toggle القائمة المنسدلة
    ════════════════════════════════════════════════ */
    window.inpToggleDischargeDD = function(ddId, demoId, patientName) {
        var dd = document.getElementById(ddId);
        if (!dd) return;
        var isOpen = dd.style.display === 'block';
        dd.style.display = isOpen ? 'none' : 'block';
    };

    /* ════════════════════════════════════════════════
       inpDDSelectType — اختيار نوع الخروج من الـ dropdown
    ════════════════════════════════════════════════ */
    window.inpDDSelectType = function(demoId, typeVal) {
        var dateEl = document.getElementById('inp-dd-date-' + demoId);
        var dateVal = dateEl ? dateEl.value : new Date().toISOString().split('T')[0];

        if (!dateVal) {
            alert('الرجاء تحديد تاريخ الخروج أولاً');
            return;
        }

        /* أغلق الـ dropdown */
        var allDDs = document.querySelectorAll('[id^="inp-discharge-dd-"]');
        allDDs.forEach(function(d) { d.style.display = 'none'; });

        /* نفس منطق inpConfirmDischarge */
        dischargeTarget = demoId;
        var cardId = 'demo-inpatient-' + demoId.replace('demo-', '');
        var card = document.getElementById(cardId);
        if (card) {
            card.style.transition = 'opacity .4s ease, transform .4s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateX(40px)';
            setTimeout(function() {
                card.style.display = 'none';
                /* تحديث العداد */
                var h3s = document.querySelectorAll('.main-card h3');
                h3s.forEach(function(h) {
                    if (h.getAttribute('data-ar') === 'المرضى المقيمون') {
                        var countEl = h.closest('.main-card')
                                       ? h.closest('.main-card').querySelector('.card-count')
                                       : null;
                        if (countEl) {
                            var cur = parseInt(countEl.textContent, 10) || 0;
                            if (cur > 0) countEl.textContent = cur - 1;
                        }
                    }
                });
                /* رسالة فارغة إذا لا يوجد مرضى */
                var list = document.getElementById('inpatients-patients-list');
                if (list) {
                    var remaining = list.querySelectorAll('.patient-item:not([style*="display: none"])');
                    if (remaining.length === 0) {
                        list.innerHTML = '<p style="text-align:center;color:#94a3b8;padding:24px 0;font-size:0.88rem;">لا يوجد مرضى مقيمون حالياً</p>';
                    }
                }
            }, 420);
        }

        inpCloseFileModal();
        inpShowToast('تم تسجيل الخروج — ' + typeVal + ' — ' + dateVal);
        dischargeTarget = null;
    };

})();
</script>

<style>
/* ══ Ward Rooms Grid ══ */
.ward-rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 18px;
    padding: 8px 0;
}
.ward-room-card {
    background: var(--bg-card, #fff);
    border-radius: 18px;
    padding: 20px 16px 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    cursor: default;
    transition: transform .2s, box-shadow .2s;
    border: 1.5px solid transparent;
    position: relative;
    min-height: 160px;
    justify-content: center;
}
.ward-room-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 22px rgba(0,0,0,0.12);
}
.ward-room-card.occupied { border-color: rgba(14,165,233,.18); }
.ward-room-card.vacant {
    border-color: rgba(148,163,184,.13);
    background: var(--bg-main, #f8fafc);
}
.ward-room-number {
    position: absolute;
    top: 12px;
    left: 14px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted, #94a3b8);
    direction: rtl;
}
.ward-room-bed { font-size: 2.4rem; margin-top: 10px; }
.ward-room-name {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-main, #1e293b);
    text-align: center;
    direction: rtl;
}
.ward-room-vacant-text {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-muted, #94a3b8);
}
.ward-room-link {
    font-size: 0.78rem;
    color: var(--primary, #0ea5e9);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
    cursor: pointer;
    direction: rtl;
}
.ward-room-link:hover { text-decoration: underline; }
body.dark-mode .ward-room-card { background: var(--bg-card); }
body.dark-mode .ward-room-card.vacant { background: rgba(255,255,255,.03); }
</style>

<script>
(function() {
var MEN_ROOMS = [
<?php
$stmtMen = $pdo->query("
    SELECT * FROM medical_records
    WHERE residency_status = 'مقيم'
    AND gender = 'ذكر'
    ORDER BY created_at DESC
");

$menPatients = $stmtMen->fetchAll(PDO::FETCH_ASSOC);

$room = 1;

foreach($menPatients as $p):
?>
{
    num: '<?= str_pad($room, 2, '0', STR_PAD_LEFT) ?>',
    patient: '<?= htmlspecialchars($p['full_name']) ?>',
    id: '<?= $p['id'] ?>'
},
<?php
$room++;
endforeach;
?>
];

var WOMEN_ROOMS = [
<?php
$stmtWomen = $pdo->query("
    SELECT * FROM medical_records
    WHERE residency_status = 'مقيم'
    AND gender = 'أنثى'
    ORDER BY created_at DESC
");

$womenPatients = $stmtWomen->fetchAll(PDO::FETCH_ASSOC);

$room = 1;

foreach($womenPatients as $p):
?>
{
    num: '<?= str_pad($room, 2, '0', STR_PAD_LEFT) ?>',
    patient: '<?= htmlspecialchars($p['full_name']) ?>',
    id: '<?= $p['id'] ?>'
},
<?php
$room++;
endforeach;
?>
];

    var BED_OCCUPIED_SVG = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 48"><rect x="4" y="28" width="56" height="12" rx="4" fill="%230ea5e9"/><rect x="4" y="24" width="10" height="16" rx="3" fill="%230284c7"/><rect x="8" y="14" width="20" height="14" rx="4" fill="%23e0f2fe" stroke="%230ea5e9" stroke-width="1.5"/><rect x="4" y="38" width="8" height="6" rx="2" fill="%230284c7"/><rect x="52" y="38" width="8" height="6" rx="2" fill="%230284c7"/></svg>';
    var BED_VACANT_SVG   = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 48"><rect x="4" y="28" width="56" height="12" rx="4" fill="%23cbd5e1"/><rect x="4" y="24" width="10" height="16" rx="3" fill="%2394a3b8"/><rect x="8" y="14" width="20" height="14" rx="4" fill="%23f1f5f9" stroke="%23cbd5e1" stroke-width="1.5"/><rect x="4" y="38" width="8" height="6" rx="2" fill="%2394a3b8"/><rect x="52" y="38" width="8" height="6" rx="2" fill="%2394a3b8"/></svg>';

    function buildRoomCard(room) {
        var div = document.createElement('div');
        div.className = 'ward-room-card ' + (room.patient ? 'occupied' : 'vacant');

        var numEl = document.createElement('div');
        numEl.className = 'ward-room-number';
        numEl.textContent = 'غرفة ' + room.num;
        div.appendChild(numEl);

        var bedEl = document.createElement('div');
        bedEl.className = 'ward-room-bed';
        var img = document.createElement('img');
        img.src = room.patient ? BED_OCCUPIED_SVG : BED_VACANT_SVG;
        img.style.cssText = 'width:56px;height:auto;' + (room.patient ? '' : 'opacity:0.45;');
        img.alt = room.patient ? 'مشغول' : 'شاغر';
        bedEl.appendChild(img);
        div.appendChild(bedEl);

        if (room.patient) {
            var nameEl = document.createElement('div');
            nameEl.className = 'ward-room-name';
            nameEl.textContent = room.patient;
            div.appendChild(nameEl);

            var link = document.createElement('div');
            link.className = 'ward-room-link';
            link.innerHTML = '<i class="fas fa-folder-open"></i> عرض الملف';
            if (room.id) {
                link.onclick = function() {
                    if (typeof inpOpenFileModal === 'function') {
                        inpOpenFileModal(room.id);
                    }
                };
            }
            div.appendChild(link);
        } else {
            var vEl = document.createElement('div');
            vEl.className = 'ward-room-vacant-text';
            vEl.textContent = 'شاغر';
            div.appendChild(vEl);
        }
        return div;
    }

    function renderWard(rooms, gridId) {
        var grid = document.getElementById(gridId);
        if (!grid) return;
        grid.innerHTML = '';
        rooms.forEach(function(r) { grid.appendChild(buildRoomCard(r)); });
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderWard(MEN_ROOMS,   'men-rooms-grid');
        renderWard(WOMEN_ROOMS, 'women-rooms-grid');
    });
})();
</script>
<!-- ═══════════════════════════════════════════════
     PATIENT FILE MODAL — Redesigned
═══════════════════════════════════════════════ -->
<!-- ═══════════════════════════════════════════════
     PATIENT FILE — Inline Dashboard Section (no modal/popup)
═══════════════════════════════════════════════ -->
<div id="patientFileModal" style="display:none;">

    <!-- ── شريط العودة + أزرار الإجراءات ── -->
    <div class="pfm-header" id="pfmDialog" style="border-radius:16px;margin-bottom:20px;">
        <div class="pfm-header-left">
            <!-- زر العودة إلى الغرف -->
            <button onclick="pfmClose()" style="display:flex;align-items:center;gap:7px;padding:8px 16px;background:#f1f5f9;border:1.5px solid rgba(14,165,233,0.22);border-radius:10px;color:#0ea5e9;font-size:0.82rem;font-weight:700;font-family:'Cairo',sans-serif;cursor:pointer;transition:all .2s;white-space:nowrap;" onmouseover="this.style.background='rgba(14,165,233,0.1)'" onmouseout="this.style.background='#f1f5f9'">
                <i class="fas fa-arrow-right"></i>
                <span>العودة إلى الغرف</span>
            </button>
            <div class="pfm-header-icon" style="margin-right:8px;">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="pfm-header-info">
                <h3 class="pfm-header-title" id="pfmPatientName">الملف الطبي</h3>
                <span class="pfm-header-sub" id="pfmPatientId"></span>
            </div>
        </div>
        <div class="pfm-header-actions">

            <!-- زر تعديل الملف الطبي -->
            <button id="pfmEditBtn" onclick="pfmToggleEditMode()" title="تعديل الملف الطبي" class="pfm-discharge-btn" style="background:#f1f5f9;border:1.5px solid rgba(14,165,233,0.25);color:#0ea5e9;">
                <i class="fas fa-edit"></i><span>إضافة متابعة</span>
            </button>

            <!-- زر تسجيل الخروج + Dropdown -->
            <div class="pfm-discharge-wrapper" id="pfmDischargeWrapper">
                <button class="pfm-discharge-btn" id="pfmDischargeBtn" onclick="pfmToggleDischarge(event)">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                    <i class="fas fa-chevron-down pfm-discharge-arrow" id="pfmDischargeArrow"></i>
                </button>

                <!-- Dropdown Panel -->
                <div class="pfm-discharge-panel" id="pfmDischargePanel">
                    <div class="pfm-discharge-panel-header">
                        <i class="fas fa-door-open"></i>
                        تفاصيل تسجيل الخروج
                    </div>

                    <div class="pfm-discharge-field">
                        <label><i class="fas fa-calendar-alt"></i> تاريخ الخروج</label>
                        <input type="date" id="pfmDischargeDate" class="pfm-discharge-input">
                    </div>

                    <div class="pfm-discharge-field">
                        <label><i class="fas fa-tag"></i> نوع الخروج</label>
                        <div class="pfm-discharge-types">
                            <label class="pfm-type-option pfm-type-normal">
                                <input type="radio" name="pfmDischargeType" value="خروج عادي">
                                <div class="pfm-type-card">
                                    <i class="fas fa-check-circle"></i>
                                    <span>خروج عادي</span>
                                </div>
                            </label>
                            <label class="pfm-type-option pfm-type-personal">
                                <input type="radio" name="pfmDischargeType" value="خروج بطلب شخصي">
                                <div class="pfm-type-card">
                                    <i class="fas fa-user-check"></i>
                                    <span>بطلب شخصي</span>
                                </div>
                            </label>
                            <label class="pfm-type-option pfm-type-escape">
                                <input type="radio" name="pfmDischargeType" value="هروب">
                                <div class="pfm-type-card">
                                    <i class="fas fa-running"></i>
                                    <span>هروب</span>
                                </div>
                            </label>
                            <label class="pfm-type-option pfm-type-transfer">
                                <input type="radio" name="pfmDischargeType" value="تحويل الى مصلحة اخرى">
                                <div class="pfm-type-card">
                                    <i class="fas fa-ambulance"></i>
                                    <span>تحويل الى مصلحة اخرى</span>
                                </div>
                            </label>
                            <label class="pfm-type-option pfm-type-death">
                                <input type="radio" name="pfmDischargeType" value="وفاة">
                                <div class="pfm-type-card">
                                    <i class="fas fa-heart-broken"></i>
                                    <span>وفاة</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- حقل المصلحة — يظهر فقط عند تحويل -->
                    <div id="pfmTransferSvcField" style="display:none;margin-bottom:10px;animation:pfmFieldIn .2s ease;">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#475569;margin-bottom:5px;"><i class="fas fa-hospital" style="color:#0ea5e9;margin-left:4px;"></i> اسم المصلحة المحوّل إليها</label>
                        <input type="text" id="pfmTransferSvcName" placeholder="مثال: مصلحة الجراحة — مستشفى..." style="width:100%;padding:8px 12px;border:1.5px solid rgba(14,165,233,.3);border-radius:9px;font-size:0.83rem;font-family:'Cairo',sans-serif;background:#f0f9ff;color:#0f172a;outline:none;box-sizing:border-box;transition:border-color .2s;">
                    </div>
                    <button class="pfm-discharge-confirm" onclick="pfmConfirmDischarge()">
                        <i class="fas fa-check"></i>
                        تأكيد الخروج
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── محتوى الملف الطبي ── -->
    <div id="pfmBody">
        <div id="patientFileContent"></div>
        <div id="fileModalBody"></div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════
     ARCHIVE RECORD MODAL  (Popup مركزي لأرشيف المرضى)
     • يفتح فوق الصفحة دون إخفاء قائمة المرضى أو تغيير layout الصفحة.
     • يحمّل view_record.php داخل #archiveRecordBody.
     • زر الإغلاق يخفي الـ Modal فقط (display:none).
═══════════════════════════════════════════════ -->
<div id="archiveRecordModal" class="arm-overlay" style="display:none;" onclick="armBackdrop(event)">
    <div class="arm-dialog" role="dialog" aria-modal="true" aria-label="الملف الطبي">
        <div class="arm-topbar">
            <div class="arm-topbar-info">
                <span class="arm-topbar-icon"><i class="fas fa-notes-medical"></i></span>
                <span class="arm-topbar-title">الملف الطبي</span>
            </div>
            <div class="arm-topbar-actions">
                <!-- زر تعديل -->
                <button type="button" class="arm-btn arm-btn-edit" id="armEditBtn" onclick="armToggleEdit()">
                    <i class="fas fa-edit"></i><span>تعديل</span>
                </button>
                <!-- زر حفظ التعديلات (يظهر فقط أثناء التعديل) -->
                <button type="button" class="arm-btn arm-btn-save" id="armSaveBtn" onclick="armSaveEdits()" style="display:none;">
                    <i class="fas fa-save"></i><span>حفظ التعديلات</span>
                </button>
                <!-- زر طباعة -->
                <button type="button" class="arm-btn arm-btn-print" id="armPrintBtn" onclick="armPrint()">
                    <i class="fas fa-print"></i><span>طباعة</span>
                </button>
                <!-- زر إغلاق -->
                <button type="button" class="arm-close-btn" onclick="closeArchiveRecord()" aria-label="إغلاق">
                    <i class="fas fa-times"></i><span>إغلاق</span>
                </button>
            </div>
        </div>
        <div class="arm-body" id="archiveRecordBody"></div>
    </div>
</div>

<style>
/* ── Archive Record Modal (مستقل تماماً عن patientFileModal الخاص بالغرف) ── */
.arm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.62);
    backdrop-filter: blur(5px);
    z-index: 99998;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    direction: rtl;
    animation: armOverlayIn 0.2s ease;
}
@keyframes armOverlayIn { from { opacity: 0; } to { opacity: 1; } }

.arm-dialog {
    background: #ffffff;
    width: 100%;
    max-width: 880px;
    max-height: 92vh;
    border-radius: 18px;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: armDialogIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: 'Cairo', sans-serif;
}
@keyframes armDialogIn {
    from { opacity: 0; transform: translateY(14px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.arm-topbar {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
    border-bottom: 1.5px solid rgba(14, 165, 233, 0.18);
}
.arm-topbar-info { display: flex; align-items: center; gap: 10px; }
.arm-topbar-icon {
    width: 38px; height: 38px;
    display: inline-flex; align-items: center; justify-content: center;
    background: #0ea5e9; color: #fff;
    border-radius: 11px; font-size: 1.05rem;
    box-shadow: 0 4px 10px rgba(14, 165, 233, 0.3);
}
.arm-topbar-title { font-size: 1.02rem; font-weight: 800; color: #0f172a; }

.arm-close-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px;
    background: #fff;
    border: 1.5px solid rgba(239, 68, 68, 0.3);
    border-radius: 10px;
    color: #ef4444;
    font-size: 0.82rem; font-weight: 700;
    font-family: 'Cairo', sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.arm-close-btn:hover { background: #ef4444; color: #fff; }

/* ── أزرار الإجراءات (تعديل / حفظ / طباعة) ── */
.arm-topbar-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.arm-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 0.82rem; font-weight: 700;
    font-family: 'Cairo', sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    border: 1.5px solid transparent;
}
.arm-btn:disabled { opacity: 0.7; cursor: default; }
.arm-btn-edit  { background: #fff; border-color: rgba(14,165,233,0.35); color: #0ea5e9; }
.arm-btn-edit:hover  { background: #0ea5e9; color: #fff; }
.arm-btn-save  { background: linear-gradient(135deg,#10b981,#34d399); color: #fff; box-shadow: 0 4px 12px rgba(16,185,129,0.28); }
.arm-btn-save:hover  { filter: brightness(1.05); }
.arm-btn-print { background: #fff; border-color: rgba(2,132,199,0.3); color: #0284c7; }
.arm-btn-print:hover { background: #0284c7; color: #fff; }

/* ── حقول التعديل داخل الملف الطبي ── */
.arm-body .arm-input {
    width: 100%;
    font-family: 'Cairo', sans-serif;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0f172a;
    background: #fff;
    border: 1.5px solid rgba(14,165,233,0.45);
    border-radius: 8px;
    padding: 6px 9px;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.18s, box-shadow 0.18s;
    direction: rtl;
}
.arm-body textarea.arm-input { resize: vertical; line-height: 1.6; min-height: 38px; }
.arm-body .arm-input:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.15); }
.arm-body .vr-vital-chip .arm-input { text-align: center; font-weight: 800; color: #ef4444; }
.arm-body.arm-editing .vr-field,
.arm-body.arm-editing .vr-vital-chip { border-color: rgba(14,165,233,0.3); }

body.dark-mode .arm-body .arm-input { background: #0b1220; color: #e2e8f0; border-color: rgba(14,165,233,0.4); }

.arm-body {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 18px;
    -webkit-overflow-scrolling: touch;
}

/* وضع الليل */
body.dark-mode .arm-dialog { background: #0f172a; }
body.dark-mode .arm-topbar {
    background: linear-gradient(135deg, #0b1220, #0e1a2b);
    border-bottom-color: rgba(14, 165, 233, 0.2);
}
body.dark-mode .arm-topbar-title { color: #f1f5f9; }
body.dark-mode .arm-close-btn { background: #1e293b; }

/* استجابة للشاشات الصغيرة */
@media (max-width: 600px) {
    .arm-overlay { padding: 0; }
    .arm-dialog { max-width: 100%; max-height: 100vh; border-radius: 0; }
    .arm-close-btn span,
    .arm-btn span { display: none; }
    .arm-topbar-title { display: none; }
}
</style>

<!-- ═══════════════════════════════════════════════
     MODAL STYLES
═══════════════════════════════════════════════ -->
<style>
@keyframes pfmFieldIn {
    from { opacity:0; transform:translateY(-6px); }
    to   { opacity:1; transform:translateY(0); }
}

/* Patient File — Inline (no overlay, no fixed positioning) */
#patientFileModal {
    animation: pfmFadeIn 0.25s ease;
}

@keyframes pfmFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Header ── */
.pfm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    background: var(--bg-card, #fff);
    border-bottom: 1.5px solid rgba(14, 165, 233, 0.12);
    border-radius: 16px 16px 0 0;
    position: sticky;
    top: 0;
    z-index: 10;
    gap: 12px;
}
body.dark-mode .pfm-header {
    background: var(--bg-card, #1e293b);
    border-bottom-color: rgba(255,255,255,0.07);
}

.pfm-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}

.pfm-header-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #0ea5e9, #06b6d4);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #fff;
    flex-shrink: 0;
}

.pfm-header-info {
    min-width: 0;
}

.pfm-header-title {
    font-size: 1rem;
    font-weight: 800;
    color: var(--text-main, #1e293b);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pfm-header-sub {
    font-size: 0.72rem;
    color: var(--text-muted, #64748b);
    display: block;
}

body.dark-mode .pfm-header-title { color: #f1f5f9; }
body.dark-mode .pfm-header-sub   { color: #94a3b8; }

.pfm-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

/* ── Discharge Button ── */
.pfm-discharge-wrapper {
    position: relative;
}

.pfm-discharge-btn {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    background: #f1f5f9;
    border: 1.5px solid rgba(14, 165, 233, 0.25);
    border-radius: 10px;
    color: #0ea5e9;
    font-size: 0.82rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    cursor: pointer;
    transition: all 0.22s ease;
    white-space: nowrap;
}

body.dark-mode .pfm-discharge-btn {
    background: rgba(255,255,255,0.07);
    border-color: rgba(255,255,255,0.12);
    color: #7dd3fc;
}

.pfm-discharge-btn:hover {
    background: rgba(14, 165, 233, 0.1);
    border-color: rgba(14, 165, 233, 0.45);
    color: #0284c7;
    transform: translateY(-1px);
}

.pfm-discharge-btn.open {
    background: rgba(14, 165, 233, 0.12);
    border-color: rgba(14, 165, 233, 0.5);
}

.pfm-discharge-arrow {
    font-size: 0.7rem;
    transition: transform 0.3s ease;
}

.pfm-discharge-btn.open .pfm-discharge-arrow {
    transform: rotate(180deg);
}

/* Dropdown Panel */
.pfm-discharge-panel {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    min-width: 340px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(14, 165, 233, 0.2), 0 4px 20px rgba(0,0,0,0.1);
    border: 1px solid rgba(14, 165, 233, 0.15);
    padding: 18px;
    z-index: 100;
    opacity: 0;
    transform: translateY(-8px) scale(0.97);
    pointer-events: none;
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    direction: rtl;
}

body.dark-mode .pfm-discharge-panel {
    background: #1e293b;
    border-color: rgba(255,255,255,0.08);
}

.pfm-discharge-panel.open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: all;
}

.pfm-discharge-panel-header {
    font-size: 0.82rem;
    font-weight: 800;
    color: #0ea5e9;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 7px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(14,165,233,0.1);
}

.pfm-discharge-field {
    margin-bottom: 14px;
}

.pfm-discharge-field label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.76rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 7px;
}

body.dark-mode .pfm-discharge-field label {
    color: #94a3b8;
}

.pfm-discharge-field label i {
    color: #0ea5e9;
    font-size: 0.72rem;
}

.pfm-discharge-input {
    width: 100%;
    padding: 9px 13px;
    border: 1.5px solid rgba(14,165,233,0.2);
    border-radius: 10px;
    font-size: 0.84rem;
    font-family: 'Cairo', sans-serif;
    background: #f8fafc;
    color: #0f172a;
    outline: none;
    transition: all 0.2s ease;
    direction: ltr;
    text-align: right;
}

.pfm-discharge-input:focus {
    border-color: #0ea5e9;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(14,165,233,0.1);
}

body.dark-mode .pfm-discharge-input {
    background: #0f172a;
    color: #f1f5f9;
    border-color: rgba(255,255,255,0.1);
}

/* Discharge Type Radio Cards */
.pfm-discharge-types {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.pfm-type-option {
    flex: 1;
    min-width: 80px;
    cursor: pointer;
}

.pfm-type-option input[type="radio"] {
    display: none;
}

.pfm-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    padding: 9px 8px;
    border: 1.5px solid rgba(14,165,233,0.15);
    border-radius: 10px;
    background: #f8fafc;
    font-size: 0.7rem;
    font-weight: 700;
    color: #64748b;
    transition: all 0.2s ease;
    text-align: center;
    line-height: 1.2;
}

body.dark-mode .pfm-type-card {
    background: #0f172a;
    border-color: rgba(255,255,255,0.07);
    color: #94a3b8;
}

.pfm-type-card i {
    font-size: 1rem;
}

.pfm-type-option input:checked + .pfm-type-card {
    border-width: 2px;
    font-weight: 800;
}

/* Colors per type */
.pfm-type-normal   input:checked + .pfm-type-card { background: rgba(16,185,129,0.1); border-color: #10b981; color: #10b981; }
.pfm-type-personal input:checked + .pfm-type-card { background: rgba(14,165,233,0.1); border-color: #0ea5e9; color: #0ea5e9; }
.pfm-type-escape   input:checked + .pfm-type-card { background: rgba(245,158,11,0.1); border-color: #f59e0b; color: #f59e0b; }
.pfm-type-transfer input:checked + .pfm-type-card { background: rgba(139,92,246,0.1); border-color: #8b5cf6; color: #8b5cf6; }
.pfm-type-death    input:checked + .pfm-type-card { background: rgba(239,68,68,0.1);  border-color: #ef4444; color: #ef4444; }

.pfm-type-normal   .pfm-type-card i { color: #10b981; }
.pfm-type-personal .pfm-type-card i { color: #0ea5e9; }
.pfm-type-escape   .pfm-type-card i { color: #f59e0b; }
.pfm-type-transfer .pfm-type-card i { color: #8b5cf6; }
.pfm-type-death    .pfm-type-card i { color: #ef4444; }

.pfm-discharge-confirm {
    width: 100%;
    padding: 11px;
    background: linear-gradient(135deg, #0ea5e9, #06b6d4);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.84rem;
    font-weight: 800;
    font-family: 'Cairo', sans-serif;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(14,165,233,0.25);
    margin-top: 4px;
}

.pfm-discharge-confirm:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(14,165,233,0.35);
}

/* Close Button */
.pfm-close-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #f1f5f9;
    border: 1.5px solid rgba(14, 165, 233, 0.2);
    color: #64748b;
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.pfm-close-btn:hover {
    background: rgba(239,68,68,0.08);
    border-color: rgba(239,68,68,0.3);
    color: #ef4444;
    transform: scale(1.05);
}

body.dark-mode .pfm-close-btn {
    background: rgba(255,255,255,0.06);
    border-color: rgba(255,255,255,0.1);
    color: #94a3b8;
}

/* ── Body ── */
.pfm-body {
    padding: 0;
    overflow-y: visible;
    scrollbar-width: thin;
    scrollbar-color: rgba(14,165,233,0.3) transparent;
}

.pfm-body::-webkit-scrollbar { width: 5px; }
.pfm-body::-webkit-scrollbar-track { background: transparent; }
.pfm-body::-webkit-scrollbar-thumb { background: rgba(14,165,233,0.3); border-radius: 10px; }

/* Legacy content inside modal inherits well */
.pfm-body .file-modal { display: block !important; position: static !important;
    background: none !important; overflow: visible !important; }
.pfm-body .file-modal-content { margin: 0 !important; border-radius: 0 !important;
    padding: 20px 24px !important; box-shadow: none !important; max-width: 100% !important; width: 100% !important; }
.pfm-body .close-file-modal { display: none !important; }

/* ═══════════════════════════════════════
   إخفاء العنوان المكرر داخل fileModalBody
   (يأتي من view_record.php)
═══════════════════════════════════════ */
#fileModalBody > .vr-page-header,
#fileModalBody > .page-header,
#fileModalBody > div:first-child > .vr-page-header,
#fileModalBody .medfile-page-title,
#fileModalBody .vr-top-header,
#fileModalBody .vr-header,
#fileModalBody .record-header,
#fileModalBody .file-header {
    display: none !important;
}

/* Responsive */
@media (max-width: 640px) {
    .pfm-dialog { border-radius: 16px; }
    .pfm-discharge-panel { min-width: 280px; left: auto; right: 0; }
    .pfm-header { padding: 14px 16px; }
    .pfm-discharge-btn span { display: none; }
    .pfm-discharge-btn { padding: 9px 12px; }
}
</style>

<!-- ═══════════════════════════════════════════════
     MODAL JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
/* ── Open / Close ── */
function inpOpenFileModal(id) {
    // تحديد الـ section النشط (men أو women) لإخفائه وإظهار الملف مكانه
    const activeSection = document.querySelector('.interface.active');

    // أخفِ محتوى الغرف (grid + page-header) داخل الـ section النشط
    if (activeSection) {
        activeSection.querySelectorAll('.ward-rooms-grid, .page-header').forEach(el => {
            el.style.display = 'none';
        });
        // انقل div الملف الطبي داخل الـ section النشط إذا لم يكن فيه
        const fileDiv = document.getElementById('patientFileModal');
        if (fileDiv && fileDiv.parentNode !== activeSection) {
            activeSection.appendChild(fileDiv);
        }
        window._pfmActiveSection = activeSection;
    }

    // أظهر div الملف الطبي
    const fileDiv = document.getElementById('patientFileModal');
    if (fileDiv) {
        fileDiv.style.display = 'block';
        fileDiv.style.animation = 'pfmFadeIn 0.25s ease';
    }

    // جلب محتوى view_record.php وحقنه
    fetch('view_record.php?id=' + id)
        .then(res => res.text())
        .then(html => {
            const modalBody = document.getElementById('fileModalBody');
            modalBody.innerHTML = html;
            document.getElementById('patientFileContent').innerHTML = '';
            modalBody.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                newScript.textContent = oldScript.textContent;
                document.body.appendChild(newScript);
                newScript.remove();
            });

            /* ── حذف العنوان المكرر "الملف الطبي / السجل الطبي الكامل" ── */
            (function removeDuplicateTitle() {
                const keywords = ['السجل الطبي الكامل', 'الملف الطبي'];
                /* استهداف عناصر العنوان والـ headers الأولى */
                const candidates = modalBody.querySelectorAll(
                    'h1, h2, h3, .page-header, .vr-page-header, .vr-header, ' +
                    '.record-header, .file-header, .medfile-page-title, .vr-top-header'
                );
                candidates.forEach(function(el) {
                    const txt = el.textContent || '';
                    if (keywords.some(k => txt.includes(k))) {
                        el.style.display = 'none';
                    }
                });
                /* أيضًا: أي div مباشر أول إذا كان يحتوي فقط على نص عنوان */
                const firstDiv = modalBody.querySelector(':scope > div:first-child');
                if (firstDiv) {
                    const txt = firstDiv.textContent.trim();
                    const isOnlyTitle = keywords.some(k => txt.includes(k)) && firstDiv.children.length <= 2;
                    if (isOnlyTitle) firstDiv.style.display = 'none';
                }
            })();
            _pfmOpenModal(id, '');
        })
        .catch(() => {
            document.getElementById('fileModalBody').innerHTML =
                '<p style="padding:20px;color:#ef4444">تعذّر تحميل الملف.</p>';
            _pfmOpenModal(id, '');
        });
}

function _pfmOpenModal(id, name) {
    // Set header info
    document.getElementById('pfmPatientId').textContent = id ? 'ID: ' + id : '';
    if (name) document.getElementById('pfmPatientName').textContent = 'الملف الطبي — ' + name;
    else document.getElementById('pfmPatientName').textContent = 'الملف الطبي';

    // Set today as default discharge date
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('pfmDischargeDate');
    if (dateInput && !dateInput.value) dateInput.value = today;

    // Store current patient ID
    window._pfmCurrentPatientId = id;

    // Close discharge panel if open
    pfmCloseDischarge();

    // سكرول للأعلى
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function closePatientFile() { pfmClose(); }

function pfmClose() {
    pfmCloseDischarge();

    // أخفِ div الملف الطبي
    const fileDiv = document.getElementById('patientFileModal');
    if (fileDiv) {
        fileDiv.style.opacity = '0';
        fileDiv.style.transition = 'opacity 0.2s ease';
        setTimeout(() => {
            fileDiv.style.display = 'none';
            fileDiv.style.opacity = '';
            fileDiv.style.transition = '';
            // أفرغ المحتوى
            const mb = document.getElementById('fileModalBody');
            if (mb) mb.innerHTML = '';
        }, 200);
    }

    // أعِد إظهار محتوى الغرف (grid + page-header)
    const activeSection = window._pfmActiveSection || document.querySelector('.interface.active');
    if (activeSection) {
        activeSection.querySelectorAll('.ward-rooms-grid, .page-header').forEach(el => {
            el.style.display = '';
        });
    }

    window._pfmCurrentPatientId = null;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// pfmOverlayClick removed — no overlay in inline mode

// Close old fileModal API too (legacy)
(function(){
    const oldClose = document.querySelector && document.querySelector('.close-file-modal');
})();

/* ── Discharge Dropdown ── */
function pfmToggleDischarge(e) {
    e.stopPropagation();
    const panel = document.getElementById('pfmDischargePanel');
    const btn   = document.getElementById('pfmDischargeBtn');
    const isOpen = panel.classList.contains('open');
    if (isOpen) {
        pfmCloseDischarge();
    } else {
        panel.classList.add('open');
        btn.classList.add('open');
        // click outside to close
        setTimeout(() => document.addEventListener('click', pfmOutsideDischarge), 10);
    }
}

function pfmCloseDischarge() {
    document.getElementById('pfmDischargePanel').classList.remove('open');
    document.getElementById('pfmDischargeBtn').classList.remove('open');
    document.removeEventListener('click', pfmOutsideDischarge);
}

function pfmOutsideDischarge(e) {
    const wrapper = document.getElementById('pfmDischargeWrapper');
    if (wrapper && !wrapper.contains(e.target)) pfmCloseDischarge();
}

/* ── Confirm Discharge ── */
function pfmConfirmDischarge() {
    const date = document.getElementById('pfmDischargeDate').value;
    const typeEl = document.querySelector('input[name="pfmDischargeType"]:checked');
    const type = typeEl ? typeEl.value : '';

    if (!date) {
        pfmShowToast('⚠️ الرجاء إدخال تاريخ الخروج', 'warn');
        return;
    }
    if (!type) {
        pfmShowToast('⚠️ الرجاء اختيار نوع الخروج', 'warn');
        return;
    }

    // هنا يمكن إضافة AJAX لحفظ بيانات الخروج لاحقاً
    // مثال: fetch('discharge_patient.php', { method:'POST', body: JSON.stringify({id, date, type}) })
    console.log('Discharge:', { id: window._pfmCurrentPatientId, date, type });

    pfmCloseDischarge();
    pfmShowToast('✅ تم تسجيل الخروج: ' + type + ' — ' + date, 'success');
}

/* ── Toast Notification ── */
function pfmShowToast(msg, kind) {
    let toast = document.getElementById('pfmToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'pfmToast';
        toast.style.cssText = `
            position:fixed; bottom:28px; right:28px; z-index:999999;
            padding:12px 20px; border-radius:12px; font-size:0.84rem;
            font-weight:700; font-family:'Cairo',sans-serif;
            box-shadow:0 8px 24px rgba(0,0,0,0.15);
            transition: all 0.3s ease; opacity:0; transform:translateY(10px);
            direction:rtl; max-width:320px;
        `;
        document.body.appendChild(toast);
    }
    const colors = {
        success: { bg:'linear-gradient(135deg,#10b981,#34d399)', color:'#fff' },
        warn:    { bg:'linear-gradient(135deg,#f59e0b,#fbbf24)', color:'#fff' },
        error:   { bg:'linear-gradient(135deg,#ef4444,#f87171)', color:'#fff' },
    };
    const c = colors[kind] || colors.success;
    toast.style.background = c.bg;
    toast.style.color = c.color;
    toast.textContent = msg;
    setTimeout(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; }, 10);
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
    }, 3500);
}

/* legacy: keep old window.onclick working without breaking other stuff */
(function(){
    const origWindowOnclick = window.onclick;
    window.onclick = function(e) {
        if (origWindowOnclick) origWindowOnclick(e);
    };
})();
</script>

<!-- ══════════════ MEDCOMM MODALS ══════════════ -->
<div id="consultModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);z-index:99999;align-items:center;justify-content:center;backdrop-filter:blur(5px);" onclick="if(event.target===this)closeConsultModal()">
    <div style="background:#fff;border-radius:20px;width:min(620px,95vw);max-height:85vh;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.25);overflow:hidden;direction:rtl;font-family:'Cairo',sans-serif;animation:medcommModalIn .28s cubic-bezier(.4,0,.2,1);">
        <div id="consultModalHeader" style="background:linear-gradient(135deg,#0ea5e9,#06b6d4);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;"><i class="fas fa-comments-medical"></i></div>
                <div>
                    <div style="font-size:0.95rem;font-weight:800;color:#fff;" id="consultModalTitle">استشارة طبية</div>
                    <div style="font-size:0.72rem;color:rgba(255,255,255,.8);" id="consultModalSub">تفاصيل الاستشارة</div>
                </div>
            </div>
            <button onclick="closeConsultModal()" style="width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,.18);border:1.5px solid rgba(255,255,255,.3);color:#fff;cursor:pointer;font-size:0.95rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">✕</button>
        </div>
        <div id="consultModalBody" style="padding:20px;overflow-y:auto;flex:1;min-height:0;"></div>
        <div style="padding:14px 20px;border-top:1px solid rgba(14,165,233,.1);background:#fafcff;display:flex;gap:10px;flex-shrink:0;">
            <button class="pif-btn pif-btn-ghost" onclick="closeConsultModal()" style="flex:1;">إغلاق</button>
            <button class="pif-btn pif-btn-primary" id="consultReplyBtn" style="flex:2;display:none;" onclick="sendConsultReply()"><i class="fas fa-reply"></i> إرسال الرد</button>
        </div>
    </div>
</div>

<style>
@keyframes medcommModalIn {
    from { opacity:0; transform:scale(.94) translateY(18px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}
</style>

<!-- ═══════════════════════════════════════════════
     تعديل الملف الطبي — Edit Mode
═══════════════════════════════════════════════ -->
<script>
(function () {

    /* ══════════════════════════════════════════════════════════════════
       الحقول الشخصية → edit عادي كامل (replace مسموح)
       الحقول الطبية  → نفس textarea الحالية، لكن append-only:
                         - النص القديم محمي (readonly للجزء القديم)
                         - الكتابة مسموح فقط بعد نهاية النص القديم
                         - أي إضافة تُسبق تلقائياً بـ [DD/MM/YYYY]
       ══════════════════════════════════════════════════════════════════ */

    /* الحقول الشخصية — edit عادي */
    var personalFields = [
        'full_name','birth_date','birth_place','birth_info','age','gender',
        'marital_status','job','address','phone','residency_status',
        'entry_date','room_number','blood_type',
        'pregnancy_follow','last_period','expected_birth','pregnancy_count',
        'birth_count','abortions','cesarean','father_status',
        'fetus_position','fetus_move','fetus_weight'
    ];

    /* الحقول الطبية — append-only داخل نفس textarea */
    var medicalFields = [
        'reason_exam','reason_visit','symptoms',
        'chronic_patient','chronic_family','genetic_diseases',
        'medical_tests','radiology',
        'diagnostic','medications','prescription','medical_report',
        'doctor_notes','general_notes'
    ];

    /* للتوافق مع الكود الأصلي */
    var wideFields = medicalFields;

    var _editActive = false;

    /* تاريخ اليوم DD/MM/YYYY */
    function _todayLabel() {
        var d  = new Date();
        var dd = String(d.getDate()).padStart(2,'0');
        var mm = String(d.getMonth()+1).padStart(2,'0');
        return dd + '/' + mm + '/' + d.getFullYear();
    }

    /* ── منع التعديل على النص القديم داخل textarea ── */
    function _makeAppendOnly(ta, frozenLength) {
        /* frozenLength = طول النص المحمي (القديم + رأس التاريخ) */
        function guard(e) {
            var start = ta.selectionStart;
            var end   = ta.selectionEnd;
            /* إذا حاول التحديد أو التعديل داخل المنطقة المحمية */
            if (start < frozenLength || end < frozenLength) {
                /* أعد المؤشر لنهاية المحتوى */
                ta.selectionStart = ta.selectionEnd = ta.value.length;
                if (e) e.preventDefault();
                return false;
            }
        }
        ta.addEventListener('keydown', function(e) {
            var start = ta.selectionStart;
            var end   = ta.selectionEnd;
            /* منع أي مفتاح يمس المنطقة المحمية */
            if (start < frozenLength || end < frozenLength) {
                /* السماح فقط بمفاتيح التنقل */
                var navKeys = ['ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End','PageUp','PageDown'];
                if (navKeys.indexOf(e.key) === -1) {
                    e.preventDefault();
                    ta.selectionStart = ta.selectionEnd = ta.value.length;
                }
            }
            /* منع Backspace/Delete إذا كان سيمس النص القديم */
            if (e.key === 'Backspace' && start <= frozenLength && start === end) {
                e.preventDefault();
            }
            if (e.key === 'Delete' && start < frozenLength) {
                e.preventDefault();
            }
        });
        ta.addEventListener('mouseup',   guard);
        ta.addEventListener('touchend',  guard);
        ta.addEventListener('select',    guard);
        /* منع paste فوق المنطقة المحمية */
        ta.addEventListener('paste', function(e) {
            if (ta.selectionStart < frozenLength) {
                e.preventDefault();
                ta.selectionStart = ta.selectionEnd = ta.value.length;
            }
        });
        /* منع cut من المنطقة المحمية */
        ta.addEventListener('cut', function(e) {
            if (ta.selectionStart < frozenLength || ta.selectionEnd < frozenLength) {
                e.preventDefault();
            }
        });
    }

    window.pfmToggleEditMode = function () {
        if (_editActive) { _exitEditMode(false); }
        else             { _enterEditMode(); }
    };

    function _enterEditMode() {
        var body = document.getElementById('fileModalBody');
        if (!body) return;

        /* ══ 1. حقول vr-field ══ */
        body.querySelectorAll('.vr-field').forEach(function (fieldEl) {
            var valueEl   = fieldEl.querySelector('.vr-field-value');
            if (!valueEl || valueEl.dataset.editDone) return;
            var fieldName = fieldEl.dataset.fieldName || '';
            var rawText   = valueEl.textContent.trim();

            if (personalFields.indexOf(fieldName) !== -1) {
                /* ── شخصي: input عادي ── */
                var isWide = (wideFields.indexOf(fieldName) !== -1);
                var input;
                if (isWide) {
                    input = document.createElement('textarea');
                    input.rows = 3;
                    input.style.cssText = 'width:100%;resize:vertical;border:1.5px solid rgba(14,165,233,.3);border-radius:8px;padding:7px 10px;font-size:0.82rem;font-family:\'Cairo\',sans-serif;color:#1e293b;background:#f0f9ff;outline:none;box-sizing:border-box;direction:rtl;transition:border-color .2s;';
                } else {
                    input = document.createElement('input');
                    input.type = 'text';
                    input.style.cssText = 'width:100%;border:1.5px solid rgba(14,165,233,.3);border-radius:8px;padding:6px 10px;font-size:0.82rem;font-family:\'Cairo\',sans-serif;color:#1e293b;background:#f0f9ff;outline:none;box-sizing:border-box;direction:rtl;transition:border-color .2s;';
                }
                input.value = rawText;
                input.dataset.fieldName  = fieldName;
                input.dataset.editType   = 'personal';
                input.onfocus = function() { this.style.borderColor='rgba(14,165,233,.6)'; };
                input.onblur  = function() { this.style.borderColor='rgba(14,165,233,.3)'; };
                valueEl.style.display = 'none';
                valueEl.dataset.editDone = '1';
                fieldEl.appendChild(input);

            } else if (medicalFields.indexOf(fieldName) !== -1) {
                /* ── طبي: نفس textarea، append-only ── */
                var datePrefix = '\n[' + _todayLabel() + '] ';
                /* إذا النص القديم فارغ: نبدأ بالتاريخ مباشرة بدون سطر فارغ */
                var prefix = rawText ? (rawText + datePrefix) : ('[' + _todayLabel() + '] ');
                var frozenLen = prefix.length;

                var ta = document.createElement('textarea');
                ta.rows = 3;
                ta.value = prefix;
                ta.dataset.fieldName  = fieldName;
                ta.dataset.editType   = 'medical';
                ta.dataset.frozenLen  = frozenLen;
                ta.style.cssText = 'width:100%;resize:vertical;border:1.5px solid rgba(14,165,233,.3);border-radius:8px;padding:7px 10px;font-size:0.82rem;font-family:\'Cairo\',sans-serif;color:#1e293b;background:#f0f9ff;outline:none;box-sizing:border-box;direction:rtl;transition:border-color .2s;';
                ta.onfocus = function() { this.style.borderColor='rgba(14,165,233,.6)'; };
                ta.onblur  = function() { this.style.borderColor='rgba(14,165,233,.3)'; };

                _makeAppendOnly(ta, frozenLen);

                valueEl.style.display = 'none';
                valueEl.dataset.editDone = '1';
                fieldEl.appendChild(ta);

                /* cursor لنهاية النص */
                setTimeout(function() {
                    ta.focus();
                    ta.selectionStart = ta.selectionEnd = ta.value.length;
                }, 0);
            }
        });

        /* ══ 2. العلامات الحيوية — edit عادي (chips) ══ */
        body.querySelectorAll('.vr-vital-chip').forEach(function (chip) {
            var chipValue = chip.querySelector('.chip-value');
            if (!chipValue || chipValue.dataset.editDone) return;
            var fieldName = chip.dataset.fieldName || '';
            var rawText   = chipValue.textContent.trim();
            var datePrefix = rawText ? (rawText + '\n[' + _todayLabel() + '] ') : ('[' + _todayLabel() + '] ');
            var frozenLen  = datePrefix.length;
            var ta = document.createElement('textarea');
            ta.rows = 2;
            ta.value = datePrefix;
            ta.dataset.fieldName = fieldName;
            ta.dataset.editType  = 'medical';
            ta.dataset.frozenLen = frozenLen;
            ta.style.cssText = 'width:100%;resize:vertical;border:1.5px solid rgba(239,68,68,.3);border-radius:7px;padding:4px 8px;font-size:0.82rem;font-weight:800;color:#ef4444;text-align:center;font-family:\'Cairo\',sans-serif;background:#fff5f5;outline:none;box-sizing:border-box;direction:ltr;transition:border-color .2s;';
            ta.onfocus = function() { this.style.borderColor='rgba(239,68,68,.6)'; };
            ta.onblur  = function() { this.style.borderColor='rgba(239,68,68,.3)'; };
            _makeAppendOnly(ta, frozenLen);
            chipValue.style.display = 'none';
            chipValue.dataset.editDone = '1';
            chip.appendChild(ta);
            setTimeout(function() { ta.selectionStart = ta.selectionEnd = ta.value.length; }, 0);
        });

        /* ══ 3. بطاقة العلاج (fiche) — append-only ══ */
        body.querySelectorAll('[data-fiche-field]').forEach(function (displayEl) {
            if (displayEl.dataset.editDone) return;
            var fieldName = displayEl.dataset.ficheField;
            var rawText   = (displayEl.innerText || displayEl.textContent || '').trim();

            var datePrefix = rawText ? (rawText + '\n[' + _todayLabel() + '] ') : ('[' + _todayLabel() + '] ');
            var frozenLen  = datePrefix.length;

            var ta = document.createElement('textarea');
            ta.rows = 4;
            ta.value = datePrefix;
            ta.dataset.ficheField = fieldName;
            ta.dataset.editType   = 'medical';
            ta.dataset.frozenLen  = frozenLen;
            ta.style.cssText = 'width:100%;resize:vertical;border:1.5px solid rgba(124,58,237,.35);border-radius:8px;padding:8px 12px;font-size:0.84rem;font-family:\'Cairo\',sans-serif;color:#1e293b;background:#faf5ff;outline:none;box-sizing:border-box;direction:rtl;transition:border-color .2s;line-height:1.6;';
            if (fieldName === 'fiche_medications') ta.style.fontFamily = '\'Courier New\',\'Cairo\',monospace';
            ta.onfocus = function() { this.style.borderColor='rgba(124,58,237,.65)'; };
            ta.onblur  = function() { this.style.borderColor='rgba(124,58,237,.35)'; };

            _makeAppendOnly(ta, frozenLen);

            displayEl.style.display = 'none';
            displayEl.dataset.editDone = '1';
            displayEl.parentNode.insertBefore(ta, displayEl.nextSibling);

            setTimeout(function() { ta.selectionStart = ta.selectionEnd = ta.value.length; }, 0);
        });

        /* ══ 4. التقرير الطبي (rapport) — append-only ══ */
        body.querySelectorAll('[data-rapport-field]').forEach(function (displayEl) {
            if (displayEl.dataset.editDone) return;
            var fieldName = displayEl.dataset.rapportField;
            var rawText   = (displayEl.innerText || displayEl.textContent || '').trim();

            var datePrefix = rawText ? (rawText + '\n[' + _todayLabel() + '] ') : ('[' + _todayLabel() + '] ');
            var frozenLen  = datePrefix.length;

            var ta = document.createElement('textarea');
            ta.rows = 8;
            ta.value = datePrefix;
            ta.dataset.rapportField = fieldName;
            ta.dataset.editType     = 'medical';
            ta.dataset.frozenLen    = frozenLen;
            ta.style.cssText = 'width:100%;resize:vertical;border:1.5px solid rgba(14,165,233,.35);border-radius:8px;padding:10px 14px;font-size:0.9rem;font-family:\'Times New Roman\',Times,serif;color:#1e293b;background:#f0f9ff;outline:none;box-sizing:border-box;direction:rtl;transition:border-color .2s;line-height:32px;';
            ta.onfocus = function() { this.style.borderColor='rgba(14,165,233,.65)'; };
            ta.onblur  = function() { this.style.borderColor='rgba(14,165,233,.35)'; };

            _makeAppendOnly(ta, frozenLen);

            displayEl.style.display = 'none';
            displayEl.dataset.editDone = '1';
            displayEl.parentNode.insertBefore(ta, displayEl.nextSibling);

            setTimeout(function() { ta.selectionStart = ta.selectionEnd = ta.value.length; }, 0);
        });

        var editBtn = document.getElementById('pfmEditBtn');
        if (editBtn) {
            editBtn.innerHTML = '<i class="fas fa-times"></i><span>إلغاء التعديل</span>';
            editBtn.style.background = 'rgba(239,68,68,0.08)';
            editBtn.style.borderColor = 'rgba(239,68,68,0.3)';
            editBtn.style.color = '#ef4444';
        }

        _showSaveBtn();
        _editActive = true;
    }

    function _exitEditMode(keepValues) {
        var body = document.getElementById('fileModalBody');
        if (!body) return;

        /* ══ 1. حقول vr-field ══ */
        body.querySelectorAll('.vr-field').forEach(function (fieldEl) {
            var valueEl = fieldEl.querySelector('.vr-field-value');
            if (!valueEl) return;

            var input = fieldEl.querySelector('input[data-field-name], textarea[data-field-name]');
            if (!input) return;

            if (keepValues) {
                if (input.dataset.editType === 'medical') {
                    /* الحقل الطبي: نحفظ كامل محتوى textarea كما هو */
                    valueEl.innerHTML = input.value.replace(/\n/g, '<br>');
                } else {
                    /* الحقل الشخصي */
                    valueEl.textContent = input.value;
                }
            }
            valueEl.style.display = '';
            delete valueEl.dataset.editDone;
            input.remove();
        });

        /* ══ 2. العلامات الحيوية ══ */
        body.querySelectorAll('.vr-vital-chip').forEach(function (chip) {
            var chipValue = chip.querySelector('.chip-value');
            var ta        = chip.querySelector('textarea[data-field-name]');
            if (!chipValue || !ta) return;
            if (keepValues) chipValue.innerHTML = ta.value.replace(/\n/g, '<br>');
            chipValue.style.display = '';
            delete chipValue.dataset.editDone;
            ta.remove();
        });

        /* ══ 3. بطاقة العلاج ══ */
        body.querySelectorAll('textarea[data-fiche-field]').forEach(function (ta) {
            var fieldName = ta.dataset.ficheField;
            var displayEl = body.querySelector('[data-fiche-field="' + fieldName + '"]:not(textarea)');
            if (displayEl) {
                if (keepValues) displayEl.innerHTML = ta.value.replace(/\n/g, '<br>');
                displayEl.style.display = '';
                delete displayEl.dataset.editDone;
            }
            ta.remove();
        });

        /* ══ 4. التقرير الطبي ══ */
        body.querySelectorAll('textarea[data-rapport-field]').forEach(function (ta) {
            var fieldName = ta.dataset.rapportField;
            var displayEl = body.querySelector('[data-rapport-field="' + fieldName + '"]:not(textarea)');
            if (displayEl) {
                if (keepValues) displayEl.innerHTML = ta.value.replace(/\n/g, '<br>');
                displayEl.style.display = '';
                delete displayEl.dataset.editDone;
            }
            ta.remove();
        });

        var editBtn = document.getElementById('pfmEditBtn');
        if (editBtn) {
            editBtn.innerHTML = '<i class="fas fa-edit"></i><span>إضافة متابعة</span>';
            editBtn.style.background = '#f1f5f9';
            editBtn.style.borderColor = 'rgba(14,165,233,0.25)';
            editBtn.style.color = '#0ea5e9';
        }

        _hideSaveBtn();
        _editActive = false;
    }

    function _showSaveBtn() {
        var existing = document.getElementById('pfmSaveEditBtn');
        if (existing) { existing.style.display = 'flex'; return; }
        var btn = document.createElement('button');
        btn.id = 'pfmSaveEditBtn';
        btn.className = 'pfm-discharge-btn';
        btn.style.cssText = 'background:rgba(16,185,129,0.2);border-color:rgba(180,255,220,0.5);color:#fff;';
        btn.innerHTML = '<i class="fas fa-save"></i><span>حفظ التغييرات</span>';
        btn.onmouseover = function() { this.style.background='rgba(16,185,129,0.35)'; };
        btn.onmouseout  = function() { this.style.background='rgba(16,185,129,0.2)'; };
        btn.onclick = _saveChanges;
        var dischargeWrapper = document.getElementById('pfmDischargeWrapper');
        if (dischargeWrapper) dischargeWrapper.parentNode.insertBefore(btn, dischargeWrapper);
    }

    function _hideSaveBtn() {
        var btn = document.getElementById('pfmSaveEditBtn');
        if (btn) btn.style.display = 'none';
    }

    function _saveChanges() {
        var recordId = window._pfmCurrentPatientId;
        if (!recordId) { alert('تعذّر تحديد السجل الطبي.'); return; }

        var body = document.getElementById('fileModalBody');
        var formData = new FormData();
        formData.append('record_id', recordId);
        var hasData = false;

        /* ── الحقول (شخصية + طبية): كلها inputs/textareas بـ data-field-name ── */
        body.querySelectorAll('.vr-field').forEach(function (fieldEl) {
            var input = fieldEl.querySelector('input[data-field-name], textarea[data-field-name]');
            if (!input || !input.dataset.fieldName) return;
            /* للحقول الطبية: نرسل كامل المحتوى (القديم + الجديد معاً) */
            formData.append('fields[' + input.dataset.fieldName + ']', input.value);
            hasData = true;
        });

        /* ── العلامات الحيوية ── */
        body.querySelectorAll('.vr-vital-chip').forEach(function (chip) {
            var ta = chip.querySelector('textarea[data-field-name]');
            if (!ta || !ta.dataset.fieldName) return;
            formData.append('fields[' + ta.dataset.fieldName + ']', ta.value);
            hasData = true;
        });

        /* ── بطاقة العلاج ── */
        body.querySelectorAll('textarea[data-fiche-field]').forEach(function (ta) {
            if (!ta.dataset.ficheField) return;
            formData.append('fiche[' + ta.dataset.ficheField + ']', ta.value);
            hasData = true;
        });

        /* ── التقرير الطبي ── */
        body.querySelectorAll('textarea[data-rapport-field]').forEach(function (ta) {
            if (!ta.dataset.rapportField) return;
            formData.append('rapport[' + ta.dataset.rapportField + ']', ta.value);
            hasData = true;
        });

        if (!hasData) { alert('لا توجد بيانات للحفظ.'); return; }

        var saveBtn = document.getElementById('pfmSaveEditBtn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>جارٍ الحفظ...</span>';
        }

        fetch('update_record.php', { method:'POST', body:formData })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                _exitEditMode(true);
                if (window.pfmShowToast) pfmShowToast('✅ تم حفظ التغييرات بنجاح', 'success');
                else alert('✅ تم حفظ التغييرات بنجاح');
            } else {
                alert('خطأ: ' + (json.message || 'فشل الحفظ'));
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i><span>حفظ التغييرات</span>';
                }
            }
        })
        .catch(function() {
            alert('حدث خطأ في الاتصال بالخادم.');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i><span>حفظ التغييرات</span>';
            }
        });
    }

    var origPfmClose = window.pfmClose;
    window.pfmClose = function() {
        if (_editActive) _exitEditMode(false);
        _hideSaveBtn();
        if (origPfmClose) origPfmClose();
    };

})();
</script>
<!-- ════════════════════════════════════════════════════════════════
     MedChifaGiz — تفعيل أزرار "الفحوصات التكميلية"
     يُلصق كما هو قرب نهاية dr_dashboard.php (انظر تعليمات التركيب).
     لا يلمس أي كود آخر. يستخدم:
       - window._apfCurrentRecordId : معرّف المريض المفتوح حالياً (نفس قيمة rapport/fiche)
       - showAddPatientToast()      : دالة الرسائل الموجودة في dr_dashboard.js

     ✅ إصلاح منطقي (Minimal Patch):
       لم يعد الزر يطلب حفظ الملف. إذا لم يُحفظ الملف بعد (لا يوجد patient_id)،
       تُخزَّن البيانات في Draft مؤقت (apf_send_pending) وتظهر رسالة نجاح فوراً،
       ثم تُرسَل تلقائياً للـ API بالـ patient_id الحقيقي فور حفظ الملف.
       (نفس أسلوب apf_fiche_pending / apf_rapport_new الموجود أصلاً).
     ════════════════════════════════════════════════════════════════ -->
<script>
/* ── طابور الإرسال المؤجَّل المشترك (يُركَّب مرة واحدة) ── */
(function apfSendQueueBootstrap() {
    if (window.__apfSendQueueInstalled) return;
    window.__apfSendQueueInstalled = true;

    var QKEY = 'apf_send_pending';

    function readQueue() {
        try { return JSON.parse(sessionStorage.getItem(QKEY) || '[]'); }
        catch (e) { return []; }
    }
    function writeQueue(q) {
        try { sessionStorage.setItem(QKEY, JSON.stringify(q)); } catch (e) {}
    }

    // إضافة طلب للطابور (بدون patient_id — يُضاف عند الحفظ)
    window.apfEnqueueSend = function (url, fields) {
        var q = readQueue();
        q.push({ url: url, fields: fields });
        writeQueue(q);
    };

    // تفريغ الطابور وإرساله بالـ patient_id الحقيقي
    window.apfFlushSendQueue = function () {
        var id = parseInt(window._apfCurrentRecordId, 10) || 0;
        if (id <= 0) return;
        var q = readQueue();
        if (!q.length) return;
        sessionStorage.removeItem(QKEY);
        q.forEach(function (item) {
            try {
                var fd = new FormData();
                Object.keys(item.fields).forEach(function (k) { fd.append(k, item.fields[k]); });
                fd.append('patient_id', id);
                fetch(item.url, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .catch(function (e) { console.warn('[APF] flush send error:', e); });
            } catch (e) { console.warn('[APF] flush item error:', e); }
        });
    };

    // ربط التفريغ بعد حفظ الملف: نلتفّ حول apfResetAll (تُستدعى بعد ضبط _apfCurrentRecordId)
    var _origApfResetAll = window.apfResetAll;
    window.apfResetAll = function () {
        try { window.apfFlushSendQueue(); } catch (e) { console.warn('[APF] flush on reset error:', e); }
        if (typeof _origApfResetAll === 'function') {
            return _origApfResetAll.apply(this, arguments);
        }
    };
})();

(function complementaryExamsModule() {
    var API_URL = window.LAB_RADIO_API_URL || 'lab_radiology_api.php';

    /* رسالة آمنة سواء توفّرت showAddPatientToast أم لا */
    function toast(msg, type) {
        if (typeof window.showAddPatientToast === 'function') {
            window.showAddPatientToast(msg, type);
        } else {
            alert(msg);
        }
    }

    function getVal(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    /* معرّف المريض المفتوح حالياً — نفس مصدر rapport/fiche */
    function currentPatientId() {
        return parseInt(window._apfCurrentRecordId, 10) || 0;
    }

    function sendRequest(action, fieldId, textKey, btn, defaultLabel) {
        var patientId = currentPatientId();
        var text = getVal(fieldId);

        // منع إرسال طلب فارغ
        if (!text) {
            toast('⚠️ لا يمكن إرسال طلب فارغ', 'warn');
            return;
        }

        // ✅ إذا لم يُحفظ الملف بعد: خزّن كـ Draft وأظهر نجاحاً — سيُرسَل تلقائياً بعد الحفظ
        if (patientId <= 0) {
            var draft = { action: action };
            draft[textKey] = text;
            window.apfEnqueueSend(API_URL, draft);
            toast('✅ تم تسجيل الطلب — سيُرسَل تلقائياً عند حفظ الملف', 'success');
            if (btn) {
                var lbl = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check" style="margin-left:6px;"></i> تم التسجيل';
                setTimeout(function () { btn.innerHTML = defaultLabel || lbl; }, 2500);
            }
            return;
        }

        if (btn) {
            btn.disabled  = true;
            btn.dataset._label = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left:6px;"></i> جاري الإرسال...';
        }

        var fd = new FormData();
        fd.append('action', action);
        fd.append('patient_id', patientId);
        fd.append(textKey, text);

        fetch(API_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (btn) btn.disabled = false;
                if (res && res.success) {
                    toast('✅ ' + (res.message || 'تم الإرسال بنجاح'), 'success');
                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-check" style="margin-left:6px;"></i> تم الإرسال';
                        setTimeout(function () { btn.innerHTML = defaultLabel; }, 2500);
                    }
                } else {
                    toast('❌ ' + (res && res.message ? res.message : 'فشل الإرسال'), 'error');
                    if (btn) btn.innerHTML = defaultLabel;
                }
            })
            .catch(function () {
                if (btn) { btn.disabled = false; btn.innerHTML = defaultLabel; }
                toast('❌ تعذّر الاتصال بالخادم', 'error');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var labBtn = document.getElementById('sendLabRequestBtn');
        if (labBtn) {
            labBtn.addEventListener('click', function () {
                sendRequest('send_lab_request', 'apf_medical_tests', 'analysis_text', labBtn, '📤 إرسال للمخبر');
            });
        }

        var radioBtn = document.getElementById('sendRadiologyRequestBtn');
        if (radioBtn) {
            radioBtn.addEventListener('click', function () {
                sendRequest('send_radiology_request', 'apf_radiology', 'radiology_text', radioBtn, '📤 إرسال للأشعة');
            });
        }
    });
})();
</script>
<!-- ════════════════════════════════════════════════════════════════
     MedChifaGiz — تفعيل إرسال الوصفة للصيدلية + إرسال fiche للممرض
     يُلصق كما هو قرب نهاية dr_dashboard.php (انظر تعليمات التركيب).
     إضافة فقط: يتجاوز (override) الدالتين stub الموجودتين دون حذف أي سطر:
        apfSendPrescriptionToPharmacy()  → الزر "إرسال الوصفة للصيدلي"
        apfSendFicheToNurse()            → الزر "إرسال fiche للممرض"
     يستخدم window._apfCurrentRecordId (نفس مصدر rapport/fiche) و showAddPatientToast.

     ✅ إصلاح منطقي (Minimal Patch):
       لم يعد الزر يطلب حفظ الملف. إذا لم يُحفظ الملف بعد (لا يوجد patient_id)،
       تُخزَّن جميع بيانات المريض الحالية في Draft مؤقت (apf_send_pending)
       وتظهر رسالة نجاح فوراً، ثم تُرسَل تلقائياً للـ API بالـ patient_id الحقيقي
       فور حفظ الملف. (نفس أسلوب apf_fiche_pending / apf_rapport_new الموجود أصلاً).
     ════════════════════════════════════════════════════════════════ -->
<script>
/* ── طابور الإرسال المؤجَّل المشترك (يُركَّب مرة واحدة — نفس النسخة في ملف الفحوصات) ── */
(function apfSendQueueBootstrap() {
    if (window.__apfSendQueueInstalled) return;
    window.__apfSendQueueInstalled = true;

    var QKEY = 'apf_send_pending';

    function readQueue() {
        try { return JSON.parse(sessionStorage.getItem(QKEY) || '[]'); }
        catch (e) { return []; }
    }
    function writeQueue(q) {
        try { sessionStorage.setItem(QKEY, JSON.stringify(q)); } catch (e) {}
    }

    window.apfEnqueueSend = function (url, fields) {
        var q = readQueue();
        q.push({ url: url, fields: fields });
        writeQueue(q);
    };

    window.apfFlushSendQueue = function () {
        var id = parseInt(window._apfCurrentRecordId, 10) || 0;
        if (id <= 0) return;
        var q = readQueue();
        if (!q.length) return;
        sessionStorage.removeItem(QKEY);
        q.forEach(function (item) {
            try {
                var fd = new FormData();
                Object.keys(item.fields).forEach(function (k) { fd.append(k, item.fields[k]); });
                fd.append('patient_id', id);
                fetch(item.url, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .catch(function (e) { console.warn('[APF] flush send error:', e); });
            } catch (e) { console.warn('[APF] flush item error:', e); }
        });
    };

    var _origApfResetAll = window.apfResetAll;
    window.apfResetAll = function () {
        try { window.apfFlushSendQueue(); } catch (e) { console.warn('[APF] flush on reset error:', e); }
        if (typeof _origApfResetAll === 'function') {
            return _origApfResetAll.apply(this, arguments);
        }
    };
})();

(function doctorSendModule() {
    var PHARMACY_URL = window.PHARMACY_API_URL || 'pharmacy_api.php';
    var NURSE_URL    = window.NURSE_API_URL    || 'nurse_treatment_api.php';

    function toast(msg, type) {
        if (typeof window.showAddPatientToast === 'function') window.showAddPatientToast(msg, type);
        else alert(msg);
    }
    function gv(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }
    function currentPatientId() {
        return parseInt(window._apfCurrentRecordId, 10) || 0;
    }
    // ذكر → men / أنثى → women
    function aileFromGender(g) {
        if (g === 'ذكر') return 'men';
        if (g === 'أنثى') return 'women';
        return '';
    }
    // جمع بيانات المريض المشتركة من واجهة الطبيب الحالية
    function patientCommon() {
        var gender = gv('apf_gender');
        return {
            patient_id:   currentPatientId(),
            patient_name: gv('apf_full_name') || gv('apf_rx_patient_name'),
            birth_info:   gv('apf_birth_info'),
            gender:       gender,
            room:         gv('apf_room_number'),
            service:      window.HOSPITAL_SERVICE || '',
            aile:         aileFromGender(gender),
            doctor_name:  window.DOCTOR_NAME || '',
            diagnostic:   gv('apf_fiche_diagnostic')
        };
    }

    // إرسال فوري أو تخزين كـ Draft حسب توفّر patient_id
    function sendOrQueue(url, fields, patientId, okMsg, loadingMsg) {
        // ✅ لم يُحفظ الملف بعد → Draft مؤقت يُرسَل تلقائياً عند الحفظ
        if (patientId <= 0) {
            window.apfEnqueueSend(url, fields);
            toast('✅ تم تسجيل الطلب — سيُرسَل تلقائياً عند حفظ الملف', 'success');
            return;
        }
        var fd = new FormData();
        Object.keys(fields).forEach(function (k) { fd.append(k, fields[k]); });
        fd.append('patient_id', patientId);

        toast(loadingMsg, 'info');
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.success) toast('✅ ' + (res.message || okMsg), 'success');
                else toast('❌ ' + (res && res.message ? res.message : 'فشل الإرسال'), 'error');
            })
            .catch(function () { toast('❌ تعذّر الاتصال بالخادم', 'error'); });
    }

    /* ════════ إرسال الوصفة للصيدلية ════════ */
    window.apfSendPrescriptionToPharmacy = function () {
        var c = patientCommon();
        var prescription = gv('apf_rx_prescription');

        if (!prescription) { toast('⚠️ لا يمكن إرسال وصفة فارغة — اكتب الأدوية أولاً', 'warn'); return; }

        var fields = {
            action:       'send_prescription',
            patient_name: c.patient_name,
            birth_info:   c.birth_info,
            gender:       c.gender,
            room:         c.room,
            service:      c.service,
            aile:         c.aile,
            doctor_name:  c.doctor_name,
            diagnostic:   c.diagnostic,
            rx_date:      gv('apf_rx_date'),
            notes:        gv('apf_rx_doctor_notes'),
            // الوصفة نص حر: كل سطر = دواء (الـ API يحوّلها لـ JSON دون فقدان أي معلومة)
            medicines:    prescription
        };

        sendOrQueue(PHARMACY_URL, fields, c.patient_id,
            'تم إرسال الوصفة للصيدلية', '📤 جاري إرسال الوصفة للصيدلية...');
    };

    /* ════════ إرسال fiche العلاج للممرض ════════ */
    window.apfSendFicheToNurse = function () {
        var c = patientCommon();
        var medications = gv('apf_fiche_medications');

        if (!medications) { toast('⚠️ لا يمكن إرسال fiche فارغة — اكتب العلاجات أولاً', 'warn'); return; }

        var fields = {
            action:         'send_treatment',
            patient_name:   c.patient_name,
            birth_info:     c.birth_info,
            gender:         c.gender,
            room:           c.room,
            service:        c.service,
            aile:           c.aile,
            doctor_name:    c.doctor_name,
            motif:          gv('apf_reason_exam'),
            diagnostic:     c.diagnostic,
            admission_date: gv('apf_admission_date'),
            // العلاجات نص حر: كل سطر = علاج (الـ API يحوّلها لـ JSON)
            treatments:     medications
        };

        sendOrQueue(NURSE_URL, fields, c.patient_id,
            'تم إرسال fiche للممرض', '📤 جاري إرسال fiche العلاج للممرض...');
    };
})();
</script>
    <!-- مُصدِّر PDF المشترك (يجب تحميله قبل سكربتات التقارير) -->
    <script src="report_pdf_export.js"></script>
    <!-- ميزة توليد التقارير الطبية بالذكاء الاصطناعي (Groq) -->
    <script src="medical_report.js"></script>
    <!-- أرشيف التقارير الطبية -->
    <script src="medical_reports_archive.js"></script>
<script src="ai_file_organizer.js"></script>
<script>
function checkPatientAccount() {

    const email = document.getElementById('apf_email').value.trim();

    if (email === '') {
        document.getElementById('apf_account_status').value = '';
        return;
    }

    const formData = new FormData();
    formData.append('email', email);

    fetch('check_patient_account.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {

        if (data.exists) {

            if (data.status === 'active') {
                document.getElementById('apf_account_status').value =
                    '✅ للمريض حساب مفعل';
            } else {
                document.getElementById('apf_account_status').value =
                    '🟡 للمريض حساب لكنه غير مفعل';
            }

        } else {

            document.getElementById('apf_account_status').value =
                '❌ لا يوجد حساب لهذا البريد';

        }

    })
    .catch(() => {
        document.getElementById('apf_account_status').value =
            'خطأ في الاتصال';
    });

}
</script>

<!-- ═══════════════════════════════════════════════════════
     نافذة "استشارة جديدة" — Medical Case Management
     Front-End فقط | فتح/إغلاق وتبديل النطاق بـ CSS عبر :checked
═══════════════════════════════════════════════════════ -->
<input type="checkbox" id="cncOpen" class="cnc-toggle">
<div id="csltNewModal" role="dialog" aria-modal="true" aria-label="استشارة جديدة">
    <label for="cncOpen" class="cnc-backdrop" aria-label="إغلاق"></label>
    <div class="cnc-dialog">

        <!-- مدخلات نطاق الاستشارة (CSS فقط) -->
       <input class="cnc-toggle" type="radio" name="cnc-scope" id="cnc-scope-internal" value="internal" checked>
        <input class="cnc-toggle" type="radio" name="cnc-scope" id="cnc-scope-external" value="external">

        <!-- رأس النافذة -->
        <div class="cnc-head">
            <div class="cnc-head-info">
                <div class="cnc-head-icon"><i class="fas fa-notes-medical"></i></div>
                <div class="cnc-head-titles">
                    <h3>استشارة جديدة</h3>
                    <p>إنشاء حالة استشارة طبية جديدة</p>
                </div>
            </div>
            <label for="cncOpen" class="cnc-close" aria-label="إغلاق"><i class="fas fa-times"></i></label>
        </div>

        <!-- جسم النافذة -->
        <div class="cnc-body">

            <!-- القسم 1: معلومات الاستشارة -->
            <div class="cnc-card">
                <div class="cnc-card-title"><i class="fas fa-circle-info"></i> معلومات الاستشارة</div>

                <div class="cnc-field">
                    <span class="cnc-label"><i class="fas fa-tags"></i> نوع الاستشارة</span>
                    <div class="cnc-chip-row">
               <input class="cnc-chip-input" type="radio" name="cnc-type" id="cnc-type-1" value="medical_opinion" checked>
                        <label class="cnc-chip" for="cnc-type-1"><i class="fas fa-user-md"></i> رأي طبي</label>
                       <input class="cnc-chip-input" type="radio" name="cnc-type" id="cnc-type-2" value="urgent_opinion">
                        <label class="cnc-chip" for="cnc-type-2"><i class="fas fa-bolt"></i> رأي عاجل</label>
                       <input class="cnc-chip-input" type="radio" name="cnc-type" id="cnc-type-3" value="case_discussion">
                        <label class="cnc-chip" for="cnc-type-3"><i class="fas fa-comments"></i> مناقشة حالة</label>
                        <input class="cnc-chip-input" type="radio" name="cnc-type" id="cnc-type-4" value="patient_transfer">
                        <label class="cnc-chip" for="cnc-type-4"><i class="fas fa-exchange-alt"></i> طلب تحويل مريض</label>
                        <input class="cnc-chip-input" type="radio" name="cnc-type" id="cnc-type-5" value="xray_review">
                        <label class="cnc-chip" for="cnc-type-5"><i class="fas fa-x-ray"></i> طلب تفسير أشعة</label>
<input class="cnc-chip-input" type="radio" name="cnc-type" id="cnc-type-6" value="lab_review">
                        <label class="cnc-chip" for="cnc-type-6"><i class="fas fa-vials"></i> طلب تفسير تحاليل</label>
                        <input class="cnc-chip-input" type="radio" name="cnc-type" id="cnc-type-7" value="follow_up">
                        <label class="cnc-chip" for="cnc-type-7"><i class="fas fa-heart-pulse"></i> متابعة حالة</label>
                    </div>
                </div>

                <div class="cnc-field" style="margin-bottom:0;">
                    <span class="cnc-label"><i class="fas fa-location-crosshairs"></i> نطاق الاستشارة</span>
                    <div class="cnc-scope-row">
                        <label class="cnc-scope-card" for="cnc-scope-internal">
                            <i class="fas fa-hospital"></i>
                            <span><b>استشارة داخلية</b><small>داخل العيادة</small></span>
                        </label>
                        <label class="cnc-scope-card" for="cnc-scope-external">
                            <i class="fas fa-earth-africa"></i>
                            <span><b>استشارة خارجية</b><small>كل أطباء MedChifaGiz</small></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- القسم 2: اختيار الطبيب -->
            <div class="cnc-card">
                <div class="cnc-card-title"><i class="fas fa-user-doctor"></i> اختيار الطبيب</div>
                <div class="cnc-doc-internal">
    <select id="consultationDoctor" class="cnc-input">
        <option value="">اختر الطبيب</option>
    </select>
</div>
                <div class="cnc-doc-external">
                    <div class="cnc-ext-search-wrap">
                        <i class="fas fa-magnifying-glass cnc-ext-search-icon"></i>
                        <input type="text" id="cncExtDoctorSearch" class="cnc-ext-search-input" placeholder="ابحث بالاسم، التخصص، أو الولاية..." autocomplete="off">
                    </div>
                    <input type="hidden" id="cncExtSelectedDoctorId" value="">
                    <div id="cncExtDoctorResults" class="cnc-ext-doc-results">
                        <div class="cnc-ext-doc-hint"><i class="fas fa-circle-info"></i> ابدأ بكتابة اسم الطبيب أو التخصص أو الولاية.</div>
                    </div>
                </div>
            </div>

            <style>
                /* ══ نطاق مخصص لبطاقات البحث الحي عن الأطباء (استشارة خارجية) فقط ══ */
                .cnc-ext-search-wrap{position:relative;display:flex;align-items:center;}
                .cnc-ext-search-icon{position:absolute;right:14px;color:#0ea5e9;font-size:0.9rem;pointer-events:none;}
                .cnc-ext-search-input{width:100%;box-sizing:border-box;padding:11px 40px 11px 14px;border:1.5px solid rgba(14,165,233,.28);border-radius:12px;background:#f0f9ff;color:#0f172a;font-family:'Cairo',sans-serif;font-size:0.92rem;outline:none;transition:border-color .2s, box-shadow .2s;direction:rtl;}
                .cnc-ext-search-input:focus{border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.15);}
                .cnc-ext-doc-results{margin-top:12px;display:flex;flex-direction:column;gap:10px;max-height:320px;overflow-y:auto;}
                .cnc-ext-doc-hint{display:flex;align-items:center;gap:8px;color:#64748b;font-size:0.85rem;background:#f8fafc;border:1px dashed rgba(100,116,139,.3);border-radius:12px;padding:14px;font-family:'Cairo',sans-serif;}
                .cnc-ext-doc-card{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1.5px solid rgba(14,165,233,.18);border-radius:14px;background:#ffffff;cursor:pointer;transition:transform .15s ease, box-shadow .15s ease, border-color .15s ease, background .15s ease;position:relative;}
                .cnc-ext-doc-card:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(14,165,233,.15);border-color:rgba(14,165,233,.4);}
                .cnc-ext-doc-card.cnc-ext-doc-selected{border-color:#0ea5e9;background:#e0f2fe;box-shadow:0 4px 14px rgba(14,165,233,.25);}
                .cnc-ext-doc-avatar{width:46px;height:46px;border-radius:50%;flex-shrink:0;object-fit:cover;background:#dbeafe;display:flex;align-items:center;justify-content:center;color:#0ea5e9;font-size:1.2rem;overflow:hidden;}
                .cnc-ext-doc-avatar img{width:100%;height:100%;object-fit:cover;}
                .cnc-ext-doc-info{display:flex;flex-direction:column;gap:2px;min-width:0;}
                .cnc-ext-doc-name{font-weight:700;color:#0f172a;font-size:0.92rem;font-family:'Cairo',sans-serif;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
                .cnc-ext-doc-meta{font-size:0.78rem;color:#475569;display:flex;flex-wrap:wrap;gap:4px 10px;font-family:'Cairo',sans-serif;}
                .cnc-ext-doc-meta span{display:inline-flex;align-items:center;gap:4px;}
                .cnc-ext-doc-meta i{color:#0ea5e9;font-size:0.72rem;}
                .cnc-ext-doc-check{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:22px;height:22px;border-radius:50%;background:#0ea5e9;color:#fff;display:none;align-items:center;justify-content:center;font-size:0.75rem;}
                .cnc-ext-doc-card.cnc-ext-doc-selected .cnc-ext-doc-check{display:flex;animation:cncExtPop .18s ease;}
                @keyframes cncExtPop{from{transform:translateY(-50%) scale(.5);opacity:0;}to{transform:translateY(-50%) scale(1);opacity:1;}}
                .cnc-ext-doc-empty{color:#94a3b8;font-size:0.85rem;text-align:center;padding:16px;font-family:'Cairo',sans-serif;}
            </style>

            <!-- القسم 3: المريض -->
            <div class="cnc-card">
                <div class="cnc-card-title"><i class="fas fa-user-injured"></i> المريض</div>
                <select id="consultationPatient" class="cnc-input">
    <option value="">اختر المريض</option>
</select>
            </div>

            <!-- القسم 4: موضوع الاستشارة -->
            <div class="cnc-card">
                <div class="cnc-card-title"><i class="fas fa-tag"></i> موضوع الاستشارة</div>
               <input
    id="consultationTitle"
    class="cnc-input"
    type="text"
    placeholder="مثال: تقييم حالة عصبية — مريض بالطابق 2">
            </div>

            <!-- القسم 5: تفاصيل الحالة -->
            <div class="cnc-card">
                <div class="cnc-card-title"><i class="fas fa-align-left"></i> تفاصيل الحالة</div>
               <textarea
    id="consultationDescription"
    class="cnc-textarea"
    placeholder="اشرح الحالة الطبية بالتفصيل..."></textarea>
            </div>

            <!-- القسم 6: المرفقات -->
            <div class="cnc-card">
                <div class="cnc-card-title"><i class="fas fa-paperclip"></i> المرفقات</div>
                <button type="button" id="cncAttachOpenBtn" class="cnc-attach-btn"><i class="fas fa-folder-open"></i> إرفاق من الملف الطبي</button>
                <div class="cnc-check-list">
                    <label class="cnc-check">
                        <input type="checkbox" class="cnc-attach-type-cb" data-attach-type="radiology"><span class="cnc-box"></span>
                        <i class="fas fa-x-ray cnc-check-ico"></i> الأشعة
                    </label>
                    <label class="cnc-check">
                        <input type="checkbox" class="cnc-attach-type-cb" data-attach-type="lab"><span class="cnc-box"></span>
                        <i class="fas fa-vials cnc-check-ico"></i> التحاليل
                    </label>
                    <label class="cnc-check">
                        <input type="checkbox" class="cnc-attach-type-cb" data-attach-type="report"><span class="cnc-box"></span>
                        <i class="fas fa-file-medical cnc-check-ico"></i> التقرير الطبي
                    </label>
                    <label class="cnc-check">
                        <input type="checkbox" class="cnc-attach-type-cb" data-attach-type="prescription"><span class="cnc-box"></span>
                        <i class="fas fa-prescription cnc-check-ico"></i> الوصفات
                    </label>
                    <label class="cnc-check">
                        <input type="checkbox" class="cnc-attach-type-cb" data-attach-type="images"><span class="cnc-box"></span>
                        <i class="fas fa-image cnc-check-ico"></i> الصور
                    </label>
                </div>

                <input type="hidden" id="cncAttachSelectedIds" value="">
                <div id="cncAttachResults" class="cnc-attach-results">
                    <div class="cnc-attach-hint"><i class="fas fa-circle-info"></i> اختر المريض أولاً، ثم حدّد نوع المرفقات لعرضها من الملف الطبي.</div>
                </div>
            </div>

            <style>
                /* ══ نطاق مخصص لعرض مرفقات الملف الطبي داخل قسم "المرفقات" فقط ══ */
                .cnc-attach-results{margin-top:14px;display:flex;flex-direction:column;gap:10px;max-height:340px;overflow-y:auto;}
                .cnc-attach-hint,.cnc-attach-empty{display:flex;align-items:center;gap:8px;color:#64748b;font-size:0.85rem;background:#f8fafc;border:1px dashed rgba(100,116,139,.3);border-radius:12px;padding:14px;font-family:'Cairo',sans-serif;}
                .cnc-attach-empty{color:#94a3b8;justify-content:center;}
                .cnc-attach-card{display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:1.5px solid rgba(14,165,233,.18);border-radius:14px;background:#ffffff;transition:transform .15s ease, box-shadow .15s ease, border-color .15s ease, background .15s ease;position:relative;}
                .cnc-attach-card:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(14,165,233,.13);border-color:rgba(14,165,233,.35);}
                .cnc-attach-card.cnc-attach-selected{border-color:#0ea5e9;background:#e0f2fe;}
                .cnc-attach-icon{width:42px;height:42px;border-radius:12px;flex-shrink:0;background:#dbeafe;color:#0ea5e9;display:flex;align-items:center;justify-content:center;font-size:1.05rem;}
                .cnc-attach-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px;}
                .cnc-attach-title{font-weight:700;color:#0f172a;font-size:0.9rem;font-family:'Cairo',sans-serif;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
                .cnc-attach-meta{font-size:0.76rem;color:#475569;display:flex;flex-wrap:wrap;gap:4px 10px;font-family:'Cairo',sans-serif;}
                .cnc-attach-meta span{display:inline-flex;align-items:center;gap:4px;}
                .cnc-attach-meta i{color:#0ea5e9;font-size:0.7rem;}
                .cnc-attach-preview-box{display:none;margin-top:6px;background:#f0f9ff;border:1px solid rgba(14,165,233,.2);border-radius:10px;padding:10px 12px;font-size:0.8rem;color:#1e293b;font-family:'Cairo',sans-serif;white-space:pre-wrap;line-height:1.6;max-height:160px;overflow-y:auto;}
                .cnc-attach-preview-box.cnc-open{display:block;}
                .cnc-attach-actions{display:flex;align-items:center;gap:10px;margin-top:2px;}
                .cnc-attach-preview-btn{border:none;background:rgba(14,165,233,.12);color:#0ea5e9;font-family:'Cairo',sans-serif;font-size:0.76rem;font-weight:700;padding:5px 12px;border-radius:20px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:background .15s ease;}
                .cnc-attach-preview-btn:hover{background:rgba(14,165,233,.22);}
                .cnc-attach-pick{position:absolute;left:12px;top:12px;width:20px;height:20px;cursor:pointer;accent-color:#0ea5e9;}
            </style>


            <!-- القسم 7: الخصوصية -->
            <div class="cnc-card cnc-privacy">
                <div class="cnc-card-title"><i class="fas fa-user-shield"></i> الخصوصية</div>
                <label class="cnc-privacy-row">
                    <input type="checkbox"><span class="cnc-box"></span>
                    <span class="cnc-privacy-txt">
                        <b>إخفاء بيانات المريض</b>
                        <small>سيتم إخفاء اسم المريض ورقم الملف والبيانات الشخصية عند إرسال الاستشارة.</small>
                    </span>
                </label>
            </div>

            <!-- القسم 8: الأولوية -->
            <div class="cnc-card">
                <div class="cnc-card-title"><i class="fas fa-triangle-exclamation"></i> الأولوية</div>
                <div class="cnc-prio-row">
<input class="cnc-prio-input" type="radio" name="cnc-priority" id="cnc-prio-normal" value="normal" checked>
                    <label class="cnc-prio-card normal" for="cnc-prio-normal">
                        <span class="cnc-prio-dot"></span> عادية <small>ضمن الجدول العادي</small>
                    </label>
                    <input class="cnc-prio-input" type="radio" name="cnc-priority" id="cnc-prio-medium" value="medium">
                    <label class="cnc-prio-card medium" for="cnc-prio-medium">
                        <span class="cnc-prio-dot"></span> مستعجلة <small>خلال ساعات</small>
                    </label>
                    <input class="cnc-prio-input" type="radio" name="cnc-priority" id="cnc-prio-urgent" value="urgent">
                    <label class="cnc-prio-card urgent" for="cnc-prio-urgent">
                        <span class="cnc-prio-dot"></span> عاجلة جداً <small>فوري</small>
                    </label>
                </div>
            </div>

            <!-- القسم 9: الأطباء المشاركون -->
            <div class="cnc-card">
                <div class="cnc-card-title"><i class="fas fa-user-group"></i> الأطباء المشاركون</div>
                <p class="cnc-colab-subtitle">اختياري — يمكن إضافة طبيب واحد أو أكثر للمشاركة في نفس الاستشارة.</p>

                <div class="cnc-ext-search-wrap">
                    <i class="fas fa-magnifying-glass cnc-ext-search-icon"></i>
                    <input type="text" id="cncColabDoctorSearch" class="cnc-ext-search-input" placeholder="ابحث بالاسم، التخصص، أو الولاية..." autocomplete="off">
                </div>
                <div id="cncColabDoctorResults" class="cnc-ext-doc-results">
                    <div class="cnc-ext-doc-hint"><i class="fas fa-circle-info"></i> ابدأ بكتابة اسم الطبيب أو التخصص أو الولاية لإضافته كمشارك.</div>
                </div>

                <input type="hidden" id="cncColabSelectedIds" value="">
                <div id="cncColabChips" class="cnc-colab-chips"></div>
            </div>

            <style>
                /* ══ نطاق مخصص لقسم "الأطباء المشاركون" فقط — بطاقات (Chips) والحالات الخاصة بنتائج البحث هنا ══ */
                .cnc-colab-subtitle{margin:0 0 14px;color:#64748b;font-size:0.82rem;font-family:'Cairo',sans-serif;}
                .cnc-colab-chips{margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;}
                .cnc-colab-chip{display:inline-flex;align-items:center;gap:8px;background:#e0f2fe;border:1.5px solid rgba(14,165,233,.35);color:#0f172a;font-family:'Cairo',sans-serif;font-size:0.85rem;font-weight:700;padding:6px 8px 6px 14px;border-radius:999px;}
                .cnc-colab-chip i.fa-user-doctor{color:#0ea5e9;font-size:0.8rem;}
                .cnc-colab-chip-name{white-space:nowrap;}
                .cnc-colab-chip-remove{border:none;background:rgba(14,165,233,.18);color:#0ea5e9;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:0.7rem;transition:background .15s ease;}
                .cnc-colab-chip-remove:hover{background:#0ea5e9;color:#fff;}
                .cnc-colab-doc-disabled{opacity:.5;cursor:not-allowed;}
                .cnc-colab-doc-badge{font-size:0.72rem;font-weight:700;color:#64748b;background:#f1f5f9;padding:4px 10px;border-radius:20px;font-family:'Cairo',sans-serif;white-space:nowrap;flex-shrink:0;}
                .cnc-colab-doc-badge-added{color:#0ea5e9;background:#e0f2fe;}
            </style>

        </div>

        <!-- القسم 10: الأزرار -->
        <div class="cnc-foot">
            <label for="cncOpen" class="cnc-btn-cancel">إلغاء</label>
<button
    type="button"
    class="cnc-btn-create"
    id="createConsultationBtn">
    <i class="fas fa-paper-plane"></i>
    إنشاء الاستشارة
</button>        </div>

    </div>
</div>

</body>
</html>
