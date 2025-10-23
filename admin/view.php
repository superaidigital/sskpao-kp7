<?php
$pageTitle = "รายละเอียดคำร้อง"; // กำหนด Title
include 'header.php'; // เรียก Header
require '../db_config.php'; // เรียก DB

// --- 1. ตรวจสอบ ID ---
if (!isset($_GET['id']) || !(int)$_GET['id']) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-md'>ไม่พบรหัสคำร้อง</div>";
    include 'footer.php';
    exit;
}
$id = (int)$_GET['id'];

// --- 2. ดึงข้อมูลคำร้อง ---
// เราจะดึงข้อมูลเกือบทั้งหมดมาแสดง
$sql = "SELECT * FROM form_submissions WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-md'>ไม่พบข้อมูลคำร้อง ID: $id</div>";
    include 'footer.php';
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

// --- 3. ฟังก์ชันช่วยแปลงข้อมูล ---

// แปลงวันที่เป็น พ.ศ.
function formatThaiDate($datetime) {
    if (!$datetime) return "N/A";
    $timestamp = strtotime($datetime);
    $thai_year = (int)date('Y', $timestamp) + 543;
    return date('d/m/', $timestamp) . $thai_year . date(' H:i น.', $timestamp);
}

// แปลงช่องทางรับ
function formatDelivery($method) {
    if ($method == 'pickup') return 'รับด้วยตนเอง';
    if ($method == 'mail') return 'จัดส่งทางไปรษณีย์';
    if ($method == 'email') return 'รับทางอีเมล (ไฟล์สแกน)';
    return 'ไม่ระบุ';
}

// แปลงคำนำหน้า
function formatPrefix($prefix, $other_prefix) {
    if ($prefix == 'other') {
        return htmlspecialchars($other_prefix);
    }
    return htmlspecialchars($prefix);
}

// กำหนดสีสถานะ
function getStatusColor($status) {
    if ($status == 'pending') return 'bg-yellow-100 text-yellow-800 border-yellow-300';
    if ($status == 'processing') return 'bg-blue-100 text-blue-800 border-blue-300';
    if ($status == 'completed') return 'bg-green-100 text-green-800 border-green-300';
    if ($status == 'rejected') return 'bg-red-100 text-red-800 border-red-300';
    return 'bg-gray-100 text-gray-800 border-gray-300';
}

?>

<!-- ----------------------------------------------------------------- -->
<!-- ส่วน Popup แจ้งเตือนอัปโหลดสำเร็จ (Modal) -->
<!-- ----------------------------------------------------------------- -->
<?php
// ตรวจสอบว่ามี 'upload_success' และผู้รับเป็น 'email' หรือไม่
if (isset($_GET['upload_success']) && $row['delivery_method'] == 'email'):
    
    // --- เตรียมข้อมูลสำหรับ Email ---
    $email_to = $row['email'];
    $applicant_name = $row['first_name'] . ' ' . $row['last_name'];
    $request_id = $row['id'];
    $applicant_phone = $row['phone']; // <--- ดึงเบอร์โทรมาใช้
    
    // *** สำคัญ: ควรตั้งค่าข้อมูลนี้ในระบบ หรือดึงจาก Session ตอนล็อกอิน (auth.php) ***
    $admin_name = $_SESSION['admin_username'] ?? '[ชื่อ-นามสกุลเจ้าหน้าที่]'; 
    $admin_phone = $_SESSION['admin_phone'] ?? '[เบอร์ติดต่อกองการเจ้าหน้าที่]'; 
    $admin_department = "กองการเจ้าหน้าที่";
    $admin_organization = "องค์การบริหารส่วนจังหวัดศรีสะเกษ";

    // --- ช่วยคิดข้อความอีเมล (อัปเดตส่วนรหัสผ่าน) ---
    $subject = "แจ้งผลการยื่นคำร้องขอสำเนา ก.พ.7 (เลขที่: {$request_id}) - {$admin_organization}";
    
    $body = "เรียน คุณ{$applicant_name}\n\n";
    $body .= "ตามที่ท่านได้ยื่นคำร้องขอสำเนาทะเบียนประวัติ ก.พ.7 (เลขที่คำร้อง: {$request_id}) ผ่านช่องทางอีเมล นั้น\n\n";
    $body .= "บัดนี้ {$admin_organization} ได้ดำเนินการจัดเตรียมเอกสาร (ไฟล์สแกน) ให้ท่านเรียบร้อยแล้ว โดยได้แนบไฟล์ PDF ที่เข้ารหัสมาพร้อมกับอีเมลนี้\n\n";
    $body .= "--------------------------------------------------\n";
    // --- (อัปเดตข้อความตรงนี้) ---
    $body .= "รหัสผ่านสำหรับเปิดไฟล์: คือ เบอร์โทรศัพท์ ({$applicant_phone}) ที่ท่านใช้ลงทะเบียน\n";
    $body .= "--------------------------------------------------\n\n";
    $body .= "จึงเรียนมาเพื่อโปรดทราบ\n\n";
    $body .= "หากมีข้อสงสัยประการใด สามารถติดต่อสอบถามได้ที่:\n";
    $body .= "คุณ{$admin_name} (ผู้ดำเนินการ)\n";
    $body .= "{$admin_department}\n";
    $body .= "{$admin_organization}\n";
    $body .= "โทร. {$admin_phone}";

    // สร้างลิงก์ mailto:
    $mailto_link = "mailto:{$email_to}?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);

