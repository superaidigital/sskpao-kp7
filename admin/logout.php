<?php
session_start();

// ล้างค่า session ทั้งหมด
session_unset();

// ทำลาย session
session_destroy();

// กลับไปยังหน้าล็อกอิน
header('Location: login.php');
exit;
?>
