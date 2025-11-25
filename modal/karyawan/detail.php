<!-- projeks3/modal/karyawan/detail.php -->
<div id="modalDetail" class="modal">
    <div class="modal-box" style="max-width: 800px;">
        <h3>👤 Detail Karyawan</h3>

        <!-- Employee Header -->
        <div class="detail-header">
            <img id="detail_photo" src="" alt="Foto" class="detail-photo">
            <div class="detail-info">
                <h3 id="detail_name">-</h3>
                <p id="detail_code">Kode: -</p>
                <p id="detail_phone">HP: -</p>
                <p id="detail_join">Bergabung: -</p>
                <span id="detail_status" class="status-badge">-</span>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('info')">📋 Info</button>
            <button class="tab-btn" onclick="switchTab('attendance')">⏰ Absensi</button>
        </div>

        <!-- Tab Content: Info -->
        <div id="tab-info" class="tab-content active">
            <p style="color: #636e72;">Informasi lengkap karyawan ditampilkan di header di atas.</p>
            <p style="color: #636e72; margin-top: 10px;">Untuk melakukan absensi, silakan pilih tab
                <strong>Absensi</strong>.</p>
        </div>

        <!-- Tab Content: Attendance -->
        <div id="tab-attendance" class="tab-content">
            <input type="hidden" id="attendance_employee_id">

            <!-- Current Time -->
            <div class="time-display" id="current_time">00:00:00</div>

            <!-- Alert Message -->
            <div id="attendance_alert" class="alert" style="display: none;"></div>

            <!-- Check In Section -->
            <div class="attendance-section">
                <h4 style="margin-bottom: 15px;">✅ Check In</h4>
                <div class="attendance-form">
                    <label>Password untuk Check In</label>
                    <input type="password" id="checkin_password" placeholder="Masukkan password Anda">
                    <button type="button" class="btn-checkin" onclick="checkIn()"
                        style="width: 100%; margin-top: 10px;">
                        🕐 Check In Sekarang
                    </button>
                </div>
            </div>

            <!-- Check Out Section -->
            <div class="attendance-section">
                <h4 style="margin-bottom: 15px;">🏁 Check Out</h4>
                <div class="attendance-form">
                    <label>Password untuk Check Out</label>
                    <input type="password" id="checkout_password" placeholder="Masukkan password Anda">
                    <button type="button" class="btn-checkout" onclick="checkOut()"
                        style="width: 100%; margin-top: 10px;">
                        🕐 Check Out Sekarang
                    </button>
                </div>
            </div>

            <!-- Attendance History -->
            <div class="attendance-history">
                <h4 style="margin-bottom: 15px;">📊 Riwayat Absensi</h4>
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
                            <td colspan="4" style="text-align: center;">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Close Button -->
        <div class="modal-actions" style="margin-top: 30px;">
            <button type="button" class="btn-cancel" onclick="closeDetailModal()" style="width: 100%;">
                ❌ Tutup
            </button>
        </div>
    </div>
</div>