// File: /js/timeline/timeline_pesanan.js
// Timeline Dashboard - FIXED SESSION STORAGE

document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Timeline Dashboard initialized");

  // ===== SUCCESS MESSAGE HANDLER (TIMELINE PAGE ONLY) =====
  // 🔧 FIXED: Gunakan key "timeline_showSuccess" untuk halaman timeline
  if (sessionStorage.getItem("timeline_showSuccess") === "true") {
    const message =
      sessionStorage.getItem("timeline_successMessage") ||
      "✅ Operasi berhasil!";

    const alertDiv = document.createElement("div");
    alertDiv.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      padding: 16px 24px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 10000;
      font-weight: 600;
      animation: slideIn 0.3s ease-out;
    `;
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
      alertDiv.style.animation = "slideOut 0.3s ease-out";
      setTimeout(() => alertDiv.remove(), 300);
    }, 3000);

    // 🔧 FIXED: Clear timeline-specific session storage
    sessionStorage.removeItem("timeline_showSuccess");
    sessionStorage.removeItem("timeline_successMessage");

    console.log("✅ Timeline success message displayed:", message);
  }

  // Add CSS animation styles
  const style = document.createElement("style");
  style.textContent = `
    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(style);

  // Backup semua order items saat pertama kali load
  allOrderItems = Array.from(document.querySelectorAll(".order-item"));
  console.log("✅ Total order items loaded:", allOrderItems.length);

  generateCalendar();
  generateDeadlineList();
  setupRealTimeSearch();
  setupTimelineEditHandlers(); // Setup edit handlers
  setupModalHandlers();

  console.log("🖱️ Double-click any order to open EDIT modal");
});

<?php
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            ✅ Data berhasil diupdate!
          </div>';

    // AUTO-REDIRECT setelah 2 detik
    echo '<script>
            setTimeout(function() {
                window.location.href = "timeline_pesanan.php";
            }, 1500);
          </script>';
}

if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
    echo '<div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            ❌ ' . htmlspecialchars($error_message) . '
          </div>';
}
?>

<?php
// ✅ FILTER: Query deadline TANPA status "Barang Telah Diambil"
$currentMonth = date('Y-m');
$deadlines_query = "
    SELECT 
        dl.deadline_date,
        d.order_code,
        c.name as customer_name,
        s.service_name,
        di.brand,
        st.status_name
    FROM deadlines dl
    JOIN drops d ON dl.drop_id = d.id_drop
    JOIN customers c ON d.customer_id = c.id_customer
    JOIN services s ON d.service_id = s.id_service
    JOIN drop_items di ON d.id_drop = di.drop_id
    JOIN statuses st ON dl.status_id = st.id_status
    WHERE DATE_FORMAT(dl.deadline_date, '%Y-%m') = '$currentMonth'
    AND st.status_name != 'Barang Telah Diambil'
    ORDER BY dl.deadline_date
";
$deadlines_result = mysqli_query($conn, $deadlines_query);
$deadlines = [];

  // Jika belum ada backup, ambil semua order items dari DOM
  if (allOrderItems.length === 0) {
    allOrderItems = Array.from(document.querySelectorAll(".order-item"));
  }

  // Clone array untuk manipulasi
  const ordersArray = [...allOrderItems];

  // RESET: Set semua ke display block
  ordersArray.forEach((order) => (order.style.display = "block"));

  // Jika filter deadline, filter berdasarkan kriteria
  if (sortBy.startsWith("deadline-")) {
    const filteredOrders = ordersArray.filter((order) => {
      const estDate = order.getAttribute("data-est-date");
      const daysUntil = getDaysUntilDeadline(estDate);

      if (sortBy === "deadline-critical") {
        return daysUntil >= 0 && daysUntil <= 2;
      } else if (sortBy === "deadline-warning") {
        return daysUntil >= 3 && daysUntil <= 5;
      } else if (sortBy === "deadline-safe") {
        return daysUntil >= 6;
      }
      return false;
    });

    // Urutkan berdasarkan deadline terdekat
    filteredOrders.sort((a, b) => {
      const dateA = new Date(a.getAttribute("data-est-date"));
      const dateB = new Date(b.getAttribute("data-est-date"));
      return dateA - dateB;
    });

    // Tampilkan hasil filter
    timelineBody.innerHTML = "";
    filteredOrders.forEach((order) => timelineBody.appendChild(order));

    showDeadlineFilterMessage(sortBy, filteredOrders.length);
  } else {
    // Sort berdasarkan tanggal (newest/oldest)
    ordersArray.sort((a, b) => {
      const dateA = new Date(a.getAttribute("data-est-date"));
      const dateB = new Date(b.getAttribute("data-est-date"));
      return sortBy === "newest" ? dateB - dateA : dateA - dateB;
    });

    timelineBody.innerHTML = "";
    ordersArray.forEach((order) => timelineBody.appendChild(order));
    showSortMessage(sortBy);
  }

  // Re-attach double click handlers untuk EDIT MODAL
  setupTimelineEditHandlers();
}
?>

