<?php
session_start();
require '../db_config.php';
// *** เพิ่มการเรียกใช้ Composer Autoloader ***
// ตรวจสอบให้แน่ใจว่า path ถูกต้อง (ถ้า vendor อยู่ที่เดียวกับ db_config.php)
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
} else {
    // กรณีฉุกเฉิน ถ้าหา autoload ไม่เจอ (ควรแจ้งเตือน)
    header('Location: view.php?id=' . (int)$_POST['id'] . '&upload_error=' . urlencode('ไม่พบ TCPDF Library (vendor/autoload.php)'));
    exit;
}


// --- 1. ตรวจสอบการล็อกอินและ Method ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header('Location: index.php'); // ไม่ใช่ POST ให้กลับหน้าหลัก
    exit;
}

// --- 2. รับและตรวจสอบข้อมูลที่จำเป็น (ลบ citizen_id ออก) ---
if (!isset($_POST['id']) || !isset($_FILES['pdf_file'])) {
    header('Location: view.php?id=' . (int)$_POST['id'] . '&upload_error=' . urlencode('ข้อมูลไม่ครบถ้วน'));
    exit;
}

$id = (int)$_POST['id'];
// $citizen_id = trim($_POST['citizen_id']); // ลบออก
$file = $_FILES['pdf_file'];

// --- 3. (ลบส่วนตรวจสอบเลขบัตร 13 หลัก) ---
// function isValidCitizenID($id) { ... } // ลบฟังก์ชันนี้ทิ้ง
// if (!isValidCitizenID($citizen_id)) { ... } // ลบการตรวจสอบนี้ทิ้ง

// --- 4. ตรวจสอบไฟล์ที่อัปโหลด (เหมือนเดิม) ---
if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: view.php?id=' . $id . '&upload_error=' . urlencode('เกิดข้อผิดพลาดในการอัปโหลดไฟล์'));
    exit;
}

// ตรวจสอบ Mime Type (ต้องเป็น PDF เท่านั้น)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mime_type != 'application/pdf') {
    header('Location: view.php?id=' . $id . '&upload_error=' . urlencode('ไฟล์ต้องเป็น .pdf เท่านั้น'));
    exit;
}

// --- (ใหม่) ดึงเบอร์โทรศัพท์มาใช้เป็นรหัสผ่าน ---
$sql_phone = "SELECT phone FROM form_submissions WHERE id = ?";
$stmt_phone = $conn->prepare($sql_phone);
$stmt_phone->bind_param("i", $id);
$stmt_phone->execute();
$result_phone = $stmt_phone->get_result();

if ($result_phone->num_rows == 0) {
    header('Location: view.php?id=' . $id . '&upload_error=' . urlencode('ไม่พบข้อมูลคำร้อง (ID)'));
    $conn->close();
    exit;
}
$row_phone = $result_phone->fetch_assoc();
$password = $row_phone['phone']; // <--- ใช้เบอร์โทรเป็นรหัสผ่าน
$stmt_phone->close();

if (empty($password) || !ctype_digit($password)) {
     header('Location: view.php?id=' . $id . '&upload_error=' . urlencode('ไม่พ
บเบอร์โทรศัพท์ของผู้ยื่น หรือเบอร์โทรไม่ถูกต้อง'));
    $conn->close();
    exit;
}
// --- จบส่วนดึงเบอร์โทร ---


// --- 5. เตรียมการบันทึกไฟล์ (เหมือนเดิม) ---
$upload_dir = '../uploads/'; // โฟลเดอร์ที่เราสร้างไว้ (อยู่นอก admin)
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true); // พยายามสร้างถ้ายังไม่มี
}

// สร้างชื่อไฟล์ใหม่ (เหมือนเดิม)
$new_filename = 'request_' . $id . '_' . time() . '.pdf';
$destination = $upload_dir . $new_filename;

// ---------------------------------------------
// !!! ส่วนสำหรับการเข้ารหัสไฟล์ (TCPDF) !!!
// ---------------------------------------------
        
$tmp_file = $file['tmp_name'];
// $password = $citizen_id; // (ถูกกำหนดไว้ด้านบนแล้ว)
$file_moved = false; // ตั้งค่าเริ่มต้น

try {
    // 1. สร้างอ็อบเจกต์ TCPDF (เหมือนเดิม)
    $pdf = new \TCPDF();

    // 2. อ่านไฟล์ PDF ที่อัปโหลดเข้ามา (เหมือนเดิม)
    $page_count = $pdf->setSourceFile($tmp_file);

    // 3. ตั้งค่ารหัสผ่าน (ใช้ $password ที่เป็นเบอร์โทร)
    $pdf->SetProtection(
        permissions: [], 
        user_pass: $password, // <-- ใช้เบอร์โทรที่ดึงมา
        owner_pass: null, 
        mode: 1, 
        pubkeys: null
    );

    // 4. วนลูปเพื่อ import ทุกหน้า (เหมือนเดิม)
    for ($i = 1; $i <= $page_count; $i++) {
        $template_id = $pdf->importPage($i);
        $pdf->AddPage(); 
        $pdf->useTemplate($template_id); 
    }

    // 5. บันทึกเป็นไฟล์ใหม่ (เหมือนเดิม)
    $pdf->Output($destination, 'F'); 
    
    $file_moved = file_exists($destination);

} catch (Exception $e) {
    // (เหมือนเดิม)
    header('Location: view.php?id=' . $id . '&upload_error=' . urlencode('TCPDF Error: ' . $e->getMessage()));
    $conn->close();
    exit;
}

// ---------------------------------------------
// จบส่วน TCPDF
// ---------------------------------------------


// --- 6. ตรวจสอบการย้ายไฟล์และอัปเดตฐานข้อมูล (เหมือนเดิม) ---
if ($file_moved) {
    // ย้ายไฟล์สำเร็จ อัปเดตฐานข้อมูล
    
    // อัปเดต 2 field: uploaded_file และ status
    $sql = "UPDATE form_submissions SET uploaded_file = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $new_status = 'completed'; // เปลี่ยนสถานะเป็น 'completed' อัตโนมัติ
    $stmt->bind_param("ssi", $new_filename, $new_status, $id);
    
    if ($stmt->execute()) {
        // อัปเดตสำเร็จ
        header('Location: view.php?id=' . $id . '&upload_success=1');
    } else {
        // ย้ายไฟล์สำเร็จ แต่ อัปเดต DB ไม่สำเร็จ
        unlink($destination); 
        header('Location: view.php?id=' . $id . '&upload_error=' . urlencode('อัปเดตฐานข้อมูลไม่สำเร็จ'));
    }
    $stmt->close();
    
} else {
    // ย้ายไฟล์ไม่สำเร็จ
    header('Location: view.php?id=' . $id . '&upload_error=' . urlencode('ไม่สามารถบันทึกไฟล์ลงเซิร์ฟเวอร์ได้'));
}

$conn->close();
exit;

?>

