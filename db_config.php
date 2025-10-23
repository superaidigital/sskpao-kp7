<?php
/*
 * -----------------------------------------------------------------
 * ไฟล์ตั้งค่าการเชื่อมต่อฐานข้อมูล (Database Configuration)
 * -----------------------------------------------------------------
 * กรุณาแก้ไขค่าด้านล่างให้ตรงกับข้อมูล
 * ฐานข้อมูล MySQL ของคุณ
 */

define('DB_HOST', 'localhost');      // เช่น "localhost" หรือ IP ของเซิร์ฟเวอร์
define('DB_USERNAME', 'root'); // ชื่อผู้ใช้ฐานข้อมูล
define('DB_PASSWORD', ''); // รหัสผ่าน
define('DB_NAME', 'sskpao_kp7');   // ชื่อฐานข้อมูล

/*
 * สร้างการเชื่อมต่อ
 * ใช้ mysqli (MySQL Improved)
 */
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// ตั้งค่า character set เป็น utf8mb4 เพื่อรองรับภาษาไทย
if (!$conn->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
    exit();
}

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    // หากเชื่อมต่อไม่ได้ ให้หยุดการทำงานและแสดงข้อผิดพลาด
    // (ในระบบจริง ไม่ควรแสดง error นี้ให้ผู้ใช้เห็น)
    die("Connection failed: " . $conn->connect_error);
}

?>
