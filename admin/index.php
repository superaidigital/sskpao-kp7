<?php
$pageTitle = "รายการคำร้องทั้งหมด"; // กำหนด Title
include 'header.php'; // เรียก Header
require '../db_config.php'; // เรียก DB

// --- 1. ดึงข้อมูลสถิติ (ส่วนนี้ยังคงเหมือนเดิม คือนับเฉพาะที่ยังไม่เสร็จ) ---
$total_pending = 0;
$total_processing = 0;
$total_rejected = 0;

// Query สำหรับนับสถานะที่ยังไม่เสร็จสิ้น
$sql_stats = "SELECT status, COUNT(*) as count FROM form_submissions WHERE status != 'completed' GROUP BY status";
$result_stats = $conn->query($sql_stats);

if ($result_stats && $result_stats->num_rows > 0) {
    while($row_stat = $result_stats->fetch_assoc()) {
        if ($row_stat['status'] == 'pending') {
            $total_pending = $row_stat['count'];
        } elseif ($row_stat['status'] == 'processing') {
            $total_processing = $row_stat['count'];
        } elseif ($row_stat['status'] == 'rejected') {
            $total_rejected = $row_stat['count'];
        }
    }
}

// Query สำหรับนับคำร้องวันนี้ (ทุกสถานะ)
$sql_today = "SELECT COUNT(*) as total_today FROM form_submissions WHERE DATE(submitted_at) = CURDATE()";
$result_today = $conn->query($sql_today);
$total_today = ($result_today && $result_today->num_rows > 0) ? $result_today->fetch_assoc()['total_today'] : 0;


// --- 2. ดึงข้อมูลสำหรับตาราง (ปรับปรุงใหม่) ---

// รับค่า GET parameters สำหรับการกรอง
$current_view = isset($_GET['view']) && $_GET['view'] == 'completed' ? 'completed' : 'pending';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// สร้าง SQL พื้นฐาน (เพิ่ม delivery_method)
$sql = "SELECT id, first_name, last_name, submitted_at, status, delivery_method FROM form_submissions";
$where_clauses = [];
$params = [];
$types = "";

// 1. เพิ่มเงื่อนไขสำหรับ view (pending หรือ completed)
if ($current_view == 'completed') {
    $where_clauses[] = "status = 'completed'";
} else {
    // 'pending' view includes pending, processing, rejected
    $where_clauses[] = "status != 'completed'";
}

// 2. เพิ่มเงื่อนไขสำหรับการค้นหา (ถ้ามี)
if (!empty($search_term)) {
    // ค้นหาจาก id, first_name, หรือ last_name
    $where_clauses[] = "(id = ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_like = "%" . $search_term . "%";
    
    // เราใช้ sss (string) 3 ตัวสำหรับ id, first_name, last_name
    // เพื่อความง่ายในการ bind_param แม้ว่า id จะเป็น integer
    $params[] = $search_term; 
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "sss";
}

// รวม WHERE clauses
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// เพิ่ม ORDER BY
$sql .= " ORDER BY submitted_at DESC";

// ใช้ Prepared Statement เพื่อความปลอดภัย
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("SQL Prepare Failed: " . $conn->error);
}

// Bind parameters ถ้ามีการค้นหา
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!-- Stat Cards (เหมือนเดิม) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    
    <!-- Card 1: รอดำเนินการ -->
    <div class="bg-yellow-100 border-l-4 border-yellow-500 p-6 rounded-lg shadow-md">
        <!-- ... (โค้ดการ์ดเหมือนเดิม) ... -->
        <div class="flex justify-between items-center">
            <div>
                <div class="text-sm font-medium text-yellow-700 uppercase">รอดำเนินการ</div>
                <div class="text-3xl font-bold text-yellow-900"><?php echo $total_pending; ?></div>
            </div>
            <svg class="w-12 h-12 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </div>
    
    <!-- Card 2: กำลังดำเนินการ -->
    <div class="bg-blue-100 border-l-4 border-blue-500 p-6 rounded-lg shadow-md">
        <!-- ... (โค้ดการ์ดเหมือนเดิม) ... -->
        <div class="flex justify-between items-center">
            <div>
                <div class="text-sm font-medium text-blue-700 uppercase">กำลังดำเนินการ</div>
                <div class="text-3xl font-bold text-blue-900"><?php echo $total_processing; ?></div>
            </div>
            <svg class="w-12 h-12 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m-15.357-2a8.001 8.001 0 0015.357 2m0 0H15" />
            </svg>
        </div>
    </div>

    <!-- Card 3: ปฏิเสธ -->
    <div class="bg-red-100 border-l-4 border-red-500 p-6 rounded-lg shadow-md">
        <!-- ... (โค้ดการ์ดเหมือนเดิม) ... -->
        <div class="flex justify-between items-center">
            <div>
                <div class="text-sm font-medium text-red-700 uppercase">ปฏิเสธ</div>
                <div class="text-3xl font-bold text-red-900"><?php echo $total_rejected; ?></div>
            </div>
            <svg class="w-12 h-12 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
        </div>
    </div>

    <!-- Card 4: คำร้องวันนี้ (ทั้งหมด) -->
    <div class="bg-green-100 border-l-4 border-green-500 p-6 rounded-lg shadow-md">
        <!-- ... (โค้ดการ์ดเหมือนเดิม) ... -->
        <div class="flex justify-between items-center">
            <div>
                <div class="text-sm font-medium text-green-700 uppercase">คำร้องวันนี้ (ทั้งหมด)</div>
                <div class="text-3xl font-bold text-green-900"><?php echo $total_today; ?></div>
            </div>
            <svg class="w-12 h-12 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
    </div>