<link rel="stylesheet" href="../css/timeline_pesanan.css">
<link rel="stylesheet" href="../css/timeline_pesanan-responsive.css">
<link rel="stylesheet" href="../css/drop/drop_modal.css">
<link rel="stylesheet" href="../css/drop/drop_form.css">

<main class="dashboard-container">

    <div class="dashboard-content">
        <!-- TIMELINE SECTION -->
        <div class="timeline-card">
            <h2>Timeline Pesanan</h2>

            <div class="filter-row">
                <select id="sortFilter" onchange="sortOrders()">
                    <option value="newest">Tanggal Terbaru</option>
                    <option value="oldest">Tanggal Terlama</option>
                    <option value="deadline-critical">🔴 Deadline Kritis (≤2 hari)</option>
                    <option value="deadline-warning">🟡 Deadline Mendesak (3-5 hari)</option>
                    <option value="deadline-safe">🟢 Deadline Aman (≥6 hari)</option>
                </select>
                <div class="search-box">
                    <input type="text" id="searchOrder" placeholder="Cari pesanan...">
                    <button onclick="searchOrders()">🔍</button>
                </div>
            </div>

            <div class="filter-type">
                <button class="active" onclick="filterByCategory('all')">All</button>
                <?php
                $category_query = "SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != ''";
                $category_result = mysqli_query($conn, $category_query);

                while ($category = mysqli_fetch_assoc($category_result)) {
                    echo "<button onclick=\"filterByCategory('{$category['category']}')\">{$category['category']}</button>";
                }
                ?>
            </div>

            <div class="timeline-body" id="orderTimeline">
                <?php
                // ✅ FILTER: Query timeline TANPA status "Barang Telah Diambil"
                $timeline_query = "
                SELECT 
                    d.id_drop,
                    d.order_code,
                    d.trans_date,
                    d.est_finish_date,
                    dl.status_id,
                    d.employee_id,
                    c.id_customer,
                    c.name as customer_name,
                    c.phone as phone_number,
                    s.id_service,
                    s.service_name,
                    s.category,
                    s.price_min,
                    s.price_max,
                    s.duration,
                    di.brand,
                    di.fixed_price,
                    di.service_id as drop_item_service_id,
                    st.status_name,
                    p.status as payment_status,
                    p.payment_method,
                    p.payment_date,
                    p.amount_paid,
                    e.name as employee_name,
                    COALESCE(di.fixed_price, s.price_min) as actual_price
                FROM drops d
                JOIN customers c ON d.customer_id = c.id_customer
                JOIN drop_items di ON d.id_drop = di.drop_id
                JOIN services s ON di.service_id = s.id_service
                JOIN deadlines dl ON d.id_drop = dl.drop_id
                JOIN statuses st ON dl.status_id = st.id_status
                LEFT JOIN payments p ON d.id_drop = p.drop_id
                LEFT JOIN employees e ON d.employee_id = e.id_employee
                WHERE d.est_finish_date >= CURDATE()
                AND st.status_name != 'Barang Telah Diambil'
                ORDER BY d.trans_date DESC 
                LIMIT 10
            ";
                $timeline_result = mysqli_query($conn, $timeline_query);

                if (mysqli_num_rows($timeline_result) > 0) {
                    while ($order = mysqli_fetch_assoc($timeline_result)) {
                        $status_class = '';
                        switch ($order['status_name']) {
                            case 'Menunggu':
                                $status_class = 'status-waiting';
                                break;
                            case 'Diproses':
                                $status_class = 'status-process';
                                break;
                            case 'Selesai':
                                $status_class = 'status-done';
                                break;
                            default:
                                $status_class = 'status-waiting';
                        }

                        $est_date_formatted = date('d M Y', strtotime($order['est_finish_date']));

                        // Format harga - gunakan actual_price (fixed_price) untuk display
                        $actual_price = $order['actual_price'] ?? $order['price_min'];
                        $price_display = 'Rp ' . number_format($actual_price, 0, ',', '.');

                        $actual_service_id = $order['drop_item_service_id'] ?: $order['id_service'];

                        echo "
                        <div class='order-item' 
                            data-id-drop='" . htmlspecialchars($order['id_drop']) . "'
                            data-drop-id='" . htmlspecialchars($order['id_drop']) . "'
                            data-category='" . strtolower($order['category']) . "' 
                            data-order-code='" . htmlspecialchars($order['order_code']) . "'
                            data-customer='" . htmlspecialchars($order['customer_name']) . "'
                            data-phone='" . htmlspecialchars($order['phone_number']) . "'
                            data-service='" . htmlspecialchars($order['service_name']) . "'
                            data-service-id='" . htmlspecialchars($actual_service_id) . "'
                            data-category-full='" . htmlspecialchars($order['category']) . "'
                            data-brand='" . htmlspecialchars($order['brand']) . "'
                            data-trans-date='" . htmlspecialchars($order['trans_date']) . "'
                            data-est-date='" . htmlspecialchars($order['est_finish_date']) . "'
                            data-status-id='" . htmlspecialchars($order['status_id']) . "'
                            data-price-min='" . htmlspecialchars($order['price_min']) . "'
                            data-price-max='" . htmlspecialchars($order['price_max']) . "'
                            data-fixed-price='" . htmlspecialchars($actual_price) . "'
                            data-price-display='" . htmlspecialchars($price_display) . "'
                            data-duration='" . htmlspecialchars($order['duration']) . " hari'
                            data-payment-status='" . htmlspecialchars($order['payment_status'] ?? 'Belum Lunas') . "'
                            data-payment-method='" . htmlspecialchars($order['payment_method'] ?? 'Tunai') . "'
                            data-payment-date='" . htmlspecialchars($order['payment_date'] ?? '') . "'
                            data-amount-paid='" . htmlspecialchars($order['amount_paid'] ?? '0') . "'
                            data-employee-id='" . htmlspecialchars($order['employee_id'] ?? '') . "'
                            data-employee-name='" . htmlspecialchars($order['employee_name'] ?? '') . "'>
                            <div class='order-header'>
                                <span class='order-id'>{$order['order_code']}</span>
                                <span class='order-status {$status_class}'>{$order['status_name']}</span>
                            </div>
                            <div class='order-details'>
                                <strong>{$order['customer_name']}</strong> - {$order['service_name']} ({$order['brand']})
                            </div>
                            <div class='order-time'>
                                📅 Estimasi Selesai: {$est_date_formatted}
                            </div>
                            <div class='order-hint' style='font-size: 11px; color: #999; margin-top: 5px;'>
                                💡 Double klik untuk edit
                            </div>
                        </div>
                        ";
                    }
                } else {
                    echo "<p>Tidak ada pesanan</p>";
                }
                ?>
            </div>
        </div>

        <!-- CALENDAR & DEADLINE SECTION -->
        <div class="right-sidebar">
            <div class="calendar-card">
                <div class="calendar-header">
                    <h3><?php echo date('F Y'); ?></h3>
                    <div class="calendar-nav">
                        <button onclick="changeMonth(-1)">‹</button>
                        <button onclick="changeMonth(1)">›</button>
                    </div>
                </div>

                <div class="calendar-grid" id="calendarGrid"></div>

                <div class="deadline-section">
                    <h4>Deadline Mendatang</h4>
                    <div id="deadlineList"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ORDER DETAIL MODAL -->
    <div id="orderDetailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeOrderDetail()">&times;</span>
            <h2>Detail Pesanan</h2>
            <div style="padding: 20px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px; font-weight: bold; width: 40%;">Kode Order:</td>
                        <td id="modal-order-code" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Pelanggan:</td>
                        <td id="modal-customer" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Kategori:</td>
                        <td id="modal-category" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Layanan:</td>
                        <td id="modal-service" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Brand:</td>
                        <td id="modal-brand" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Estimasi Selesai:</td>
                        <td id="modal-est-date" style="padding: 10px;">-</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</main>

