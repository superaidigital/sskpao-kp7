<?php
session_start();
// ถ้าล็อกอินแล้ว ให้เด้งไปหน้า index
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ระบบจัดการคำร้อง</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Kanit', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="w-full max-w-sm p-8 bg-white rounded-xl shadow-lg border border-gray-200">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">ระบบจัดการคำร้อง</h1>
        <p class="text-center text-gray-500 mb-6">สำหรับเจ้าหน้าที่ อบจ.ศรีสะเกษ</p>

        <?php
        // แสดงข้อความ error ถ้าล็อกอินผิด
        if (isset($_GET['error'])) {
            echo '<p class="bg-red-100 text-red-700 p-3 rounded-md text-center mb-4">ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง</p>';
        }
        ?>

        <form action="auth.php" method="POST">
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้ (Username)</label>
                <input type="text" id="username" name="username" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน (Password)</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit"
                    class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                เข้าสู่ระบบ
            </button>
        </form>
    </div>

</body>
</html>