?>
    <!-- Modal Backdrop -->
    <div id="successEmailModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50">
        <!-- Modal Content -->
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 text-center transform transition-all scale-100 opacity-100">
            <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h3 class="text-2xl font-bold text-gray-900">อัปโหลดไฟล์สำเร็จ!</h3>
            <p class="text-gray-600 mt-2 mb-6">สถานะคำร้องนี้ถูกเปลี่ยนเป็น "เสร็จสิ้น" แล้ว กรุณากดปุ่มด้านล่างเพื่อส่งอีเมลแจ้งเตือนผู้ยื่นคำร้อง (ระบบจะเปิดโปรแกรมอีเมลของคุณ)</p>
            
            <a href="<?php echo htmlspecialchars($mailto_link); ?>" 
               target="_blank"
               id="sendEmailButton"
               class="inline-flex items-center justify-center w-full py-3 px-5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                ส่งอีเมลแจ้งผู้ยื่นคำร้อง
            </a>
            
            <button type="button" 
                    onclick="document.getElementById('successEmailModal').style.display='none'" 
                    class="mt-4 w-full py-2 px-4 text-gray-700 hover:bg-gray-100 font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-300 transition duration-200">
                ปิดหน้าต่างนี้
            </button>
        </div>
    </div>
    
    <script>
        // เมื่อกดปุ่มส่งอีเมลแล้ว ให้ปิด Modal
        document.getElementById('sendEmailButton').addEventListener('click', function() {
            // หน่วงเวลาเล็กน้อยเพื่อให้ mailto: ทำงาน
            setTimeout(function() {
                document.getElementById('successEmailModal').style.display = 'none';
            }, 500);
        });
    </script>
<?php
// สิ้นสุดการตรวจสอบ upload_success
endif;
?>
<!-- ----------------------------------------------------------------- -->
<!-- จบส่วน Popup -->
<!-- ----------------------------------------------------------------- -->


<!-- หัวเรื่องและสถานะ -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">รายละเอียดคำร้อง (ID: <?php echo $id; ?>)</h2>
        <p class="text-gray-600">ยื่นเมื่อ: <?php echo formatThaiDate($row['submitted_at']); ?></p>
    </div>
    <div class="flex-shrink-0">
        <span class="px-6 py-2 inline-flex text-lg leading-5 font-semibold rounded-full border <?php echo getStatusColor($row['status']); ?>">
            สถานะ: <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
        </span>
    </div>
</div>

<!-- การ์ดแสดงไฟล์ที่อัปโหลดแล้ว (ถ้ามี) -->
<?php if (!empty($row['uploaded_file'])): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm mb-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">เอกสารที่อัปโหลดแล้ว</h3>
        <div class="flex items-center bg-gray-50 p-4 rounded-md">
            <svg class="w-10 h-10 text-red-600 mr-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3v4a1 1 0 001 1h4" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13l-1.5 1.5 1.5 1.5m4-3l1.5 1.5-1.5 1.5" />
            </svg>
            <div class="flex-grow">
                <p class="text-sm font-medium text-gray-700">ไฟล์ที่เข้ารหัส:</p>
                <p class="text-gray-600 break-all"><?php echo htmlspecialchars($row['uploaded_file']); ?></p>
            </div>
            <!-- เราใช้ ../uploads/ เพราะไฟล์นี้ถูกเรียกจาก /admin/ และไฟล์อยู่ที่ /uploads/ -->
            <a href="../uploads/<?php echo htmlspecialchars($row['uploaded_file']); ?>" 
               download 
               class="ml-4 flex-shrink-0 py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition duration-200">
                ดาวน์โหลด
            </a>
        </div>
    </div>
<?php endif; ?>