<!-- INCLUDE MODAL EDIT DARI FOLDER MODAL/DROP -->
<?php include('../modal/drop/modal_edit_item.php'); ?>

// ✅ Setup double click handler untuk EDIT MODAL (bukan detail)
function setupTimelineEditHandlers() {
  const orderItems = document.querySelectorAll(".order-item");
  console.log(`📋 Setting up edit handlers for ${orderItems.length} items`);

  orderItems.forEach((item) => {
    // Remove old listeners untuk avoid duplicate
    const newItem = item.cloneNode(true);
    item.parentNode.replaceChild(newItem, item);

    // Style untuk cursor pointer
    newItem.style.cursor = "pointer";
    newItem.style.transition = "transform 0.2s ease, box-shadow 0.2s ease";

    // Attach double click untuk EDIT MODAL
    newItem.addEventListener("dblclick", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const dropId =
        this.getAttribute("data-drop-id") || this.getAttribute("data-id-drop");

      if (!dropId) {
        console.error("❌ Drop ID not found");
        alert("Error: ID pesanan tidak ditemukan");
        return;
      }

      console.log(
        `✏️ Double-click detected - Opening EDIT modal for Drop ${dropId}`
      );

      // Panggil fungsi loadEditModal yang ada di drop_modal_edit.js
      if (typeof loadEditModal === "function") {
        loadEditModal(dropId);
      } else {
        console.error("❌ loadEditModal function not found");
        alert(
          "Error: Fungsi edit tidak tersedia. Pastikan drop_modal_edit.js sudah dimuat."
        );
      }
    });

    // Visual feedback on hover
    newItem.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-2px)";
      this.style.boxShadow = "0 4px 12px rgba(0,0,0,0.15)";
    });

    newItem.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
      this.style.boxShadow = "none";
    });
  });

  console.log("✅ Edit handlers attached");
}

