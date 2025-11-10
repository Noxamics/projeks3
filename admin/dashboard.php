<?php 
// Perbaikan path include
include('../partials/headerAdmin.php'); 
include('../db.php'); 
?>

<?php
// Query untuk mendapatkan semua deadline dalam bulan ini
$currentMonth = date('Y-m');
$deadlines_query = "
    SELECT 
        dl.deadline_date,
        d.order_code,
        c.name as customer_name,
        s.service_name,
        di.brand
    FROM deadlines dl
    JOIN drops d ON dl.drop_id = d.id_drop
    JOIN customers c ON d.customer_id = c.id_customer
    JOIN services s ON d.service_id = s.id_service
    JOIN drop_items di ON d.id_drop = di.drop_id
    WHERE DATE_FORMAT(dl.deadline_date, '%Y-%m') = '$currentMonth'
    ORDER BY dl.deadline_date
";
$deadlines_result = mysqli_query($conn, $deadlines_query);
$deadlines = [];

// Simpan deadline dalam array untuk digunakan di JavaScript
while($deadline = mysqli_fetch_assoc($deadlines_result)) {
    $deadlines[] = $deadline;
}
?>

<link rel="stylesheet" href="../css/dashboard.css">

<main class="dashboard-container">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
    </div>
    
    <div class="dashboard-content">
        <!-- TIMELINE SECTION -->
        <div class="timeline-card">
            <h2>Timeline Pesanan</h2>
            
            <div class="filter-row">
                <select id="sortFilter" onchange="sortOrders()">
                    <option value="newest">Tanggal Terbaru</option>
                    <option value="oldest">Tanggal Terlama</option>
                </select>
                <div class="search-box">
                    <input type="text" id="searchOrder" placeholder="Cari pesanan...">
                    <button onclick="searchOrders()">🔍</button>
                </div>
            </div>

            <div class="filter-type">
                <button class="active" onclick="filterByCategory('all')">All</button>
                <?php
                // Query untuk mendapatkan category dari tabel services
                $category_query = "SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != ''";
                $category_result = mysqli_query($conn, $category_query);
                
                while($category = mysqli_fetch_assoc($category_result)) {
                    echo "<button onclick=\"filterByCategory('{$category['category']}')\">{$category['category']}</button>";
                }
                ?>
            </div>

            <div class="timeline-body" id="orderTimeline">
                <?php
                // Query untuk timeline pesanan
                $timeline_query = "
                    SELECT 
                        d.order_code,
                        d.est_finish_date,
                        c.name as customer_name,
                        s.service_name,
                        s.category,
                        di.brand,
                        st.status_name
                    FROM drops d
                    JOIN customers c ON d.customer_id = c.id_customer
                    JOIN services s ON d.service_id = s.id_service
                    JOIN drop_items di ON d.id_drop = di.drop_id
                    JOIN statuses st ON d.status_id = st.id_status
                    ORDER BY d.trans_date DESC 
                    LIMIT 10
                ";
                $timeline_result = mysqli_query($conn, $timeline_query);
                
                if(mysqli_num_rows($timeline_result) > 0) {
                    while($order = mysqli_fetch_assoc($timeline_result)) {
                        $status_class = '';
                        switch($order['status_name']) {
                            case 'Menunggu': $status_class = 'status-waiting'; break;
                            case 'Diproses': $status_class = 'status-process'; break;
                            case 'Selesai': $status_class = 'status-done'; break;
                            default: $status_class = 'status-waiting';
                        }
                        
                        echo "
                        <div class='order-item' data-category='" . strtolower($order['category']) . "'>
                            <div class='order-header'>
                                <span class='order-id'>{$order['order_code']}</span>
                                <span class='order-status {$status_class}'>{$order['status_name']}</span>
                            </div>
                            <div class='order-details'>
                                <strong>{$order['customer_name']}</strong> - {$order['service_name']} ({$order['brand']})
                            </div>
                            <div class='order-time'>
                                📅 Estimasi Selesai: " . date('d M Y', strtotime($order['est_finish_date'])) . "
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

            <div class="calendar-grid">
                <div class="day">S</div>
                <div class="day">M</div>
                <div class="day">T</div>
                <div class="day">W</div>
                <div class="day">T</div>
                <div class="day">F</div>
                <div class="day">S</div>
                <!-- Sample calendar days -->
                <?php for ($i = 1; $i <= 31; $i++): ?>
                    <div class="date <?php echo $i == 7 ? 'active' : ''; ?>"><?php echo $i; ?></div>
                <?php endfor; ?>
            </div>

                <div class="deadline-section">
                    <h4>Deadline Mendatang</h4>
                    <div id="deadlineList">
                        <!-- Deadline items akan di-generate oleh JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Data deadlines dari PHP
const deadlinesData = <?php echo json_encode($deadlines); ?>;

// Fungsi untuk menghitung sisa hari
function getDaysUntilDeadline(deadlineDate) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const deadline = new Date(deadlineDate);
    const timeDiff = deadline - today;
    const daysUntil = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
    return daysUntil;
}

// Fungsi untuk mendapatkan warna berdasarkan sisa hari
function getDeadlineColorClass(daysUntil) {
    if (daysUntil < 0) return 'expired';
    if (daysUntil <= 2) return 'red';
    if (daysUntil <= 5) return 'yellow';
    return 'green';
}

// Fungsi untuk generate deadline list
function generateDeadlineList() {
    const deadlineList = document.getElementById('deadlineList');
    deadlineList.innerHTML = '';
    
    // Filter deadline yang belum lewat dan sort by date
    const upcomingDeadlines = deadlinesData
        .map(deadline => ({
            ...deadline,
            daysUntil: getDaysUntilDeadline(deadline.deadline_date)
        }))
        .filter(deadline => deadline.daysUntil >= 0)
        .sort((a, b) => a.daysUntil - b.daysUntil)
        .slice(0, 5); // Tampilkan 5 deadline terdekat
    
    if (upcomingDeadlines.length === 0) {
        deadlineList.innerHTML = '<p style="color: #fff; text-align: center; padding: 20px;">Tidak ada deadline mendatang</p>';
        return;
    }
    
    upcomingDeadlines.forEach(deadline => {
        const colorClass = getDeadlineColorClass(deadline.daysUntil);
        const formattedDate = new Date(deadline.deadline_date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        
        let daysText = '';
        if (deadline.daysUntil === 0) {
            daysText = 'Hari ini!';
        } else if (deadline.daysUntil === 1) {
            daysText = 'Besok';
        } else {
            daysText = `${deadline.daysUntil} hari lagi`;
        }
        
        const deadlineItem = document.createElement('div');
        deadlineItem.className = `deadline-item ${colorClass}`;
        deadlineItem.innerHTML = `
            <div>
                <h4>${deadline.order_code}</h4>
                <p>${deadline.customer_name} - ${deadline.service_name}</p>
                <small style="color: #666; font-size: 11px;">${daysText}</small>
            </div>
            <span>${formattedDate}</span>
        `;
        
        deadlineList.appendChild(deadlineItem);
    });
}

// Fungsi untuk calendar dengan deadline colors
function generateCalendar() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    
    const firstDay = new Date(year, month, 1);    
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    
    const calendarGrid = document.getElementById('calendarGrid');
    calendarGrid.innerHTML = '';
    
    // Hari dalam seminggu
    const days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    days.forEach(day => {
        const dayElement = document.createElement('div');
        dayElement.className = 'day';
        dayElement.textContent = day;
        calendarGrid.appendChild(dayElement);
    });
    
    // Tambahkan empty cells untuk hari sebelum tanggal 1
    const firstDayOfWeek = firstDay.getDay();
    for (let i = 0; i < firstDayOfWeek; i++) {
        const emptyElement = document.createElement('div');
        emptyElement.className = 'date empty';
        calendarGrid.appendChild(emptyElement);
    }
    
    // Tanggal dengan warna berdasarkan deadline
    for (let i = 1; i <= daysInMonth; i++) {
        const dateElement = document.createElement('div');
        dateElement.className = 'date';
        
        // Format tanggal untuk matching dengan deadline
        const currentDate = new Date(year, month, i);
        const dateString = formatDate(currentDate);
        
        // Cek apakah ada deadline di tanggal ini
        const deadlineInfo = getDeadlineInfo(dateString);
        
        if (deadlineInfo) {
            const daysUntilDeadline = deadlineInfo.daysUntil;
            dateElement.classList.add('has-deadline');
            
            // Tentukan warna berdasarkan hari menuju deadline
            if (daysUntilDeadline <= 2) {
                dateElement.classList.add('deadline-critical'); // Merah - 0-2 hari lagi
            } else if (daysUntilDeadline <= 5) {
                dateElement.classList.add('deadline-warning'); // Kuning - 3-5 hari lagi
            } else if (daysUntilDeadline >= 6) {
                dateElement.classList.add('deadline-safe'); // Hijau - 6+ hari lagi
            }
            
            // Tambah tooltip dengan info deadline
            const tooltipText = deadlineInfo.orders.map(o => 
                `${o.order_code} - ${o.customer_name}`
            ).join('\n');
            dateElement.title = `${tooltipText}\n(${daysUntilDeadline} hari lagi)`;
        }
        
        // Tandai tanggal hari ini
        if (i === now.getDate() && month === now.getMonth() && year === now.getFullYear()) {
            dateElement.classList.add('active');
        }
        
        dateElement.textContent = i;
        dateElement.setAttribute('data-date', dateString);
        calendarGrid.appendChild(dateElement);
    }
}

// Fungsi untuk format date ke YYYY-MM-DD
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Fungsi untuk mendapatkan info deadline
function getDeadlineInfo(dateString) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const deadlineDate = new Date(dateString);
    
    // Hitung selisih hari
    const timeDiff = deadlineDate - today;
    const daysUntil = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
    
    // Cari deadline di tanggal tersebut
    const deadlinesOnDate = deadlinesData.filter(deadline => 
        deadline.deadline_date === dateString
    );
    
    if (deadlinesOnDate.length > 0) {
        return {
            count: deadlinesOnDate.length,
            daysUntil: daysUntil,
            orders: deadlinesOnDate
        };
    }
    
    return null;
}

