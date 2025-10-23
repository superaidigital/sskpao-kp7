<?php
session_start();
require '../db_config.php'; // เรียกใช้ไฟล์เชื่อมต่อ DB

// ตรวจสอบว่ามีข้อมูลส่งมาหรือไม่
if (!isset($_POST['username'], $_POST['password'])) {
    header('Location: login.php?error=1');
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];

// เตรียม SQL เพื่อป้องกัน SQL Injection
$sql = "SELECT id, password FROM admin_users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // ตรวจสอบรหัสผ่านที่ Hashed
    if (password_verify($password, $user['password'])) {
        // รหัสผ่านถูกต้อง!
        session_regenerate_id(); // ป้องกัน Session Fixation
        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_id'] = $user['id'];
        
        // ไปยังหน้า Dashboard
        header('Location: index.php');
        exit;
        
    } else {
        // รหัสผ่านผิด
        header('Location: login.php?error=1');
        exit;
    }
} else {
    // ไม่พบ User
    header('Location: login.php?error=1');
    exit;
}

$stmt->close();
$conn->close();
?>
