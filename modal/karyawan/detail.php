<!-- 
  Detail & Attendance Modal
  Location: modal/karyawan/detail.php
-->
<div id="modalDetail" class="modal" role="dialog" aria-labelledby="detailModalTitle" aria-hidden="true">
    <div class="modal-box" style="max-width: 800px;">
        <h3 id="detailModalTitle">👤 Detail Karyawan</h3>
        
        <!-- Employee Header -->
        <div class="detail-header">
            <img id="detail_photo" 
                 src="" 
                 alt="Employee Photo" 
                 class="detail-photo">
            
            <div class="detail-info">
                <h3 id="detail_name">-</h3>
                <p id="detail_code">Kode: -</p>
                <p id="detail_phone">HP: -</p>
                <p id="detail_join">Bergabung: -</p>
                <div id="detail_roles" style="display: flex; gap: 5px; margin-top: 8px;"></div>
                <span id="detail_status" class="status-badge">-</span>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('info')">
                ℹ️ Info
            </button>
            <button class="tab-btn" onclick="switchTab('attendance')">
                ⏰ Absensi
            </button>
            <button class="tab-btn" onclick="switchTab('performance')">
                📊 Performa
            </button>
        </div>
        
        <!-- Tab: Info -->
        <div id="tab-info" class="tab-content active">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="background: #f8fbff; padding: 15px; border-radius: 10px; border: 2px solid #e3f2fd;">
                    <strong style="color: #0066cc; display: block; margin-bottom: 8px;">📋 Informasi Dasar</strong>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>ID:</strong> <span id="info_id">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Kode:</strong> <span id="info_code">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Nama:</strong> <span id="info_name">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>No HP:</strong> <span id="info_phone">-</span>
                    </p>
                </div>
                
                <div style="background: #f8fbff; padding: 15px; border-radius: 10px; border: 2px solid #e3f2fd;">
                    <strong style="color: #0066cc; display: block; margin-bottom: 8px;">💼 Status & Role</strong>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Status:</strong> <span id="info_status">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Bergabung:</strong> <span id="info_join">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Role:</strong> <span id="info_roles">-</span>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Tab: Attendance -->
        <div id="tab-attendance" class="tab-content">
            <input type="hidden" id="attendance_employee_id">
            
            <!-- Alert Messages -->
            <div id="attendance_alert" class="alert" style="display: none;"></div>
            
            <!-- Current Time Display -->
            <div class="time-display" id="current_time">00:00:00</div>
            
            <!-- Check In Section -->
            <div class="attendance-section" id="checkin-section">
                <h4>🟢 Check In</h4>
                <div class="attendance-form">
                    <label for="checkin_password">Masukkan Password:</label>
                    <input type="password" 
                           id="checkin_password" 
                           placeholder="Password karyawan"
                           autocomplete="off">
                    <div style="margin-top: 15px;">
                        <button type="button" class="btn-checkin" onclick="checkIn()" style="width: 100%;">
                            🟢 Check In Sekarang
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Check Out Section -->
            <div class="attendance-section" id="checkout-section">
                <h4>🔴 Check Out</h4>
                <div class="attendance-form">
                    <label for="checkout_password">Masukkan Password:</label>
                    <input type="password" 
                           id="checkout_password" 
                           placeholder="Password karyawan"
                           autocomplete="off">
                    <div style="margin-top: 15px;">
                        <button type="button" class="btn-checkout" onclick="checkOut()" style="width: 100%;">
                            🔴 Check Out Sekarang
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Attendance History -->
            <div class="attendance-history">
                <h4>📅 Riwayat Absensi (7 Hari Terakhir)</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendance_history_body">
                        <tr>
                            <td colspan="4" style="text-align: center; color: #636e72;">
                                Memuat data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab: Performance -->
        <div id="tab-performance" class="tab-content">
            <div style="background: linear-gradient(135deg, #f8fbff 0%, #e3f2fd 100%); padding: 20px; border-radius: 15px; border: 2px solid #e3f2fd; margin-bottom: 20px;">
                <h4 style="color: #0066cc; margin-bottom: 15px;">📊 Performa Hari Ini</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="background: white; padding: 15px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; font-weight: 700; color: #0066cc;" id="perf_today_shoes">0</div>
                        <div style="color: #666; font-size: 14px;">Sepatu Selesai</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; font-weight: 700; color: #2ecc71;" id="perf_today_score">0%</div>
                        <div style="color: #666; font-size: 14px;">Performance Score</div>
                    </div>
                </div>
            </div>
            
            <div style="background: #f8fbff; padding: 20px; border-radius: 15px; border: 2px solid #e3f2fd;">
                <h4 style="color: #0066cc; margin-bottom: 15px;">📈 Statistik 30 Hari Terakhir</h4>
                <div id="performance_stats">
                    <p style="color: #666; text-align: center;">Data akan ditampilkan di sini</p>
                </div>
            </div>
        </div>
        
        <!-- Close Button -->
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeDetailModal()" style="width: 100%;">
                ❌ Tutup
            </button>
        </div>
    </div>
</div>

<script>
// Update detail modal when opening
function updateDetailModalContent(employee) {
    // Update info tab
    document.getElementById('info_id').textContent = employee.id_employee || '-';
    document.getElementById('info_code').textContent = employee.employee_code || '-';
    document.getElementById('info_name').textContent = employee.name || '-';
    document.getElementById('info_phone').textContent = employee.phone || '-';
    document.getElementById('info_status').textContent = employee.status || '-';
    document.getElementById('info_join').textContent = formatDate(employee.join_date) || '-';
    document.getElementById('info_roles').textContent = employee.roles ? employee.roles.replace(/,/g, ', ') : '-';
    
    // Update performance tab
    document.getElementById('perf_today_shoes').textContent = employee.today_shoes || '0';
    document.getElementById('perf_today_score').textContent = (employee.today_score || '0') + '%';
}
</script>