// Make setupTimelineEditHandlers global
window.setupTimelineEditHandlers = setupTimelineEditHandlers;

// Search functions
function searchOrders() {
  const searchTerm = document
    .getElementById("searchOrder")
    .value.toLowerCase()
    .trim();
  if (searchTerm === "") return;

  const orderItems = document.querySelectorAll(".order-item");
  let foundResults = false;
  const activeCategory = document
    .querySelector(".filter-type button.active")
    .textContent.toLowerCase();

  orderItems.forEach((item) => {
    const orderId = item.querySelector(".order-id").textContent.toLowerCase();
    const customerName = item
      .querySelector(".order-details strong")
      .textContent.toLowerCase();
    const serviceDetails = item
      .querySelector(".order-details")
      .textContent.toLowerCase();
    const itemCategory = item.getAttribute("data-category");

    const matchesSearch =
      orderId.includes(searchTerm) ||
      customerName.includes(searchTerm) ||
      serviceDetails.includes(searchTerm);
    const matchesCategory =
      activeCategory === "all" || itemCategory === activeCategory;

    if (matchesSearch && matchesCategory) {
      item.style.display = "block";
      foundResults = true;
    } else {
      item.style.display = "none";
    }
  });

  showSearchMessage(searchTerm, foundResults, activeCategory);
}

