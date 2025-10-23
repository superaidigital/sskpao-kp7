<?php
session_start();
// ตรวจสอบว่าล็อกอินหรือยัง
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// ตั้งค่าตัวแปร $pageTitle (ให้กำหนดก่อน include ไฟล์นี้)
$pageTitle = isset($pageTitle) ? $pageTitle : "Dashboard";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - ระบบจัดการคำร้อง</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-blue-600">ระบบจัดการคำร้อง ก.พ.7</span>
                    <a href="index.php" class="ml-6 text-gray-700 hover:text-blue-600 font-medium">รายการคำร้อง</a>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">
                        สวัสดี, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                    <a href="logout.php" 
                       class="py-2 px-4 bg-red-500 hover:bg-red-600 text-white font-medium rounded-md transition duration-200">
                        ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg border border-gray-200">