// Fungsi untuk change month
function changeMonth(direction) {
    console.log('Change month:', direction);
    // Implementasi change month bisa ditambahkan nanti
}

// Panggil fungsi saat halaman load
document.addEventListener('DOMContentLoaded', function() {
    generateCalendar();
    generateDeadlineList();
    setupRealTimeSearch();
});

// Fungsi untuk pencarian pesanan
function searchOrders() {
    const searchTerm = document.getElementById('searchOrder').value.toLowerCase().trim();
    
    if (searchTerm === '') {
        return;
    }

    const orderItems = document.querySelectorAll('.order-item');
    let foundResults = false;
    const activeCategory = document.querySelector('.filter-type button.active').textContent.toLowerCase();

    orderItems.forEach(item => {
        const orderId = item.querySelector('.order-id').textContent.toLowerCase();
        const customerName = item.querySelector('.order-details strong').textContent.toLowerCase();
        const serviceDetails = item.querySelector('.order-details').textContent.toLowerCase();
        const itemCategory = item.getAttribute('data-category');
        
        const matchesSearch = orderId.includes(searchTerm) || 
            customerName.includes(searchTerm) || 
            serviceDetails.includes(searchTerm);
            
        const matchesCategory = activeCategory === 'all' || itemCategory === activeCategory;
        
        if (matchesSearch && matchesCategory) {
            item.style.display = 'block';
            foundResults = true;
        } else {
            item.style.display = 'none';
        }
    });

    showSearchMessage(searchTerm, foundResults, activeCategory);
}