function showSearchMessage(searchTerm, foundResults, activeCategory) {
  const existingMessage = document.querySelector(".search-message");
  if (existingMessage) existingMessage.remove();

  const messageDiv = document.createElement("div");
  messageDiv.className = "search-message";
  messageDiv.style.cssText = `
        background: ${foundResults ? "#d4edda" : "#f8d7da"};
        color: ${foundResults ? "#155724" : "#721c24"};
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 14px;
        border: 1px solid ${foundResults ? "#c3e6cb" : "#f5c6cb"};
    `;

  if (foundResults) {
    messageDiv.textContent =
      activeCategory === "all"
        ? `Ditemukan hasil untuk: "${searchTerm}"`
        : `Ditemukan hasil untuk: "${searchTerm}" dalam kategori ${activeCategory}`;
  } else {
    messageDiv.textContent =
      activeCategory === "all"
        ? `Tidak ditemukan hasil untuk: "${searchTerm}"`
        : `Tidak ditemukan hasil untuk: "${searchTerm}" dalam kategori ${activeCategory}`;
  }

  const timelineBody = document.getElementById("orderTimeline");
  timelineBody.insertBefore(messageDiv, timelineBody.firstChild);
  setTimeout(() => messageDiv.remove(), 3000);
}

function setupRealTimeSearch() {
  const searchInput = document.getElementById("searchOrder");
  let searchTimeout;

  searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase().trim();

    if (searchTerm === "") {
      const activeCategory = document
        .querySelector(".filter-type button.active")
        .textContent.toLowerCase();
      filterByCategory(activeCategory);
      return;
    }

    // Fungsi untuk membuka edit modal
    function openEditModal(element) {
        const dropId = element.getAttribute('data-drop-id') || element.getAttribute('data-id-drop');
        
        if (!dropId) {
            console.error('❌ Drop ID tidak ditemukan');
            alert('Error: ID pesanan tidak ditemukan');
            return;
        }

        console.log('✏️ Opening edit modal for Drop ID:', dropId);
        
        // Panggil fungsi dari drop_modal_edit.js
        if (typeof loadEditModal === 'function') {
            loadEditModal(dropId);
        } else {
            console.error('❌ Fungsi loadEditModal tidak ditemukan');
            alert('Error: Fungsi edit modal tidak tersedia. Pastikan file drop_modal_edit.js sudah dimuat.');
        }
    }
</script>

<!-- Load external JavaScript - URUTAN PENTING! -->
<!-- 1. Drop helpers harus dimuat pertama -->
<script src="../js/drop/drop_helpers.js"></script>

<!-- 2. Timeline pesanan -->
<script src="../js/timeline_pesanan.js"></script>

<!-- 3. Drop modal edit - untuk fungsi edit -->
<script src="../js/drop/drop_modal_edit.js"></script>

  showFilterMessage(category, searchTerm, foundResults);
}

function showFilterMessage(category, searchTerm, foundResults) {
  const message =
    category === "all"
      ? searchTerm === ""
        ? "Menampilkan semua pesanan"
        : `Menampilkan semua kategori dengan pencarian: "${searchTerm}"`
      : searchTerm === ""
      ? `Menampilkan kategori: ${category}`
      : `Menampilkan kategori: ${category} dengan pencarian: "${searchTerm}"`;

  const existingMessage = document.querySelector(".filter-message");
  if (existingMessage) existingMessage.remove();

  const messageDiv = document.createElement("div");
  messageDiv.className = "filter-message";
  messageDiv.style.cssText = `
        background: #e3f2fd;
        color: #0d47a1;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 14px;
        border: 1px solid #bbdefb;
    `;
  messageDiv.textContent = message;

  const timelineBody = document.getElementById("orderTimeline");
  timelineBody.insertBefore(messageDiv, timelineBody.firstChild);
  setTimeout(() => messageDiv.remove(), 3000);
}

// Initialize
document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Timeline Dashboard initialized");

  // Backup semua order items saat pertama kali load
  allOrderItems = Array.from(document.querySelectorAll(".order-item"));
  console.log("✅ Total order items loaded:", allOrderItems.length);

  generateCalendar();
  generateDeadlineList();
  setupRealTimeSearch();
  setupTimelineEditHandlers(); // Setup edit handlers
  setupModalHandlers();

  console.log("🖱️ Double-click any order to open EDIT modal");
});
