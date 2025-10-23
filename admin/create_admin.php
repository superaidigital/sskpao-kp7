<?php
/*
 * -----------------------------------------------------------------
 * ไฟล์สำหรับสร้าง Admin User (สำคัญมาก)
 * -----------------------------------------------------------------
 * 1. อัปโหลดไฟล์นี้ไปที่โฟลเดอร์ admin/
 * 2. เรียกใช้งานไฟล์นี้ผ่านเบราว์เซอร์ 1 ครั้ง (เช่น yoursite.com/admin/create_admin.php)
 * 3. !!! ลบไฟล์นี้ออกจากเซิร์ฟเวอร์ทันทีหลังใช้งาน !!!
 *
 * (กรุณาเปลี่ยน 'admin' และ 'password123' เป็นของคุณ)
 */

require '../db_config.php'; // เรียกใช้ไฟล์เชื่อมต่อ DB

// --- ตั้งค่าผู้ใช้เริ่มต้น ---
$username = 'admin'; 
$password = 'password123';
// -------------------------

// เข้ารหัสผ่าน (สำคัญมาก)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// เตรียม SQL
$sql = "INSERT INTO admin_users (username, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("SQL Prepare Failed: " . $conn->error);
}

$stmt->bind_param("ss", $username, $hashed_password);

if ($stmt->execute()) {
    echo "<h1>Admin User Created Successfully!</h1>";
    echo "<p>Username: $username</p>";
    echo "<p>Password: $password</p>";
    echo "<h2 style='color:red;'>กรุณาลบไฟล์ create_admin.php นี้ทันที!</h2>";
} else {
    echo "Error creating user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