// Fungsi untuk menampilkan pesan pencarian
function showSearchMessage(searchTerm, foundResults, activeCategory) {
    const existingMessage = document.querySelector('.search-message');
    if (existingMessage) {
        existingMessage.remove();
    }

    const messageDiv = document.createElement('div');
    messageDiv.className = 'search-message';
    messageDiv.style.cssText = `
        background: ${foundResults ? '#d4edda' : '#f8d7da'};
        color: ${foundResults ? '#155724' : '#721c24'};
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 14px;
        border: 1px solid ${foundResults ? '#c3e6cb' : '#f5c6cb'};
    `;

    if (foundResults) {
        if (activeCategory === 'all') {
            messageDiv.textContent = `Ditemukan hasil untuk: "${searchTerm}"`;
        } else {
            messageDiv.textContent = `Ditemukan hasil untuk: "${searchTerm}" dalam kategori ${activeCategory}`;
        }
    } else {
        if (activeCategory === 'all') {
            messageDiv.textContent = `Tidak ditemukan hasil untuk: "${searchTerm}"`;
        } else {
            messageDiv.textContent = `Tidak ditemukan hasil untuk: "${searchTerm}" dalam kategori ${activeCategory}`;
        }
    }

    const timelineBody = document.getElementById('orderTimeline');
    timelineBody.insertBefore(messageDiv, timelineBody.firstChild);

    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
}