</div>
<!-- End Stat Cards -->

<!-- ส่วนควบคุมการกรอง และ ค้นหา (เหมือนเดิม) -->
<div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
    <!-- 1. ปุ่มสลับ View -->
    <div class="flex bg-gray-200 rounded-lg p-1">
        <?php
            // กำหนด CSS ของปุ่มที่ถูกเลือก
            $pending_class = ($current_view == 'pending') 
                ? 'bg-white text-blue-600 shadow-md' 
                : 'text-gray-700 hover:bg-gray-100';
            $completed_class = ($current_view == 'completed') 
                ? 'bg-white text-blue-600 shadow-md' 
                : 'text-gray-700 hover:bg-gray-100';
        ?>
        <a href="?view=pending&search=<?php echo htmlspecialchars($search_term); ?>" 
           class="py-2 px-5 rounded-lg font-medium transition-all duration-200 <?php echo $pending_class; ?>">
           ที่ต้องดำเนินการ
        </a>
        <a href="?view=completed&search=<?php echo htmlspecialchars($search_term); ?>" 
           class="py-2 px-5 rounded-lg font-medium transition-all duration-200 <?php echo $completed_class; ?>">
           จัดการแล้ว
        </a>
    </div>

    <!-- 2. ช่องค้นหา -->
    <form method="GET" action="index.php" class="flex items-center">
        <!-- ส่ง view ปัจจุบันไปด้วยตอนค้นหา -->
        <input type="hidden" name="view" value="<?php echo htmlspecialchars($current_view); ?>">
        
        <input type="text" name="search" 
               value="<?php echo htmlspecialchars($search_term); ?>"
               placeholder="ค้นหา (รหัส, ชื่อ, นามสกุล)..."
               class="px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        
        <button type="submit" 
                class="py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-r-md border border-blue-600 transition duration-200">
            ค้นหา
        </button>
    </form>
</div>
<!-- End ส่วนควบคุม -->


<!-- ตารางแสดงผล -->
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">รหัส</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ชื่อ - นามสกุล</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">วันที่ยื่น</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ช่องทาง</th> <!-- เพิ่ม Header -->
                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">สถานะ</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">จัดการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // --- 1. กำหนดสีของสถานะ (เหมือนเดิม) ---
                    $status_color = 'bg-gray-200 text-gray-800'; // Default
                    if ($row['status'] == 'pending') {
                        $status_color = 'bg-yellow-200 text-yellow-800';
                    } elseif ($row['status'] == 'processing') {
                        $status_color = 'bg-blue-200 text-blue-800';
                    } elseif ($row['status'] == 'completed') {
                        $status_color = 'bg-green-200 text-green-800';
                    } elseif ($row['status'] == 'rejected') {
                        $status_color = 'bg-red-200 text-red-800';
                    }
                    
                    // --- 2. แปลงวันที่เป็น พ.ศ. ---
                    $timestamp = strtotime($row['submitted_at']);
                    $thai_year = (int)date('Y', $timestamp) + 543;
                    $thai_date = date('d/m/', $timestamp) . $thai_year . date(' H:i', $timestamp);
                    
                    // --- 3. แปลงช่องทางเป็นภาษาไทย ---
                    $delivery_text = 'ไม่ระบุ'; // Default
                    if ($row['delivery_method'] == 'pickup') {
                        $delivery_text = 'รับเอง';
                    } elseif ($row['delivery_method'] == 'mail') {
                        $delivery_text = 'ไปรษณีย์';
                    } elseif ($row['delivery_method'] == 'email') {
                        $delivery_text = 'อีเมล';
                    }

                    echo "<tr>";
                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . $row['id'] . "</td>";
                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . $thai_date . "</td>"; // ใช้วันที่ พ.ศ.
                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($delivery_text) . "</td>"; // แสดงช่องทาง
                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm'>";
                    echo "<span class='px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full " . $status_color . "'>" . htmlspecialchars(ucfirst($row['status'])) . "</span>";
                    echo "</td>";
                    echo "<td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>";
                    echo "<a href='view.php?id=" . $row['id'] . "' class='text-blue-600 hover:text-blue-900'>ดูรายละเอียด</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='px-6 py-4 text-center text-gray-500'>ไม่พบข้อมูลคำร้องที่ตรงกับเงื่อนไข</td></tr>"; // อัปเดต colspan
            }
            $stmt->close(); // ปิด prepared statement
            $conn->close();
            ?>
        </tbody>
    </table>
</div>

<?php
include 'footer.php'; // เรียก Footer
?>

