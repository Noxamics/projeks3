<!-- modal/karyawan/attendance.php -->
<div id="modalAttendance" class="modal">
    <div class="modal-box modal-attendance">
        <h3>
            <span class="modal-icon">⏰</span>
            Absensi Karyawan
        </h3>

        <div class="attendance-container">
            <!-- Employee Info -->
            <div class="employee-info-section">
                <img id="attendance_photo" src="" alt="Employee Photo" class="attendance-photo">
                <div class="employee-details">
                    <h4 id="attendance_name"></h4>
                    <p id="attendance_code"></p>
                </div>
            </div>

            <!-- Current Time Display -->
            <div class="time-display-big">
                <div class="date-display" id="current_date"></div>
                <div class="time-display" id="current_time"></div>
            </div>

            <!-- Check In Section -->
            <div class="attendance-section" id="checkin-section">
                <div class="section-header">
                    <h4>
                        <span class="icon">🟢</span>
                        Check In
                    </h4>
                    <span class="section-subtitle">Masuk Kerja</span>
                </div>

                <form id="formCheckIn" class="attendance-form">
                    <input type="hidden" id="checkin_employee_id" name="employee_id">

                    <!-- Role Selection -->
                    <div class="form-group">
                        <label for="checkin_role">
                            <span class="label-icon">👔</span>
                            Pilih Role Hari Ini
                        </label>
                        <select id="checkin_role" name="role_today" required class="role-select">
                            <option value="">-- Pilih Role --</option>
                            <!-- Options will be populated dynamically based on employee roles -->
                        </select>
                        <p class="field-note">Role yang dipilih akan menentukan tugas hari ini</p>
                    </div>

                    <!-- Kasir Auto Assignment Notice -->
                    <div id="kasir_notice" class="info-notice" style="display:none;">
                        <span class="notice-icon">💰</span>
                        <div class="notice-content">
                            <strong>Anda akan menjadi KASIR hari ini!</strong>
                            <p>Sebagai karyawan pertama yang absen, Anda otomatis ditugaskan sebagai kasir untuk hari
                                ini.</p>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="checkin_password">
                            <span class="label-icon">🔒</span>
                            Password
                        </label>
                        <input type="password" id="checkin_password" name="password" required
                            placeholder="Masukkan password Anda" class="password-input">
                        <p class="field-note">Gunakan password pribadi Anda</p>
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="checkin_notes">
                            <span class="label-icon">📝</span>
                            Catatan (Opsional)
                        </label>
                        <textarea id="checkin_notes" name="notes" rows="3" placeholder="Catatan tambahan..."></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-checkin">
                            <span>✅</span> Check In Sekarang
                        </button>
                        <button type="button" class="btn-cancel" onclick="closeAttendanceModal()">Batal</button>
                    </div>
                </form>
            </div>

            <!-- Check Out Section -->
            <div class="attendance-section" id="checkout-section" style="display:none;">
                <div class="section-header">
                    <h4>
                        <span class="icon">🔴</span>
                        Check Out
                    </h4>
                    <span class="section-subtitle">Selesai Kerja</span>
                </div>

                <!-- Today's Work Summary -->
                <div class="work-summary">
                    <h5>Ringkasan Kerja Hari Ini</h5>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="summary-icon">👟</span>
                            <div class="summary-details">
                                <strong id="summary_shoes">0</strong>
                                <span>Sepatu Selesai</span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <span class="summary-icon">⏱️</span>
                            <div class="summary-details">
                                <strong id="summary_duration">0</strong>
                                <span>Jam Kerja</span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <span class="summary-icon">👔</span>
                            <div class="summary-details">
                                <strong id="summary_role">-</strong>
                                <span>Role</span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <span class="summary-icon">⭐</span>
                            <div class="summary-details">
                                <strong id="summary_score">0</strong>
                                <span>Score</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formCheckOut" class="attendance-form">
                    <input type="hidden" id="checkout_employee_id" name="employee_id">

                    <!-- Password -->
                    <div class="form-group">
                        <label for="checkout_password">
                            <span class="label-icon">🔒</span>
                            Password
                        </label>
                        <input type="password" id="checkout_password" name="password" required
                            placeholder="Masukkan password Anda" class="password-input">
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="checkout_notes">
                            <span class="label-icon">📝</span>
                            Catatan Penutup (Opsional)
                        </label>
                        <textarea id="checkout_notes" name="notes" rows="3"
                            placeholder="Ringkasan pekerjaan atau catatan..."></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-checkout">
                            <span>🏁</span> Check Out Sekarang
                        </button>
                        <button type="button" class="btn-cancel" onclick="closeAttendanceModal()">Batal</button>
                    </div>
                </form>
            </div>

            <!-- Alert Messages -->
            <div id="attendance_alert" class="alert" style="display:none;"></div>

            <!-- Today's Attendance Info -->
            <div class="attendance-info" id="today_attendance_info">
                <h5>Status Absensi Hari Ini</h5>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Check In:</span>
                        <strong id="info_checkin">-</strong>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Check Out:</span>
                        <strong id="info_checkout">-</strong>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Role:</span>
                        <strong id="info_role">-</strong>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span id="info_status" class="status-badge">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .modal-attendance .attendance-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .employee-info-section {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: linear-gradient(135deg, #f8fbff 0%, #e3f2fd 100%);
        border-radius: 15px;
        border: 2px solid #e3f2fd;
    }

    .attendance-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 102, 204, 0.2);
    }

    .time-display-big {
        text-align: center;
        padding: 30px;
        background: linear-gradient(135deg, #0066cc 0%, #667eea 100%);
        border-radius: 15px;
        color: white;
    }

    .date-display {
        font-size: 18px;
        opacity: 0.9;
        margin-bottom: 10px;
    }

    .time-display {
        font-size: 48px;
        font-weight: 700;
        font-family: 'Courier New', monospace;
        letter-spacing: 3px;
    }

    .role-select {
        width: 100%;
        padding: 14px;
        border: 2px solid #e3f2fd;
        border-radius: 10px;
        font-size: 15px;
        background: white;
        cursor: pointer;
        transition: all 0.3s;
    }

    .role-select:focus {
        border-color: #0066cc;
        box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
    }

    .info-notice {
        display: flex;
        gap: 15px;
        padding: 15px;
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        border-radius: 8px;
        margin: 15px 0;
    }

    .notice-icon {
        font-size: 24px;
    }

    .notice-content strong {
        color: #856404;
        display: block;
        margin-bottom: 5px;
    }

    .notice-content p {
        color: #856404;
        font-size: 14px;
        margin: 0;
    }

    .work-summary {
        background: #f8fbff;
        padding: 20px;
        border-radius: 12px;
        border: 2px solid #e3f2fd;
    }

    .work-summary h5 {
        color: #0066cc;
        margin-bottom: 15px;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .summary-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: white;
        border-radius: 10px;
        border: 2px solid #e3f2fd;
    }

    .summary-icon {
        font-size: 28px;
    }

    .summary-details strong {
        display: block;
        font-size: 24px;
        color: #0066cc;
    }

    .summary-details span {
        font-size: 12px;
        color: #666;
    }

    .field-note {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
        font-style: italic;
    }

    .attendance-info {
        background: #f8fbff;
        padding: 20px;
        border-radius: 12px;
        border: 2px solid #e3f2fd;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 15px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: white;
        border-radius: 8px;
    }

    .info-label {
        color: #666;
        font-size: 14px;
    }
</style>