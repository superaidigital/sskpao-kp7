<?php
// -----------------------------------------------------------------
// สคริปต์สำหรับรับข้อมูลและบันทึกลงฐานข้อมูล
// -----------------------------------------------------------------

// ตั้งค่า header ให้ตอบกลับเป็น JSON
header('Content-Type: application/json; charset=utf-8');

// นำเข้าไฟล์ตั้งค่าฐานข้อมูล
require 'db_config.php';

// --- 1. รับข้อมูลจาก Form (ผ่าน $_POST) ---
// เราจะตรวจสอบและรับค่าทีละตัว

$prefix = isset($_POST['prefix']) ? $_POST['prefix'] : '';
$other_prefix = isset($_POST['otherPrefix']) ? $_POST['otherPrefix'] : null;
$first_name = isset($_POST['firstName']) ? $_POST['firstName'] : '';
$last_name = isset($_POST['lastName']) ? $_POST['lastName'] : '';
$position = isset($_POST['position']) ? $_POST['position'] : null;
$department = isset($_POST['department']) ? $_POST['department'] : null;
$phone = isset($_POST['phone']) ? $_POST['phone'] : '';
$reason = isset($_POST['reason']) ? $_POST['reason'] : '';
$delivery_method = isset($_POST['deliveryMethod']) ? $_POST['deliveryMethod'] : '';
$address = isset($_POST['address']) ? $_POST['address'] : null;
$email = isset($_POST['emailInput']) ? $_POST['emailInput'] : null;
$pdpa_consent = isset($_POST['pdpa']) && $_POST['pdpa'] === 'on' ? 1 : 0;


// --- 2. ตรวจสอบข้อมูลเบื้องต้น (Server-side validation) ---
// (ควรตรวจสอบให้รัดกุมกว่านี้ในระบบจริง)
if (empty($first_name) || empty($last_name) || empty($phone) || empty($reason) || empty($delivery_method) || $pdpa_consent == 0) {
    // ส่งข้อความผิดพลาดกลับไป
    echo json_encode([
        'status' => 'error',
        'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน'
    ]);
    exit;
}

// ถ้าเลือก "อื่น ๆ" แต่ไม่กรอก
if ($prefix === 'อื่น ๆ' && empty($other_prefix)) {
    $other_prefix = null; // หรือจะส่ง error กลับไปก็ได้
}

// ถ้าเลือก "ไปรษณีย์" แต่ไม่กรอกที่อยู่
if ($delivery_method === 'mail' && empty($address)) {
     $address = null; // หรือจะส่ง error กลับไป
}

// ถ้าเลือก "อีเมล" แต่ไม่กรอกอีเมล
if ($delivery_method === 'email' && empty($email)) {
     $email = null; // หรือจะส่ง error กลับไป
}


// --- 3. เตรียมคำสั่ง SQL (ใช้ Prepared Statements เพื่อป้องกัน SQL Injection) ---
$sql = "INSERT INTO form_submissions 
        (prefix, other_prefix, first_name, last_name, position, department, phone, reason, delivery_method, address, email, pdpa_consent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // หากเตรียมคำสั่ง SQL ไม่สำเร็จ
    echo json_encode([
        'status' => 'error',
        'message' => 'Server Error (SQL Prepare Failed): ' . $conn->error
    ]);
    exit;
}

// --- 4. ผูกตัวแปร (Bind Parameters) ---
// s = string, i = integer, d = double, b = blob
$stmt->bind_param("sssssssssssi", 
    $prefix,
    $other_prefix,
    $first_name,
    $last_name,
    $position,
    $department,
    $phone,
    $reason,
    $delivery_method,
    $address,
    $email,
    $pdpa_consent
);

// --- 5. สั่งทำงาน (Execute) ---
if ($stmt->execute()) {
    // ถ้าสำเร็จ
    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลคำร้องสำเร็จ'
    ]);
} else {
    // ถ้าไม่สำเร็จ
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $stmt->error
    ]);
}

// --- 6. ปิดการเชื่อมต่อ ---
$stmt->close();
$conn->close();

?>
