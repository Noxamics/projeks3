<!-- ================================================
        Attendance Modal - UPDATED VERSION (Role Auto-populated)
        File: modal/karyawan/attendance.php
        ================================================ -->

<link rel="stylesheet" href="../css/karyawan/notification.css">

<div id="modalAttendance" class="modal">
    <div class="modal-box modal-attendance">
        <h3>
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
                <div class="date-display" id="current_date">Loading...</div>
                <div class="time-display" id="current_time">00:00:00</div>
            </div>

            <!-- Check In Section -->
            <div class="attendance-section" id="checkin-section">
                <div class="section-header">
                    <h4>
                        Check In
                    </h4>
                    <span class="section-subtitle">Masuk Kerja</span>
                </div>

                <form id="formCheckIn" class="attendance-form">
                    <input type="hidden" id="checkin_employee_id" name="employee_id">

                    <!-- Role Selection - AUTO POPULATED FROM DATABASE -->
                    <div class="form-group">
                        <label for="checkin_role">
                            Pilih Role Hari Ini
                        </label>
                        <select id="checkin_role" name="role_today" class="role-select" required>
                            <option value="">-- Pilih Role --</option>
                            <!-- Options will be populated by JavaScript from employee's roles -->
                        </select>
                        <p class="field-note">Role yang tersedia sesuai dengan role Anda di sistem</p>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="checkin_password">
                            Password
                        </label>
                        <input type="password" id="checkin_password" name="password" required
                            placeholder="Masukkan password Anda" class="password-input" autocomplete="off">
                        <p class="field-note">Gunakan password pribadi Anda</p>
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="checkin_notes">
                            Catatan (Opsional)
                        </label>
                        <textarea id="checkin_notes" name="notes" rows="3" placeholder="Catatan tambahan..."></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-checkin">
                            Check In Sekarang
                        </button>
                        <button type="button" class="btn-cancel" onclick="closeAttendanceModal()">Batal</button>
                    </div>
                </form>
            </div>

            <!-- Check Out Section -->
            <div class="attendance-section" id="checkout-section" style="display:none;">
                <div class="section-header">
                    <h4>
                        Check Out
                    </h4>
                    <span class="section-subtitle">Selesai Kerja</span>
                </div>

                <!-- Today's Work Summary -->
                <div class="work-summary">
                    <h5>Ringkasan Kerja Hari Ini</h5>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-details">
                                <strong id="summary_shoes">0</strong>
                                <span>Sepatu Selesai</span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-details">
                                <strong id="summary_duration">0</strong>
                                <span>Jam Kerja</span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-details">
                                <strong id="summary_role">-</strong>
                                <span>Role</span>
                            </div>
                        </div>
                        <div class="summary-item">
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
                            Password
                        </label>
                        <input type="password" id="checkout_password" name="password" required
                            placeholder="Masukkan password Anda" class="password-input" autocomplete="off">
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="checkout_notes">
                            Catatan Penutup (Opsional)
                        </label>
                        <textarea id="checkout_notes" name="notes" rows="3"
                            placeholder="Ringkasan pekerjaan atau catatan..."></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-checkout">
                            Check Out Sekarang
                        </button>
                        <button type="button" class="btn-cancel" onclick="closeAttendanceModal()">Batal</button>
                    </div>
                </form>
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
</style>