<!-- การ์ดจัดการคำร้อง (Main Grid) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- คอลัมน์ซ้าย - รายละเอียด -->
    <div class="lg:col-span-2 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
        
        <!-- 1. ข้อมูลผู้ยื่น -->
        <h3 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">ข้อมูลผู้ยื่นคำร้อง</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
            <div class="md:col-span-2">
                <dt class="text-sm font-medium text-gray-500">ชื่อ - นามสกุล</dt>
                <dd class="mt-1 text-lg text-gray-900"><?php echo formatPrefix($row['prefix'], $row['other_prefix']) . ' ' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">ตำแหน่ง</dt>
                <dd class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($row['position']); ?></dd>
            </div>
            
            <!-- (เพิ่มส่วนนี้) ข้อมูลสังกัด -->
            <div>
                <dt class="text-sm font-medium text-gray-500">สังกัด</dt>
                <dd class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($row['department']); ?></dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">เบอร์โทรศัพท์</dt>
                <dd class="mt-1 text-lg text-gray-900 font-semibold text-blue-600"><?php echo htmlspecialchars($row['phone']); ?></dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-sm font-medium text-gray-500">เหตุผลในการขอ</dt>
                <dd class="mt-1 text-lg text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($row['reason']); ?></dd>
            </div>
        </dl>
        
        <!-- 2. ข้อมูลการรับเอกสาร -->
        <h3 class="text-xl font-semibold text-gray-800 border-b pb-2 mt-8 mb-4">ข้อมูลการรับเอกสาร</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">ช่องทางการรับ</dt>
                <dd class="mt-1 text-lg font-semibold <?php echo ($row['delivery_method'] == 'email') ? 'text-blue-600' : 'text-gray-900'; ?>">
                    <?php echo formatDelivery($row['delivery_method']); ?>
                </dd>
            </div>
            
            <?php if ($row['delivery_method'] == 'mail'): ?>
            <div class="md:col-span-2">
                <dt class="text-sm font-medium text-gray-500">ที่อยู่สำหรับจัดส่ง</dt>
                <dd class="mt-1 text-lg text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($row['address']); ?></dd>
            </div>
            <?php endif; ?>
            
            <?php if ($row['delivery_method'] == 'email'): ?>
            <div class_ ="md:col-span-2">
                <dt class="text-sm font-medium text-gray-500">อีเมลสำหรับจัดส่ง</dt>
                <dd class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($row['email']); ?></dd>
            </div>
            <?php endif; ?>
        </dl>

    </div>
    
    <!-- คอลัมน์ขวา - การจัดการ -->
    <div class="lg:col-span-1 space-y-6">

        <!-- การ์ดอัปเดตสถานะ -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">1. อัปเดตสถานะ</h3>
            
            <!-- แสดงข้อความแจ้งเตือน (ถ้ามี) -->
            <?php if (isset($_GET['status_success'])): ?>
                <div class="mb-4 p-4 bg-green-100 text-green-700 border border-green-200 rounded-md">
                    อัปเดตสถานะเรียบร้อยแล้ว
                </div>
            <?php endif; ?>
            
            <form action="update_status.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">เลือกสถานะใหม่</label>
                <select id="status" name="status" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="pending" <?php echo ($row['status'] == 'pending') ? 'selected' : ''; ?>>รอดำเนินการ</option>
                    <option value="processing" <?php echo ($row['status'] == 'processing') ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                    <option value="completed" <?php echo ($row['status'] == 'completed') ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                    <option value="rejected" <?php echo ($row['status'] == 'rejected') ? 'selected' : ''; ?>>ปฏิเสธ</N>
                </select>
                
                <button type="submit"
                        class="mt-4 w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                    อัปเดตสถานะ
                </button>
            </form>
        </div>

        <?php 
        // --- อัปเดตเงื่อนไข: ---
        // แสดงฟอร์มอัปโหลดเฉพาะถ้า...
        // 1. ผู้ใช้เลือกรับทาง 'email'
        // 2. สถานะยังไม่ 'completed'
        if ($row['delivery_method'] == 'email' && $row['status'] != 'completed'): 
        ?>
        <!-- การ์ดอัปโหลดไฟล์ (จะซ่อนอัตโนมัติเมื่ออัปโหลดสำเร็จ) -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 shadow-sm">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">2. อัปโหลดไฟล์ (PDF)</h3>
            
            <!-- แสดงข้อความแจ้งเตือน (ถ้ามี) -->
            <?php if (isset($_GET['upload_error'])): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-200 rounded-md">
                    <strong>ล้มเหลว:</strong> <?php echo htmlspecialchars(urldecode($_GET['upload_error'])); ?>
                </div>
            <?php endif; ?>

            <form action="upload.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div>
                    <label for="pdf_file" class="block text-sm font-medium text-gray-700 mb-1">เลือกไฟล์ PDF</label>
                    <input type="file" id="pdf_file" name="pdf_file" required
                           accept="application/pdf"
                           class="w-full text-sm text-gray-700 border border-gray-300 rounded-md file:p-2 file:bg-gray-100 file:border-0 file:font-medium file:text-gray-800 hover:file:bg-gray-200">
                </div>
                
                <!-- (ลบช่องกรอกเลขบัตรประชาชน) -->
                
                <button type="submit"
                        class="mt-4 w-full py-2 px-4 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-200">
                    อัปโหลดและเข้ารหัสไฟล์
                </button>
            </form>
        </div>
        
        <?php 
        endif; // --- สิ้นสุดการตรวจสอบช่องทาง E-mail และสถานะ ---
        ?>

    </div>
</div>

<?php
$conn->close();
include 'footer.php'; // เรียก Footer
?>

