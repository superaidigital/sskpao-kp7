<?php
session_start();
require '../db_config.php';

// --- 1. ตรวจสอบการล็อกอินและ Method ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header('Location: index.php'); // ไม่ใช่ POST ให้กลับหน้าหลัก
    exit;
}

// --- 2. รับและตรวจสอบข้อมูลที่จำเป็น ---
if (!isset($_POST['id']) || !isset($_POST['citizen_id']) || !isset($_FILES['pdf_file'])) {
    header('Location: view.php?id=' . (int)$_POST['id'] . '&upload_error=' . urlencode('ข้อมูลไม่ครบถ้วน'));
    exit;
}

$id = (int)$_POST['id'];
$citizen_id = trim($_POST['citizen_id']);
$file = $_FILES['pdf_file'];

// --- 3. ตรวจสอบความถูกต้องของเลขบัตร 13 หลัก ---
function isValidCitizenID($id) {
    if (strlen($id) != 13) return false;
    if (!ctype_digit($id)) return false; // ตรวจสอบว่าเป็นตัวเลขเท่านั้น
    
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int)$id[$i] * (13 - $i);
    }
    $check_digit = (11 - ($sum % 11)) % 10;
    
    return ((int)$id[12] === $check_digit);
}

if (!isValidCitizenID($citizen_id)) {
    header('Location: view.php?id=' . $id . '&upload_error=' . urlencode('เลขบัตรประชาชน 13 หลักไม่ถูกต้อง'));
    exit;
}

// --- 4. ตรวจสอบไฟล์ที่อัปโหลด ---
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

// --- 5. เตรียมการบันทึกไฟล์ ---
$upload_dir = '../uploads/'; // โฟลเดอร์ที่เราสร้างไว้ (อยู่นอก admin)
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true); // พยายามสร้างถ้ายังไม่มี
}

// สร้างชื่อไฟล์ใหม่ เพื่อป้องกันการซ้ำ และเพื่อความปลอดภัย
// เช่น request_1_timestamp.pdf
$new_filename = 'request_' . $id . '_' . time() . '.pdf';
$destination = $upload_dir . $new_filename;

// ---------------------------------------------
// !!! ส่วนสำหรับการเข้ารหัสไฟล์ (ต้องใช้ Library) !!!
// ---------------------------------------------
//
// โค้ดด้านล่างนี้เป็น "ตัวอย่าง" หากคุณติดตั้ง Library เช่น qpdf
// คุณต้องติดตั้ง qpdf บนเซิร์ฟเวอร์ก่อน
//
// $tmp_file = $file['tmp_name'];
// $password = $citizen_id;
//
// $command = "qpdf --encrypt {$password} {$password} 256 -- {$tmp_file} {$destination}";
// shell_exec($command);
//
// $file_moved = file_exists($destination);
//
// ---------------------------------------------
// โค้ดปัจจุบัน (สำหรับย้ายไฟล์โดยยังไม่เข้ารหัส)
// ---------------------------------------------

$file_moved = move_uploaded_file($file['tmp_name'], $destination);


// --- 6. ตรวจสอบการย้ายไฟล์และอัปเดตฐานข้อมูล ---
if ($file_moved) {
    // ย้ายไฟล์สำเร็จ อัปเดตฐานข้อมูล
    $sql = "UPDATE form_submissions SET uploaded_file = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_filename, $id);
    
    if ($stmt->execute()) {
        // อัปเดตสำเร็จ
        header('Location: view.php?id=' . $id . '&upload_success=1');
    } else {
        // ย้ายไฟล์สำเร็จ แต่ อัปเดต DB ไม่สำเร็จ (ต้องลบไฟล์ทิ้ง)
        unlink($destination); // ลบไฟล์ที่เพิ่งอัปโหลด
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