// Fungsi untuk real-time search
function setupRealTimeSearch() {
    const searchInput = document.getElementById('searchOrder');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm === '') {
            const activeCategory = document.querySelector('.filter-type button.active').textContent.toLowerCase();
            filterByCategory(activeCategory);
            return;
        }
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchOrders();
        }, 500);
    });
    
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchOrders();
        }
    });
}

// Fungsi untuk sorting pesanan
function sortOrders() {
    const sortBy = document.getElementById('sortFilter').value;
    const orderItems = document.querySelectorAll('.order-item');
    const timelineBody = document.getElementById('orderTimeline');
    
    const ordersArray = Array.from(orderItems);
    
    ordersArray.sort((a, b) => {
        const dateA = new Date(a.querySelector('.order-time').textContent.replace('📅 Estimasi Selesai: ', ''));
        const dateB = new Date(b.querySelector('.order-time').textContent.replace('📅 Estimasi Selesai: ', ''));
        
        if (sortBy === 'newest') {
            return dateB - dateA;
        } else {
            return dateA - dateB;
        }
    });
    
    timelineBody.innerHTML = '';
    ordersArray.forEach(order => {
        timelineBody.appendChild(order);
    });
    
    showSortMessage(sortBy);
}

// Fungsi untuk filter berdasarkan category
function filterByCategory(category) {
    const orderItems = document.querySelectorAll('.order-item');
    const filterButtons = document.querySelectorAll('.filter-type button');
    const searchTerm = document.getElementById('searchOrder').value.toLowerCase().trim();
    
    filterButtons.forEach(button => {
        button.classList.remove('active');
        if (button.textContent.toLowerCase() === category.toLowerCase() || 
            (category === 'all' && button.textContent.toLowerCase() === 'all')) {
            button.classList.add('active');
        }
    });
    
    let foundResults = false;
    orderItems.forEach(item => {
        const orderId = item.querySelector('.order-id').textContent.toLowerCase();
        const customerName = item.querySelector('.order-details strong').textContent.toLowerCase();
        const serviceDetails = item.querySelector('.order-details').textContent.toLowerCase();
        const itemCategory = item.getAttribute('data-category');
        
        const matchesCategory = category === 'all' || itemCategory === category.toLowerCase();
        const matchesSearch = searchTerm === '' || 
            orderId.includes(searchTerm) || 
            customerName.includes(searchTerm) || 
            serviceDetails.includes(searchTerm);
        
        if (matchesCategory && matchesSearch) {
            item.style.display = 'block';
            foundResults = true;
        } else {
            item.style.display = 'none';
        }
    });
    
    showFilterMessage(category, searchTerm, foundResults);
}

// Fungsi untuk menampilkan pesan filter
function showFilterMessage(category, searchTerm, foundResults) {
    const message = category === 'all' 
        ? (searchTerm === '' ? 'Menampilkan semua pesanan' : `Menampilkan semua kategori dengan pencarian: "${searchTerm}"`)
        : (searchTerm === '' ? `Menampilkan kategori: ${category}` : `Menampilkan kategori: ${category} dengan pencarian: "${searchTerm}"`);
    
    const existingMessage = document.querySelector('.filter-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'filter-message';
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
    
    const timelineBody = document.getElementById('orderTimeline');
    timelineBody.insertBefore(messageDiv, timelineBody.firstChild);
    
    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
}

// Fungsi untuk menampilkan pesan sorting
function showSortMessage(sortType) {
    const message = sortType === 'newest' 
        ? 'Pesanan diurutkan dari tanggal terbaru' 
        : 'Pesanan diurutkan dari tanggal terlama';
    
    const messageDiv = document.createElement('div');
    messageDiv.style.cssText = `
        background: #d4edda;
        color: #155724;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 14px;
        border: 1px solid #c3e6cb;
    `;
    messageDiv.textContent = message;
    
    const timelineBody = document.getElementById('orderTimeline');
    timelineBody.insertBefore(messageDiv, timelineBody.firstChild);
    
    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
}
</script>

<?php   
include('../partials/footer.php'); 
?>